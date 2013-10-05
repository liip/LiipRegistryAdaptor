<?php

namespace Liip\Registry\Adaptor\Tests\Fixtures;

class Entity
{

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function toArray()
    {
        if (!is_array($this->data)) {
            return array('data' => $data);
        }

        return $this->data;
    }
}
