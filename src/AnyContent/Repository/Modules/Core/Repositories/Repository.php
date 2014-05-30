<?php

namespace AnyContent\Repository\Modules\Core\Repositories;

use AnyContent\Repository\Modules\Core\Application\Application;


use AnyContent\Repository\FilesManager;

class Repository
{

    protected $app;
    protected $name;

    protected $contentManager = array();
    protected $configManager = null;
    protected $filesManager = null;


    public function __construct(Application $app, $repositoryName)
    {
        $this->app  = $app;
        $this->name = $repositoryName;
    }


    public function setName($name)
    {
        $this->name = $name;
    }


    public function getName()
    {
        return $this->name;
    }


    public function getContentTypesList()
    {
        return $this->app['repos']->getContentTypesList($this->name);
    }


    // todo rename
    public function getCMDL($contentTypeName)
    {
        return $this->app['repos']->getContentTypeCMDL($this->name, $contentTypeName);
    }


    public function getConfigCMDL($configTypeName)
    {
        return $this->app['repos']->getConfigTypeCMDL($this->name, $configTypeName);
    }


    public function getContentTypeDefinition($contentTypeName)
    {
        return $this->app['repos']->getContentTypeDefinition($this->name, $contentTypeName);
    }


    public function getConfigTypesList()
    {
        return $this->app['repos']->getConfigTypesList($this->name);
    }


    public function getConfigTypeDefinition($configTypeName)
    {
        return $this->app['repos']->getConfigTypeDefinition($this->name, $configTypeName);
    }


    public function hasConfigType($configTypeName)
    {
        if (array_key_exists($configTypeName, $this->getConfigTypesList()))
        {
            return true;
        }

        return false;
    }


    public function getContentManager($contentTypeName)
    {
        if (array_key_exists($contentTypeName, $this->contentManager))
        {
            return $this->contentManager[$contentTypeName];
        }

        $contentTypeDefinition = $this->getContentTypeDefinition($contentTypeName);

        if ($contentTypeDefinition)
        {

            $manager = new ContentManager($this->app, $this, $contentTypeDefinition);

            $this->contentManager[$contentTypeName] = $manager;

            return $manager;
        }

        return false;
    }


    public function getConfigManager()
    {
        if ($this->configManager != null)
        {
            return $this->configManager;
        }

        $manager = new ConfigManager($this);

        $this->configManager = $manager;

        return $manager;

    }


    public
    function getFilesManager()
    {
        if ($this->filesManager != null)
        {
            return $this->filesManager;
        }
        $manager            = new FilesManager($this->app, $this, $this->app['repos']->getFilesAdapterConfig($this->getName()));
        $this->filesManager = $manager;

        return $manager;
    }


    public
    function getDatabaseConnection()
    {
        return $this->app['repos']->getDatabaseConnection();
    }


    /* public function getFileSystem()
     {
         return $this->app['repos']->getFileSystem($this->getName());
     }*/

    public
    function getMaxTimestamp()
    {
        return $this->app['repos']->getMaxTimestamp();
    }


    public
    function getTimeshiftTimestamp($timeshift = 0)
    {
        return $this->app['repos']->getTimeshiftTimestamp($timeshift);

    }


    public
    function getAPIUser()
    {
        return $this->app['repos']->getAPIUser();
    }


    public
    function getCurrentUserName()
    {
        return $this->app['repos']->getCurrentUserName();
    }


    public
    function getCurrentUserFirstname()
    {
        return $this->app['repos']->getCurrentUserFirstname();
    }


    public
    function getCurrentUserLastname()
    {
        return $this->app['repos']->getCurrentUserLastname();
    }


    public
    function getClientIp()
    {
        return $this->app['repos']->getClientIp();
    }

}