<?php
namespace Liip\Registry\Adaptor\Lucene;

use Assert\Assertion;
use Assert\InvalidArgumentException;
use Elastica\Client;
use Elastica\Document;
use Elastica\Exception\BulkResponseException;
use Elastica\Index;
use Elastica\Query;
use Elastica\Query\MatchAll;
use Elastica\Result;
use Elastica\Search;
use Liip\Registry\Adaptor\AdaptorException;
use Liip\Registry\Adaptor\Decorator\DecoratorInterface;

class ElasticaAdaptor implements AdaptorInterface
{
    /**
     * @var \Elastica\Index[]
     */
    protected $indexes;
    /**
     * @var \Elastica\Client
     */
    protected $client;
    /**
     * @var string Name of the standard type
     */
    protected $typeName = 'collab';
    /**
     * @var DecoratorInterface
     */
    protected $decorator;

    /**
     * @param DecoratorInterface $decorator
     */
    public function __construct(DecoratorInterface $decorator)
    {
        $this->decorator = $decorator;
    }

    /**
     * Adds a document to an index.
     *
     * @param string $indexName
     * @param \Elastica\Document|array $document
     * @param string $identifier
     * @param string $typeName
     *
     * @throws \LogicException
     * @throws \Liip\Registry\Adaptor\AdaptorException
     * @return \Elastica\Document
     */
    public function registerDocument($indexName, $document, $identifier = '', $typeName = '')
    {
        $index = $this->getIndex($indexName);
        $type = $index->getType(
            empty($typeName) ? $this->typeName : $typeName
        );

        try {
            $document = $this->trancodeDataToDocument($document, $identifier);
            $type->addDocuments(array($document));
            $index->refresh();

        } catch (BulkResponseException $e) {
             throw new AdaptorException($e->getMessage(), $e->getCode(), $e);
        }

        return $document;
    }

    /**
     * Removes a document from the index.
     *
     * @param array $ids
     * @param string $index
     * @param string $type
     */
    public function removeDocuments(array $ids, $index, $type = '')
    {
        if (empty($type)) {
            $type = $this->typeName;
        }

        $client = $this->getClient();
        $client->deleteIds($ids, $index, $type);
    }

    /**
     * Updates a elsaticsearch document.
     *
     * @param  integer|string $id document id
     * @param  mixed $data raw data for request body
     * @param  string $indexName   index to update
     * @param  string $typeName    type of index to update
     *
     * @throws AdaptorException in case something when wrong while sending the request to elasticsearch.
     * @return \Elastica\Document
     *
     * @link http://www.elasticsearch.org/guide/reference/api/update.html
     */
    public function updateDocument($id, $data, $indexName, $typeName = '')
    {
        $index = $this->getIndex($indexName);
        $client = $index->getClient();
        $type = $index->getType(
            empty($typeName) ? $this->typeName : $typeName
        );

        // data array needs to have the key 'doc'
        $rawData = array(
            'doc' => $this->decorator->normalizeValue($data)
        );

        $response = $client->updateDocument(
            $id,
            $rawData,
            $index->getName(),
            $type->getName()
        );

        if ($response->hasError()) {

            $error = $this->normalizeError($response->getError());

            throw new AdaptorException(
                $error->getMessage(),
                $error->getCode(),
                $error
            );
        }

        $type->getIndex()->refresh();

        return $type->getDocument($id);
    }

    /**
     * Fetches the requested document from the index.
     *
     * @param string $id
     * @param string $indexName
     * @param string $typeName
     *
     * @return \Elastica\Document
     */
    public function getDocument($id, $indexName, $typeName = '')
    {
        $index = $this->getIndex($indexName);
        $type = $index->getType(
          empty($typeName) ? $this->typeName : $typeName
        );

        $data = $this->decorator->denormalizeValue(array($id => $type->getDocument($id)->getData()));
        return $data[$id];
    }

