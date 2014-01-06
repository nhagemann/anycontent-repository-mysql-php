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


        if (count($result['dirs'])==0)
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

            if ($filename != '.folder')
            {
                if ($filepath == $path)
                {
                    $item             = array();
                    $item['id']       = $key;
                    $item['name']     = $filename;
                    $item['url_get']  = null;
                    $item['url_href'] = null;

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

}