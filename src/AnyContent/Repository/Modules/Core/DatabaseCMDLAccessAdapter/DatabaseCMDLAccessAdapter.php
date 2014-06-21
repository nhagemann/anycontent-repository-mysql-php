<?php

namespace AnyContent\Repository\Modules\Core\DatabaseCMDLAccessAdapter;

use AnyContent\Repository\Modules\Core\Application\Application;

use AnyContent\Repository\Modules\Core\Repositories\ConfigTypeInfo;
use AnyContent\Repository\Modules\Core\Repositories\ContentTypeInfo;

use CMDL\Parser;
use CMDL\ParserException;
use CMDL\Util;

use AnyContent\Repository\Modules\Core\Repositories\RepositoryException;

class DatabaseCMDLAccessAdapter
{

    protected $app;

    protected $repositories = null;

    protected $contentTypeDefinitions = array();

    protected $configTypeDefinitions = array();

    protected $cmdl = array();


    public function __construct($app, $config, $options)
    {
        $this->app = $app;

        /** @var PDO $db */
        $dbh = $this->app['db']->getConnection();

        $sql = 'SHOW TABLES LIKE ?';

        $stmt = $dbh->prepare($sql);
        $stmt->execute(array( '_cmdl_' ));

        if ($stmt->rowCount() == 0)
        {
            $sql = <<< TEMPLATE_CMDLTABLE
        CREATE TABLE `_cmdl_` (
        `repository` varchar(255) NOT NULL DEFAULT '',
        `data_type` ENUM('content', 'config', ''),
        `name` varchar(255) NOT NULL DEFAULT '',
        `cmdl` text,
        `lastchange_timestamp` varchar(16) DEFAULT NULL,
        UNIQUE KEY `index1` (`repository`,`data_type`,`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

TEMPLATE_CMDLTABLE;

            $stmt = $dbh->prepare($sql);

            try
            {
                $stmt->execute();
            }
            catch (\PDOException $e)
            {

            }

        }

    }


    public function hasRepository($repositoryName)
    {
        if (trim($repositoryName) != '')
        {
            /** @var PDO $db */
            $dbh = $this->app['db']->getConnection();

            $sql = 'SELECT EXISTS (SELECT * FROM _cmdl_ WHERE repository=?)';

            $stmt = $dbh->prepare($sql);

            try
            {
                $stmt->execute(array( $repositoryName ));

                return (boolean)$stmt->fetchColumn();
            }
            catch (\PDOException $e)
            {

            }
        }

        return false;

    }


    public function getRepositories()
    {

        if (!$this->repositories)
        {

            /** @var PDO $db */
            $dbh = $this->app['db']->getConnection();

            $sql = 'SELECT DISTINCT (repository) FROM _cmdl_ ORDER BY repository';

            $stmt = $dbh->prepare($sql);

            try
            {
                $stmt->execute(array());

                $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                return $result;
            }
            catch (\PDOException $e)
            {

            }

            return array();

        }

        return $this->repositories;
    }


    public function getContentTypesList($repositoryName)
    {
        $contentTypes = array();
        /** @var PDO $db */
        $dbh = $this->app['db']->getConnection();

        $sql = 'SELECT name, cmdl, lastchange_timestamp FROM _cmdl_ WHERE repository = ? AND data_type = "content" ORDER BY name';

        $stmt = $dbh->prepare($sql);

        try
        {
            $stmt->execute(array( $repositoryName ));

            $result = $stmt->fetchAll();

            foreach ($result as $row)
            {

                $contentTypeDefinition = $this->getContentTypeDefinition($repositoryName, $row['name']);

                if ($contentTypeDefinition)
                {
                    $info = new ContentTypeInfo();
                    $info->setName($contentTypeDefinition->getName());
                    $info->setLastchangecmdl($row['lastchange_timestamp']);
                    $info->setTitle((string)$contentTypeDefinition->getTitle());
                    $info->setDescription((string)$contentTypeDefinition->getDescription());
                    $contentTypes[$row['name']] = $info;
                }
            }

        }
        catch (\PDOException $e)
        {

        }

        return $contentTypes;

    }


    public function getConfigTypesList($repositoryName)
    {

        $configTypes = array();

        /** @var PDO $db */
        $dbh = $this->app['db']->getConnection();

        $sql = 'SELECT name, lastchange_timestamp FROM _cmdl_ WHERE repository = ? AND data_type = "config" ORDER BY name';

        $stmt = $dbh->prepare($sql);

        try
        {
            $stmt->execute(array( $repositoryName ));

            $result = $stmt->fetchAll();

            foreach ($result as $row)
            {

                $contentTypeDefinition = $this->getConfigTypeDefinition($repositoryName, $row['name']);

                if ($contentTypeDefinition)
                {
                    $info = new ConfigTypeInfo();
                    $info->setName($contentTypeDefinition->getName());
                    $info->setLastchangecmdl($row['lastchange_timestamp']);
                    $info->setTitle((string)$contentTypeDefinition->getTitle());
                    $info->setDescription((string)$contentTypeDefinition->getDescription());
                    $configTypes[$row['name']] = $info;
                }
            }

        }
        catch (\PDOException $e)
        {

        }

        return $configTypes;

    }


    public function getContentTypeCMDL($repositoryName, $contentTypeName)
    {
        $token = $repositoryName . '$' . $contentTypeName;

        if (array_key_exists($token, $this->cmdl))
        {
            return $this->cmdl[$token]['cmdl'];
        }

        if ($this->hasRepository($repositoryName))
        {
            /** @var PDO $db */
            $dbh = $this->app['db']->getConnection();

            $sql = 'SELECT name, cmdl, lastchange_timestamp FROM _cmdl_ WHERE repository = ? AND name = ? AND data_type = "content"';

            $stmt = $dbh->prepare($sql);

            try
            {
                $stmt->execute(array( $repositoryName, $contentTypeName ));
                $row = $stmt->fetch();

                if ($row)
                {
                    $this->cmdl[$token]['cmdl']      = $row['cmdl'];
                    $this->cmdl[$token]['timestamp'] = $row['lastchange_timestamp'];

                    return $row['cmdl'];
                }

            }
            catch (\PDOException $e)
            {

            }
        }

        return false;
    }


    public function getAgeContentTypeCMDL($repositoryName, $contentTypeName)
    {

        $token = $repositoryName . '$' . $contentTypeName;
        if (array_key_exists($token, $this->cmdl))
        {
            return $this->cmdl[$token]['timestamp'];
        }
        else
        {
            if ($this->getContentTypeCMDL($repositoryName, $contentTypeName))
            {
                return $this->cmdl[$token]['timestamp'];
            }
        }

        return 0;
    }


    public function getConfigTypeCMDL($repositoryName, $configTypeName)
    {
        $token = $repositoryName . '$' . $configTypeName;

        if (array_key_exists($token, $this->cmdl))
        {
            return $this->cmdl[$token]['cmdl'];
        }

        if ($this->hasRepository($repositoryName))
        {
            /** @var PDO $db */
            $dbh = $this->app['db']->getConnection();

            $sql = 'SELECT name, cmdl, lastchange_timestamp FROM _cmdl_ WHERE repository = ? AND name = ? AND data_type = "config"';

            $stmt = $dbh->prepare($sql);

            try
            {
                $stmt->execute(array( $repositoryName, $configTypeName ));
                $row = $stmt->fetch();

                if ($row)
                {
                    $this->cmdl[$token]['cmdl']      = $row['cmdl'];
                    $this->cmdl[$token]['timestamp'] = $row['lastchange_timestamp'];

                    return $row['cmdl'];
                }

            }
            catch (\PDOException $e)
            {

            }
        }

        return false;

    }


    public function getAgeConfigTypeCMDL($repositoryName, $configTypeName)
    {

        $token = 'config$' . $repositoryName . '$' . $configTypeName;
        if (array_key_exists($token, $this->cmdl))
        {
            return $this->cmdl[$token]['timestamp'];
        }
        else
        {
            if ($this->getConfigTypeCMDL($repositoryName, $configTypeName))
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

        $cmdl = $this->getContentTypeCMDL($repositoryName, $contentTypeName);
        if ($cmdl)
        {
            try
            {

                $contentTypeDefinition = Parser::parseCMDLString($cmdl);
                $contentTypeDefinition->setName($contentTypeName);

                // after generating the definition, check if the database is up to date

                $this->app['db']->refreshContentTypeTableStructure($repositoryName, $contentTypeDefinition);

                $this->contentTypeDefinitions[$repositoryName][$contentTypeName] = $contentTypeDefinition;

                return $contentTypeDefinition;
            }
            catch (ParserException $e)
            {

            }
        }

        return false;

    }


    public function getConfigTypeDefinition($repositoryName, $configTypeName)
    {
        // check if definition already has been created
        if (array_key_exists($repositoryName, $this->configTypeDefinitions))
        {
            if (array_key_exists($configTypeName, $this->configTypeDefinitions[$repositoryName]))
            {
                return $this->configTypeDefinitions[$repositoryName][$configTypeName];
            }
        }

        $cmdl = $this->getConfigTypeCMDL($repositoryName, $configTypeName);
        if ($cmdl)
        {
            try
            {
                $configTypeDefinition = Parser::parseCMDLString($cmdl, $configTypeName, $configTypeName, 'config');
                $configTypeDefinition->setName($configTypeName);

                // after generating the definition, check if the database is up to date

                $this->app['db']->refreshConfigTypesTableStructure($repositoryName);

                $this->configTypeDefinitions[$repositoryName][$configTypeName] = $configTypeDefinition;

                return $configTypeDefinition;
            }
            catch (ParserException $e)
            {

            }
        }

        return false;

    }


    public function saveContentTypeCMDL($repositoryName, $contentTypeName, $cmdl, $locale = null, $createRepository = true)
    {

        if ($contentTypeName != Util::generateValidIdentifier($contentTypeName) || $repositoryName != Util::generateValidIdentifier($repositoryName))
        {
            throw new RepositoryException ('Invalid repository and/or content type name(s).');
        }

        try
        {

            $contentTypeDefinition = Parser::parseCMDLString($cmdl);
            $contentTypeDefinition->setName($contentTypeName);

        }
        catch (ParserException $e)
        {
            throw new RepositoryException ('Could not parse definition for content type ' . $contentTypeName);
        }

        if ($this->hasRepository($repositoryName) || $createRepository == true)
        {

            /** @var PDO $db */
            $dbh = $this->app['db']->getConnection();

            try
            {
                $timestamp = $this->app['repos']->getTimeshiftTimestamp();

                $sql = 'INSERT INTO _cmdl_ (repository,data_type,name,cmdl,lastchange_timestamp) VALUES (? ,"content", ? , ? , ?) ON DUPLICATE KEY UPDATE cmdl = ?, lastchange_timestamp = ?';

                $params   = array();
                $params[] = $repositoryName;
                $params[] = $contentTypeName;
                $params[] = $cmdl;
                $params[] = $timestamp;
                $params[] = $cmdl;
                $params[] = $timestamp;
                $stmt     = $dbh->prepare($sql);
                $stmt->execute($params);

                $this->app['db']->refreshContentTypeTableStructure($repositoryName, $contentTypeDefinition);

                $this->contentTypeDefinitions = array();

                return true;

            }
            catch (\PDOException $e)
            {

            }

        }

        return false;
    }


    public function discardContentType($repositoryName, $contentTypeName)
    {
        if ($contentTypeName != Util::generateValidIdentifier($contentTypeName) || $repositoryName != Util::generateValidIdentifier($repositoryName))
        {
            throw new RepositoryException ('Invalid repository and/or content type name(s).');
        }

        if ($this->hasRepository($repositoryName))
        {

            /** @var PDO $db */
            $dbh = $this->app['db']->getConnection();

            $sql = 'DELETE FROM _cmdl_ WHERE repository =? AND data_type = "content" AND name = ?';

            $stmt = $dbh->prepare($sql);

            try
            {
                $stmt->execute(array( $repositoryName, $contentTypeName ));
            }
            catch (\PDOException $e)
            {

            }

            $this->app['db']->truncateContentType($repositoryName, $contentTypeName);
            $this->contentTypeDefinitions = array();

            return true;

        }

        return false;

    }


    public function saveConfigTypeCMDL($repositoryName, $configTypeName, $cmdl, $locale = null, $createRepository = true)
    {

        if ($configTypeName != Util::generateValidIdentifier($configTypeName) || $repositoryName != Util::generateValidIdentifier($repositoryName))
        {
            throw new RepositoryException ('Invalid repository and/or config type name(s).');
        }

        try
        {

            $configTypeDefinition = Parser::parseCMDLString($cmdl);
            $configTypeDefinition->setName($configTypeName);

        }
        catch (ParserException $e)
        {
            throw new RepositoryException ('Could not parse definition for config type ' . $configTypeName);
        }

        if ($this->hasRepository($repositoryName) || $createRepository == true)
        {

            /** @var PDO $db */
            $dbh = $this->app['db']->getConnection();

            try
            {
                $timestamp = $this->app['repos']->getTimeshiftTimestamp();

                $sql = 'INSERT INTO _cmdl_ (repository,data_type,name,cmdl,lastchange_timestamp) VALUES (? ,"config", ? , ? , ?) ON DUPLICATE KEY UPDATE cmdl = ?, lastchange_timestamp = ?';

                $params   = array();
                $params[] = $repositoryName;
                $params[] = $configTypeName;
                $params[] = $cmdl;
                $params[] = $timestamp;
                $params[] = $cmdl;
                $params[] = $timestamp;
                $stmt     = $dbh->prepare($sql);
                $stmt->execute($params);

                $this->app['db']->refreshConfigTypesTableStructure($repositoryName, $configTypeDefinition);
                $this->configTypeDefinitions = array();

                return true;

            }
            catch (\PDOException $e)
            {

            }

        }

        return false;

    }


    public function discardConfigType($repositoryName, $configTypeName)
    {
        if ($configTypeName != Util::generateValidIdentifier($configTypeName) || $repositoryName != Util::generateValidIdentifier($repositoryName))
        {
            throw new RepositoryException ('Invalid repository and/or config type name(s).');
        }

        if ($this->hasRepository($repositoryName))
        {

            /** @var PDO $db */
            $dbh = $this->app['db']->getConnection();

            $sql = 'DELETE FROM _cmdl_ WHERE repository =? AND data_type = "config" AND name = ?';

            $stmt = $dbh->prepare($sql);

            try
            {
                $stmt->execute(array( $repositoryName, $configTypeName ));
            }
            catch (\PDOException $e)
            {

            }

            $this->app['db']->truncateConfigType($repositoryName, $configTypeName);
            $this->configTypeDefinitions = array();

            return true;

        }

        return false;
    }


    public function createRepository($repositoryName)
    {
        if ($repositoryName != Util::generateValidIdentifier($repositoryName))
        {
            throw new RepositoryException ('Invalid repository name.');
        }

        if (!$this->hasRepository($repositoryName))
        {
            /** @var PDO $db */
            $dbh = $this->app['db']->getConnection();

            $sql = 'INSERT INTO _cmdl_ (repository) VALUES (?)';

            $stmt = $dbh->prepare($sql);

            try
            {
                $stmt->execute(array( $repositoryName ));
            }
            catch (\PDOException $e)
            {

            }

        }

        $this->repositories = null;

        return true;

    }


    public function discardRepository($repositoryName)
    {
        if ($this->hasRepository($repositoryName))
        {
            foreach ($this->getContentTypesList($repositoryName) as $contentTypeName => $contentTypeInfo)
            {
                $this->truncateContentType($repositoryName, $contentTypeName);
            }
            foreach ($this->getConfigTypesList($repositoryName) as $configTypeName => $configTypeInfo)
            {
                $this->truncateConfigType($repositoryName, $configTypeName);
            }

            /** @var PDO $db */
            $dbh = $this->app['db']->getConnection();

            $sql = 'DELETE FORM _cmdl_ WHERE repository = ?';

            $stmt = $dbh->prepare($sql);

            try
            {
                $stmt->execute(array( $repositoryName ));
            }
            catch (\PDOException $e)
            {

            }

            $this->repositories = null;

            return true;
        }

        return false;
    }

    public function getCMDLConfigHash($repositoryName = null)
    {
        // Attention: There's not much sense for this calculations, since the value of the column lastchange_timestamp
        // is very important for the calculation and it's very unlikely, that someone manually editing the database
        // content will adjust this value, if he/she changes the cmdl. If the cmdl gets changed via an API call, the
        // heartbeat will be reseted anyway.

        $hash = '';

        /** @var PDO $db */
        $dbh = $this->app['db']->getConnection();

        $sql = 'SELECT name, lastchange_timestamp FROM _cmdl_ WHERE repository = ?';
        $stmt = $dbh->prepare($sql);

        try
        {
            $stmt->execute(array( $repositoryName ));
            $rows = $stmt->fetchAll();
            foreach ($rows as $row)
            {
               $hash .= $row['name'].'.'.$row['lastchange_timestamp'].'-';
            }
        }
        catch (\PDOException $e)
        {

        }


        return md5($hash);
    }

}