<?php

namespace AnyContent\Repository\Modules\Core\Application;

use AnyContent\Repository\Modules\Core\Application\Application;

use CMDL\ContentTypeDefinition;
use CMDL\Util;

class DatabaseService
{

    protected $app;

    protected $db;

    const INVALID_NAMES = 1;


    public function __construct(Application $app)
    {
        $this->app = $app;

        // http://stackoverflow.com/questions/18683471/pdo-setting-pdomysql-attr-found-rows-fails
        $this->db = new \PDO($app['config']->getDSN(), $app['config']->getDBUser(), $app['config']->getDBPassword(), array( \PDO::MYSQL_ATTR_FOUND_ROWS => true ));

        $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->db->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array( 'AnyContent\Repository\Modules\Core\Application\Statement', array() ));

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
TEMPLATE_COUNTERTABLE;

            $stmt = $dbh->prepare($sql);
            $stmt->execute();
        }
    }


    public function refreshContentTypeTableStructure($repositoryName, ContentTypeDefinition $contentTypeDefinition)
    {
        $this->refreshInfoTablesStructure();

        $contentTypeName = $contentTypeDefinition->getName();

        $tableName = $repositoryName . '$' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repositoryName) . '$' . Util::generateValidIdentifier($contentTypeName))
        {
            throw new \Exception ('Invalid repository and/or content type name(s).', self::INVALID_NAMES);
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
          `property_name` varchar(255) DEFAULT NULL,
          `workspace` varchar(255) NOT NULL DEFAULT 'default',
          `language` varchar(255) NOT NULL DEFAULT 'default',
          `property_subtype` varchar(255) DEFAULT NULL,
          `property_status` varchar(255) DEFAULT '1',
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
          `creation_username` varchar(255) DEFAULT NULL,
          `creation_firstname` varchar(255) DEFAULT NULL,
          `creation_lastname` varchar(255) DEFAULT NULL,
          `lastchange_timestamp` int(11) DEFAULT NULL,
          `lastchange_apiuser` varchar(255) DEFAULT NULL,
          `lastchange_clientip` varchar(255) DEFAULT NULL,
          `lastchange_username` varchar(255) DEFAULT NULL,
          `lastchange_firstname` varchar(255) DEFAULT NULL,
          `lastchange_lastname` varchar(255) DEFAULT NULL,
          `validfrom_timestamp` varchar(16) DEFAULT NULL,
          `validuntil_timestamp` varchar(16) DEFAULT NULL,
          KEY `id` (`id`),
          KEY `workspace` (`workspace`,`language`),
          KEY `validfrom_timestamp` (`validfrom_timestamp`,`validuntil_timestamp`,`id`,`deleted`)

         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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

        foreach ($contentTypeDefinition->getProperties() as $property)
        {
            $properties[] = 'property_' . $property;
        }

        $newfields = array();
        foreach (array_diff($properties, $fields) as $field)
        {
            $newfields[] = 'ADD COLUMN `' . $field . '` LONGTEXT';
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


    public function refreshConfigTypesTableStructure($repositoryName)
    {
        $this->refreshInfoTablesStructure();

        $tableName = $repositoryName . '$$config';

        if ($tableName != Util::generateValidIdentifier($repositoryName) . '$$config')
        {
            throw new \Exception ('Invalid repository.', self::INVALID_NAMES);
        }

        /** @var PDO $db */
        $dbh = $this->app['db']->getConnection();

        $sql = 'Show Tables Like ?';

        $stmt = $dbh->prepare($sql);
        $stmt->execute(array( $tableName ));

        if ($stmt->rowCount() == 0)
        {

            $sql = <<< TEMPLATE_CONFIGTABLE

        CREATE TABLE %s (
          `id` varchar(255) NOT NULL,
          `hash` varchar(32) NOT NULL,
          `workspace` varchar(255) NOT NULL DEFAULT 'default',
          `language` varchar(255) NOT NULL DEFAULT 'default',
          `revision` int(11) DEFAULT NULL,
          `properties` LONGTEXT,
          `lastchange_timestamp` int(11) DEFAULT NULL,
          `lastchange_apiuser` varchar(255) DEFAULT NULL,
          `lastchange_clientip` varchar(255) DEFAULT NULL,
          `lastchange_username` varchar(255) DEFAULT NULL,
          `lastchange_firstname` varchar(255) DEFAULT NULL,
          `lastchange_lastname` varchar(255) DEFAULT NULL,
          `validfrom_timestamp` varchar(16) DEFAULT NULL,
          `validuntil_timestamp` varchar(16) DEFAULT NULL,
          KEY `id` (`id`),
          KEY `workspace` (`workspace`,`language`),
          KEY `validfrom_timestamp` (`validfrom_timestamp`,`validuntil_timestamp`,`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

TEMPLATE_CONFIGTABLE;

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

        return true;
    }


    public function truncateContentType($repositoryName, $contentTypeName)
    {
        $tableName = $repositoryName . '$' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repositoryName) . '$' . Util::generateValidIdentifier($contentTypeName))
        {
            throw new \Exception ('Invalid repository and/or content type name(s).', self::INVALID_NAMES);
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


    public function truncateConfigType($repositoryName, $configTypeName)
    {
        $tableName = $repositoryName . '$$config';

        if ($configTypeName != Util::generateValidIdentifier($configTypeName) || $repositoryName != Util::generateValidIdentifier($repositoryName))
        {
            throw new \Exception ('Invalid repository and/or config type name(s).', self::INVALID_NAMES);
        }

        $dbh = $this->getConnection();

        $sql      = 'DELETE FROM ' . $tableName . ' WHERE id = ?';
        $stmt     = $dbh->prepare($sql);
        $params   = array();
        $params[] = $configTypeName;

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


    public function execute($params = array(), $debug = false)
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
                echo $e->getMessage() . "\n" . $this->sdebug();
                syslog(LOG_ERR, $e->getMessage() . ' - ' . $this->sdebug());
                die();
            }

            throw $e;
        }

        return $t;
    }


    public function dexecute($params = array())
    {
        return $this->execute($params, true);
    }


    public function dfetch($fetch_style = null, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        try
        {
            return parent::fetch($fetch_style, $cursor_orientation, $cursor_offset);
        }
        catch (\PDOException $e)
        {
            echo $e->getMessage() . "\n" . $this->sdebug();
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

        echo preg_replace_callback('/\?/', array( $this, '_replace' ), $q);
    }


    public function sdebug()
    {
        $q = $this->queryString;

        return preg_replace_callback('/\?/', array( $this, '_replace' ), $q);
    }


    protected function _replace($m)
    {
        return $this->_token . array_shift($this->_params) . $this->_token;
    }
}