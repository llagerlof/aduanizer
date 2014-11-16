<?php

namespace Aduanizer\Criteria;

class CriteriaFactory
{
    /**
     * Factory a criteria to match rows having columns with the specified values.
     * 
     * @param array $criteriaParams
     * @return Criteria
     */
    public function factory(array $criteriaParams)
    {
        $placeHolder = 0;
        $clauses = array();
        $params = array();

        foreach ($criteriaParams as $columnName => $value) {
            if (null === $value) {
                $clauses[] = "$columnName IS NULL";
            } else {
                $clauses[] = "$columnName = :$placeHolder";
                $params[":$placeHolder"] = $value;
                $placeHolder++;
            }
        }

        $where = implode(' AND ', $clauses);
        $criteria = new Criteria($where, $params);

        return $criteria;
    }

    /**
     * Factory a criteria to match rows having columns from $columns parameter
     * with values from $row parameter.
     * 
     * @param array $row
     * @param array $columns
     * @return Criteria
     */
    public function toMatch(array $row, array $columns)
    {
        $criteriaParams = array();

        foreach ($columns as $columnName) {
            $criteriaParams[$columnName] = isset($row[$columnName]) ? $row[$columnName] : null;
        }

        return $this->factory($criteriaParams);
    }
}