    /**
     * Provides a list of all documents of the given index.
     *
     * @param \Elastica\Index $index
     *
     * @return array
     * @throws \Assert\InvalidArgumentException
     */
    public function getDocuments($index)
    {
        Assertion::isInstanceOf(
            $index,
            '\Elastica\Index',
            'The given index must be of type \Elastica\Index !'
        );

        $search = new Search($index->getClient());
        $search->addIndex($index);

        $query = new Query(new MatchAll());
        $resultSet = $search->search($query);
        $results = $resultSet->getResults();

        return $this->decorator->denormalizeValue($this->extractData($results));
    }

    /**
     * Extracts information from a nested result set.
     *
     * @param array $data
     *
     * @return array
     */
    protected function extractData(array $data)
    {
        $converted = array();

        foreach($data as $value) {

            if ($value instanceof Result) {

                $converted[$value->getId()] = $value->getData();
            }
        }

        return $converted;
    }

    /**
     * determines if the risen error is of type Exception.
     *
     * @param mixed $error
     *
     * @return AdaptorException
     */
    public function normalizeError($error)
    {
        if ($error instanceof \Exception) {
            return new AdaptorException($error->getMessage(), $error->getCode(), $error);
        }

        return new AdaptorException(
            sprintf('An error accord: %s', print_r($error, true)),
            0
        );
    }

    /**
     * Provides an elasticsearch index to attach documents to.
     *
     * @param string $indexName   Name of the lucene index
     * @param array $indexOptions Set of options to be used to create an index
     * @param bool|array $specials
     *     if »bool« it deletes index first if already exists (default = false).
     *     if »array« it should b an associative array of options (option=>value).
     *     See linked web page.
     *
     * @return \Elastica|Index
     *
     * @link http://www.elasticsearch.org/guide/reference/api/admin-indices-create-index.html
     */
    public function getIndex($indexName, array $indexOptions = array(), $specials = null)
    {
        $indexName = strtolower($indexName);

        if (empty($this->indexes[$indexName])) {

            $client = $this->getClient();
            $this->indexes[$indexName] = $client->getIndex($indexName);

            if (!$this->indexes[$indexName]->exists()) {

                $this->indexes[$indexName]->create(
                    $this->mergeDefaultOptions($indexOptions),
                    $specials
                );
            }
        }

        return $this->indexes[$indexName];
    }

    /**
     * Deletes the named index from the cluster.
     *
     * @param string $name
     */
    public function deleteIndex($name)
    {
        $client = $this->getClient();

        $index = $client->getIndex($name);
        $index->close();
        $index->delete();
    }

    /**
     * Provides an elastica client.
     * @return \Elastica\Client
     */
    public function getClient()
    {
        if (empty($this->client)) {

            $this->client = new Client();
        }

        return $this->client;
    }

    /**
     * Makes sure that the given data is an Elastica\Document
     *
     * @param mixed $document
     * @param string $identifier
     *
     * @return Document
     * @throws \LogicException
     */
    protected function trancodeDataToDocument($document, $identifier)
    {
        if (!$document instanceof Document) {

            if (is_object($document)) {

                if (!method_exists($document, 'toArray')) {

                    throw new \LogicException(
                        'The given object representing a document value have to implement a toArray() method in order ' .
                        'to be able to store it in elasticsearch.'
                    );
                }

                $document = $document->toArray();
            }

            $document = $this->decorator->normalizeValue($document);

            Assertion::notEmpty($document, 'The document data may not be empty.');

            $document = new Document($identifier, $document);

        }

        return $document;
    }

    /**
     * Merges a set of default index creation options to the set of defined options.
     *
     * Will only set the default options if not already defined by the passed option    set.
     *
     * @param array $options
     *
     * @return array
     */
    protected function mergeDefaultOptions(array $options = array())
    {
        $defaultOptions = array(
            'number_of_shards'   => 5,
            'number_of_replicas' => 1,
        );

        return array_merge(array(), $defaultOptions, $options);
    }
}
