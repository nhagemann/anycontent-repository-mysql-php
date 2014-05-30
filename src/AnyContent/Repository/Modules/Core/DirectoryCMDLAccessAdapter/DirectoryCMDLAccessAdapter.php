<?php

namespace AnyContent\Repository\Modules\Core\DirectoryCMDLAccessAdapter;

use AnyContent\Repository\Modules\Core\Application\Application;

use AnyContent\Repository\Modules\Core\Repositories\ConfigTypeInfo;
use AnyContent\Repository\Modules\Core\Repositories\ContentTypeInfo;

use CMDL\Parser;
use CMDL\ParserException;

class DirectoryCMDLAccessAdapter
{
    protected $app;

    protected $repositories = null;

    protected $contentTypeDefinitions = array();

    protected $configTypeDefinitions = array();

    protected $cmdl = array();


    public function __construct($app,$config,$options)
    {
        $this->app = $app;
    }

    protected function getCMDLDirectory()
    {
        return APPLICATION_PATH . '/cmdl';
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

        if (!$this->repositories)
        {

            $path = $this->getCMDLDirectory();

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
            $path = $this->getCMDLDirectory() . '/' . $repositoryName;
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

                            $contentTypeDefinition = $this->getContentTypeDefinition($repositoryName, $contentTypeName);

                            if ($contentTypeDefinition)
                            {
                                $info = new ContentTypeInfo();
                                $info->setName($contentTypeName);
                                $info->setLastchangecmdl(@$filestats['mtime']);
                                $info->setTitle((string)$contentTypeDefinition->getTitle());
                                $info->setDescription((string)$contentTypeDefinition->getDescription());
                                $contentTypes[$contentTypeName] = $info;
                            }
                        }
                    }
                }
            }

        }

        return $contentTypes;
    }


    public function getConfigTypesList($repositoryName)
    {
        $configTypes = array();

        if ($this->hasRepository($repositoryName))
        {

            $path = $this->getCMDLDirectory() . '/' . $repositoryName . '/config';
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
                            $filestats      = stat($path . '/' . $result);
                            $configTypeName = pathinfo($result, PATHINFO_FILENAME);

                            //$contentTypeDefinition = $this->getContentTypeDefinition($repositoryName, $contentTypeName);

                            $info = new ConfigTypeInfo();
                            $info->setName($configTypeName);
                            $info->setLastchangecmdl(@$filestats['mtime']);
                            //$info->setTitle((string)$contentTypeDefinition->getTitle());
                            //$info->setDescription((string)$contentTypeDefinition->getDescription());
                            $configTypes[$configTypeName] = $info;

                            /*
                            if ($contentTypeDefinition)
                            {

                            } */
                        }
                    }
                }
            }
        }

        return $configTypes;
    }



    public function getContentTypeCMDL($repositoryName, $contentTypeName)
    {
        $token = $repositoryName . '$' . $contentTypeName;

        if ($this->hasRepository($repositoryName))
        {

            if (array_key_exists($token, $this->cmdl))
            {
                return $this->cmdl[$token]['cmdl'];
            }
            $filename = $this->getCMDLDirectory() . '/' . $repositoryName . '/' . $contentTypeName . '.cmdl';
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
        if ($this->hasRepository($repositoryName))
        {
            $token = 'config$' . $repositoryName . '$' . $configTypeName;
            if (array_key_exists($token, $this->cmdl))
            {
                return $this->cmdl[$token]['cmdl'];
            }
            $filename = $this->getCMDLDirectory() . '/' . $repositoryName . '/config/' . $configTypeName . '.cmdl';
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

}