<?php

namespace AnyContent\Repository\Modules\Core\DirectoryCMDLAccessAdapter;

use AnyContent\Repository\Modules\Core\Application\Application;

use AnyContent\Repository\Modules\Core\Repositories\ConfigTypeInfo;
use AnyContent\Repository\Modules\Core\Repositories\ContentTypeInfo;

use CMDL\CMDLParserException;
use CMDL\Parser;
use CMDL\Util;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use AnyContent\Repository\Modules\Core\Repositories\RepositoryException;

class DirectoryCMDLAccessAdapter
{

    protected $app;

    protected $repositories = null;

    protected $contentTypeDefinitions = array();

    protected $configTypeDefinitions = array();

    protected $cmdl = array();


    public function __construct($app, $config, $options)
    {
        $this->app = $app;
    }


    protected function getCMDLDirectory()
    {
        return APPLICATION_PATH . '/cmdl';
    }


    public function hasRepository($repositoryName)
    {
        if (!$this->repositories)
        {
            $this->getRepositories();
        }

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
                    if ($result === '.' || $result === '..')
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
                    if ($result === '.' || $result === '..')
                    {
                        continue;
                    }

                    if (!is_dir($path . '/' . $result))
                    {
                        if (pathinfo($result, PATHINFO_EXTENSION) == 'cmdl')
                        {
                            $filestats       = stat($path . '/' . $result);
                            $contentTypeName = pathinfo($result, PATHINFO_FILENAME);

                            try
                            {
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
                            catch (\CMDLParserException $e)
                            {
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
                    if ($result === '.' || $result === '..')
                    {
                        continue;
                    }

                    if (!is_dir($path . '/' . $result))
                    {
                        if (pathinfo($result, PATHINFO_EXTENSION) == 'cmdl')
                        {
                            $filestats      = stat($path . '/' . $result);
                            $configTypeName = pathinfo($result, PATHINFO_FILENAME);

                            $configTypeDefinition = $this->getConfigTypeDefinition($repositoryName, $configTypeName);
                            if ($configTypeDefinition)
                            {
                                $info = new ConfigTypeInfo();
                                $info->setName($configTypeName);
                                $info->setLastchangecmdl(@$filestats['mtime']);
                                $info->setTitle((string)$configTypeDefinition->getTitle());
                                $info->setDescription((string)$configTypeDefinition->getDescription());
                                $configTypes[$configTypeName] = $info;
                            }
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
            catch (CMDLParserException $e)
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
            catch (CMDLParserException $e)
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
        catch (CMDLParserException $e)
        {
            throw new RepositoryException ('Could not parse definition for content type ' . $contentTypeName);
        }

        if ($this->hasRepository($repositoryName) || $createRepository == true)
        {
            $filename = $this->getCMDLDirectory();
            if (file_exists($filename))
            {
                $filename .= '/' . $repositoryName;

                if (!file_exists($filename))
                {
                    mkdir($filename);
                }

                $filename .= '/' . $contentTypeName . '.cmdl';

                sleep(2); // We must make sure, that the timestamp of the cmdl file changes!
                if (@file_put_contents($filename, $cmdl))
                {

                    $this->app['db']->refreshContentTypeTableStructure($repositoryName, $contentTypeDefinition);

                    $this->contentTypeDefinitions = array();

                    return true;
                }
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
            $filename = $this->getCMDLDirectory() . '/' . $repositoryName . '/' . $contentTypeName . '.cmdl';
            if (file_exists($filename))
            {
                @unlink($filename);

                $this->app['db']->truncateContentType($repositoryName, $contentTypeName);
                $this->contentTypeDefinitions = array();

                return true;
            }
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
        catch (CMDLParserException $e)
        {
            throw new RepositoryException ('Could not parse definition for config type ' . $configTypeName);
        }

        if ($this->hasRepository($repositoryName) || $createRepository == true)
        {
            $filename = $this->getCMDLDirectory();
            if (file_exists($filename))
            {
                $filename .= '/' . $repositoryName;

                if (!file_exists($filename))
                {
                    mkdir($filename);
                }

                $filename .= '/config';

                if (!file_exists($filename))
                {
                    mkdir($filename);
                }

                $filename .= '/' . $configTypeName . '.cmdl';

                if (@file_put_contents($filename, $cmdl))
                {

                    $this->app['db']->refreshConfigTypesTableStructure($repositoryName, $configTypeDefinition);
                    $this->configTypeDefinitions = array();

                    return true;
                }
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
            $filename = $this->getCMDLDirectory() . '/' . $repositoryName . '/config/' . $configTypeName . '.cmdl';

            if (file_exists($filename))
            {
                @unlink($filename);

                $this->app['db']->truncateConfigType($repositoryName, $configTypeName);
                $this->configTypeDefinitions = array();

                return true;
            }
        }

        return false;
    }


    public function createRepository($repositoryName)
    {
        if ($repositoryName != Util::generateValidIdentifier($repositoryName))
        {
            throw new RepositoryException ('Invalid repository name.');
        }

        $filename = $this->getCMDLDirectory();

        @mkdir($filename . '/' . $repositoryName);

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
            $filename = $this->getCMDLDirectory();

            @rmdir($filename . '/' . $repositoryName . '/config');
            @rmdir($filename . '/' . $repositoryName);

            $this->repositories = null;

            return true;
        }

        return false;
    }


    public function getCMDLConfigHash($repositoryName = null)
    {
        if ($this->hasRepository($repositoryName))
        {

            $finder    = new Finder();
            $directory = $this->getCMDLDirectory();
            if ($repositoryName != null)
            {
                $directory .= '/' . $repositoryName;
            }
            $finder->files()->in($directory);

            $hash = '';

            /* @var SplFileInfo $file */
            foreach ($finder as $file)
            {
                $hash .= $file->getFilename() . '.' . $file->getMTime() . '-';
            }

            return md5($hash);
        }

        return false;
    }
}