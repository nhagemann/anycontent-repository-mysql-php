anycontent-repository-mysql-php
===============================

## Installation

### Composer

Just create a composer.json file with following content and run `composer install`

  {
    "require": {
        "php": ">=5.3",
        "nhagemann/anycontent-repository-mysql-php": "0.9.*"
    },
    "scripts": {
        "post-update-cmd": "AnyContent\\Repository\\Command\\Installer::postInstallUpdate",
        "post-install-cmd": "AnyContent\\Repository\\Command\\Installer::postInstallUpdate"

    },
    "minimum-stability": "dev",
    "prefer-stable": true
  }
  
### Web Server (Vhost) Configuration

Then configure your webserver to server the content of the `/web` folder, e.g. as _acrs.dev_.


### Modules Configuration

Go to the `/config` folder and copy the file _modules.example.php_ to _modules.php_.

Have a look into that file, if you want to customize your installation. You can turn off any module by removing
it's registration call ($app->registerModule()) within this file.

### Repository Configuration

Go to the `/config` folder and copy the file _config.example.yml_ to _config.yml_.

Within that file you have to specify at least your database connection.
