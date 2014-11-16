<?php

namespace Aduanizer\Criteria;

class Criteria
{
    protected $sql;
    protected $params;

    public function __construct($sql = "", array $params = array())
    {
        $this->sql = $sql;
        $this->params = $params;
    }

    public function isEmpty()
    {
        return $this->sql === null || $this->sql === "";
    }
    
    public function getSql()
    {
        return $this->sql;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function hasParams()
    {
        return (bool) $this->params;
    }
}
