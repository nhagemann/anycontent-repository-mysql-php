<?php

namespace AnyContent\Repository;

use Silex\Application;

use CMDL\ContentTypeDefinition;
use CMDL\Util;

use AnyContent\Repository\Repository;

use AnyContent\Repository\Helper;
use AnyContent\Repository\RepositoryException;

use AnyContent\Repository\Util\AdjacentList2NestedSet;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Adapter\Ftp as FTPAdapter;
use Gaufrette\Adapter\Dropbox as DropboxAdapter;
use Gaufrette\Adapter\Cache as CacheAdapter;

class FilesManager
{

    /**
     * @var Repository
     */
    protected $repository = null;

    /**
     * @var Filesystem null
     */
    protected $filesystem = null;


    public function __construct(Repository $repository, $config)
    {
        $this->repository = $repository;

        $originAdapter = $this->getAdapter($config['default']);
        $localAdapter  = $this->getAdapter($config['cache']);

        if ($localAdapter)
        {
            $cacheAdapter     = new CacheAdapter($originAdapter, $localAdapter, 3600);
            $this->filesystem = new Filesystem($cacheAdapter);

        }
        else

        {
            $this->filesystem = new Filesystem($originAdapter);
        }

    }


    protected function getAdapter($config)
    {
        $adapter = null;

        switch ($config['type'])
        {
            case 'local':

                $adapter = new LocalAdapter($config['directory'], true);

                break;
            case
                'ftp':
                $adapter = new FtpAdapter($config['directory'], $config['host'], $config['options']);
                break;

        }

        return $adapter;
    }


    public function getFolders($path)
    {
        $path = trim($path, '/');

        $result = $this->filesystem->listKeys($path);

        if (count($result['dirs']) == 0 AND $path != '')
        {
            return false;
        }

        $folders = array();
        foreach ($result['dirs'] as $key)
        {
            if ($path == '')
            {
                $p = strrpos($key, '/');
                if (!$p)
                {
                    $folders[] = $key;
                }

            }
            else
            {
                if (substr($key, 0, strlen($path) + 1) == $path . '/')
                {
                    $foldername = substr($key, strlen($path) + 1);
                    if (strpos($foldername, '/') === false)
                    {
                        $folders[] = $foldername;
                    }
                }

            }

        }

        $folders = array_values(array_unique($folders));

        return $folders;
    }


    public function getFiles($path, $info = true)
    {

        $path = trim($path, '/');

        $result = $this->filesystem->listKeys($path);

        $files = array();
        foreach ($result['keys'] as $key)
        {

            $p = strrpos($key, '/');
            if ($p)
            {
                $filename = substr($key, $p + 1);
                $filepath = substr($key, 0, $p);
            }
            else
            {
                $filename = $key;
                $filepath = '';
            }

            if ($filename[0] != '.') // exclude system files including ".folder" which gets created for empty folders
            {
                if ($filepath == $path)
                {
                    $item         = array();
                    $item['id']   = $key;
                    $item['name'] = $filename;
                    $item['urls'] = array();

                    if ($info)
                    {
                        try
                        {
                            $file = $this->filesystem->get($key);

                            $item['type'] = 'binary';
                            $item['size'] = $file->getSize();

                            $content = $file->getContent();

                            $image = @imagecreatefromstring($content);
                            if ($image)
                            {
                                $item['type']   = 'image';
                                $item['width']  = imagesx($image);
                                $item['height'] = imagesy($image);
                            }

                        }
                        catch (\Exception $e)
                        {

                        }
                    }
                    $item['timestamp_lastchange'] = $this->filesystem->getAdapter()->mtime($key);

                    $files[] = $item;
                }
            }
        }

        return $files;
    }


    public function getFile($id)
    {
        $fileName = pathinfo($id, PATHINFO_FILENAME);

        if ($fileName != '') // No access to .xxx-files
        {
            try
            {
                $file = $this->filesystem->get($id);

                return $file->getContent();

            }
            catch (\Exception $e)
            {

            }
        }

        return false;
    }


    public function deleteFile($id)
    {
        try
        {
            $file = $this->filesystem->delete($id);

            return true;

        }
        catch (\Exception $e)
        {

        }

        return false;
    }


    public function deleteFolder($path)
    {
        try
        {

            $files = $this->filesystem->listKeys($path);

            $error = false;

            foreach ($files['keys'] as $id)
            {
                if ($this->deleteFile($id) == false)
                {
                    $error = true;
                }

            }

            // remove duplicate dir entries (since every file has a dir entry in the array(
            $dirs = array_unique($files['dirs']);

            // sort to start with the most nested folder to delete
            rsort($dirs);

            foreach ($dirs as $id)
            {
                if ($this->deleteFile($id) == false)
                {
                    $error = true;
                }

            }

            return !$error;

        }
        catch (\Exception $e)
        {

        }

        return false;
    }


    public function saveFile($id, $binary)
    {
        try
        {
            $this->filesystem->write($id, $binary, true);

            $dirName    = pathinfo($id, PATHINFO_DIRNAME);
            $subFolders = explode('/', $dirName);
            for ($i = count($subFolders); $i > 0; $i--)
            {
                $subFolder = join('/', array_slice($subFolders, 0, $i));

                $hiddenFolderMarkerFile = $subFolder . '/.folder';
                if ($this->filesystem->has($hiddenFolderMarkerFile))
                {
                    break;
                }
                else
                {
                    $this->filesystem->write($hiddenFolderMarkerFile, '', true);
                }

            }

            return true;

        }
        catch (\Exception $e)
        {

        }

        return false;
    }
}