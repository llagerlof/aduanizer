<?php

namespace Aduanizer;

class ImportRegister
{
    protected $reused = array();
    protected $imported = array();

    public function contains($tableName, $bagId)
    {
        return isset($this->reused[$tableName][$bagId]) || isset($this->imported[$tableName][$bagId]);
    }

    public function getDatabaseId($tableName, $bagId)
    {
        if (isset($this->reused[$tableName][$bagId])) {
            return $this->reused[$tableName][$bagId];
        } elseif (isset($this->imported[$tableName][$bagId])) {
            return $this->imported[$tableName][$bagId];
        }
    }

    public function addReused($tableName, $bagId, $databaseId)
    {
        $this->reused[$tableName][$bagId] = $databaseId;
    }

    public function addImported($tableName, $bagId, $databaseId)
    {
        $this->imported[$tableName][$bagId] = $databaseId;
    }

    public function getReused()
    {
        return $this->reused;
    }

    public function getImported()
    {
        return $this->imported;
    }
}
