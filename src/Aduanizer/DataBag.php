<?php

namespace Aduanizer;

class DataBag
{
    protected $data = array();

    public function getData()
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function add($tableName, $bagId, array $row)
    {
        if (null === $bagId) {
            $this->data[$tableName][] = $row;
        } else {
            $this->data[$tableName][$bagId] = $row;
        }
    }

    public function exists($tableName, $bagId)
    {
        return isset($this->data[$tableName][$bagId]);
    }

    public function getTableNames()
    {
        return array_keys($this->data);
    }

    public function getRows($tableName)
    {
        return $this->data[$tableName];
    }

    public function getRow($tableName, $bagId)
    {
        return $this->data[$tableName][$bagId];
    }
}
