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


    public function setCacheDriver($cache)
    {
        $this['cache'] = $cache;
    }


    protected function initCache()
    {
        $cacheConfiguration = $this['config']->getCacheConfiguration();

        switch ($cacheConfiguration['driver']['type'])
        {
            case 'apc':
                $cacheDriver = new  \Doctrine\Common\Cache\ApcCache();
                break;
            case 'memcached':
                $memcached = new \Memcached();
                $memcached->addServer($cacheConfiguration['driver']['host'], $cacheConfiguration['driver']['port']);
                $cacheDriver = new \Doctrine\Common\Cache\MemcachedCache();
                $cacheDriver->setMemcached($memcached);
                break;
            case 'memcache':
                $memcache = new \Memcache();
                $memcache->connect($cacheConfiguration['driver']['host'], $cacheConfiguration['driver']['port']);
                $cacheDriver = new \Doctrine\Common\Cache\MemcacheCache();
                $cacheDriver->setMemcache($memcache);
                break;
            default:
                $cacheDriver = new \Doctrine\Common\Cache\ArrayCache();
                break;
        }

        if (isset($cacheConfiguration['driver']['prefix']))
        {
            $cacheDriver->setNamespace($cacheConfiguration['driver']['prefix'] . '_');
        }
        $this->setCacheDriver($cacheDriver);
    }


    public function init()
    {
        $this->initCache();
    }


    public function run($request = null)
    {

        parent::run($request);
    }

}

