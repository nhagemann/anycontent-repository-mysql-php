<?php

namespace AnyContent\Repository\Modules\Core\Repositories;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AnyContent\Repository\Modules\Core\Application\Application;

use AnyContent\Repository\Modules\Core\Repositories\Repository;

use AnyContent\Repository\Modules\Core\Repositories\ConfigTypeInfo;
use AnyContent\Repository\Modules\Core\Repositories\ContentTypeInfo;

use CMDL\Parser;
use CMDL\ParserException;

class RepositoryManager
{

    protected $app;

    protected $cmdlAccessAdapter = null;

    protected $apiUser = null;

    protected $username = null;

    protected $firstname = null;

    protected $lastname = null;


    /**
     * acrs_repositories
     * acrs_
     */

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->cmdlAccessAdapter = $app->getCMDLAccessAdapter($app['config']->getCMDLAccessAdapterConfig());
    }


    public function setUserInfo($apiUser = null, $username = null, $firstname = null, $lastname = null)
    {
        $this->apiUser   = $apiUser;
        $this->username  = $username;
        $this->firstname = $firstname;
        $this->lastname  = $lastname;
    }


    public function getAPIUser()
    {
        return $this->apiUser;
    }


    public function getCurrentUserName()
    {
        return $this->username;
    }


    public function getCurrentUserFirstname()
    {
        return $this->firstname;
    }


    public function getCurrentUserLastname()
    {
        return $this->lastname;
    }


    public function getClientIp()
    {
        // cannot determine client ip if repository class is used outside of request scope (i.e. tests)
        if (isset($app['request']))
        {
            return $this->app['request']->getClientIp();
        }

        return null;
    }


    public function get($repositoryName)
    {
        if ($this->cmdlAccessAdapter->hasRepository($repositoryName))
        {
            $repository = new Repository($this->app, $repositoryName);

            return $repository;
        }

        return false;
    }


    public function hasRepository($repositoryName)
    {
        return $this->cmdlAccessAdapter->hasRepository($repositoryName);
    }


    public function createRepository($repositoryName)
    {
        return $this->cmdlAccessAdapter->createRepository($repositoryName);
    }


    public function discardRepository($repositoryName)
    {
        return $this->cmdlAccessAdapter->discardRepository($repositoryName);
    }


    public function getRepositories()
    {
        return $this->cmdlAccessAdapter->getRepositories();
    }


    public function getContentTypesList($repositoryName)
    {
        return $this->cmdlAccessAdapter->getContentTypesList($repositoryName);
    }


    public function getConfigTypesList($repositoryName)
    {
        return $this->cmdlAccessAdapter->getConfigTypesList($repositoryName);
    }


    public function getContentTypeCMDL($repositoryName, $contentTypeName)
    {
        return $this->cmdlAccessAdapter->getContentTypeCMDL($repositoryName, $contentTypeName);

    }


    public function saveContentTypeCMDL($repositoryName, $contentTypeName, $cmdl, $locale = null)
    {
        return $this->cmdlAccessAdapter->saveContentTypeCMDL($repositoryName, $contentTypeName, $cmdl, $locale);
    }


    public function getAgeContentTypeCMDL($repositoryName, $contentTypeName)
    {
        return $this->cmdlAccessAdapter->getAgeContentTypeCMDL($repositoryName, $contentTypeName);
    }


    public function getConfigTypeCMDL($repositoryName, $configTypeName)
    {
        return $this->cmdlAccessAdapter->getConfigTypeCMDL($repositoryName, $configTypeName);

    }


    public function getAgeConfigTypeCMDL($repositoryName, $configTypeName)
    {
        return $this->cmdlAccessAdapter->getAgeConfigTypeCMDL($repositoryName, $configTypeName);
    }


    public function saveConfigTypeCMDL($repositoryName, $contentTypeName, $cmdl, $locale = null)
    {
        return $this->cmdlAccessAdapter->saveConfigTypeCMDL($repositoryName, $contentTypeName, $cmdl, $locale);
    }


    public function getContentTypeDefinition($repositoryName, $contentTypeName)
    {
        return $this->cmdlAccessAdapter->getContentTypeDefinition($repositoryName, $contentTypeName);
    }


    public function getConfigTypeDefinition($repositoryName, $configTypeName)
    {
        return $this->cmdlAccessAdapter->getConfigTypeDefinition($repositoryName, $configTypeName);
    }


    public function discardContentType($repositoryName, $contentTypeName)
    {
        return $this->cmdlAccessAdapter->discardContentType($repositoryName, $contentTypeName);
    }


    public function discardConfigType($repositoryName, $configTypeName)
    {
        return $this->cmdlAccessAdapter->discardConfigType($repositoryName, $configTypeName);
    }


    public function getDatabaseConnection()
    {
        return $this->app['db']->getConnection();
    }


    public function getFilesAdapterConfig($repositoryName)
    {
        return $this->app['config']->getFilesAdapterConfig($repositoryName);
    }


    public static function getMaxTimestamp()
    {
        //19.01.2038
        return number_format(2147483647, 4, '.', '');
    }


    public static function getMaxTimeshift()
    {
        // roundabout 10 years, equals to 1.1.1980

        return number_format(315532800, 4, '.', '');
    }


    public static function getTimeshiftTimestamp($timeshift = 0)
    {
        if ($timeshift < self::getMaxTimeshift())
        {
            return number_format(microtime(true) - $timeshift, 4, '.', '');
        }

        return $timeshift;
    }

}