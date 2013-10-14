<?php

namespace AnyContent\Repository;

use Silex\Application;

class Repository
{
    protected $app;

    public function __construct(Application $app, $repositoryName)
    {
        $this->app = $app;
    }

    public function getContentTypes()
    {

    }
}