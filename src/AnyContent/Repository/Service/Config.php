<?php

namespace AnyContent\Repository\Service;

use Silex\Application;

use Symfony\Component\Finder\Finder;

use Symfony\Component\Yaml\Parser;

class Config
{

    protected $app;

    protected $yml = null;

    protected $basepath = null;


    public function __construct(Application $app, $basepath = null)
    {
        $this->app      = $app;
        $this->basepath = $basepath;
    }


    public function getCMDLDirectory()
    {
        return $this->basepath . 'cmdl';
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

        $config['default'] = null;
        $config['cache']   = null;
        if (isset($yml['files']['default_adapter']))
        {
            $config['default'] = $yml['files']['default_adapter'];

            if ($config['default']['type'] == 'directory')
            {
                $directory = $config['default']['directory'];
                if ($directory[0] != '/')
                {
                    $directory = APPLICATION_PATH . '/' . $directory;
                }

                $config['default']['directory'] = '/' . trim($directory, '/') . '/' . $repositoryName;
            }
        }
        if (isset($yml['files']['cache_adapter']))
        {
            $config['cache'] = $yml['files']['cache_adapter'];
        }

        /*  if (isset($yml['repositories'][$repositoryName]['files']['default_adapter']))
          {
              $config['default'] = $yml['repositories'][$repositoryName]['files']['default_adapter'];
          }
          if (isset($yml['repositories'][$repositoryName]['files']['cache_adapter']))
          {
              $config['cache'] = $yml['repositories'][$repositoryName]['files']['cache_adapter'];
          }*/

        return $config;
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


    public function getLastCMDLConfigChangeTimestamp()
    {
        $finder = new Finder();
        $finder->files()->in($this->getCMDLDirectory());

        $t = 0;

        /* @var File file */
        foreach ($finder as $file)
        {
            if ($file->getMTime() > $t)
            {
                $t = $file->getMTime();

            }
        }

        return $t;
    }

}