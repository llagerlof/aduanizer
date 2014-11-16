<?php

namespace Aduanizer\Criteria;

class Target
{
    protected $tableName;
    protected $criteria;

    public function __construct($tableName, Criteria $criteria)
    {
        $this->tableName = $tableName;
        $this->criteria = $criteria;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Returns the criteria object
     * 
     * @return Criteria
     */
    public function getCriteria()
    {
        return $this->criteria;
    }
}
