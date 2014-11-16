<?php

namespace Aduanizer;

use Aduanizer\Adapter\Adapter;
use Aduanizer\Criteria\CriteriaFactory;
use Aduanizer\Map\Map;
use Aduanizer\Map\Table;

class Importer
{
    protected $adapter;
    protected $map;
    protected $criteriaFactory;

    public function __construct(Adapter $adapter, Map $map, CriteriaFactory $criteriaFactory)
    {
        $this->adapter = $adapter;
        $this->map = $map;
        $this->criteriaFactory = $criteriaFactory;
    }

    public function import(ImportRegister $register, DataBag $dataBag)
    {
        foreach ($dataBag->getTableNames() as $tableName) {
            $this->importTable($register, $dataBag, $tableName);
        }
    }

    public function importTable(ImportRegister $register, DataBag $dataBag, $tableName)
    {
        foreach ($dataBag->getRows($tableName) as $bagId => $row) {
            $this->importRow($register, $dataBag, $tableName, $bagId);
        }
    }

    public function importRow(ImportRegister $register, DataBag $dataBag, $tableName, $bagId)
    {
        if ($register->contains($tableName, $bagId)) {
            return $register->getDatabaseId($tableName, $bagId);
        }

        $table = $this->map->getTable($tableName);
        $primaryKey = $table->getPrimaryKey();
        $row = $dataBag->getRow($tableName, $bagId);

        foreach ($table->getForeignKeys() as $column => $foreignTableName) {
            if (empty($row[$column])) {
                continue;
            }

            $foreignKey = $this->importRow($register, $dataBag, $foreignTableName, $row[$column]);
            $row[$column] = $foreignKey;
        }

        $existing = $this->findExistingRow($table, $row);

        if ($existing) {
            $databaseId = $existing[$primaryKey];
            $register->addReused($tableName, $bagId, $databaseId);
        } else {
            $databaseId = $this->adapter->insert($table, $row);
            $register->addImported($tableName, $bagId, $databaseId);
        }

        if ($table->hasPrimaryKey() && !$table->isForeignKey($primaryKey)) {
            $row[$primaryKey] = $databaseId;
        }

        $dataBag->add($tableName, $bagId, $row);

        return $databaseId;
    }

    public function findExistingRow(Table $table, array $row)
    {
        foreach ($table->getUniqueKeys() as $uniqueColumns) {
            $criteria = $this->criteriaFactory->toMatch($row, $uniqueColumns);

            if (!$criteria->hasParams()) {
                continue;
            }

            $existing = $this->adapter->getRows($table, $criteria);

            if ($existing) {
                return $existing[0];
            }
        }
    }
}
