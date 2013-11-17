<?php

namespace AnyContent\Repository;

use Silex\Application;

use CMDL\ContentTypeDefinition;
use CMDL\Util;

use AnyContent\Repository\Helper;
use AnyContent\Repository\RepositoryException;

class ContentManager
{

    /**
     * @var Repository
     */
    protected $repository = null;
    protected $contentTypeDefinition = null;


    public function __construct($repository, ContentTypeDefinition $contentTypeDefinition)
    {
        $this->repository            = $repository;
        $this->contentTypeDefinition = $contentTypeDefinition;
    }


    /**
     * @param $record
     *
     * @return array
     * @throws Exception
     */
    public function getRecord($id, $clippingName = 'default', $workspace = 'default', $language = 'none', $timeshift = 0)
    {
        $repositoryName  = $this->repository->getName();
        $contentTypeName = $this->contentTypeDefinition->getName();

        $row = $this->getRecordTableRow($id, $workspace, $language, $timeshift);

        return $this->getRecordDataStructureFromRow($row, $repositoryName, $contentTypeName, $clippingName);

    }


    protected function getRecordTableRow($id, $workspace = 'default', $language = 'none', $timeshift = 0)
    {

        $repositoryName  = $this->repository->getName();
        $contentTypeName = $this->contentTypeDefinition->getName();

        $tableName = $repositoryName . '$' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repositoryName) .'$'. Util::generateValidIdentifier($contentTypeName))
        {
            throw new Exception ('Invalid repository and/or content type name(s).', self::INVALID_NAMES);
        }

        $dbh = $this->repository->getDatabaseConnection();

        $timestamp = $this->repository->getTimeshiftTimestamp($timeshift);

        $sql      = 'SELECT * FROM ' . $tableName . ' WHERE id = ? AND workspace = ? AND language = ? AND deleted = 0 AND validfrom_timestamp <= ? AND validuntil_timestamp > ?';
        $stmt     = $dbh->prepare($sql);
        $params   = array();
        $params[] = $id;
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
                return $row;
            }
            throw new RepositoryException('Record not found.', RepositoryException::REPOSITORY_RECORD_NOT_FOUND);

        }
        catch (\PDOException $e)
        {
            throw new RepositoryException('Record not found.', RepositoryException::REPOSITORY_RECORD_NOT_FOUND);
        }
    }


    public function getRecords($clippingName, $workspace, $language, $timeshift, $orderBy = 'id ASC', $limit = null, $page = 1, $subset = null, $filter = null)
    {
        $records         = array();
        $repositoryName  = $this->repository->getName();
        $contentTypeName = $this->contentTypeDefinition->getName();

        $tableName = $repositoryName . '$' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repositoryName) .'$'. Util::generateValidIdentifier($contentTypeName))
        {
            throw new Exception ('Invalid repository and/or content type name(s).', self::INVALID_NAMES);
        }

        $dbh = $this->repository->getDatabaseConnection();

        $timestamp = $this->repository->getTimeshiftTimestamp($timeshift);

        $sql      = 'SELECT * FROM ' . $tableName . ' WHERE workspace = ? AND language = ? AND deleted = 0 AND validfrom_timestamp <= ? AND validuntil_timestamp > ? ORDER BY ' . $orderBy;

        if ($limit != null)
        {
            $sql .= ' LIMIT '.  (((int)$page - 1) * (int)$limit) . ',' . (int)$limit;
        }

        $stmt     = $dbh->prepare($sql);
        $params   = array();
        $params[] = $workspace;
        $params[] = $language;
        $params[] = $timestamp;
        $params[] = $timestamp;

        try
        {
            $stmt->execute($params);

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $row)
            {
                $records[$row['id']] = $this->getRecordDataStructureFromRow($row, $repositoryName, $contentTypeName, $clippingName);
            }

            return $records;

        }
        catch (\PDOException $e)
        {
            throw new RepositoryException('Record not found.', RepositoryException::REPOSITORY_RECORD_NOT_FOUND);
        }

    }


    /**
     * @param $record
     *
     * @return int
     * @throws Exception
     */
    public function saveRecord($record, $clippingName = 'default', $workspace = 'default', $language = 'none')
    {
        $repositoryName  = $this->repository->getName();
        $contentTypeName = $this->contentTypeDefinition->getName();

        $tableName = $repositoryName . '$' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repositoryName) .'$'. Util::generateValidIdentifier($contentTypeName))
        {
            throw new Exception ('Invalid repository and/or content type name(s).', self::INVALID_NAMES);
        }

        $possibleProperties = $this->contentTypeDefinition->getProperties($clippingName);

        $notallowed = array_diff(array_keys($record['properties']), $possibleProperties);

        if (count($notallowed) != 0)
        {
            throw new RepositoryException('Trying to store undefined properties: ' . join(',', $notallowed) . '.', RepositoryException::REPOSITORY_INVALID_PROPERTIES);
        }

        $mandatoryProperties = $this->contentTypeDefinition->getMandatoryProperties($clippingName);

        $missing = array();
        foreach ($mandatoryProperties as $property)
        {
            if (array_key_exists($property, $record['properties']))
            {
                if ($record['properties'][$property] == '')
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
            throw new RepositoryException('Trying to store record, but missing mandatory properties: ' . join(',', $missing) . '.', RepositoryException::REPOSITORY_MISSING_MANDATORY_PROPERTIES);
        }

        $dbh = $this->repository->getDatabaseConnection();

        $mode = 'insert';

        // fix record array structure, if someone forgot the id
        if (!isset($record['id']))
        {
            $record['id'] = 0;
        }

        if ($record['id'] != 0)
        {
            try
            {
                $row = self::getRecordTableRow($record['id'], $workspace, $language);

                $mode = 'update';

                // transfer all properties, which are not set int the record to be saved
                foreach ($row as $key => $value)
                {

                    if (Helper::startsWith($key, 'property_'))
                    {
                        $property = substr($key, 9);

                        if (!array_key_exists($property, $record['properties']))
                        {
                            $record['properties'][$property] = $value;

                        }
                    }
                }

                $mode               = 'update';
                $record['revision'] = $row['revision'] + 1;

            }
            catch (RepositoryException $e)
            {

            }
        }

        if ($mode == 'insert')
        {
            // update counter for new record

            $sql = 'INSERT INTO _counter_ (repository,content_type,counter) VALUES (? , ? ,1) ON DUPLICATE KEY UPDATE counter=counter+1;';

            $params   = array();
            $params[] = $repositoryName;
            $params[] = $contentTypeName;
            $stmt     = $dbh->prepare($sql);
            $stmt->execute($params);

            $sql  = 'SELECT counter FROM _counter_ WHERE repository = ? AND content_type = ?';
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchColumn(0);

            if ($record['id'] == null)
            {
                $record['id'] = $result;
            }
            $record['revision'] = 1;
        }

        $timestamp          = time();
        $timeshiftTimestamp = $this->repository->getTimeshiftTimestamp();

        if ($mode == 'update')
        {
            // invalidate current revision

            $sql      = 'UPDATE ' . $tableName . ' SET validuntil_timestamp = ? WHERE id = ? AND workspace = ? AND language = ? AND deleted = 0 AND validfrom_timestamp <=? AND validuntil_timestamp >?';
            $params   = array();
            $params[] = $timeshiftTimestamp;
            $params[] = $record['id'];
            $params[] = $workspace;
            $params[] = $language;
            $params[] = $timeshiftTimestamp;
            $params[] = $timeshiftTimestamp;
            $stmt     = $dbh->prepare($sql);
            $stmt->dexecute($params);
        }

        $values         = array();
        $values['id']   = $record['id'];
        $values['hash'] = md5(serialize($record['properties']));
        //$values['name']      = @$record['properties']['name'];
        $values['workspace'] = $workspace;
        $values['language']  = $language;
        //$values['subtype']   = @$record['properties']['subtype'];
        //$values['status']    = @$record['properties']['status'];
        $values['revision'] = $record['revision'];
        $values['deleted']  = 0;

        if ($mode == 'insert')
        {
            $values['creation_timestamp'] = $timestamp;
            $values['creation_apiuser']   = $this->repository->getAPIUser();
            $values['creation_clientip']  = $this->repository->getClientIp();
            $values['creation_username']  = $this->repository->getCurrentUserName();
            $values['creation_firstname'] = $this->repository->getCurrentUserFirstname();
            $values['creation_lastname']  = $this->repository->getCurrentUserLastname();
        }
        else
        {
            $values['creation_timestamp'] = $row['creation_timestamp'];
            $values['creation_apiuser']   = $row['creation_apiuser'];
            $values['creation_clientip']  = $row['creation_clientip'];
            $values['creation_username']  = $row['creation_username'];
            $values['creation_firstname'] = $row['creation_firstname'];
            $values['creation_lastname']  = $row['creation_lastname'];
        }

        $values['lastchange_timestamp'] = $timestamp;
        $values['lastchange_apiuser']   = $this->repository->getAPIUser();
        $values['lastchange_clientip']  = $this->repository->getClientIp();
        $values['lastchange_username']  = $this->repository->getCurrentUserName();
        $values['lastchange_firstname'] = $this->repository->getCurrentUserFirstname();
        $values['lastchange_lastname']  = $this->repository->getCurrentUserLastname();

        $values['validfrom_timestamp']  = $timeshiftTimestamp;
        $values['validuntil_timestamp'] = $this->repository->getMaxTimestamp();

        foreach ($record['properties'] AS $property => $value)
        {
            $values['property_' . $property] = $value;
        }

        if ($mode == 'update')
        {

            $values['parent_id']      = $row['parent_id'];
            $values['position']       = $row['position'];
            $values['position_left']  = $row['position_left'];
            $values['position_right'] = $row['position_right'];
            $values['position_level'] = $row['position_level'];

        }

        $sql = 'INSERT INTO ' . $tableName;
        $sql .= ' (' . join(',', array_keys($values)) . ')';
        $sql .= ' VALUES ( ?';
        $sql .= str_repeat(' , ?', count($values) - 1);
        $sql .= ')';
        $stmt = $dbh->prepare($sql);
        $stmt->dexecute(array_values($values));

        return $record['id'];

    }


    protected function getRecordDataStructureFromRow($row, $repositoryName, $contentTypeName, $clippingName)
    {
        $record                 = array();
        $record['id']           = $row['id'];
        $record['repository']   = $repositoryName;
        $record['content_type'] = $contentTypeName;
        $record['workspace']    = $row['workspace'];
        $record['clipping']     = $clippingName;
        $record['language']     = $row['language'];
        $record['properties']   = array();

        $properties = $this->contentTypeDefinition->getProperties($clippingName);
        foreach ($properties as $property)
        {
            $record['properties'][$property] = $row['property_' . $property];
        }
        $record['info']             = array();
        $record['info']['revision'] = $row['revision'];

        $record['info']['creation']['timestamp'] = $row['creation_timestamp'];
        $record['info']['creation']['username']  = $row['creation_username'];
        $record['info']['creation']['firstname'] = $row['creation_firstname'];
        $record['info']['creation']['lastname']  = $row['creation_lastname'];

        $record['info']['lastchange']['timestamp'] = $row['lastchange_timestamp'];
        $record['info']['lastchange']['username']  = $row['lastchange_username'];
        $record['info']['lastchange']['firstname'] = $row['lastchange_firstname'];
        $record['info']['lastchange']['lastname']  = $row['lastchange_lastname'];

        $record['info']['position'] = $row['position'];
        $record['info']['level']    = $row['position_level'];

        return $record;
    }


    public function hasProperty($property, $clippingName = null)
    {
        $possibleProperties = $this->contentTypeDefinition->getProperties($clippingName);
        if (in_array($property, $possibleProperties))
        {
            return true;
        }

        return false;

    }
}