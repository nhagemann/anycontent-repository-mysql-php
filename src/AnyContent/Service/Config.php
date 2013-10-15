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
}