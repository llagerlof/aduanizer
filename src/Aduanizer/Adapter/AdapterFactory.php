<?php

namespace Aduanizer\Adapter;

use Aduanizer\Exception;

class AdapterFactory
{
    protected $adapters = array();

    public function __construct()
    {
        $this->addAdapter('oci8', 'Aduanizer\Adapter\Oci8Adapter');
        $this->addAdapter('pdo', 'Aduanizer\Adapter\PDOAdapter');
    }

    public function addAdapter($name, $className)
    {
        $this->adapters[$name] = $className;
    }

    /**
     * Return an new adapter according to the settings provided.
     * 
     * @param array $settings
     * @return Adapter
     * @throws Exception
     */
    public function factory(array $settings)
    {
        $adapter = isset($settings['adapter']) ? $settings['adapter'] : null;

        if (isset($this->adapters[$adapter])) {
            $className = $this->adapters[$settings['adapter']];
            return new $className($settings);
        } else {
            throw new Exception("Invalid adapter: $adapter");
        }
    }
}
