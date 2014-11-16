<?php

namespace Aduanizer\Adapter;

use Aduanizer\Exception;
use Aduanizer\Map\Table;
use PDO;

class PDOAdapter extends Adapter
{
    protected $pdo;

    public function init()
    {
        if (!isset($this->settings['dsn'], $this->settings['username'])) {
            throw new Exception("Required PDOAdapter params: dsn, username");
        }

        $password = isset($this->settings['password']) ? $this->settings['password'] : null;

        $this->pdo = new PDO($this->settings['dsn'], $this->settings['username'], $password);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    protected function getRowsImpl(Table $table, $query, array $params)
    {
        $sql = $this->getSelectSql($table, $query);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        if (!$idGeneration()->isAssigned() && $table->hasPrimaryKey()) {
            unset($row[$table->getPrimaryKey()]);
        }

        $sql = $this->getInsertSql($table, $row);
        $values = array_values($row);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        if ($idGeneration->isAutoIncrement()) {
            return $this->pdo->lastInsertId();
        } elseif ($idGeneration->isAssigned()) {
            return $row[$table->getPrimaryKey()];
        } else {
            throw new Exception("Unsupported id generation type: {$idGeneration->getType()}");
        }
    }

    protected function getInsertSql(Table $table, array $row)
    {
        $tableName = $table->getName();
        $into = isset($this->settings['schema']) ? $this->settings['schema'] . '.' . $tableName : $tableName;
        $columns = implode(',', array_keys($row));
        $placeHolders = implode(',', array_fill(0, count($row), '?'));

        $sql = "INSERT INTO $into($columns) VALUES($placeHolders)";

        return $sql;
    }

    public function beginTransaction()
    {
        $this->pdo->beginTransaction();
    }

    public function commit()
    {
        $this->pdo->commit();
    }

    public function rollback()
    {
        $this->pdo->rollBack();
    }
}
