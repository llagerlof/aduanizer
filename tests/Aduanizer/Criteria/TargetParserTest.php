<?php

namespace Aduanizer\Criteria;

class TargetParserTest extends \PHPUnit_Framework_TestCase
{
    public function testTableColumnEqualsValue()
    {
        $targetParser = new TargetParser();
        $target = $targetParser->parse("user.id = 123");
        $criteria = $target->getCriteria();

        $this->assertEquals("user", $target->getTableName());
        $this->assertEquals("id = 123", $criteria->getSql());
    }

    public function testCompoundCriteria()
    {
        $targetParser = new TargetParser();
        $target = $targetParser->parse("user.active = 1 AND last_update >= NOW() - INTERVAL 1 DAY");
        $criteria = $target->getCriteria();
        
        $this->assertEquals("user", $target->getTableName());
        $this->assertEquals("active = 1 AND last_update >= NOW() - INTERVAL 1 DAY", $criteria->getSql());
    }

    public function testOnlyTableName()
    {
        $targetParser = new TargetParser();
        $target = $targetParser->parse("user");
        $criteria = $target->getCriteria();

        $this->assertEquals("user", $target->getTableName());
        $this->assertTrue($criteria->isEmpty());
    }
}
