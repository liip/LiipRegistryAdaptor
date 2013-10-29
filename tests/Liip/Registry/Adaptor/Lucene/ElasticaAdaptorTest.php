<?php

namespace Liip\Registry\Adaptor\Lucene;

use Elastica\Client;
use Elastica\Exception\ClientException;
use Elastica\Exception\InvalidException;
use Elastica\Index;
use Elastica\Result;
use Elastica\Response;
use Liip\Registry\Adaptor\AdaptorException;
use Liip\Registry\Adaptor\Decorator\NoOpDecorator;
use Liip\Registry\Adaptor\Tests\Fixtures\Entity;
use Liip\Registry\Adaptor\Tests\RegistryTestCase;

class ElasticaAdaptorFunctionalTest extends RegistryTestCase
{
    /**
     * @var string Name of the es index to be used throughout the test suite.
     */
    protected static $indexName = 'testindex';

    /**
     * @var string Name of the es type to be used throughout the test suite.
     */
    protected static $typeName = 'testtype';

    /**
     * Provides a ElasticaAdaptor with the normalizer decorator
     *
     * @return ElasticaAdaptor
     */
    protected function getElasticaAdapter()
    {
        return new ElasticaAdaptor(new NoOpDecorator());
    }

    /**
     * Determines if elasticsearch is installed. It makes no sense to run this tests if not.
     */
    protected function setUp()
    {
        if (!class_exists('\Elastica\Index')) {
            $this->markTestSkipped(
                'The elastica library is not available. Please make sure to install the elastica library as proposed by composer.'
            );
        }

        try {
            $adaptor = $this->getElasticaAdapter();
            $adaptor->getIndex(self::$indexName);

        } catch (ClientException $e) {
            $this->markTestSkipped(
                'The connection attemped to elasticsearch server failed. Error: '. $e->getMessage()
            );
        }
    }

    /**
     * restores the state of the elasticsearch cluster before the test suite run.
     */
    public function tearDown()
    {
        $client =  new Client();
        $index = new Index($client, self::$indexName);

        if ($index->exists()) {

            $response = $index->delete();

            if ($response->hasError()) {

                throw new \PHPUnit_Framework_Exception(
                    sprintf(
                        'Failed to delete the elasticsearch index: %s',
                        self::$indexName
                    )
                );
            }
        }
    }

    /**
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::getIndex
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::__construct
     */
    public function testGetIndex()
    {
        $adaptor = $this->getElasticaAdapter();
        $index = $adaptor->getIndex(self::$indexName);

        $attrib = $this->readAttribute($adaptor, 'indexes');

        $this->assertSame($index, $attrib[self::$indexName]);
        $this->assertInstanceOf('\Elastica\Index', $index);
    }

    /**
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::getIndex
     */
    public function testGetIndexFromCache()
    {
        $adaptor = $this->getElasticaAdapter();
        $index = $adaptor->getIndex(self::$indexName);

        $this->assertSame($index, $adaptor->getIndex(self::$indexName));
    }

    /**
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::registerDocument
     */
    public function testRegisterDocumentExpectingInvalidArgumentException()
    {
        $adaptor = $this->getElasticaAdapter();

        $this->setExpectedException('\Assert\InvalidArgumentException');

        $adaptor->registerDocument(self::$indexName, array(), 'myDocument');
    }

    /**
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::registerDocument
     */
    public function testRegisterDocumentExpectingAdaptorException()
    {
        $type = $this->getMockBuilder('\\Elastica\\Type')
            ->disableOriginalConstructor()
            ->setMethods(array('addDocuments'))
            ->getMock();
        $type
            ->expects($this->once())
            ->method('addDocuments')
            ->will($this->throwException(new InvalidException('Array has to consist of at least one element')));

        $index = $this->getMockBuilder('\\Elastica\\Index')
            ->disableOriginalConstructor()
            ->setMethods(array('getType'))
            ->getMock();
        $index
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue($type));

        $adaptor = $this->getProxyBuilder('\\Liip\\Registry\\Adaptor\\Lucene\\ElasticaAdaptor')
            ->disableOriginalConstructor()
            ->setProperties(array('indexes', 'decorator'))
            ->getProxy();
        $adaptor->indexes = array(strtolower(self::$indexName) => $index);
        $adaptor->decorator = new NoOpDecorator();

        $this->setExpectedException('\Liip\Registry\Adaptor\AdaptorException');

