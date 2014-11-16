<?php

namespace Aduanizer;

use Aduanizer\FileHandling\FileGateway;

class Cli
{
    public function run()
    {
        $opts = getopt('e:i:a:m:o:h');

        $adapterFile = isset($opts['a']) ? $opts['a'] : 'adapter.yml';
        $mapFile = isset($opts['m']) ? $opts['m'] : 'map.yml';
        $outputFile = isset($opts['o']) ? $opts['o'] : 'data.yml';

        if (isset($opts['h'])) {
            $this->usage();
        } elseif (isset($opts['e'], $opts['i'])) {
            throw new Exception("Options -e and -i are mutually exclusive");
        } elseif (isset($opts['e'])) {
            $targetString = $opts['e'];
            $this->export($adapterFile, $mapFile, $outputFile, $targetString);
        } elseif (isset($opts['i'])) {
            $inputFile = $opts['i'];
            $this->import($adapterFile, $mapFile, $inputFile);
        } else {
            $this->usage();
        }
    }

    public function export($adapterFile, $mapFile, $outputFile, $targetString)
    {
        echo "Exporting...\n";

        if (file_exists($outputFile)) {
            throw new Exception("Output file already exists: $outputFile");
        }

        $fileGateway = new FileGateway();
        $adapterConfig = $fileGateway->load($adapterFile);
        $mapConfig = $fileGateway->load($mapFile);
        
        $facade = new AduanizerFacade();
        $data = $facade->export($adapterConfig, $mapConfig, $targetString);
        
        $fileGateway->save($outputFile, $data);
    }

    public function import($adapterFile, $mapFile, $dataFile)
    {
        echo "Importing...\n";

        $fileGateway = new FileGateway();
        $adapterConfig = $fileGateway->load($adapterFile);
        $mapConfig = $fileGateway->load($mapFile);
        $data = $fileGateway->load($dataFile);
        
        $facade = new AduanizerFacade();
        $facade->import($adapterConfig, $mapConfig, $data);
    }

    public function usage()
    {
        echo "Usage: aduanizer -e table.primarykey=id [-a adapter.yml] [-m map.yml] [-o data.json]\n";
        echo "       aduanizer -i data.json [-a adapter.yml] [-m map.yml]\n";
        echo "\n";
        echo "Actions\n";
        echo "  -e  export rows from the specified table according to the given criteria\n";
        echo "  -i  import data from the specified file\n";
        echo "  -h  display this usage message\n";
        echo "\n";
        echo "Configuration files\n";
        echo "  -a  adapter settings file specifying how to connect to the database\n";
        echo "  -m  map file describing the relevant database structure\n";
        echo "  -o  output data to the specified file\n";
        echo "\n";
    }
}
