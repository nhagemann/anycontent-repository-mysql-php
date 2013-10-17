<?php

namespace AnyContent\Repository\Service;

use Silex\Application;

use CMDL\ContentTypeDefinition;
use CMDL\Util;

class Database
{

    protected $app;

    protected $db;

    const INVALID_NAMES = 1;


    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->db = new \PDO($app['config']->getDSN(), $app['config']->getDBUser(), $app['config']->getDBPassword());

        $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->db->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array( 'AnyContent\Repository\Service\Statement', array() ));

        $this->db->exec("SET NAMES utf8");
    }


    public function getConnection()
    {
        return $this->db;
    }


    public function refreshInfoTablesStructure()
    {

        /** @var PDO $db */
        $dbh = $this->app['db']->getConnection();

        $sql = "Show Tables Like '_info_'";

        $stmt = $dbh->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() == 0)
        {
            $sql = <<< TEMPLATE_INFOTABLE
CREATE TABLE `_info_` (
  `repository` varchar(128) NOT NULL DEFAULT '',
  `content_type` varchar(128) NOT NULL DEFAULT '',
  `workspace` varchar(64) NOT NULL DEFAULT '',
  `records_count` bigint(20) DEFAULT 0,
  `last_cmdl_change_timestamp` int(11) DEFAULT 0,
  `last_content_change_timestamp` int(11) DEFAULT 0,
  `last_position_change_timestamp` int(11) DEFAULT 0,
  `last_cmdl_change_timestamp` int(11) DEFAULT 0,
  PRIMARY KEY (`repository`,`content_type`,`workspace`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
TEMPLATE_INFOTABLE;

            $stmt = $dbh->prepare($sql);
            $stmt->execute();
        }

        $sql = "Show Tables Like '_counter_'";

        $stmt = $dbh->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() == 0)
        {
            $sql = <<< TEMPLATE_COUNTERTABLE
CREATE TABLE `_counter_` (
  `repository` varchar(128) NOT NULL DEFAULT '',
  `content_type` varchar(128) NOT NULL DEFAULT '',
  `counter` bigint(20) DEFAULT 0,
  PRIMARY KEY (`repository`,`content_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
TEMPLATE_COUNTERTABLE;

            $stmt = $dbh->prepare($sql);
            $stmt->execute();
        }
    }


    public function refreshContentTypeTableStructure($repositoryName, ContentTypeDefinition $contentType)
    {
        $this->refreshInfoTablesStructure();

        $contentTypeName = 'example01';

        $tableName = $repositoryName . '_' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repositoryName . '_' . $contentTypeName))
        {
            throw new Exception ('Invalid repository and/or content type name(s).', self::INVALID_NAMES);
        }

        /** @var PDO $db */
        $dbh = $this->app['db']->getConnection();

        $sql = 'Show Tables Like ?';

        $stmt = $dbh->prepare($sql);
        $stmt->execute(array( $tableName ));

        if ($stmt->rowCount() == 0)
        {

            $sql = <<< TEMPLATE_CONTENTTABLE

        CREATE TABLE %s (
          `id` int(11) unsigned NOT NULL,
          `hash` varchar(32) NOT NULL,
          `name` varchar(255) DEFAULT NULL,
          `workspace` varchar(255) NOT NULL DEFAULT 'default',
          `language` varchar(255) NOT NULL DEFAULT 'none',
          `subtype` varchar(255) DEFAULT NULL,
          `status` varchar(255) DEFAULT '1',
          `parent_id` int(11) DEFAULT NULL,
          `position` int(11) DEFAULT NULL,
          `position_left` int(11) DEFAULT NULL,
          `position_right` int(11) DEFAULT NULL,
          `position_level` int(11) DEFAULT NULL,
          `revision` int(11) DEFAULT NULL,
          `deleted` tinyint(1) DEFAULT '0',
          `creation_timestamp` int(11) DEFAULT NULL,
          `creation_apiuser` varchar(255) DEFAULT NULL,
          `creation_clientip` varchar(255) DEFAULT NULL,
          `creation_user` varchar(255) DEFAULT NULL,
          `creation_firstname` varchar(255) DEFAULT NULL,
          `creation_lastname` varchar(255) DEFAULT NULL,
          `lastchange_timestamp` int(11) DEFAULT NULL,
          `lastchange_apiuser` varchar(255) DEFAULT NULL,
          `lastchange_clientip` varchar(255) DEFAULT NULL,
          `lastchange_user` varchar(255) DEFAULT NULL,
          `lastchange_firstname` varchar(255) DEFAULT NULL,
          `lastchange_lastname` varchar(255) DEFAULT NULL,
          `validfrom_timestamp` varchar(16) DEFAULT NULL,
          `validuntil_timestamp` varchar(16) DEFAULT NULL

         ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

TEMPLATE_CONTENTTABLE;

            $sql  = sprintf($sql, $tableName);
            $stmt = $dbh->prepare($sql);

            try
            {

                $stmt->execute();

            }
            catch (\PDOException $e)
            {

                return false;
            }

        }

        $sql = sprintf('DESCRIBE %s', $tableName);

        $stmt = $dbh->prepare($sql);
        $stmt->execute();

        $fields = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

        $properties = array();

        foreach ($contentType->getProperties() as $property)
        {
            $properties[] = 'property_' . $property;
        }

        $newfields = array();
        foreach (array_diff($properties, $fields) as $field)
        {
            $newfields[] = 'ADD COLUMN `' . $field . '` TEXT';
        }

        if (count($newfields) != 0)
        {
            $sql = sprintf('ALTER TABLE %s', $tableName);
            $sql .= ' ' . join($newfields, ',');
            $stmt = $dbh->prepare($sql);
            $stmt->execute();
        }



        return true;
    }


    public function deleteRepository($repositoryName,$contentTypeName)
    {
        $tableName = $repositoryName . '_' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repositoryName . '_' . $contentTypeName))
        {
            throw new Exception ('Invalid repository and/or content type name(s).', self::INVALID_NAMES);
        }

        $sql = 'DROP TABLE IF EXISTS ' . $tableName;
        $dbh = $this->getConnection();

        $stmt = $dbh->prepare($sql);

        try
        {
            $stmt->dexecute();

        }
        catch (\PDOException $e)
        {
            return false;
        }

        $sql      = 'DELETE FROM _info_ WHERE repository = ? AND content_type = ?';
        $stmt     = $dbh->prepare($sql);
        $params   = array();
        $params[] = $repositoryName;
        $params[] = $contentTypeName;

        try
        {
            $stmt->execute($params);

        }
        catch (\PDOException $e)
        {
            return false;
        }

        $sql      = 'DELETE FROM _counter_ WHERE repository = ? AND content_type = ?';
        $stmt     = $dbh->prepare($sql);
        $params   = array();
        $params[] = $repositoryName;
        $params[] = $contentTypeName;

        try
        {
            $stmt->execute($params);

        }
        catch (\PDOException $e)
        {
            return false;
        }

        return true;
    }
}


class Statement extends \PDOStatement
{

    protected function __construct()
    {
        // need this empty construct()!
    }


    protected $_params = array();
    protected $_token = "'";


    public function execute($params = array(),$debug=false)
    {
        $this->_params = $params;
        try
        {
            $t = parent::execute($params);
        }
        catch (\PDOException $e)
        {
            if ($debug)
            {
                echo $e->getMessage() ."\n".$this->debug();
                die();
            }
            throw $e;
        }

        return $t;
    }

    public function dexecute($params=array())
    {
        return $this->execute($params,true);
    }

    public function dfetch($fetch_style = null, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        try
        {
           return parent::fetch($fetch_style,$cursor_orientation,$cursor_offset);
        }
        catch (\PDOException $e)
        {
            echo $e->getMessage() ."\n".$this->debug();
            die();
        }
    }
        /*
    public function bindValue($parameter, $value, $data_type = \PDO::PARAM_STR)
    {

        if ($value == null)
        {
            $data_type = \PDO::PARAM_INT;
        }
        $this->_params[] = $value;

        return parent::bindValue($parameter, $value, $data_type);

    } */


    public function debug()
    {
        $q = $this->queryString;

        return preg_replace_callback('/\?/', array( $this, '_replace' ), $q);
    }


    protected function _replace($m)
    {
        return $this->_token . array_shift($this->_params) . $this->_token;
    }
}