<?php

namespace AnyContent\Repository;

use Silex\Application as SilexApplication;

class Application extends SilexApplication
{

    protected $storageAdapter = array();


    public function __construct(array $values = array())
    {

        parent::__construct($values);

    }


    public function registerStorageAdapter($type, $class, $options = array())
    {
        $this->storageAdapter[$type] = array( 'class' => $class, 'options' => $options );

    }


    public function getStorageAdapter($config, $baseFolder)
    {
        if (array_key_exists($config['type'], $this->storageAdapter))
        {

            $class   = $this->storageAdapter[$config['type']]['class'];
            $options = $this->storageAdapter[$config['type']]['options'];
            unset($config['type']);

            $adapter = new $class($config, $baseFolder, $options);
        }
        else
        {
            throw new \Exception ('Unknown storage adapter type ' . $config['type'] . '.');
        }

        return $adapter;
    }


    public function run($request = null)
    {
        parent::run($request);
    }

}

