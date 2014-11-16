<?php

namespace Aduanizer\Criteria;

use Aduanizer\Exception;

class TargetParser
{
    /**
     * Parse a target string in the format of "tablename.where+clause".
     *
     * @param type $targetString
     * @return \Aduanizer\Criteria\Target
     * @throws Exception
     */
    public function parse($targetString)
    {
        $separatorPosition = strpos($targetString, '.');

        if (false === $separatorPosition) {
            $tableName = $targetString;
            $where = null;
        } else {
            $tableName = substr($targetString, 0, $separatorPosition);
            $where = substr($targetString, $separatorPosition + 1);
        }

        $criteria = new Criteria($where);
        $target = new Target($tableName, $criteria);

        return $target;
    }
}