        $adaptor->registerDocument(self::$indexName, array('Tux'), 'myDocument');
    }

    /**
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::registerDocument
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::transcodeDataToDocument
     */
    public function testRegisterDocumentExpectingLogicException()
    {
        $adaptor = $this->getProxyBuilder('\\Liip\\Registry\\Adaptor\\Lucene\\ElasticaAdaptor')
            ->disableOriginalConstructor()
            ->getProxy();

        $this->setExpectedException('\Liip\Registry\Adaptor\AdaptorException');

        $this->setExpectedException('\LogicException');

        $adaptor->registerDocument(self::$indexName, new \SplObjectStorage(), 'myDocument');
    }

    /**
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::registerDocument
     */
    public function testRegisterDocument()
    {
        $adaptor = $this->getElasticaAdapter();

        $this->assertInstanceOf(
            '\Elastica\Document',
            $adaptor->registerDocument(self::$indexName, array('Mascott' => 'Tux'))
        );
    }

    public function testRegisterJsonserializableDocument()
    {
        if (!interface_exists('\JsonSerializable')) {
            $this->markTestSkipped('JsonSerializable is supported from PHP 5.4.');
        }

        $document = $this->getMockBuilder('\JsonSerializable')
            ->setMethods(array('jsonSerialize'))
            ->getMockForAbstractClass();
        $document
            ->expects($this->once())
            ->method('jsonSerialize')
            ->will(
                $this->returnValue(
                    array(
                        'id' => 12343543,
                        'name' => 'Tux'
                    )
                )
            );


        $adaptor = $this->getElasticaAdapter();
        $this->assertInstanceOf(
            '\Elastica\Document',
            $adaptor->registerDocument(self::$indexName, $document)
        );
    }

    /**
     * @dataProvider updateDocumentDataprovider
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::updateDocument
     */
    public function testUpdateDocument($expected, $id, $registerData, $updateData)
    {
        $adaptor = $this->getElasticaAdapter();
        $adaptor->registerDocument(
            self::$indexName,
            $registerData,
            $id
        );

        $updatedDocument = $adaptor->updateDocument(
            $id,
            $updateData,
            self::$indexName
        );

        $this->assertEquals($expected, $updatedDocument->getData());
    }
    public static function updateDocumentDataprovider()
    {
        return array(
            'valid array data adding fields'   => array(
                array(
                    "food" => "Crisps",
                    "nearNonFood" => "Sponch",
                    'Mascott' => 'Tux'
                ),
                'foodStock',
                array('Mascott' => 'Tux'),
                array(
                    'food' => 'Crisps',
                    'nearNonFood' => 'Sponch'
                ),
            ),
            'valid array data overriding fields'   => array(
                array(
                    "Mascott" => "Linus",
                ),
                'foodStock',
                array('Mascott' => 'Tux'),
                array(
                    "Mascott" => "Linus",
                ),
            ),
        );
    }

    /**
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::updateDocument
     */
    public function testUpdateDocumentExpectingException()
    {
        $response = $this->getMockBuilder('\\Elastica\\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('hasError', 'getError'))
            ->getMock();
        $response
            ->expects($this->once())
            ->method('hasError')
            ->will($this->returnValue(true));

        $client = $this->getMockBuilder('\\Elastica\\Client')
            ->setMethods(array('updateDocument'))
            ->getMock();
        $client
            ->expects($this->once())
            ->method('updateDocument')
            ->will($this->returnValue($response));

        $index = $this->getMockBuilder('\\Elastica\Index')
            ->disableOriginalConstructor()
            ->setMethods(array('getClient'))
            ->getMock();
        $index
            ->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $adaptor = $this->getProxyBuilder('\\Liip\\Registry\\Adaptor\\Lucene\\ElasticaAdaptor')
            ->setConstructorArgs(array(new NoOpDecorator()))
            ->setProperties(array('indexes'))
            ->getProxy();

        $adaptor->indexes[self::$indexName] = $index;

        $this->setExpectedException('\\Liip\\Registry\\Adaptor\\AdaptorException');

        $rawData = array(
            'doc' => array(
                'food' => 'Crisps',
                'nearNonFood' => 'Sponch'
            )
        );

        $adaptor->updateDocument(
            'foodStock',
            $rawData,
            self::$indexName
        );
    }

    /**
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::getDocument
     */
    public function testGetDocument()
    {
        $adaptor = $this->getElasticaAdapter();
        $adaptor->registerDocument(
            self::$indexName,
            array('tux' => 'devil'),
            'toBeRetrieved'
        );

        $this->assertEquals(
            array('tux' => 'devil'),
            $adaptor->getDocument('toBeRetrieved', self::$indexName)
        );
    }

    /**
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::getDocuments
     */
    public function testGetDocuments()
    {
        $adaptor = $this->getElasticaAdapter();
        $adaptor->registerDocument(
            self::$indexName,
            array('tux' => 'devil'),
            'toBeRetrieved'
        );
        $adaptor->registerDocument(
            self::$indexName,
            array('mascott' => 'Gnu'),
            'toBeRetrieved2'
        );

        $this->assertEquals(
            array(
                'toBeRetrieved' => array('tux' => 'devil'),
                'toBeRetrieved2' => array('mascott' => 'Gnu'),
            ),
            $adaptor->getDocuments($adaptor->getIndex(self::$indexName))
        );

    }

    /**
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::removeDocuments
     */
    public function testRemoveDocuments()
    {
        $adaptor = $this->getElasticaAdapter();
        $adaptor->registerDocument(
            self::$indexName,
            array('tux' => 'devil'),
            'toBeRemoved'
        );
        $adaptor->registerDocument(
            self::$indexName,
            array('tux' => 'devil'),
            'toBeRemoved2'
        );

        $index = $adaptor->getIndex(self::$indexName);
        $type = $index->getType('collab');

        $adaptor->removeDocuments(array('toBeRemoved', 'toBeRemoved2'), self::$indexName);

        // this is afaik the only safe way to really find out if the document was removed from index.
        $this->setExpectedException('\\Elastica\\Exception\\NotFoundException');
        $type->getDocument('toBeRemoved');
        $type->getDocument('toBeRemoved2');
    }

    /**
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::getClient
     */
    public function testGetClient()
    {
        $registry = $this->getElasticaAdapter();
        $client = $registry->getClient();

        $this->assertAttributeInstanceOf('\\Elastica\\Client', 'client', $registry);
        $this->assertInstanceOf('\\Elastica\\Client', $client);
    }

    /**
     * @dataProvider normalizeErrorDataprovider
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::normalizeError
     */
    public function testNormalizeError($error)
    {
        $adaptor = $this->getElasticaAdapter();

        $this->assertInstanceOf(
            '\\Liip\\Registry\\Adaptor\\AdaptorException',
            $adaptor->normalizeError($error)
        );
    }
    public static function normalizeErrorDataprovider()
    {
        return array(
            'error is a string' => array('The leprechauns made me do it!!'),
            'error is an array' => array(array('The leprechauns made me do it!!')),
            'error is of type AdaptorException' => array(
                new AdaptorException('The leprechauns made me do it!!')
            ),
        );
    }

    /**
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::deleteIndex
     */
    public function testDeleteIndex()
    {
        $adaptor = $this->getElasticaAdapter();
        $adaptor->getIndex(self::$indexName);

        $adaptor->deleteIndex(self::$indexName);

        $client = new Client();
        $index = $client->getIndex(self::$indexName);

        $this->assertFalse($index->exists());
    }

    /**
     * @dataProvider extractDataDataprovider
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::extractData
     */
    public function testExtractData($expected, $value)
    {
        $registry = $this->getProxyBuilder('\\Liip\\Registry\\Adaptor\\Lucene\\ElasticaAdaptor')
            ->setConstructorArgs(array(new NoOpDecorator()))
            ->setMethods(array('extractData'))
            ->getProxy();

        $this->assertEquals($expected, $registry->extractData($value));
    }
    public static function extractDataDataprovider()
    {
        return array(
            'Data of type array' => array(
                array(
                    'WorldOfOs' => array('mascott' => 'tux'),
                    'GuggiMenu' => array('Dish Of Day' => 'Salmon al limone'),
                ),
                array(
                    0 => new Result(array(
                        '_index' => 'registry_worlds',
                        '_type' => 'collab',
                        '_id' => 'WorldOfOs',
                        '_score' => 1,
                        '_source'=> array('mascott' => 'tux'),
                    )),
                    1 => new Result(array(
                        '_index' => 'registry_worlds',
                        '_type' => 'collab',
                        '_id' => 'GuggiMenu',
                        '_score' => 1,
                        '_source'=> array('Dish Of Day' => 'Salmon al limone'),
                    )),
                )
            ),
            'Data of type string' => array(
                array('WorldOfOs' => 'this is a string'),
                array(
                    0 => new Result(array(
                        '_index' => 'registry_worlds',
                        '_type' => 'collab',
                        '_id' => 'WorldOfOs',
                        '_score' => 1,
                        '_source'=> 'this is a string',
                    )),
                )
            ),
        );
    }

    /**
     * @dataProvider mergeDefaultOptionsDataprovider
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::mergeDefaultOptions
     */
    public function testMergeDefaultOptions($expected, $options)
    {
        $adaptor = $this->getProxyBuilder('\Liip\Registry\Adaptor\Lucene\ElasticaAdaptor')
            ->disableOriginalConstructor()
            ->setMethods(array('mergeDefaultOptions'))
            ->getProxy();

        $this->assertEquals($expected, $adaptor->mergeDefaultOptions($options));
    }
    public function mergeDefaultOptionsDataprovider()
    {
        return array(
            'empty options' => array(
                array(
                    'number_of_shards' => 5,
                    'number_of_replicas' => 1
                ),
                array()
            ),
            'preset options' => array(
                array(
                    'number_of_shards' => 10,
                    'number_of_replicas' => 1,
                    'type' => array(),
                ),
                array(
                    'number_of_shards' => 10,
                    'type' => array(),
                )
            ),
        );
    }

    /**
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::getIndexType
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::setIndexType
     */
    public function testIndexType()
    {
        $adaptor = $this->getElasticaAdapter();

        $this->assertAttributeEquals('collab', 'typeName', $adaptor);

        $adaptor->setIndexType('Tux');

        $this->assertEquals('Tux', $adaptor->getIndexType());
    }
    /**
     * @expectedException \Elastica\Exception\ResponseException
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::deleteType
     */
    public function testDeleteType()
    {
        $adaptor = $this->getElasticaAdapter();
        $index = $adaptor->getIndex(self::$indexName);
        $type = $index->getType(self::$typeName);
        
        $this->assertEquals(self::$typeName, $type->getName());
        
        $adaptor->deleteType(self::$indexName, self::$typeName);
        // type no longer exists: will raise an exception
        $index->getType(self::$typeName);
    }

    /**
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::getDocuments
     */
    public function testGetTypeMapping()
    {
        $type = $this->getMockBuilder('\\Elastica\\Type')
            ->disableOriginalConstructor()
            ->setMethods(array('getMapping'))
            ->getMock();
        $type
            ->expects($this->once())
            ->method('getMapping')
            ->will($this->returnValue('tested'));

        $index = $this->getMockBuilder('\\Elastica\\Index')
            ->disableOriginalConstructor()
            ->setMethods(array('getType'))
            ->getMock();
        $index
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue($type));

        $adaptor = $this->getProxyBuilder('\\Liip\\Registry\\Adaptor\\Lucene\\ElasticaAdaptor')
            ->disableOriginalConstructor()
            ->setProperties(array('indexes'))
            ->getProxy();
        $adaptor->indexes = array(strtolower(self::$indexName) => $index);

        $adaptor->getTypeMapping(self::$indexName, self::$typeName);
    }

    /**
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::getDocuments
     */
    public function testGetIndexMapping()
    {
        $index = $this->getMockBuilder('\\Elastica\\Index')
            ->disableOriginalConstructor()
            ->setMethods(array('getMapping'))
            ->getMock();
        $index
            ->expects($this->once())
            ->method('getMapping')
            ->will($this->returnValue('tested'));

        $adaptor = $this->getProxyBuilder('\\Liip\\Registry\\Adaptor\\Lucene\\ElasticaAdaptor')
            ->disableOriginalConstructor()
            ->setProperties(array('indexes'))
            ->getProxy();
        $adaptor->indexes = array(strtolower(self::$indexName) => $index);

        $adaptor->getIndexMapping(self::$indexName);
    }

    /**
     * @dataProvider transcodeDataToDocumentDateprovider
     * @covers \Liip\Registry\Adaptor\Lucene\ElasticaAdaptor::transcodeDataToDocument
     */
    public function testTranscodeDataToDocument($expected, $field, $document)
    {
        $adaptor = $this->getProxyBuilder('\\Liip\\Registry\\Adaptor\\Lucene\\ElasticaAdaptor')
            ->disableOriginalConstructor()
            ->setMethods(array('transcodeDataToDocument'))
            ->setProperties(array('decorator'))
            ->getProxy();
        $adaptor->decorator = new NoOpDecorator();


        $esDoc = $adaptor->transcodeDataToDocument($document, 'mascotDoc');

        $this->assertEquals('mascotDoc', $esDoc->getId());
        $this->assertEquals($expected, $esDoc->get($field));
    }

    public function transcodeDataToDocumentDateprovider()
    {
        $entity = new Entity(array('otherMascots' => array('Tux', 'Gnu')));

        $class = new \stdClass;
        $class->osMascotts = array('Tux', 'Gnu');

        return array(
            'transcode array' => array(array('Tux', 'Gnu'), 'mascots', array('mascots' => array('Tux', 'Gnu'))),
            'transcode entity' => array(array('Tux', 'Gnu'), 'otherMascots', $entity),
            'transcode stdClass' => array(array('Tux', 'Gnu'), 'osMascotts', $class),
        );
    }
}
