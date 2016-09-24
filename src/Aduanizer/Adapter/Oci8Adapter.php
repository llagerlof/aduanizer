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
        if (!isset(
            $this->settings['username'],
            $this->settings['password'],
            $this->settings['connectionString']
        )) {
            throw new Exception(
                "Required Oci8Adapter params: " .
                "username, password, connectionString"
            );
        }

        $character_set = isset($this->settings['characterSet'])
                       ? $this->settings['characterSet']
                       : null;

        $this->conn = oci_connect(
            $this->settings['username'],
            $this->settings['password'],
            $this->settings['connectionString'],
            $character_set
        );

        if (!$this->conn) {
            $error = oci_error();
            return $this->onError(
                "Could not connect to {$this->settings['connectionString']}",
                $error
            );
        }

        $this->setupDateFormat('yyyy-mm-dd', 'yyyy-mm-dd hh24:mi:ss');
    }

    protected function setupDateFormat($dateFormat, $timestampFormat)
    {
        $stmtDateFormat = oci_parse(
            $this->conn,
            "ALTER SESSION SET NLS_DATE_FORMAT = '$dateFormat'"
        );
        $success = $this->execute($stmtDateFormat);

        if (!$success) {
            $error = oci_error($stmtDateFormat);
            return $this->onError("Could not set date format", $error);
        }

        $stmtTimestampFormat = oci_parse(
            $this->conn,
            "ALTER SESSION SET NLS_TIMESTAMP_FORMAT = '$timestampFormat'"
        );
        $success = $this->execute($stmtTimestampFormat);

        if (!$success) {
            $error = oci_error($stmtTimestampFormat);
            return $this->onError("Could not set timestamp format", $error);
        }
    }

    protected function getRowsImpl(Table $table, $query, array $params)
    {
        $sql = $this->getSelectSql($table, $query);
        $statement = oci_parse($this->conn, $sql);

        if (!$statement) {
            $error = oci_error($this->conn);
            return $this->onError("Could not parse select", $error);
        }

        foreach ($params as $placeHolder => $value) {
            $success = oci_bind_by_name(
                $statement,
                $placeHolder,
                $params[$placeHolder]
            );

            if (!$success) {
                $error = oci_error($statement);
                return $this->onError("Could not bind '$placeHolder'", $error);
            }
        }

        $success = $this->execute($statement);

        if (!$success) {
            $error = oci_error($statement);
            return $this->onError("Could not execute select", $error);
        }

        $rows = array();
        $skip = 0;
        $maxrows = -1;
        $flags = OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC;
        $total = oci_fetch_all($statement, $rows, $skip, $maxrows, $flags);

        if ($total === false) {
            $error = oci_error($statement);
            return $this->onError("Could not fetch rows", $error);
        }

        foreach ($rows as $i => $row) {
            $rows[$i] = array_change_key_case($row, CASE_LOWER);
        }

        return $rows;
    }

    protected function getSelectSql(Table $table, $query)
    {
        $tableName = $table->getName();
        $from = isset($this->settings['schema'])
              ? $this->settings['schema'] . '.' . $tableName
              : $tableName;
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

        if (!$statement) {
            $error = oci_error($this->conn);
            return $this->onError("Could not parse insert", $error);
        }

        foreach (array_keys($row) as $i => $column) {
            $success = oci_bind_by_name($statement, ":$i", $row[$column]);

            if (!$success) {
                $error = oci_error($statement);
                return $this->onError("Could not bind '$column'", $error, $row);
            }
        }

        if ($idGeneration->isReturning()) {
            $success = oci_bind_by_name(
                $statement,
                ":returningPk",
                $row[$table->getPrimaryKey()], strlen(PHP_INT_MAX)
            );

            if (!$success) {
                $error = oci_error($statement);
                return $this->onError(
                    "Could not bind 'returningPk'",
                    $error,
                    $row
                );
            }
        }

        $success = $this->execute($statement);

        if (!$success) {
            $error = oci_error($statement);
            return $this->onError("Could not execute insert", $error, $row);
        }

        if ($table->hasPrimaryKey()) {
            return $row[$table->getPrimaryKey()];
        }
    }

    protected function getInsertSql(Table $table, array $row)
    {
        $tableName = $table->getName();
        $into = isset($this->settings['schema'])
              ? $this->settings['schema'] . '.' . $tableName
              : $tableName;

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

        return $success;
    }

    protected function onError($msg, array $oci_error, $data = null)
    {
        throw new Exception(
            sprintf(
                "%s\nOCI Error: %s - %s @ offset %d\nSQL: %s\nData: %s",
                $msg,
                $oci_error['code'],
                $oci_error['message'],
                $oci_error['offset'],
                $oci_error['sqltext'],
                serialize($data)
            )
        );
    }

    protected function getNextVal(Table $table)
    {
        if (!$table->hasSequence()) {
            throw new Exception(
                "Sequence not set for table {$table->getName()}."
            );
        }

        $sq_statement = oci_parse(
            $this->conn,
            "SELECT {$table->getSequence()}.nextval FROM dual"
        );
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
