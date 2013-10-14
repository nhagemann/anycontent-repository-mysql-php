<?php

namespace AnyContent\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

use AnyContent\Repository\Repository;

class RepositoryManager
{

    protected $app;

    protected $repositories = null;


    public function __construct(Application $app)
    {
        $this->app = $app;

    }


    public function get($repositoryName)
    {
        if ($this->hasRepository($repositoryName))
        {
            $repository = new Repository($this->app, $repositoryName);

            return $repository;
        }

        return false;
    }


    public function hasRepository($repositoryName)
    {
        if (in_array($repositoryName, $this->getRepositories()))
        {
            return true;
        }

        return false;

    }


    public function getRepositories()
    {
        $path = $this->app['config']->getCMDLDirectory();

        if (!$this->repositories)
        {

            $repositories = array();
            $path         = realpath($path);
            if (is_dir($path))
            {
                $results = scandir($path);

                foreach ($results as $result)
                {
                    if ($result === '.' or $result === '..')
                    {
                        continue;
                    }

                    if (is_dir($path . '/' . $result))
                    {
                        $repositories[] = $result;
                    }
                }
            }
            $this->repositories = $repositories;
        }

        return $this->repositories;
    }
}