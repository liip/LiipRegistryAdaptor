<?php

namespace Liip\Registry\Strategy;


interface StrategyInterface
{
    /**
     *  by the terms of a concrete strategy.
     *
     * @param array $data Data to be altered by the current strategy.
     *
     * @return array
     */
    public function execute(array $data = array());
}
