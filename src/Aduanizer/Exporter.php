<?php

namespace Aduanizer;

use Aduanizer\Adapter\Adapter;
use Aduanizer\Criteria\Criteria;
use Aduanizer\Criteria\CriteriaFactory;
use Aduanizer\Map\Map;
use Aduanizer\Map\Table;

class Exporter
{
    protected $adapter;
    protected $map;
    protected $criteriaFactory;

    public function __construct(
        Adapter $adapter,
        Map $map,
        CriteriaFactory $criteriaFactory
    ) {
        $this->adapter = $adapter;
        $this->map = $map;
        $this->criteriaFactory = $criteriaFactory;
    }

    public function exportTable(
        DataBag $dataBag,
        Table $table,
        Criteria $criteria
    ) {
        $tableName = $table->getName();
        $primaryKey = $table->getPrimaryKey();

        $rows = $this->adapter->getRows($table, $criteria);

        foreach ($rows as $row) {
            if ($primaryKey && !isset($row[$primaryKey])) {
                throw new Exception(
                    "Missing primary key column $primaryKey on $tableName row."
                );
            }
            
            $id = $primaryKey ? $row[$primaryKey] : null;

            $dataBag->add($tableName, $id, $row);
            $this->exportForeignKeys($dataBag, $table, $row);
            $this->exportChildren($dataBag, $table, $row);
        }
    }

    public function exportForeignKeys(
        DataBag $dataBag,
        Table $table,
        array $row
    ) {
        foreach ($table->getForeignKeys() as $column => $foreignTableName) {
            $foreignKey = $row[$column];

            if (!$foreignKey
                || $dataBag->exists($foreignTableName, $foreignKey)
            ) {
                continue;
            }

            $foreignTable = $this->map->getTable($foreignTableName);
            $criteria = $this->criteriaFactory->factory(
                array($foreignTable->getPrimaryKey() => $foreignKey)
            );
            $this->exportTable($dataBag, $foreignTable, $criteria);
        }
    }

    public function exportChildren(DataBag $dataBag, Table $table, array $row)
    {
        $children = $table->getChildren();

        if ($children) {
            $primaryKey = $table->getPrimaryKey();
            $id = $primaryKey ? $row[$primaryKey] : null;

            if (!$id) {
                throw new Exception(
                    "Unable to export children of {$table->getName()} " .
                    "without its primary key."
                );
            }

            foreach ($children as $childTableName => $column) {
                $childTable = $this->map->getTable($childTableName);
                $criteria = $this->criteriaFactory->factory(
                    array($column => $id)
                );
                $this->exportTable($dataBag, $childTable, $criteria);
            }
        }
    }
}
