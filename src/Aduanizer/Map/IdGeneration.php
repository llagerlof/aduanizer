<?php

namespace Aduanizer\Map;

use Aduanizer\Exception;

class IdGeneration
{
    const AUTOINCREMENT = 'autoincrement';
    const ASSIGNED = 'assigned';
    const SEQUENCE = 'sequence';
    const RETURNING = 'returning';

    protected $type;

    public static function getValidTypes()
    {
        return array(
            self::AUTOINCREMENT,
            self::ASSIGNED,
            self::SEQUENCE,
            self::RETURNING
        );
    }
    
    public static function isValidType($type)
    {
        return in_array($type, static::getValidTypes());
    }

    public function __construct($type)
    {
        if (!static::isValidType($type)) {
            throw new Exception("Invalid id generation type: $type");
        }

        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function isAutoIncrement()
    {
        return $this->type == self::AUTOINCREMENT;
    }

    public function isAssigned()
    {
        return $this->type == self::ASSIGNED;
    }

    public function isSequence()
    {
        return $this->type == self::SEQUENCE;
    }

    public function isReturning()
    {
        return $this->type == self::RETURNING;
    }
}
