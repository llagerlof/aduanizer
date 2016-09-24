<?php

namespace Aduanizer;

use Aduanizer\Adapter\AdapterFactory;
use Aduanizer\Criteria\CriteriaFactory;
use Aduanizer\Criteria\TargetParser;
use Aduanizer\Map\MapFactory;

class AduanizerFacade
{
    protected $adapterFactory;
    protected $mapFactory;
    protected $criteriaFactory;
    protected $targetParser;

    public function __construct()
    {
        $this->adapterFactory = new AdapterFactory();
        $this->mapFactory = new MapFactory();
        $this->criteriaFactory = new CriteriaFactory();
        $this->targetParser = new TargetParser();
    }

    public function export(
        array $adapterConfig,
        array $mapConfig,
        $targetString
    ) {
        $adapter = $this->adapterFactory->factory($adapterConfig);
        $map = $this->mapFactory->factory($mapConfig);
        $target = $this->targetParser->parse($targetString);

        $exporter = new Exporter($adapter, $map, $this->criteriaFactory);
        $dataBag = new DataBag();
        $table = $map->getTable($target->getTableName());
        $criteria = $target->getCriteria();

        $adapter->beginTransaction();
        $exporter->exportTable($dataBag, $table, $criteria);
        $adapter->rollback();

        return $dataBag->getData();
    }

    public function import(
        array $adapterConfig,
        array $mapConfig,
        array $data
    ) {
        $adapter = $this->adapterFactory->factory($adapterConfig);
        $map = $this->mapFactory->factory($mapConfig);

        $importer = new Importer($adapter, $map, $this->criteriaFactory);
        $register = new ImportRegister();
        $dataBag = new DataBag();
        $dataBag->setData($data);

        $adapter->beginTransaction();
        $importer->import($register, $dataBag);
        $adapter->commit();
    }
}
