<?php

namespace Liip\Registry\Strategy\Lucene\Elastica;


use Elastica\Query\Field;
use Liip\Registry\Strategy\StrategyInterface;

class QueryStrategy implements StrategyInterface
{
    /**
     * @var \Elastica\Query\Field
     */
    protected $esField;

    /**
     * Finds documents in Elasticsearch.
     *
     * @param array $data Data to be altered by the current strategy.
     *
     * @return array
     */
    public function execute(array $data = array())
    {
        $field = $this->getField();

        $field->setField($fieldId);
        $field->setQueryString($querystring);

        return $field;
    }

    /**
     * Provides an instance of the Elastica\Field class.
     *
     * @return Field
     */
    protected function getField()
    {
        if (empty($this->esField)) {
            $this->esField = new Field();
        }

        return $this->esField;
    }
}
