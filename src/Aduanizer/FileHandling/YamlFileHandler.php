<?php

namespace Aduanizer\FileHandling;

class YamlFileHandler implements FileHandler
{
    public function load($filename)
    {
        return yaml_parse_file($filename);
    }

    public function save($filename, $content)
    {
        yaml_emit_file($filename, $content);
    }
}
