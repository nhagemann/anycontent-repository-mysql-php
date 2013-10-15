<?php

namespace AnyContent\Repository\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

use AnyContent\Repository\Repository;

use AnyContent\Repository\Entity\ContentTypeInfo;

use CMDL\Parser;
use CMDL\ParserException;

class RepositoryManager
{

    protected $app;

    protected $repositories = null;

    protected $contentTypes = null;


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


    public function getContentTypesList($repositoryName)
    {
        $contentTypes = array();
        if ($this->hasRepository($repositoryName))
        {
            $path = $this->app['config']->getCMDLDirectory() . '/' . $repositoryName;
            $path = realpath($path);
            if (is_dir($path))
            {
                $results = scandir($path);

                foreach ($results as $result)
                {
                    if ($result === '.' or $result === '..')
                    {
                        continue;
                    }

                    if (!is_dir($path . '/' . $result))
                    {
                        if (pathinfo($result, PATHINFO_EXTENSION) == 'cmdl')
                        {
                            $filestats       = stat($path . '/' . $result);
                            $contentTypeName = pathinfo($result, PATHINFO_FILENAME);

                            $info = new ContentTypeInfo();

                            $info->setName($contentTypeName);
                            $info->setAgeCmdl(@$filestats['mtime']);
                            $contentTypes[$contentTypeName] = $info;
                        }
                    }
                }
            }
        }

        return $contentTypes;
    }


    public function getCMDL($repositoryName, $contentTypeName)
    {
        if ($this->hasRepository($repositoryName))
        {
            $cmdl = @file_get_contents($this->app['config']->getCMDLDirectory() . '/' . $repositoryName . '/' . $contentTypeName . '.cmdl');
            if ($cmdl)
            {
                return $cmdl;
            }
        }

        return false;
    }


    public function getContentType($repositoryName, $contentTypeName)
    {
        $cmdl = $this->getCMDL($repositoryName, $contentTypeName);
        if ($cmdl)
        {
            try
            {
                $contentType = Parser::parseCMDLString($cmdl);

                return $contentType;
            }
            catch (ParserException $e)
            {

            }
        }

        return false;

    }
}