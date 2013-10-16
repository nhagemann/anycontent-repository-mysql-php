<?php

namespace AnyContent\Repository;

use Silex\Application;

use CMDL\ContentTypeDefinition;
use CMDL\Util;

use AnyContent\Repository\Util as RepositoryUtil;
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
     * @return int
     * @throws Exception
     */
    public function getRecord($id, $clippingName = 'default', $workspace = 'default', $language = 'none', $timeshift = 0)
    {
        $repositoryName  = $this->repository->getName();
        $contentTypeName = $this->contentTypeDefinition->getName();

        throw new RepositoryException('Record not found.', RepositoryException::REPOSITORY_RECORD_NOT_FOUND);

    }


    protected function getRecordTableRow($id, $clippingName = 'default', $workspace = 'default', $language = 'none', $timeshift = 0)
    {

        $repositoryName  = $this->repository->getName();
        $contentTypeName = $this->contentTypeDefinition->getName();

        $tableName = $repositoryName . '_' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repositoryName . '_' . $contentTypeName))
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

        $tableName = $repositoryName . '_' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repositoryName . '_' . $contentTypeName))
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

        if ($record['id'] != 0)
        {
            try
            {
                $row  = self::getRecordTableRow($record['id'], $clippingName, $workspace, $language);
                $mode = 'update';

                // transfer all properties, which are not set int the record to be saved
                foreach ($row as $key => $value)
                {

                    if (RepositoryUtil::startsWith($key, 'property_'))
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

        $values              = array();
        $values['id']        = $record['id'];
        $values['hash']      = md5(serialize($record['properties']));
        $values['name']      = @$record['properties']['name'];
        $values['workspace'] = $workspace;
        $values['language']  = $language;
        $values['subtype']   = @$record['properties']['subtype'];
        $values['status']    = @$record['properties']['status'];
        $values['revision']  = $record['revision'];
        $values['deleted']   = 0;

        if ($mode == 'insert')
        {
            $values['creation_timestamp'] = $timestamp;
            $values['creation_apiuser']   = $this->repository->getAPIUser();
            $values['creation_clientip']  = $this->repository->getClientIp();
            $values['creation_user']      = $this->repository->getCurrentUserName();
            $values['creation_firstname'] = $this->repository->getCurrentUserFirstname();
            $values['creation_lastname']  = $this->repository->getCurrentUserLastname();
        }
        else
        {
            $values['creation_timestamp'] = $row['creation_timestamp'];
            $values['creation_apiuser']   = $row['creation_apiuser'];
            $values['creation_clientip']  = $row['creation_clientip'];
            $values['creation_user']      = $row['creation_user'];
            $values['creation_firstname'] = $row['creation_firstname'];
            $values['creation_lastname']  = $row['creation_lastname'];
        }

        $values['lastchange_timestamp'] = $timestamp;
        $values['lastchange_apiuser']   = $this->repository->getAPIUser();
        $values['lastchange_clientip']  = $this->repository->getClientIp();
        $values['lastchange_user']      = $this->repository->getCurrentUserName();
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

        if ($mode == 'update')
        {

            $stmt = $dbh->prepare('UPDATE content SET validuntil_timestamp = ? WHERE record_id = ? AND content_type = ? AND workspace = ? AND repository = ? AND record_deleted = 0 AND validfrom_timestamp <=? AND validuntil_timestamp >?');

            $stmt->bindValue(1, $timeshift_timestamp);
            $stmt->bindValue(2, $record->id);
            $stmt->bindValue(3, $record->content_type);
            $stmt->bindValue(4, $record->workspace);
            $stmt->bindValue(5, self::$current_repository);
            $stmt->bindValue(6, $timeshift_timestamp);
            $stmt->bindValue(7, $timeshift_timestamp);
            $stmt->execute();

            //$record->info['revision']=$record->info['revision']+1;
            //error_log($stmt->errorCode());
            //echo $stmt->debug();

            $sql = 'INSERT INTO content';
            $sql .= ' (record_id, content_type, workspace, record_name, record_subtype, record_properties';
            $sql .= ' ,record_revision, repository,  creation_timestamp,creation_user,lastchange_timestamp,lastchange_user';

            $sql .= ' ,position_left, position_right, position_level,record_position';
            $sql .= ' ,record_status,validfrom_timestamp, validuntil_timestamp)';
            $sql .= ' VALUES';
            $sql .= ' (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ';

            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(1, $record->id);
            $stmt->bindValue(2, $record->content_type);
            $stmt->bindValue(3, $record->workspace);
            $stmt->bindValue(4, $record->getProperty('name'));
            $stmt->bindValue(5, $record->getProperty('subtype'));
            $properties = $record->properties;
            $stmt->bindValue(6, json_encode($properties));
            $stmt->bindValue(7, $record->info['revision']);
            $stmt->bindValue(8, self::$current_repository);

            //$stmt->bindValue(9, $record->info['creation_timestamp']);
            //$stmt->bindValue(10, $record->info['creation_user']);

            $stmt->bindValue(9, self::$current_row['creation_timestamp']);
            $stmt->bindValue(10, self::$current_row['creation_user']);
            $stmt->bindValue(11, $timestamp);
            $stmt->bindValue(12, $username);
            $stmt->bindValue(13, self::$current_row['position_left']);
            $stmt->bindValue(14, self::$current_row['position_right']);
            $stmt->bindValue(15, self::$current_row['position_level']);
            $stmt->bindValue(16, self::$current_row['record_position']);

            $stmt->bindValue(17, $record->getProperty('status'));
            $stmt->bindValue(18, $timeshift_timestamp);
            $stmt->bindValue(19, Service_Helper::getMaxTimestamp());

            $stmt->execute();
        }

        // @todo: next lines cannot be processed. Should maybe reused for a non archivable content type
        /* if ($mode == 'update')
          {
          $stmt = $dbh->prepare('UPDATE content SET record_name =?, record_subtype=?, record_properties =?, record_revision=?, lastchange_timestamp=?, lastchange_user=?, record_status=? WHERE record_id = ? AND content_type = ? AND workspace = ? AND repository = ?');


          $stmt->bindValue(1, $record->getProperty('name'));
          $stmt->bindValue(2, $record->getProperty('subtype'));
          $properties = $record->properties;
          $stmt->bindValue(3, json_encode($properties));
          $stmt->bindValue(4, $record->info['revision']);

          $stmt->bindValue(5, $timestamp);
          $stmt->bindValue(6, $username);
          $stmt->bindValue(7, $record->getProperty('status'));

          $stmt->bindValue(8, $record->id);
          $stmt->bindValue(9, $record->content_type);
          $stmt->bindValue(10, $record->workspace);
          $stmt->bindValue(11, self::$current_repository);

          $stmt->execute();

          //error_log($stmt->errorCode());
          } */

        if (self::$current_repository == 'admin' AND $record->content_type == 'content_type')
        {
            Service_Admin::saveCMDL($record->getProperty('subtype'), $record->getProperty('name'), $record->getProperty('cmdl'));
        }

        self::_updateContentInfo($record->content_type, $record->workspace);

        // Store Index Values

        /*
          $indexes = CMDLManager::getIndexDefinition($record->content_type);
          $stmt = $dbh->prepare('DELETE FROM content_index WHERE repository = ? AND content_type = ? AND record_id = ?');
          $stmt->bindValue(1, self::$current_repository);
          $stmt->bindValue(2, $record->content_type);
          $stmt->bindValue(3, $record->id);
          $stmt->execute();
          //error_log(print_r($index,true));

          foreach ($indexes as $index)
          {
          error_log($property);
          $stmt = $dbh->prepare("INSERT INTO content_index (repository, content_type, record_id, index_name, index_value) values (?,?,?, ?, ?)");
          $stmt->bindValue(1, self::$current_repository);
          $stmt->bindValue(2, $record->content_type);
          $stmt->bindValue(3, $record->id);
          $stmt->bindValue(4, $index['name']);
          $stmt->bindValue(5, $record->getProperty($index['property']));
          $stmt->execute();
          }
         */

        return (int)$record->id;
    }
}