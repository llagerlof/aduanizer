<?php

namespace Aduanizer\Map;

use Aduanizer\Exception;

class MapFactory
{
    protected $defaults = array(
        'primaryKey' => 'id',
        'idGeneration' => 'autoincrement',
        'sequence' => '{tableName}_seq',
    );

    protected $validTableParams = array(
        'table',
        'primaryKey',
        'idGeneration',
        'sequence',
        'uniqueKeys',
        'exclude',
        'replace',
        'replaceCode',
        'foreignKeys',
        'children',
    );

    public function setDefaults(array $defaults)
    {
        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $this->defaults)) {
                throw new Exception("Unknown default param: $key");
            }

            $this->defaults[$key] = $value;
        }
    }

    public function getDefaultPrimaryKeyForTable(Table $table)
    {
        return str_replace(
            '{tableName}',
            $table->getName(),
            $this->defaults['primaryKey']
        );
    }

    public function getDefaultSequenceForTable(Table $table)
    {
        return str_replace(
          array('{tableName}', '{primaryKey}'),
          array($table->getName(), $table->getPrimaryKey()),
          $this->defaults['sequence']
        );
    }

    public function factory(array $params) {
        $map = new Map();

        foreach ($params as $key => $element) {
            if (isset($element['table'])) {
                $table = $this->factoryTable($element['table'], $element);
                $map->addTable($table);
            } elseif (isset($element['default'])) {
                $this->setDefaults($element['default']);
            } else {
                throw new Exception("Unknown element #$key");
            }
        }

        return $map;
    }

    public function factoryTable($tableName, array $params)
    {
        foreach ($params as $key => $value) {
            if (!in_array($key, $this->validTableParams)) {
                throw new Exception("Unknown param $key for table $tableName.");
            }
        }

        $table = new Table($tableName);

        if (array_key_exists('primaryKey', $params)) {
            $table->setPrimaryKey($params['primaryKey']);
        } else {
            $table->setPrimaryKey($this->getDefaultPrimaryKeyForTable($table));
        }

        if (isset($params['idGeneration'])) {
            $idGeneration = new IdGeneration($params['idGeneration']);
        } elseif (isset($params['sequence'])) {
            $idGeneration = new IdGeneration('sequence');
        } else {
            $idGeneration = new IdGeneration($this->defaults['idGeneration'])
        }
        $table->setIdGeneration($idGeneration);

        if ($table->getIdGeneration()->isSequence()) {
            if (isset($params['sequence'])) {
                $table->setSequence($params['sequence']);
            } else {
                $table->setSequence($this->getDefaultSequenceForTable($table));
            }
        } elseif (isset($params['sequence'])) {
            throw new Exception(
                "Extraneous sequence parameter for table $tableName."
            );
        }

        if (isset($params['uniqueKeys'])) {
            $table->setUniqueKeys($params['uniqueKeys']);
        }

        if (isset($params['exclude'])) {
            if (is_array($params['exclude'])) {
                $table->setExcludes($params['exclude']);
            } else {
                $table->exclude($params['exclude']);
            }
        }

        if (isset($params['replace'])) {
            $table->setReplace($params['replace']);
        }

        if (isset($params['replaceCode'])) {
            if (!is_array($params['replaceCode'])) {
                throw new Exception(
                    "Invalid replaceCode param for table $tableName. " .
                    "It should be an array."
                );
            }

            foreach ($params['replaceCode'] as $column => $code) {
                $function = create_function("\$$tableName", $code);

                if (!$function) {
                    throw new Exception(
                        "Could not create replace function for " .
                        "$tableName.$column."
                    );
                }

                $table->addReplaceFunction($column, $function);
            }
        }

        if (isset($params['foreignKeys'])) {
            $table->setForeignKeys($params['foreignKeys']);
        }

        if (isset($params['children'])) {
            $table->setChildren($params['children']);
        }

        return $table;
    }
}
