<?php

namespace AnyContent\Repository\Modules\Core\Application;

use Silex\Application as SilexApplication;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

class Application extends SilexApplication
{

    protected $modules = array();

    protected $storageAdapter = array();

    protected $cmdlAccessAdapter = array();


    public function __construct(array $values = array())
    {

        parent::__construct($values);

        $this['config'] = $this->share(function ($this)
        {
            return new ConfigService($this);
        });

        $this['db'] = $this->share(function ($app)
        {
            return new DatabaseService($app);
        });
    }


    public function registerModule($class, $options = array())
    {
        $this->modules[$class] = array( 'class' => $class, 'options' => $options );

    }


    public function registerCMDLAccessAdapter($type, $class, $options = array())
    {
        $this->cmdlAccessAdapter[$type] = array( 'class' => $class, 'options' => $options );
    }


    public function getCMDLAccessAdapter($config)
    {
        if (array_key_exists($config['type'], $this->cmdlAccessAdapter))
        {

            $class   = $this->cmdlAccessAdapter[$config['type']]['class'];
            $options = $this->cmdlAccessAdapter[$config['type']]['options'];
            unset($config['type']);

            $adapter = new $class($this, $config, $options);

        }
        else
        {
            throw new \Exception ('Unknown CMDL access adapter type ' . $config['type'] . '.');
        }

        return $adapter;
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


    public function initModules()
    {
        /*
        $this->register(new ConsoleServiceProvider(), array(
            'console.name'              => 'AnyContent CMCK Console',
            'console.version'           => '1.0.0',
            'console.project_directory' => APPLICATION_PATH
        )); */

        foreach ($this->modules as $module)
        {
            $class = $module['class'] . '\Module';
            $o     = new $class;
            $o->init($this, $module['options']);
            $module['module']                = $o;
            $this->modules[$module['class']] = $module;
        }

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
        $this->initModules();
    }


    public function run(Request $request = null)
    {

        parent::run($request);
    }


    /**
     * Registers a before filter.
     *
     * Before filters are run before any route has been matched. This override additionally provides the application
     * object to the filter.
     *
     * @param mixed   $callback Before filter callback
     * @param integer $priority The higher this value, the earlier an event
     *                          listener will be triggered in the chain (defaults to 0)
     */
    public function before($callback, $priority = 0)
    {
        $app = $this;

        $this->on(KernelEvents::REQUEST, function (GetResponseEvent $event) use ($callback, $app)
        {
            if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType())
            {
                return;
            }

            $ret = call_user_func($app['callback_resolver']->resolveCallback($callback), $event->getRequest(), $app);

            if ($ret instanceof Response)
            {
                $event->setResponse($ret);
            }
        }, $priority);
    }


    /**
     * Registers an after filter.
     *
     * After filters are run after the controller has been executed. This override additionally provides the application
     * object to the filter.
     *
     * @param mixed   $callback After filter callback
     * @param integer $priority The higher this value, the earlier an event
     *                          listener will be triggered in the chain (defaults to 0)
     */
    public function after($callback, $priority = 0)
    {
        $app = $this;

        $this->on(KernelEvents::RESPONSE, function (FilterResponseEvent $event) use ($callback, $app)
        {
            if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType())
            {
                return;
            }

            call_user_func($app['callback_resolver']->resolveCallback($callback), $event->getRequest(), $event->getResponse(), $app);
        }, $priority);
    }
}

