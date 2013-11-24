<?php

namespace AnyContent\Repository\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

use AnyContent\Repository\Repository;

use AnyContent\Repository\Entity\ContentTypeInfo;

use CMDL\Parser;
use CMDL\ParserException;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Adapter\Ftp as FTPAdapter;
use Gaufrette\Adapter\Dropbox as DropboxAdapter;
use Gaufrette\Adapter\Cache as CacheAdapter;

class RepositoryManager
{

    protected $app;

    protected $repositories = null;

    protected $contentTypeDefinitions = array();

    protected $cmdl = array();

    protected $apiUser = null;

    protected $username = null;

    protected $firstname = null;

    protected $lastname = null;


    public function __construct(Application $app)
    {
        $this->app = $app;

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
            $token = $repositoryName . '$' . $contentTypeName;
            if (array_key_exists($token, $this->cmdl))
            {
                return $this->cmdl[$token]['cmdl'];
            }
            $filename = $this->app['config']->getCMDLDirectory() . '/' . $repositoryName . '/' . $contentTypeName . '.cmdl';
            $cmdl     = @file_get_contents($filename);
            if ($cmdl)
            {
                $filestats                       = stat($filename);
                $this->cmdl[$token]['cmdl']      = $cmdl;
                $this->cmdl[$token]['timestamp'] = @$filestats['mtime'];

                return $cmdl;
            }
        }

        return false;
    }


    public function getAgeCMDL($repositoryName, $contentTypeName)
    {
        $token = $repositoryName . '$' . $contentTypeName;
        if (array_key_exists($token, $this->cmdl))
        {
            return $this->cmdl[$token]['timestamp'];
        }
        else
        {
            if ($this->getCMDL($repositoryName, $contentTypeName))
            {
                return $this->cmdl[$token]['timestamp'];
            }
        }

        return 0;
    }


    public function getContentTypeDefinition($repositoryName, $contentTypeName)
    {
        // check if definition already has been created
        if (array_key_exists($repositoryName, $this->contentTypeDefinitions))
        {
            if (array_key_exists($contentTypeName, $this->contentTypeDefinitions[$repositoryName]))
            {
                return $this->contentTypeDefinitions[$repositoryName][$contentTypeName];
            }
        }

        $cmdl = $this->getCMDL($repositoryName, $contentTypeName);
        if ($cmdl)
        {
            try
            {
                $contentTypeDefinition = Parser::parseCMDLString($cmdl);
                $contentTypeDefinition->setName($contentTypeName);

                // after generating the definition, check if the database is up to date
                $timestamp = $this->getAgeCMDL($repositoryName, $contentTypeName);
                $dbh       = $this->getDatabaseConnection();
                $sql       = 'SELECT last_cmdl_change_timestamp FROM _info_ WHERE repository = ? AND content_type = ?';

                $params   = array();
                $params[] = $repositoryName;
                $params[] = $contentTypeName;
                $stmt     = $dbh->prepare($sql);
                $stmt->execute($params);
                $result = (int)$stmt->fetchColumn(0);

                if ($result < $timestamp)
                {

                    $this->app['db']->refreshContentTypeTableStructure($repositoryName, $contentTypeDefinition);

                    $sql = 'INSERT INTO _info_ (repository,content_type,last_cmdl_change_timestamp) VALUES (? , ? ,?) ON DUPLICATE KEY UPDATE last_cmdl_change_timestamp=?;';

                    $params   = array();
                    $params[] = $repositoryName;
                    $params[] = $contentTypeName;
                    $params[] = $timestamp;
                    $params[] = $timestamp;
                    $stmt     = $dbh->prepare($sql);
                    $stmt->execute($params);

                }

                $this->contentTypeDefinitions[$repositoryName][$contentTypeName] = $contentTypeDefinition;

                return $contentTypeDefinition;
            }
            catch (ParserException $e)
            {

            }
        }

        return false;

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


    public static function getTimeshiftTimestamp($timeshift = 0)
    {
        return number_format(microtime(true) - $timeshift, 4, '.', '');
    }

}