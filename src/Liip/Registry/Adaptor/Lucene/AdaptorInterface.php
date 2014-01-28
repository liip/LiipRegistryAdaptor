<?php

namespace Liip\Registry\Adaptor\Lucene;


interface AdaptorInterface
{

    /**
     * Adds a document to an index.
     *
     * @param string $indexName
     * @param mixed $document
     * @param string $identifier
     * @param string $typeName
     *
     * @return object Representation of a Lucene document
     */
    public function registerDocument($indexName, $document, $identifier = '', $typeName = '');

    /**
     * Removes a lucene document from the index.
     *
     * @param array $ids
     * @param string $index
     * @param string $type
     */
    public function removeDocuments(array $ids, $index, $type = '');

    /**
     * Updates a lucene document.
     *
     * @param  integer|string $id document id
     * @param  mixed $data raw data for request body
     * @param  string $indexName   index to update
     * @param  string $typeName    type of index to update
     *
     * @return object
     */
    public function updateDocument($id, $data, $indexName, $typeName = '');

    /**
     * Fetches the requested lucene document from the lucene index.
     *
     * @param string $id
     * @param string $indexName
     * @param string $typeName
     *
     * @return object Representation of a Lucene document
     */
    public function getDocument($id, $indexName, $typeName = '');

    /**
     * Provides a list of all documents of the given index.
     *
     * @param mixed   $index Name of the lucene index or an object representing a lucene index
     * @param integer $limit Amount of result items to be returned. If set to 0 (zero) all documents of the result set will be returned. Defaults to 10.
     *
     * @return array
     */
    public function getDocuments($index, $limit = 10);

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
     * @return object Representation of a lucene index
     *
     * @link http://www.elasticsearch.org/guide/reference/api/admin-indices-create-index.html
     */
    public function getIndex($indexName, array $indexOptions = array(), $specials = null);

    /**
     * Deletes the named index from the cluster.
     *
     * @param string $name
     */
    public function deleteIndex($name);

    /**
     * Provides an Lucene client.
     * @return object Representation of a client handling requests to lucene.
     */
    public function getClient();

    /**
     * Reveals the currently set index type name.
     * @return string
     */
    public function getIndexType();

    /**
     * Defines the name of the default index type;
     *
     * @param string $typeName
     */
    public function setIndexType($typeName);
}
