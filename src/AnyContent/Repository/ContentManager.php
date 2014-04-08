<?php

namespace AnyContent\Repository;

use Silex\Application;

use CMDL\ContentTypeDefinition;
use CMDL\Util;

use AnyContent\Repository\Entity\Filter;

use AnyContent\Repository\Helper;
use AnyContent\Repository\RepositoryException;

use AnyContent\Repository\Util\AdjacentList2NestedSet;

class ContentManager
{

    /**
     * @var Repository
     */
    protected $repository = null;
    protected $contentTypeDefinition = null;

    protected $currentlySynchronizingProperties = false;


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
    public function getRecord($id, $viewName = 'default', $workspace = 'default', $language = 'default', $timeshift = 0)
    {
        $repositoryName  = $this->repository->getName();
        $contentTypeName = $this->contentTypeDefinition->getName();

        $row = $this->getRecordTableRow($id, $workspace, $language, $timeshift);

        $record               = $this->getRecordDataStructureFromRow($row, $repositoryName, $contentTypeName, $viewName);
        $info                 = array();
        $info['repository']   = $repositoryName;
        $info['content_type'] = $contentTypeName;
        $info['workspace']    = $workspace;
        $info['view']         = $viewName;
        $info['language']     = $language;

        return array( 'info' => $info, 'record' => $record );

    }


