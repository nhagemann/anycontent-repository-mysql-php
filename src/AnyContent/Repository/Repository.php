<?php

namespace AnyContent\Repository;

use Silex\Application;

class Repository
{
    protected $app;
    protected $name;

    public function __construct(Application $app, $repositoryName)
    {
        $this->app = $app;
        $this->name = $repositoryName;
    }

    public function getContentTypesList()
    {
       return $this->app['repos']->getContentTypesList($this->name);
    }

    public function getCMDL($contentTypeName)
    {
        return $this->app['repos']->getCMDL($this->name,$contentTypeName);
    }

    public function getContentTypeDefinition($contentTypeName)
    {
        return $this->app['repos']->getContentTypeDefinition($this->name,$contentTypeName);
    }
}