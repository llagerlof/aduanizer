<?php

namespace Aduanizer\Map;

use Aduanizer\Exception;

class Map
{
    protected $tables = array();

    public function addTable(Table $table)
    {
        $this->tables[$table->getName()] = $table;
    }
    
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * Returns the table according to its name.
     * 
     * @param string $tableName
     * @return Table
     * @throws Exception
     */
    public function getTable($tableName)
    {
        if (isset($this->tables[$tableName])) {
            return $this->tables[$tableName];
        } else {
            throw new Exception("Undefined table $tableName");
        }
    }
}
