<?php

namespace Aduanizer\FileHandling;

class JsonFileHandler implements FileHandler
{
    public function load($filename)
    {
        $contents = file_get_contents($filename);
        return json_decode($contents, true);
    }

    public function save($filename, $content)
    {
        $json = json_encode($content);
        file_put_contents($filename, $json);
    }
}