    protected function getRecordTableRow($id, $workspace = 'default', $language = 'default', $timeshift = 0)
    {

        $repositoryName  = $this->repository->getName();
        $contentTypeName = $this->contentTypeDefinition->getName();

        $tableName = $repositoryName . '$' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repositoryName) . '$' . Util::generateValidIdentifier($contentTypeName))
        {
            throw new RepositoryException ('Invalid repository and/or content type name(s).');
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


    public function getRecords($viewName = 'default', $workspace = 'default', $orderBy = 'id ASC', $limit = null, $page = 1, $subset = null, Filter $filter = null, $language = 'default', $timeshift = 0)
    {
        $records         = array();
        $repositoryName  = $this->repository->getName();
        $contentTypeName = $this->contentTypeDefinition->getName();

        $tableName = $repositoryName . '$' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repositoryName) . '$' . Util::generateValidIdentifier($contentTypeName))
        {
            throw new Exception ('Invalid repository and/or content type name(s).', self::INVALID_NAMES);
        }

        $dbh = $this->repository->getDatabaseConnection();

        $sqlSubset = '';
        if ($subset != null)
        {
            $orderBy             = 'position ASC';
            $parentRecordId      = 0;
            $includeParentRecord = 1;
            $depth               = null;

            $subset = explode(',', $subset);

            if (isset($subset[0]))
            {
                $parentRecordId = (int)$subset[0];
            }
            if (isset($subset[1]))
            {
                $includeParentRecord = (int)$subset[1];
            }
            if (isset($subset[2]))
            {
                $depth = (int)$subset[2];
            }

            if ($parentRecordId != 0)
            {
                try
                {
                    $row = $this->getRecordTableRow($parentRecordId, $workspace, $language, $timeshift);

                    if ($depth < 0) // climb upwards
                    {
                        if ($includeParentRecord == 1)
                        {
                            $sqlSubset = ' AND (position_left <= ' . (int)$row['position_left'] . ' AND position_right >=' . (int)$row['position_right'] . ')';
                            $depth     = $depth - 1;
                        }
                        else
                        {
                            $sqlSubset = ' AND (position_left < ' . (int)$row['position_left'] . ' AND position_right >' . (int)$row['position_right'] . ')';
                        }

                    }
                    else
                    {
                        $maxlevel = (int)$row['position_level'] + $depth + 1;
                        if ($includeParentRecord == 1)
                        {
                            $sqlSubset = ' AND (position_left >= ' . (int)$row['position_left'] . ' AND position_right <=' . (int)$row['position_right'] . ')';
                        }
                        else
                        {
                            $sqlSubset = ' AND (position_left > ' . (int)$row['position_left'] . ' AND position_right <' . (int)$row['position_right'] . ')';
                        }
                    }

                }
                catch (Exception $e)
                {
                    return $records;
                }
            }
            else
            {
                // Take all records, that do have a sorting position

                $maxlevel  = $depth + 1;
                $sqlSubset = ' AND NOT position IS NULL';
            }
            if ($depth != null AND $depth > 0)
            {
                $sqlSubset .= ' AND position_level <' . $maxlevel;
            }
        }

        if ($filter)
        {
            $sqlFilter = $filter->getMySQLExpression();
            if ($sqlFilter)
            {
                $sqlSubset .= ' AND ' . $sqlFilter['sql'];
            }

        }

        $timestamp = $this->repository->getTimeshiftTimestamp($timeshift);

        $sql = 'SELECT * FROM ' . $tableName . ' WHERE workspace = ? AND language = ? AND deleted = 0 AND validfrom_timestamp <= ? AND validuntil_timestamp > ? ' . $sqlSubset . ' ORDER BY ' . $orderBy;

        if ($limit != null AND $subset == null)
        {
            $sql .= ' LIMIT ' . (((int)$page - 1) * (int)$limit) . ',' . (int)$limit;
        }

        $stmt     = $dbh->prepare($sql);
        $params   = array();
        $params[] = $workspace;
        $params[] = $language;
        $params[] = $timestamp;
        $params[] = $timestamp;

        if ($filter AND $sqlFilter)
        {
            $params = array_merge($params, $sqlFilter['params']);
        }

        try
        {
            $stmt->execute($params);

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $row)
            {
                $records[$row['id']] = $this->getRecordDataStructureFromRow($row, $repositoryName, $contentTypeName, $viewName);
            }

            if ($subset AND $depth < 0) // climb upwards
            {

                $records = array_slice($records, $depth);

            }

            $info                 = array();
            $info['repository']   = $repositoryName;
            $info['content_type'] = $contentTypeName;
            $info['workspace']    = $workspace;
            $info['view']         = $viewName;
            $info['language']     = $language;

            $count              = $this->countRecords($workspace, $filter, $language, $timeshift);
            $info['count']      = $count['count'];
            $info['lastchange'] = $count['lastchange'];

            return array( 'info' => $info, 'records' => $records );

        }
        catch (\PDOException $e)
        {
            if ($e->getCode() == 42)
            {
                throw new RepositoryException('Could not execute your orderBy command.', RepositoryException::REPOSITORY_BAD_PARAMS);
            }
        }

    }


    /**
     * @param $record
     *
     * @return int
     * @throws Exception
     */
    public function saveRecord($record, $viewName = 'default', $workspace = 'default', $language = 'default')
    {
        $repositoryName  = $this->repository->getName();
        $contentTypeName = $this->contentTypeDefinition->getName();

        $tableName = $repositoryName . '$' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repositoryName) . '$' . Util::generateValidIdentifier($contentTypeName))
        {
            throw new RepositoryException ('Invalid repository and/or content type name(s).', RepositoryException::INVALID_NAMES);
        }

        $possibleProperties = $this->contentTypeDefinition->getProperties($viewName);

        $notallowed = array_diff(array_keys($record['properties']), $possibleProperties);

        if (count($notallowed) != 0)
        {
            throw new RepositoryException('Trying to store undefined properties: ' . join(',', $notallowed) . '.', RepositoryException::REPOSITORY_INVALID_PROPERTIES);
        }

        $mandatoryProperties = $this->contentTypeDefinition->getMandatoryProperties($viewName);

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
            $stmt->execute($params);
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

        $stmt->execute(array_values($values));

        if ($this->contentTypeDefinition->hasSynchronizedProperties() AND $this->currentlySynchronizingProperties == false)
        {
            $this->synchronizeProperties($record, $viewName, $workspace, $language);
        }

        return $record['id'];

    }


    protected function synchronizeProperties($sourceRecord, $viewName, $sourceWorkspace, $sourceLanguage)
    {
        $this->currentlySynchronizingProperties = true;
        $synchronizedProperties                 = $this->contentTypeDefinition->getSynchronizedProperties();

        $repositoryName  = $this->repository->getName();
        $contentTypeName = $this->contentTypeDefinition->getName();

        $tableName = $repositoryName . '$' . $contentTypeName;

        $sql = 'SELECT * FROM ' . $tableName . ' WHERE id = ? AND deleted = 0 AND validfrom_timestamp <= ? AND validuntil_timestamp > ? ';

        $dbh = $this->repository->getDatabaseConnection();

        // invalidate current revision

        $timestamp = $this->repository->getTimeshiftTimestamp();

        $stmt     = $dbh->prepare($sql);
        $params   = array();
        $params[] = $sourceRecord['id'];
        $params[] = $timestamp;
        $params[] = $timestamp;
        $stmt->execute($params);

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $row)
        {
            $save            = false;
            $targetRecord    = $this->getRecordDataStructureFromRow($row, $repositoryName, $contentTypeName, $viewName);
            $targetWorkspace = $row['workspace'];
            $targetLanguage  = $row['language'];

            foreach ($synchronizedProperties[ContentTypeDefinition::SCOPE_SYNCHRONIZED_PROPERTY_GLOBAL] as $property)
            {
                if ($targetRecord['properties'][$property] != $sourceRecord['properties'][$property])
                {
                    $save                                  = true;
                    $targetRecord['properties'][$property] = $sourceRecord['properties'][$property];
                }

            }

            if ($sourceLanguage == $targetLanguage)
            {
                foreach ($synchronizedProperties[ContentTypeDefinition::SCOPE_SYNCHRONIZED_PROPERTY_WORKSPACES] as $property)
                {
                    if ($targetRecord['properties'][$property] != $sourceRecord['properties'][$property])
                    {
                        $save                                  = true;
                        $targetRecord['properties'][$property] = $sourceRecord['properties'][$property];
                    }

                }
            }

            if ($sourceWorkspace == $targetWorkspace)
            {
                foreach ($synchronizedProperties[ContentTypeDefinition::SCOPE_SYNCHRONIZED_PROPERTY_LANGUAGES] as $property)
                {
                    if ($targetRecord['properties'][$property] != $sourceRecord['properties'][$property])
                    {
                        $save                                  = true;
                        $targetRecord['properties'][$property] = $sourceRecord['properties'][$property];
                    }

                }
            }

            if ($save == true)
            {
                $this->saveRecord($targetRecord, $viewName, $targetWorkspace, $targetLanguage);

            }
        }

        $this->currentlySynchronizingProperties = false;
    }


    public function deleteRecord($id, $workspace = 'default', $language = 'default')
    {
        $repositoryName  = $this->repository->getName();
        $contentTypeName = $this->contentTypeDefinition->getName();

        $tableName = $repositoryName . '$' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repositoryName) . '$' . Util::generateValidIdentifier($contentTypeName))
        {
            throw new Exception ('Invalid repository and/or content type name(s).', self::INVALID_NAMES);
        }

        // get row of current revision

        try
        {
            $row = $this->getRecordTableRow($id, $workspace, $language);
        }
        catch (RepositoryException $e)
        {
            return false;
        }

        $dbh = $this->repository->getDatabaseConnection();

        // invalidate current revision

        $timeshiftTimestamp = $this->repository->getTimeshiftTimestamp();

        $sql      = 'UPDATE ' . $tableName . ' SET validuntil_timestamp = ? WHERE id = ? AND workspace = ? AND language = ? AND deleted = 0 AND validfrom_timestamp <=? AND validuntil_timestamp >?';
        $params   = array();
        $params[] = $timeshiftTimestamp;
        $params[] = $id;
        $params[] = $workspace;
        $params[] = $language;
        $params[] = $timeshiftTimestamp;
        $params[] = $timeshiftTimestamp;
        $stmt     = $dbh->prepare($sql);
        $stmt->execute($params);

        // copy last revision row and mark record as deleted

        $row['revision']             = $row['revision'] + 1;
        $row['deleted']              = 1;
        $row['lastchange_timestamp'] = $timeshiftTimestamp;
        $row['lastchange_apiuser']   = $this->repository->getAPIUser();
        $row['lastchange_clientip']  = $this->repository->getClientIp();
        $row['lastchange_username']  = $this->repository->getCurrentUserName();
        $row['lastchange_firstname'] = $this->repository->getCurrentUserFirstname();
        $row['lastchange_lastname']  = $this->repository->getCurrentUserLastname();
        $row['validfrom_timestamp']  = $timeshiftTimestamp;
        $row['validuntil_timestamp'] = $this->repository->getMaxTimestamp();

        $sql = 'INSERT INTO ' . $tableName;
        $sql .= ' (' . join(',', array_keys($row)) . ')';
        $sql .= ' VALUES ( ?';
        $sql .= str_repeat(' , ?', count($row) - 1);
        $sql .= ')';
        $stmt = $dbh->prepare($sql);
        $stmt->execute(array_values($row));

        return true;
        /*
        // mark new revision as deleted

        $sql = 'UPDATE ' . $tableName . ' SET deleted=1 WHERE workspace = ? AND language = ?  AND id = ? AND validfrom_timestamp <= ? AND validuntil_timestamp > ?';

        $timestamp = $this->repository->getTimeshiftTimestamp();

        $stmt     = $dbh->prepare($sql);
        $params   = array();
        $params[] = $workspace;
        $params[] = $language;
        $params[] = $id;
        $params[] = $timestamp;
        $params[] = $timestamp;

        $stmt->execute($params);

        if ($stmt->rowCount() == 1)
        {

            return true;
        }

        return false;
        */
    }


    public function countRecords($workspace = 'default', $filter = null, $language = 'default', $timeshift = 0)
    {
        $repositoryName  = $this->repository->getName();
        $contentTypeName = $this->contentTypeDefinition->getName();

        $tableName = $repositoryName . '$' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repositoryName) . '$' . Util::generateValidIdentifier($contentTypeName))
        {
            throw new Exception ('Invalid repository and/or content type name(s).', self::INVALID_NAMES);
        }

        $dbh = $this->repository->getDatabaseConnection();

        $sqlSubset = '';
        if ($filter)
        {
            $sqlFilter = $filter->getMySQLExpression();
            if ($sqlFilter)
            {
                $sqlSubset = ' AND ' . $sqlFilter['sql'];
            }

        }

        $sql = 'SELECT COUNT(*) AS C FROM ' . $tableName . ' WHERE workspace = ? AND language = ? AND deleted = 0 AND validfrom_timestamp <= ? AND validuntil_timestamp > ? ' . $sqlSubset;

        $timestamp = $this->repository->getTimeshiftTimestamp($timeshift);

        $stmt     = $dbh->prepare($sql);
        $params   = array();
        $params[] = $workspace;
        $params[] = $language;
        $params[] = $timestamp;
        $params[] = $timestamp;

        if ($filter AND $sqlFilter)
        {
            $params = array_merge($params, $sqlFilter['params']);
        }

        $stmt->execute($params);

        $count = $stmt->fetchColumn();

        $sql  = 'SELECT MAX(validfrom_timestamp) AS T FROM ' . $tableName . ' WHERE workspace = ? AND language = ? AND validfrom_timestamp <= ? AND validuntil_timestamp > ? ' . $sqlSubset;
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        $timestamp = $stmt->fetchColumn();
        if (!$timestamp)
        {
            $timestamp = 0;
        }

        return array( 'count' => $count, 'lastchange' => $timestamp );
        //return array( 'count' => $count, 'lastchange' => array_shift(explode('.', $timestamp)) );

    }


    public function sortRecords($list, $workspace = 'default', $language = 'default')
    {
        $repositoryName  = $this->repository->getName();
        $contentTypeName = $this->contentTypeDefinition->getName();

        $tableName = $repositoryName . '$' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repositoryName) . '$' . Util::generateValidIdentifier($contentTypeName))
        {
            throw new Exception ('Invalid repository and/or content type name(s).', self::INVALID_NAMES);
        }

        $dbh = $this->repository->getDatabaseConnection();

        $tempTableName      = $tableName . '_' . substr(md5(uniqid(microtime(), true)), 0, 8);
        $timeshiftTimestamp = $this->repository->getTimeshiftTimestamp();

        $sql = 'CREATE TEMPORARY TABLE ' . $tempTableName . ' SELECT * FROM ' . $tableName . ' WHERE workspace = ? AND language = ? AND validfrom_timestamp <= ? AND validuntil_timestamp > ? AND deleted=0';

        $params   = array();
        $params[] = $workspace;
        $params[] = $language;
        $params[] = $timeshiftTimestamp;
        $params[] = $timeshiftTimestamp;
        $stmt     = $dbh->prepare($sql);
        $stmt->execute($params);

        $sql    = 'UPDATE ' . $tempTableName . ' SET parent_id = null, position=null, position_left = null, position_right = null, position_level = null';
        $params = array();
        $stmt   = $dbh->prepare($sql);
        $stmt->execute($params);

        $transform = new AdjacentList2NestedSet($list);

        $transform->traverse(0);
        $nestedSet = $transform->getNestedSet();

        $pos = 0;
        $ids = array();
        foreach ($nestedSet as $id => $item)
        {
            $ids[] = $id;
            $pos++;

            $sql      = 'UPDATE ' . $tempTableName . ' SET parent_id = ?, position=?, position_left = ?, position_right = ?, position_level = ? WHERE id = ?';
            $params   = array();
            $params[] = $item['parent_id'];
            $params[] = $pos;
            $params[] = $item['left'];
            $params[] = $item['right'];
            $params[] = $item['level'];
            $stmt     = $dbh->prepare($sql);
            $params[] = $id;
            $stmt->execute($params);
        }

        // end validity of all current records
        $sql                = 'UPDATE ' . $tableName . ' SET validuntil_timestamp = ? WHERE workspace = ? AND language = ? AND validfrom_timestamp <= ? AND validuntil_timestamp > ? AND deleted=0';
        $timeshiftTimestamp = $this->repository->getTimeshiftTimestamp();
        $params             = array();
        $params[]           = $timeshiftTimestamp;
        $params[]           = $workspace;
        $params[]           = $language;
        $params[]           = $timeshiftTimestamp;
        $params[]           = $timeshiftTimestamp;
        $stmt               = $dbh->prepare($sql);
        $stmt->execute($params);

        // set validity of all new records

        $sql      = 'UPDATE ' . $tempTableName . ' SET revision=revision+1, validfrom_timestamp = ?';
        $params   = array();
        $params[] = $timeshiftTimestamp;
        $stmt     = $dbh->prepare($sql);
        $stmt->execute($params);

        // Merge back

        $stmt = $dbh->prepare('INSERT INTO ' . $tableName . ' SELECT * FROM ' . $tempTableName);
        $stmt->execute();
        $stmt = $dbh->prepare('DROP TABLE ' . $tempTableName);
        $stmt->execute();

        //self::_updateContentInfo($content_type, $workspace);

        return true;
    }


    protected function getRecordDataStructureFromRow($row, $repositoryName, $contentTypeName, $viewName)
    {
        $record               = array();
        $record['id']         = $row['id'];
        $record['properties'] = array();

        $properties = $this->contentTypeDefinition->getProperties($viewName);
        foreach ($properties as $property)
        {
            $record['properties'][$property] = $row['property_' . $property];
        }
        $record['info']                       = array();
        $record['info']['revision']           = $row['revision'];
        $record['info']['revision_timestamp'] = $row['validfrom_timestamp'];
        $record['info']['hash']               = $row['hash'];

        $record['info']['creation']['timestamp'] = $row['creation_timestamp'];
        $record['info']['creation']['username']  = $row['creation_username'];
        $record['info']['creation']['firstname'] = $row['creation_firstname'];
        $record['info']['creation']['lastname']  = $row['creation_lastname'];

        $record['info']['lastchange']['timestamp'] = $row['lastchange_timestamp'];
        $record['info']['lastchange']['username']  = $row['lastchange_username'];
        $record['info']['lastchange']['firstname'] = $row['lastchange_firstname'];
        $record['info']['lastchange']['lastname']  = $row['lastchange_lastname'];

        $record['info']['position']  = $row['position'];
        $record['info']['parent_id'] = $row['parent_id'];
        $record['info']['level']     = $row['position_level'];

        return $record;
    }


    public function hasProperty($property, $viewName = null)
    {
        $possibleProperties = $this->contentTypeDefinition->getProperties($viewName);
        if (in_array($property, $possibleProperties))
        {
            return true;
        }

        return false;

    }
}