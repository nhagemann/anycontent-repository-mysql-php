<?php

namespace AnyContent\Repository\Command;

use Symfony\Component\Filesystem\Filesystem;

class Installer
{

    public static function postInstallUpdate()
    {

        echo "...\n";
        echo "Creating Default CMDL Folder.\n";
        echo "Creating Default Files Folder.\n";
        echo "Creating Config Folder with default config.\n";
        echo "Creating Web Folder.\n";
        echo "...\n";
        echo "Done.\n";

        $filesystem = new Filesystem();
        $baseDir = realpath(__DIR__.'/../../../../../../../');

        $filesystem->mkdir($baseDir.'/cmdl');
        $filesystem->mkdir($baseDir.'/config');
        $filesystem->mkdir($baseDir.'/web');
        $filesystem->mkdir($baseDir.'/log');

        // All copy commands do not overwrite eventually existing files!

        $filesystem->copy(__DIR__.'/resources/config.example.yml',$baseDir.'/config/config.yml');

        $filesystem->copy(__DIR__.'/../../../../web/index.php',$baseDir.'/web/index.php');
        $filesystem->copy(__DIR__.'/../../../../web/.htaccess',$baseDir.'/web/.htaccess');

    }
}