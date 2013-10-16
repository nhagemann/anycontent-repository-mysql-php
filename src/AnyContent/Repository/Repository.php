<?php

namespace AnyContent\Repository;

use Silex\Application;

use AnyContent\Repository\ContentManager;

class Repository
{

    protected $app;
    protected $name;

    protected $manager = array();


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


    public function getCMDL($contentTypeName)
    {
        return $this->app['repos']->getCMDL($this->name, $contentTypeName);
    }


    public function getContentTypeDefinition($contentTypeName)
    {
        return $this->app['repos']->getContentTypeDefinition($this->name, $contentTypeName);
    }


    public function getContentManager($contentTypeName)
    {
        if (array_key_exists($contentTypeName, $this->manager))
        {
            return $this->manager[$contentTypeName];
        }

        $manager = new ContentManager($this, $this->getContentTypeDefinition($contentTypeName));

        $this->manager[$contentTypeName] = $manager;

        return $manager;

    }


    public function getDatabaseConnection()
    {
        return $this->app['repos']->getDatabaseConnection();
    }


    public function getMaxTimestamp()
    {
        return $this->app['repos']->getMaxTimestamp();
    }


    public function getTimeshiftTimestamp($timeshift = 0)
    {
        return $this->app['repos']->getTimeshiftTimestamp($timeshift);

    }


    public function getAPIUser()
    {
        return $this->app['repos']->getAPIUser();
    }


    public function getCurrentUserName()
    {
        return $this->app['repos']->getCurrentUserName();
    }


    public function getCurrentUserFirstname()
    {
        return $this->app['repos']->getCurrentUserFirstname();
    }


    public function getCurrentUserLastname()
    {
        return $this->app['repos']->getCurrentUserLastname();
    }

    public function getClientIp()
    {
        return $this->app['repos']->getClientIp();
    }

}