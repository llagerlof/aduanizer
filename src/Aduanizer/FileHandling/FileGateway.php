<?php

namespace Aduanizer\FileHandling;

use Aduanizer\Exception;

class FileGateway implements FileHandler
{
    protected $handlers = array();

    public function __construct()
    {
        $this->addHandler('yml', 'Aduanizer\FileHandling\YamlFileHandler');
        $this->addHandler('json', 'Aduanizer\FileHandling\JsonFileHandler');
    }

    public function addHandler($extension, $loader)
    {
        $this->handlers[$extension] = $loader;
    }

    /**
     * Return a handler capable of loading and saving the file according to its
     * extension.
     *
     * @param string $filename
     * @return FileHandler
     * @throws Exception
     */
    public function getCapableHandler($filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if (!isset($this->handlers[$extension])) {
            throw new Exception("File extension not supported: $extension");
        }

        if (is_string($this->handlers[$extension])) {
            $className = $this->handlers[$extension];
            $this->handlers[$extension] = new $className();
        }

        return $this->handlers[$extension];
    }

    public function load($filename)
    {
        return $this->getCapableHandler($filename)->load($filename);
    }

    public function save($filename, $content)
    {
        $this->getCapableHandler($filename)->save($filename, $content);
    }
}
