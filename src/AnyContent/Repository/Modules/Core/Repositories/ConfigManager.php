<?php

namespace AnyContent\Repository;

namespace AnyContent\Repository\Modules\Core\Repositories;

use CMDL\ContentTypeDefinition;
use CMDL\Util;

use AnyContent\Repository\Helper;
use AnyContent\Repository\Modules\Core\Repositories\RepositoryException;

class ConfigManager
{

    /**
     * @var Repository
     */
    protected $repository = null;


    public function __construct($repository)
    {
        $this->repository = $repository;

    }


    public function hasConfigType($configTypeName)
    {
        return $this->repository->hasConfigType($configTypeName);
    }


    public function getConfigTypeDefinition($configTypeName)
    {
        return $this->repository->getConfigTypeDefinition($configTypeName);
    }


    /**
     * @param $record
     *
     * @return array
     * @throws Exception
     */
    public function getConfig($configTypeName, $workspace = 'default', $language = 'default', $timeshift = 0)
    {

        $configTypeDefinition = $this->repository->getConfigTypeDefinition($configTypeName);

        if ($configTypeDefinition)
        {
            $repositoryName = $this->repository->getName();

            $configTypeName = $configTypeDefinition->getName();

            $tableName = $repositoryName . '$$config';

            if ($tableName != Util::generateValidIdentifier($repositoryName) . '$$config')
            {
                throw new RepositoryException ('Invalid repository and/or config type name(s).');
            }

            $dbh = $this->repository->getDatabaseConnection();

            $timestamp = $this->repository->getTimeshiftTimestamp($timeshift);

            $sql      = 'SELECT * FROM ' . $tableName . ' WHERE id = ? AND workspace = ? AND language = ? AND validfrom_timestamp <= ? AND validuntil_timestamp > ?';
            $stmt     = $dbh->prepare($sql);
            $params   = array();
            $params[] = $configTypeName;
            $params[] = $workspace;
            $params[] = $language;
            $params[] = $timestamp;
            $params[] = $timestamp;

            try
            {

                $stmt->execute($params);

                $row = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($row)
                {

                    $record                               = array();
                    $record['id']                         = $configTypeName;
                    $properties                           = json_decode($row['properties'], true);
                    $record['properties']                 = $properties;
                    $record['info']                       = array();
                    $record['info']['revision']           = $row['revision'];
                    $record['info']['revision_timestamp'] = $row['validfrom_timestamp'];
                    $record['info']['hash']               = $row['hash'];

                    $info                = array();
                    $info['repository']  = $repositoryName;
                    $info['config_type'] = $configTypeName;
                    $info['workspace']   = $workspace;
                    $info['language']    = $language;

                    $record['info']['lastchange']['timestamp'] = $row['lastchange_timestamp'];
                    $record['info']['lastchange']['username']  = $row['lastchange_username'];
                    $record['info']['lastchange']['firstname'] = $row['lastchange_firstname'];
                    $record['info']['lastchange']['lastname']  = $row['lastchange_lastname'];

                    return array( 'info' => $info, 'record' => $record );
                }
                else
                {
                    return false;
                }

            }
            catch (\PDOException $e)
            {
               return false;
            }

        }

        return false;

    }


    public function saveConfig($configTypeDefinition, $properties, $workspace = 'default', $language = 'default')
    {

        $repositoryName = $this->repository->getName();

        $configTypeName = $configTypeDefinition->getName();

        $tableName = $repositoryName . '$$config';

        if ($tableName != Util::generateValidIdentifier($repositoryName) . '$$config')
        {
            throw new RepositoryException ('Invalid repository and/or config type name(s).');
        }

        $possibleProperties = $configTypeDefinition->getProperties();

        $notallowed = array_diff(array_keys($properties), $possibleProperties);

        if (count($notallowed) != 0)
        {
            throw new RepositoryException('Trying to store undefined properties: ' . join(',', $notallowed) . '.', RepositoryException::REPOSITORY_INVALID_PROPERTIES);
        }

        $mandatoryProperties = $configTypeDefinition->getMandatoryProperties('default');

        $missing = array();
        foreach ($mandatoryProperties as $property)
        {
            if (array_key_exists($property, $properties))
            {
                if ($properties[$property] == '')
                {
                    $missing[] = $property;
                }
            }
            else
            {
                $missing[] = $property;
            }
        }

        if (count($missing) != 0)
        {
            throw new RepositoryException('Trying to store config, but missing mandatory properties: ' . join(',', $missing) . '.', RepositoryException::REPOSITORY_MISSING_MANDATORY_PROPERTIES);
        }

        $dbh = $this->repository->getDatabaseConnection();

        $timestamp          = time();
        $timeshiftTimestamp = $this->repository->getTimeshiftTimestamp();

        // get current revision

        $revision = 0;

        try
        {
            $record = $this->getConfig($configTypeName, $workspace, $language);
            $revision = $record['record']['info']['revision'];
        }
        catch (RepositoryException $e)
        {
           // never mind we don't need an existing record
        }

        // invalidate current revision

        $sql      = 'UPDATE ' . $tableName . ' SET validuntil_timestamp = ? WHERE id = ? AND workspace = ? AND language = ? AND validfrom_timestamp <=? AND validuntil_timestamp >?';
        $params   = array();
        $params[] = $timeshiftTimestamp;
        $params[] = $configTypeName;
        $params[] = $workspace;
        $params[] = $language;
        $params[] = $timeshiftTimestamp;
        $params[] = $timeshiftTimestamp;
        $stmt     = $dbh->prepare($sql);
        $stmt->execute($params);

        $values         = array();
        $values['id']   = $configTypeName;
        $values['hash'] = md5(serialize($properties));

        $values['workspace'] = $workspace;
        $values['language']  = $language;
        $values['revision']  = $revision + 1;

        $values['properties'] = json_encode($properties);

        $values['lastchange_timestamp'] = $timestamp;
        $values['lastchange_apiuser']   = $this->repository->getAPIUser();
        $values['lastchange_clientip']  = $this->repository->getClientIp();
        $values['lastchange_username']  = $this->repository->getCurrentUserName();
        $values['lastchange_firstname'] = $this->repository->getCurrentUserFirstname();
        $values['lastchange_lastname']  = $this->repository->getCurrentUserLastname();

        $values['validfrom_timestamp']  = $timeshiftTimestamp;
        $values['validuntil_timestamp'] = $this->repository->getMaxTimestamp();

        $sql = 'INSERT INTO ' . $tableName;
        $sql .= ' (' . join(',', array_keys($values)) . ')';
        $sql .= ' VALUES ( ?';
        $sql .= str_repeat(' , ?', count($values) - 1);
        $sql .= ')';
        $stmt = $dbh->prepare($sql);
        $stmt->execute(array_values($values));

        return true;

    }

}
