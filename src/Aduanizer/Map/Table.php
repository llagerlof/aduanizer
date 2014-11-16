<?php

namespace Aduanizer\Map;

class Table
{
    protected $name;
    protected $primaryKey;
    protected $idGeneration;
    protected $sequence;
    protected $uniqueKeys = array();
    protected $foreignKeys = array();
    protected $children = array();
    protected $excludes = array();
    protected $replace = array();

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
    }

    public function hasPrimaryKey()
    {
        return $this->primaryKey !== null && $this->primaryKey !== "";
    }

    /**
     * Returns the id generation type object
     * 
     * @return IdGeneration
     */
    public function getIdGeneration()
    {
        return $this->idGeneration;
    }

    public function setIdGeneration(IdGeneration $idGeneration)
    {
        $this->idGeneration = $idGeneration;
    }

    public function getSequence()
    {
        return $this->sequence;
    }

    public function hasSequence()
    {
        return null !== $this->sequence;
    }

    public function setSequence($sequence)
    {
        $this->sequence = $sequence;
    }

    public function getUniqueKeys()
    {
        return $this->uniqueKeys;
    }

    public function setUniqueKeys(array $uniqueKeys)
    {
        foreach ($uniqueKeys as $columns) {
            if (is_array($columns)) {
                $this->addUniqueKey($columns);
            } else {
                $this->addUniqueKey(array($columns));
            }
        }
    }

    public function addUniqueKey(array $columns)
    {
        $this->uniqueKeys[] = $columns;
    }

    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }

    public function setForeignKeys(array $foreignKeys)
    {
        foreach ($foreignKeys as $column => $tableName) {
            $this->addForeignKey($column, $tableName);
        }
    }

    public function addForeignKey($column, $tableName)
    {
        $this->foreignKeys[$column] = $tableName;
    }

    public function isForeignKey($column)
    {
        return isset($this->foreignKeys[$column]);
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function setChildren(array $children)
    {
        foreach ($children as $tableName => $column) {
            $this->addChild($tableName, $column);
        }
    }

    public function addChild($tableName, $column)
    {
        $this->children[$tableName] = $column;
    }

    public function getExcludes()
    {
        return $this->excludes;
    }

    public function setExcludes(array $excludes)
    {
        foreach ($excludes as $column) {
            $this->exclude($column);
        }
    }

    public function exclude($column)
    {
        $this->excludes[] = $column;
    }

    public function setReplace(array $replace)
    {
        foreach ($replace as $column => $replacement) {
            $this->addReplace($column, $replacement);
        }
    }

    public function addReplace($column, $replacement)
    {
        $this->replace[$column] = $replacement;
    }

    public function getReplace()
    {
        return $this->replace;
    }
}
