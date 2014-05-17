<?php

namespace AnyContent\Repository;

use Silex\Application;

use CMDL\ContentTypeDefinition;
use CMDL\Util;

use AnyContent\Repository\Repository;

//use AnyContent\Repository\Helper;
//use AnyContent\Repository\RepositoryException;

//use AnyContent\Repository\Util\AdjacentList2NestedSet;

//use Gaufrette\Filesystem;
//use Gaufrette\Adapter\Local as LocalAdapter;
//use Gaufrette\Adapter\Ftp as FTPAdapter;
//use Gaufrette\Adapter\Dropbox as DropboxAdapter;
//use Gaufrette\Adapter\Cache as CacheAdapter;

//use Gaufrette\Adapter\AwsS3 as AmazonAdapter;
use Aws\S3\S3Client;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class FilesManager
{

    /**
     * @var Repository
     */
    protected $repository = null;

    protected $scheme = null;

    /**
     * @var Filesystem null
     */
    protected $filesystem = null;


    public function __construct(Repository $repository, $config)
    {

        $this->repository = $repository;

        $this->filesystem = new Filesystem();

        switch ($config['type'])
        {
            case 'directory':

                //$adapter = new LocalAdapter($config['directory'], true);

                $directory = $config['directory'];
                if ($directory[0] != '/')
                {
                    $directory = APPLICATION_PATH . '/' . $directory;
                }

                $this->scheme = 'file://' . $directory;

                break;
            case 'ftp':
                die('FTP');
                break;
            case 's3':

                // Create an Amazon S3 client object
                $client = S3Client::factory(array( 'key' => $config['key'], 'secret' => $config['secret'] ));

                //$client->deleteMatchingObjects('cxiorepo1','/example/Test');
                $client->deleteObject(array('Bucket'=>'cxiorepo1','Key'=>'example/Test/'));
                die();
                // Register the stream wrapper from a client object
                $client->registerStreamWrapper();

                $this->scheme = 's3://' . $config['bucketname'];

                break;

            case 'dropbox':

                die ('Dropbox');
                break;

        }

        if (file_exists($this->scheme))
        {
            $this->scheme .= '/' . $repository->getName();
            if (!file_exists($this->scheme))
            {
                $this->filesystem->mkdir($this->scheme);
            }
        }
        else
        {
            throw new \Exception ('Files base folder ' . $directory . ' missing.');
        }

        // var_dump($this->scheme);

    }


    public function getFiles($path, $info = true)
    {
        $path = trim($path, '/');

        $files  = array();
        $finder = new Finder();

        $finder->depth('==0');

        try
        {
            /* @var $file \SplFileInfo */
            foreach ($finder->in($this->scheme . '/' . $path) as $file)
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

                    if ($info == true)
                    {
                        $extension = strtolower($extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION)); // To be compatible with some older PHP 5.3 versions

                        if (in_array($extension, array( 'gif', 'png', 'jpg', 'jpeg' )))
                        {
                            $content = $file->getContents();

                            if (function_exists('imagecreatefromstring'))
                            {
                                $image = @imagecreatefromstring($content);
                                if ($image)
                                {
                                    $item['type']   = 'image';
                                    $item['width']  = imagesx($image);
                                    $item['height'] = imagesy($image);
                                }
                            }
                        }

                    }

                    $files[] = $item;
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

            return @file_get_contents($this->scheme . '/' . $id);

        }

        return false;

    }


    public function saveFile($id, $binary)
    {
        $id       = trim($id, '/');
        $fileName = pathinfo($id, PATHINFO_FILENAME);

        if ($fileName != '') // No writing of .xxx-files
        {

            return $this->filesystem->dumpFile($this->scheme . '/' . $id, $binary, null);

            //return file_put_contents($this->scheme . '/' . $id, $binary);

        }

        return false;
    }


    public function deleteFile($id)
    {
        try
        {
            $this->filesystem->delete($id);

            return true;
        }
        catch (\Exception $e)
        {

        }

        return false;
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
            foreach ($finder->in($this->scheme . '/' . $path) as $file)
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


    /**
     * Adds hidden .folder files to every subfolder in path if necessary
     *
     * @param $path
     */
    public function createFolder($path)
    {
        $path = trim($path, '/');

        return $this->filesystem->mkdir($this->scheme . '/' . $path . '/');

        /*
        $subFolders = explode('/', $path);
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

        } */
    }


    public function deleteFolder($path)
    {
        $files = array();
        $path = trim($path, '/');

        $finder = new Finder();

        $sort = function (\SplFileInfo $a, \SplFileInfo $b)
        {
            $n1 = substr_count($a->getPath(), '/');
            $n2 = substr_count($b->getPath(), '/');

            return strcmp($n1, $n2);
        };

        $finder->sort($sort);


        var_dump($path);
        try
        {
            /* @var $file \SplFileInfo */
            foreach ($finder->in($this->scheme . '/' . $path) as $file)
            {


                    $files[] = $file->getFilename();


            }

        }
        catch (\Exception $e)
        {

        }

        if (count($files)==0)
        {
            rmdir($this->scheme . '/' . $path);
        }
        var_dump ($files);

        return false;

        $folder = $this->scheme . '/' . $path;

        $info = parse_url($folder);
        if ($info['scheme'] == 'file')
        {

            $folder = $info['path']; // Skip Stream Wrapper, since rmdir won't find the directory on some systems
        }

        try
        {
            if ($this->filesystem->exists($folder))
            {
                $this->filesystem->remove($folder);

            }

            return true;
        }
        catch (\Exception $e)
        {
            echo $e->getMessage();
        }

        return false;
    }

}