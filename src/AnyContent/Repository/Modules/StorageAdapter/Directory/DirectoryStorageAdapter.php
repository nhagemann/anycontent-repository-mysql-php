<?php

namespace AnyContent\Repository\Modules\StorageAdapter\Directory;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class DirectoryStorageAdapter
{

    /**
     * @var Filesystem null
     */
    protected $filesystem = null;

    protected $directory = null;

    protected $imagesize = true;


    public function __construct($config, $baseFolder, $options = array())
    {
        $this->filesystem = new Filesystem();

        $directory = $config['directory'];
        if ($directory[0] != '/')
        {
            $directory = APPLICATION_PATH . '/' . $directory;
        }

        if (file_exists($directory))
        {
            $directory .= '/' . $baseFolder;
            if (!file_exists($directory))
            {
                $this->filesystem->mkdir($directory);
            }
        }
        else
        {
            throw new \Exception ('Files base folder ' . $directory . ' missing.');
        }

        $this->directory = $directory;

        if (isset($config['imagesize']))
        {
            $this->imagesize = (boolean)$config['imagesize'];
        }
    }


    public function getFolders($path)
    {
        $path    = trim($path, '/');
        $folders = array();
        $finder  = new Finder();

        $finder->depth('==0');

        try
        {
            /* @var $file \SplFileInfo */
            foreach ($finder->in($this->directory . '/' . $path) as $file)
            {
                if ($file->isDir())
                {

                    $folders[] = $file->getFilename();

                }
            }

        }
        catch (\Exception $e)
        {
            return false;
        }

        return $folders;

    }


    public function getFiles($path)
    {

        $path = trim($path, '/');

        $files  = array();
        $finder = new Finder();

        $finder->depth('==0');

        try
        {
            /* @var $file \SplFileInfo */
            foreach ($finder->in($this->directory . '/' . $path) as $file)
            {
                if (!$file->isDir())
                {
                    $item                         = array();
                    $item['id']                   = trim($path . '/' . $file->getFilename(), '/');
                    $item['name']                 = $file->getFilename();
                    $item['urls']                 = array();
                    $item['type']                 = 'binary';
                    $item['size']                 = $file->getSize();
                    $item['timestamp_lastchange'] = $file->getMTime();

                    $extension = strtolower($extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION)); // To be compatible with some older PHP 5.3 versions

                    if (in_array($extension, array( 'gif', 'png', 'jpg', 'jpeg' )))
                    {
                        $item['type'] = 'image';

                        if ($this->imagesize == true)
                        {

                            $content = $file->getContents();

                            if (function_exists('imagecreatefromstring'))
                            {
                                $image = @imagecreatefromstring($content);
                                if ($image)
                                {

                                    $item['width']  = imagesx($image);
                                    $item['height'] = imagesy($image);
                                }
                            }
                        }

                    }

                    $files[$file->getFilename()] = $item;
                }

            }
        }
        catch (\Exception $e)
        {
            return false;
        }

        return $files;
    }


    public function getFile($id)
    {

        $id = trim($id, '/');

        $fileName = pathinfo($id, PATHINFO_FILENAME);

        if ($fileName != '') // No access to .xxx-files
        {

            return @file_get_contents($this->directory . '/' . $id);

        }

        return false;

    }


    public function saveFile($id, $binary)
    {
        $id       = trim($id, '/');
        $fileName = pathinfo($id, PATHINFO_FILENAME);

        if ($fileName != '') // No writing of .xxx-files
        {
            $this->filesystem->dumpFile($this->directory . '/' . $id, $binary);

            return true;
        }

        return false;
    }


    public function deleteFile($id)
    {
        try
        {
            if ($this->filesystem->exists($this->directory . '/' . $id))
            {
                $this->filesystem->remove($this->directory . '/' . $id);

                return true;
            }
        }
        catch (\Exception $e)
        {

        }

        return false;
    }


    public function createFolder($path)
    {
        $path = trim($path, '/');

        return $this->filesystem->mkdir($this->directory . '/' . $path . '/');
    }


    public function deleteFolder($path)
    {

        $path = trim($path, '/');

        $folder = $this->directory . '/' . $path;

        try
        {
            if ($this->filesystem->exists($folder))
            {
                $this->filesystem->remove($folder);

                return true;
            }

        }
        catch (\Exception $e)
        {
            echo $e->getMessage();
        }

        return false;
    }
}
