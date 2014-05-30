<?php

namespace AnyContent\Repository\Modules\Core\Application;

use AnyContent\Repository\Modules\Core\Application\Application;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use Symfony\Component\Yaml\Parser;

class ConfigService
{

    protected $app;

    protected $yml = null;

    protected $basepath = null;

    protected $cacheData = 0;

    protected $cacheFileListings = 0;

    protected $cacheCMDL = 0;


    public function __construct(Application $app)
    {

        $this->app      = $app;
        $this->basepath = APPLICATION_PATH;
    }


    public function getCMDLDirectory()
    {
        return $this->basepath . '/cmdl';
    }


    public function getDSN()
    {
        $yml = $this->getYML();

        if (!isset($yml['database']['host']) || !isset($yml['database']['name']))
        {
            throw new \Exception ('Missing or incomplete database configuration.');
        }

        $dsn = 'mysql:host=' . $yml['database']['host'];
        $dsn .= ';dbname=' . $yml['database']['name'];
        $dsn .= ';port=' . $this->getDBPort();

        return $dsn;
    }


    public function getDBUser()
    {
        $yml = $this->getYML();

        if (!isset($yml['database']['user']))
        {
            throw new \Exception ('Missing or incomplete database configuration.');
        }

        return $yml['database']['user'];

    }


    public function getDBPassword()
    {
        $yml = $this->getYML();

        if (!isset($yml['database']['password']))
        {
            return '';
        }

        return $yml['database']['password'];
    }


    public function getDBPort()
    {
        return '3306';
    }


    public function getFilesAdapterConfig($repositoryName)
    {
        $yml = $this->getYML();

        if (isset($yml['files']['default_adapter']))
        {
            return $yml['files']['default_adapter'];
        }

        return null;
    }


    public function getCacheConfiguration()
    {
        $yml = $this->getYML();

        $cache = array( 'driver' => array( 'type' => 'none' ), 'data' => 60, 'files' => 0, 'cmdl' => 0 );

        if (isset($yml['cache']))
        {
            $cache = array_merge($cache, $yml['cache']);

            if ($cache['driver']['type'] == 'memcache' || $cache['driver']['type'] == 'memcached')
            {
                if (!isset($cache['driver']['host']))
                {
                    $cache['driver']['host'] = 'localhost';
                }
                if (!isset($cache['driver']['port']))
                {
                    $cache['driver']['port'] = '11211';
                }
            }
        }

        $this->cacheData         = $cache['data'];
        $this->cacheFileListings = $cache['files'];
        $this->cacheCMDL         = $cache['cmdl'];

        return $cache;
    }


    public function getMinutesCachingData()
    {
        return $this->cacheData;
    }


    public function getMinutesCachingFileListings()
    {
        return $this->cacheFileListings;
    }


    public function getMinutesCachingCMDL()
    {
        return $this->cacheCMDL;
    }


    protected function getYML()
    {
        if ($this->yml)
        {
            return $this->yml;
        }

        $configFile = file_get_contents(APPLICATION_PATH . '/config/config.yml');

        $yamlParser = new Parser();

        $this->yml = $yamlParser->parse($configFile);

        return $this->yml;
    }


    public function getCMDLConfigHash()
    {
        $finder = new Finder();
        $finder->files()->in($this->getCMDLDirectory());

        $hash = '';

        /* @var SplFileInfo $file */
        foreach ($finder as $file)
        {
            $hash .= $file->getFilename() . '.' . $file->getMTime() . '-';
        }

        return md5($hash);
    }

}