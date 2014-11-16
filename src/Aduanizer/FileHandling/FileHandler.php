<?php

namespace Aduanizer\FileHandling;

interface FileHandler
{
    public function load($filename);

    public function save($filename, $content);
}
