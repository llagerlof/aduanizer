<?php

namespace Aduanizer\Adapter;

use Aduanizer\Exception;
use Aduanizer\Map\Table;

class Oci8Adapter extends Adapter
{
    protected $conn;
    protected $autoCommit = true;

    protected function init()
    {
        if (!isset($this->settings['username'], $this->settings['password'], $this->settings['connectionString'])) {
            throw new Exception("Required Oci8Adapter params: username, password, connectionString");
        }

        $character_set = isset($this->settings['characterSet']) ? $this->settings['characterSet'] : null;

        $this->conn = oci_connect(
            $this->settings['username'],
            $this->settings['password'],
            $this->settings['connectionString'],
            $character_set
        );

        if (!$this->conn) {
            $error = oci_error();
            throw new Exception("Could not connect to {$this->settings['connectionString']}: " . $error['message']);
        }

        $this->setupDateFormat('yyyy-mm-dd', 'yyyy-mm-dd hh24:mi:ss');
    }

    protected function setupDateFormat($dateFormat, $timestampFormat)
    {
        $stmtDateFormat = oci_parse($this->conn, "ALTER SESSION SET NLS_DATE_FORMAT = '$dateFormat'");
        $this->execute($stmtDateFormat);

        $stmtTimestampFormat = oci_parse($this->conn, "ALTER SESSION SET NLS_TIMESTAMP_FORMAT = '$timestampFormat'");
        $this->execute($stmtTimestampFormat);
    }

    protected function getRowsImpl(Table $table, $query, array $params)
    {
        $sql = $this->getSelectSql($table, $query);
        $statement = oci_parse($this->conn, $sql);

        foreach ($params as $placeHolder => $value) {
            oci_bind_by_name($statement, $placeHolder, $params[$placeHolder]);
        }

        $this->execute($statement);

        $rows = array();
        oci_fetch_all($statement, $rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);

        foreach ($rows as $i => $row) {
            $rows[$i] = array_change_key_case($row, CASE_LOWER);
        }

        return $rows;
    }

    protected function getSelectSql(Table $table, $query)
    {
        $tableName = $table->getName();
        $from = isset($this->settings['schema']) ? $this->settings['schema'] . '.' . $tableName : $tableName;
        $where = $query == "" ? "" : "WHERE $query";
        $sql = "SELECT * FROM $from $where";

        return $sql;
    }

    protected function insertImpl(Table $table, array $row)
    {
        $idGeneration = $table->getIdGeneration();

        if ($idGeneration->isSequence()) {
            $row[$table->getPrimaryKey()] = $this->getNextVal($table);
        } elseif (!$idGeneration->isAssigned() && $table->hasPrimaryKey()) {
            unset($row[$table->getPrimaryKey()]);
        }

        $sql = $this->getInsertSql($table, $row);
        $statement = oci_parse($this->conn, $sql);

        foreach (array_keys($row) as $i => $column) {
            oci_bind_by_name($statement, ":$i", $row[$column]);
        }

        if ($idGeneration->isReturning()) {
            oci_bind_by_name($statement, ":returningPk", $row[$table->getPrimaryKey()], strlen(PHP_INT_MAX));
        }

        $this->execute($statement);

        if ($table->hasPrimaryKey()) {
            return $row[$table->getPrimaryKey()];
        }
    }

    protected function getInsertSql(Table $table, array $row)
    {
        $tableName = $table->getName();
        $into = isset($this->settings['schema']) ? $this->settings['schema'] . '.' . $tableName : $tableName;

        $columnsList = array();
        $placeHoldersList = array();

        foreach (array_keys($row) as $i => $column) {
            $columnsList[] = $column;
            $placeHoldersList[] = ":$i";
        }

        $columns = implode(',', $columnsList);
        $placeHolders = implode(',', $placeHoldersList);

        $sql = "INSERT INTO $into($columns) VALUES($placeHolders)";

        if ($table->getIdGeneration()->isReturning()) {
            $sql .= " RETURNING {$table->getPrimaryKey()} INTO :returningPk";
        }

        return $sql;
    }

    protected function execute($statement)
    {
        $mode = $this->autoCommit ? OCI_COMMIT_ON_SUCCESS : OCI_NO_AUTO_COMMIT;
        $success = oci_execute($statement, $mode);

        if (!$success) {
            throw new Exception("Could not execute statement: " . oci_error($this->conn));
        }
    }

    protected function getNextVal(Table $table)
    {
        if (!$table->hasSequence()) {
            throw new Exception("Sequence not set for table {$table->getName()}.");
        }

        $sq_statement = oci_parse($this->conn, "SELECT {$table->getSequence()}.nextval FROM dual");
        $this->execute($sq_statement);
        return current(oci_fetch_row($sq_statement));
    }

    public function beginTransaction()
    {
        $this->autoCommit = false;
    }

    public function commit()
    {
        oci_commit($this->conn);
        $this->autoCommit = true;
    }

    public function rollback()
    {
        oci_rollback($this->conn);
        $this->autoCommit = true;
    }
}
