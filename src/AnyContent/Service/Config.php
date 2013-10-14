<?php

namespace AnyContent\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

class Config
{

    protected $app;
    protected $basepath = null;



    public function __construct(Application $app, $basepath = null)
    {
        $this->app = $app;
        $this->basepath = $basepath;
    }


    public function getCMDLDirectory()
    {
        return $this->basepath.'cmdl';
    }
}