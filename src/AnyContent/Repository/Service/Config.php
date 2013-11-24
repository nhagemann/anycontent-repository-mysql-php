<?php

namespace AnyContent\Repository\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

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

        return 'mysql:host=localhost;dbname=anycontent';
    }


    public function getDBUser()
    {
        return 'root';
    }


    public function getDBPassword()
    {
        return '';
    }


    public function getFilesAdapterConfig($repositoryName)
    {
        $yml = $this->getYML();

        $config['default'] = null;
        $config['cache']   = null;
        if (isset($yml['files']['default_adapter']))
        {
            $config['default'] = $yml['files']['default_adapter'];
            $config['default']['directory'] = '/'.trim($config['default']['directory'],'/').'/'.$repositoryName;
        }
        if (isset($yml['files']['cache_adapter']))
        {
            $config['cache'] = $yml['files']['cache_adapter'];
        }
        if (isset($yml['repositories'][$repositoryName]['files']['default_adapter']))
        {
            $config['default'] = $yml['repositories'][$repositoryName]['files']['default_adapter'];
        }
        if (isset($yml['repositories'][$repositoryName]['files']['cache_adapter']))
        {
            $config['cache'] = $yml['repositories'][$repositoryName]['files']['cache_adapter'];
        }

        return $config;
    }


    protected function getYML()
    {
        if ($this->yml)
        {
            return $this->yml;
        }

        $configFile = file_get_contents('../config/config.yml');

        $yamlParser = new Parser();

        $this->yml = $yamlParser->parse($configFile);

        return $this->yml;
    }
}