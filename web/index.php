<?php
if (!defined('APPLICATION_PATH'))
{
    define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/..'));
}

require_once __DIR__ . '/../vendor/autoload.php';

$app          = new \AnyContent\Repository\Modules\Core\Application\Application;
$app['debug'] = true;

// Detect environment (default: prod) by checking for the existence of $app_env
if (isset($app_env) && in_array($app_env, array('prod','dev','test','console'))) { $app['env'] = $app_env; }else{$app['env'] = 'prod';}


$app->registerModule('AnyContent\Repository\Modules\Core\ResponseCache');
$app->registerModule('AnyContent\Repository\Modules\Core\ExtractUserInfo');

$app->registerModule('AnyContent\Repository\Modules\Core\Repositories');
$app->registerModule('AnyContent\Repository\Modules\Core\ContentRecords');
$app->registerModule('AnyContent\Repository\Modules\Core\ConfigRecords');
$app->registerModule('AnyContent\Repository\Modules\Core\Files');


$app->registerModule('AnyContent\Repository\Modules\Core\DirectoryCMDLAccessAdapter');






// admin routes
// $app->get('/1/admin/refresh/{repositoryName}/{contentTypeName}', 'AnyContent\Repository\Controller\AdminController::refresh');
// $app->get('/1/admin/delete/{repositoryName}/{contentTypeName}', 'AnyContent\Repository\Controller\AdminController::delete');




if ($app['debug'])
{
    $app->register(new Silex\Provider\MonologServiceProvider(), array(
        'monolog.logfile' => __DIR__ . '/../log/debug.log', 'monolog.level' => \Monolog\Logger::INFO
    ));
}



$app->registerStorageAdapter('directory', 'AnyContent\Repository\Modules\StorageAdapter\Directory\DirectoryStorageAdapter');
$app->registerStorageAdapter('s3', 'AnyContent\Repository\Modules\StorageAdapter\S3\S3StorageAdapter');
$app->registerStorageAdapter('s3pp', 'AnyContent\Repository\Modules\StorageAdapter\S3\S3PPStorageAdapter');

require_once(APPLICATION_PATH . '/config/modules.php');

$app->init();

if ($app['env'] == 'test')
{
    return $app;
}

$app->run();
