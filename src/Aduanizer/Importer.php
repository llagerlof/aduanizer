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

    public function __construct(
        Adapter $adapter,
        Map $map,
        CriteriaFactory $criteriaFactory
    ) {
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

    public function importTable(
        ImportRegister $register,
        DataBag $dataBag,
        $tableName
    ) {
        foreach ($dataBag->getRows($tableName) as $bagId => $row) {
            $this->importRow($register, $dataBag, $tableName, $bagId);
        }
    }

    public function importRow(
        ImportRegister $register,
        DataBag $dataBag,
        $tableName,
        $bagId
    ) {
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

            $foreignKey = $this->importRow(
                $register,
                $dataBag,
                $foreignTableName,
                $row[$column]
            );

            if ($foreignKey === null) {
                throw new Exception(
                    "Foreign row {$tableName}.{$foreignTableName} " .
                    "should have returned its primary key"
                );
            }

            $row[$column] = $foreignKey;
        }

        $existing = $this->findExistingRow($table, $row);

        if ($existing) {
            $databaseId = $this->fetchRowId($table, $existing);
            $register->addReused($tableName, $bagId, $databaseId);
        } else {
            $insertedId = $this->adapter->insert($table, $row);

            if ($primaryKey) {
                $databaseId = $insertedId;
            } else {
                $databaseId = $this->fetchRowId($table, $row);
            }

            $register->addImported($tableName, $bagId, $databaseId);
        }

        if ($table->hasPrimaryKey() && !$table->isForeignKey($primaryKey)) {
            $row[$primaryKey] = $databaseId;
        }

        $dataBag->add($tableName, $bagId, $row);

        if ($primaryKey) {
            return $databaseId;
        }
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

    /**
     * Returns a value that uniquely identifies the row within this table, so
     * that the row isn't inserted multiple times during import.
     *
     * Best case scenario this is the primary key, but could also be the first
     * unique key in case this table has no primary key. If none of these are
     * available then all values are concatenated and a hash is returned.
     *
     * @param array $row
     * @return string|int
     */
    public function fetchRowId(Table $table, array $row)
    {
        // The table has a primary key so we use it straight away.
        if ($table->getPrimaryKey()) {
            return $row[$table->getPrimaryKey()];
        }

        // There's no primary key so we try to concat all column names and
        // values from the first unique key.
        if ($table->getUniqueKeys()) {
            $concat = null;
            $uniqueKeys = $table->getUniqueKeys();
            $firstUniqueKeyColumns = $uniqueKeys[0];

            foreach ($firstUniqueKeyColumns as $column) {
                $concat .= $column . $row[$column];
            }

            return $concat;
        }

        // There are no suitable keys so we concat values from all columns to
        // generate a hash that represents this row. This cannot be used to
        // find this exact row in the table again but it's useful during import
        // to avoid inserting the same data multiple times.
        // FIXME: unless it's supposed to be?
        $concat = null;

        foreach ($row as $column => $value) {
            $concat .= $column . $value;
        }

        return md5($concat);
    }
}
