<?php

namespace Aduanizer\Adapter;

use Aduanizer\Criteria\Criteria;
use Aduanizer\Map\Table;

abstract class Adapter
{
    protected $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->init();
    }

    public function getRows(Table $table, Criteria $criteria)
    {
        $query = $criteria->getSql();
        $params = $criteria->getParams();

        if (isset($this->settings['inputStringFilter'])) {
            $params = $this->stringFilter(
                $this->settings['inputStringFilter'],
                $params
            );
        }

        $rows = $this->getRowsImpl($table, $query, $params);

        foreach ($table->getExcludes() as $column) {
            foreach ($rows as $i => $row) {
                unset($rows[$i][$column]);
            }
        }

        foreach ($table->getReplace() as $column => $replacedValue) {
            foreach ($rows as $i => $row) {
                if (array_key_exists($column, $row)) {
                    $rows[$i][$column] = $replacedValue;
                }
            }
        }

        foreach ($table->getReplaceFunction() as $column => $function) {
            foreach ($rows as $i => $row) {
                if (array_key_exists($column, $row)) {
                    $rows[$i][$column] = call_user_func($function, $rows[$i]);
                }
            }
        }

        if (isset($this->settings['outputStringFilter'])) {
            foreach ($rows as $i => $row) {
                $rows[$i] = $this->stringFilter(
                    $this->settings['outputStringFilter'],
                    $row
                );
            }
        }

        return $rows;
    }

    public function insert(Table $table, array $row)
    {
        foreach ($table->getExcludes() as $column) {
            unset($row[$column]);
        }

        foreach ($table->getReplace() as $column => $replacedValue) {
            if (array_key_exists($column, $row)) {
                $row[$column] = $replacedValue;
            }
        }

        foreach ($table->getReplaceFunction() as $column => $function) {
            if (array_key_exists($column, $row)) {
                $row[$column] = call_user_func($function, $row);
            }
        }

        if (isset($this->settings['inputStringFilter'])) {
            $row = $this->stringFilter(
                $this->settings['inputStringFilter'],
                $row
            );
        }

        return $this->insertImpl($table, $row);
    }

    public function stringFilter($function, array $row) {
        foreach ($row as $column => $value) {
            if (is_string($value)) {
                $row[$column] = $function($value);
            }
        }

        return $row;
    }

    abstract protected function init();

    abstract protected function getRowsImpl(
        Table $table,
        $query,
        array $params
    );

    abstract protected function insertImpl(Table $table, array $row);

    abstract public function beginTransaction();

    abstract public function commit();

    abstract public function rollback();
}
