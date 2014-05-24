<?php
if (!defined('APPLICATION_PATH'))
{
    define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/..'));
}

require_once __DIR__ . '/../vendor/autoload.php';

use AnyContent\Repository\Service\RepositoryManager;
use AnyContent\Repository\Service\ContentManager;
use AnyContent\Repository\Service\Config;
use AnyContent\Repository\Service\Database;

$app          = new \AnyContent\Repository\Application;
$app['debug'] = true;

// Detect environment (default: prod) by checking for the existence of $app_env
if (isset($app_env) && in_array($app_env, array( 'prod', 'dev', 'test' )))
{
    $app['env'] = $app_env;
}
else
{
    $app['env'] = 'prod';
}

// extracting apiuser (authentification) and userinfo (query parameter userinfo)
$before1 = 'AnyContent\Repository\Middleware\ExtractUserInfo::execute';

$before2 = 'AnyContent\Repository\Middleware\RequestLogger::execute';

$before3 = 'AnyContent\Repository\Middleware\ResponseCache::before';

$afterRead  = 'AnyContent\Repository\Middleware\ResponseCache::afterRead';
$afterWrite = 'AnyContent\Repository\Middleware\ResponseCache::afterWrite';

// json formatter to make json human readable
$afterJson = 'AnyContent\Repository\Middleware\PrettyPrint::execute';

// get repository status (additional query parameters: timeshift, language)
$app->get('/1/{repositoryName}/info', 'AnyContent\Repository\Controller\RepositoryController::index')->before($before1)
    ->before($before2)->before($before3)->after($afterRead);
$app->get('/1/{repositoryName}/info/{workspace}', 'AnyContent\Repository\Controller\RepositoryController::index')
    ->before($before1)->before($before2)->before($before3)->after($afterRead);

// list content
$app->get('/1/{repositoryName}/content', 'AnyContent\Repository\Controller\ContentController::index')->before($before1)
    ->before($before2)->before($before3)->after($afterRead);

// get record (additional query parameters: timeshift, language)
$app->get('/1/{repositoryName}/content/{contentTypeName}/record/{id}', 'AnyContent\Repository\Controller\ContentController::getOne')
    ->before($before1)->before($before2)->before($before3)->after($afterRead);
$app->get('/1/{repositoryName}/content/{contentTypeName}/record/{id}/{workspace}', 'AnyContent\Repository\Controller\ContentController::getOne')
    ->before($before1)->before($before2)->before($before3)->after($afterRead);
$app->get('/1/{repositoryName}/content/{contentTypeName}/record/{id}/{workspace}/{clippingName}', 'AnyContent\Repository\Controller\ContentController::getOne')
    ->before($before1)->before($before2)->before($before3)->after($afterRead);

// get records (additional query parameters: timeshift, language, order, properties, limit, page, subset, filter)
$app->get('/1/{repositoryName}/content/{contentTypeName}/records', 'AnyContent\Repository\Controller\ContentController::getMany')
    ->before($before1)->before($before2)->before($before3)->after($afterRead);
$app->get('/1/{repositoryName}/content/{contentTypeName}/records/{workspace}', 'AnyContent\Repository\Controller\ContentController::getMany')
    ->before($before1)->before($before2)->before($before3)->after($afterRead);
$app->get('/1/{repositoryName}/content/{contentTypeName}/records/{workspace}/{clippingName}', 'AnyContent\Repository\Controller\ContentController::getMany')
    ->before($before1)->before($before2)->before($before3)->after($afterRead);

// delete record (additional query parameter: language)
$app->delete('/1/{repositoryName}/content/{contentTypeName}/record/{id}', 'AnyContent\Repository\Controller\ContentController::deleteOne')
    ->before($before1)->before($before2)->after($afterWrite);
$app->delete('/1/{repositoryName}/content/{contentTypeName}/record/{id}/{workspace}', 'AnyContent\Repository\Controller\ContentController::deleteOne')
    ->before($before1)->before($before2)->after($afterWrite);

// insert/update record (additional query parameters: record, language)
$app->post('/1/{repositoryName}/content/{contentTypeName}/records', 'AnyContent\Repository\Controller\ContentController::post')
    ->before($before1)->before($before2)->after($afterWrite);
$app->post('/1/{repositoryName}/content/{contentTypeName}/records/{workspace}/{clippingName}', 'AnyContent\Repository\Controller\ContentController::post')
    ->before($before1)->before($before2)->after($afterWrite);

// sort records (additional query parameters: list, language)
$app->post('/1/{repositoryName}/content/{contentTypeName}/sort-records', 'AnyContent\Repository\Controller\ContentController::sort')
    ->before($before1)->before($before2)->after($afterWrite);
$app->post('/1/{repositoryName}/content/{contentTypeName}/sort-records/{workspace}', 'AnyContent\Repository\Controller\ContentController::sort')
    ->before($before1)->before($before2)->after($afterWrite);

// get cmdl for a content type
$app->get('/1/{repositoryName}/content/{contentTypeName}/cmdl', 'AnyContent\Repository\Controller\RepositoryController::cmdl')
    ->before($before1)->before($before2)->before($before3)->after($afterRead);
$app->get('/1/{repositoryName}/content/{contentTypeName}/cmdl/{locale}', 'AnyContent\Repository\Controller\RepositoryController::cmdl')
    ->before($before1)->before($before2)->before($before3)->after($afterRead);

// get records status for a content type (additional query parameter: language)
//$app->get('/1/{repositoryName}/content/{contentTypeName}/info', 'AnyContent\Repository\Controller\ContentController::info')->before($before1)->before($before2);
//$app->get('/1/{repositoryName}/content/{contentTypeName}/info/{workspace}', 'AnyContent\Repository\Controller\ContentController::info')->before($before1)->before($before2);

// list configs
$app->get('/1/{repositoryName}/config', 'AnyContent\Repository\Controller\ConfigController::index')->before($before1)
    ->before($before2)->before($before3)->after($afterRead);

// get cmdl for a config type
$app->get('/1/{repositoryName}/config/{configTypeName}/cmdl', 'AnyContent\Repository\Controller\ConfigController::cmdl')
    ->before($before1)->before($before2)->before($before3)->after($afterRead);
$app->get('/1/{repositoryName}/config/{configTypeName}/cmdl/{locale}', 'AnyContent\Repository\Controller\ConfigController::cmdl')
    ->before($before1)->before($before2)->before($before3)->after($afterRead);

// get config (additional query parameters: timeshift, language)
$app->get('/1/{repositoryName}/config/{configTypeName}/record', 'AnyContent\Repository\Controller\ConfigController::getConfig')
    ->before($before1)->before($before2)->before($before3)->after($afterRead);
$app->get('/1/{repositoryName}/config/{configTypeName}/record/{workspace}', 'AnyContent\Repository\Controller\ConfigController::getConfig')
    ->before($before1)->before($before2)->before($before3)->after($afterRead);

// insert/update config (additional query parameters: language)
$app->post('/1/{repositoryName}/config/{configTypeName}/record', 'AnyContent\Repository\Controller\ConfigController::post')
    ->before($before1)->before($before2)->after($afterWrite);
$app->post('/1/{repositoryName}/config/{configTypeName}/record/{workspace}', 'AnyContent\Repository\Controller\ConfigController::post')
    ->before($before1)->before($before2)->after($afterWrite);

// get binary file
$app->get('/1/{repositoryName}/file/{path}', 'AnyContent\Repository\Controller\FilesController::binary')
    ->before($before1)->before($before2)->assert('path', '.+');

// list files
$app->get('/1/{repositoryName}/files', 'AnyContent\Repository\Controller\FilesController::scan')->before($before1)
    ->before($before2)->before($before3)->after($afterRead);
$app->get('/1/{repositoryName}/files/', 'AnyContent\Repository\Controller\FilesController::scan')->before($before1)
    ->before($before2)->before($before3)->after($afterRead);
$app->get('/1/{repositoryName}/files/{path}', 'AnyContent\Repository\Controller\FilesController::scan')
    ->before($before1)->before($before2)->before($before3)->after($afterRead)->assert('path', '.+');

// save file (post body contains binary)
$app->post('/1/{repositoryName}/file/{path}', 'AnyContent\Repository\Controller\FilesController::postFile')
    ->before($before1)->before($before2)->assert('path', '.+')->after($afterWrite);

// create folder
$app->post('/1/{repositoryName}/files/{path}', 'AnyContent\Repository\Controller\FilesController::createFolder')
    ->before($before1)->before($before2)->assert('path', '.+')->after($afterWrite);

// delete file
$app->delete('/1/{repositoryName}/file/{path}', 'AnyContent\Repository\Controller\FilesController::deleteFile')
    ->before($before1)->before($before2)->assert('path', '.+')->after($afterWrite);

// delete files
$app->delete('/1/{repositoryName}/files/{path}', 'AnyContent\Repository\Controller\FilesController::deleteFiles')
    ->before($before1)->before($before2)->assert('path', '.+')->after($afterWrite);
$app->delete('/1/{repositoryName}/files', 'AnyContent\Repository\Controller\FilesController::deleteFiles')
    ->before($before1)->before($before2)->after($afterWrite);
$app->delete('/1/{repositoryName}/files/', 'AnyContent\Repository\Controller\FilesController::deleteFiles')
    ->before($before1)->before($before2)->after($afterWrite);

// simplification routes, solely for human interaction with the api
$app->get('/', 'AnyContent\Repository\Controller\RepositoryController::welcomeShortCut')->before($before1)
    ->before($before2);
$app->get('/1', 'AnyContent\Repository\Controller\RepositoryController::welcome')->before($before1)->before($before2);
$app->get('/1/', 'AnyContent\Repository\Controller\RepositoryController::welcome')->before($before1)->before($before2);
$app->get('/1/{repositoryName}', 'AnyContent\Repository\Controller\RepositoryController::getInfoShortCut')
    ->before($before1)->before($before2);
$app->get('/1/{repositoryName}/config/{configTypeName}', 'AnyContent\Repository\Controller\ConfigController::getConfigShortCut')
    ->before($before1)->before($before2);
$app->get('/1/{repositoryName}/content/{contentTypeName}', 'AnyContent\Repository\Controller\ContentController::getContentShortCut')
    ->before($before1)->before($before2);

// admin routes
$app->get('/1/admin/refresh/{repositoryName}/{contentTypeName}', 'AnyContent\Repository\Controller\AdminController::refresh')
    ->before($before1)->before($before2);
$app->get('/1/admin/delete/{repositoryName}/{contentTypeName}', 'AnyContent\Repository\Controller\AdminController::delete')
    ->before($before1)->before($before2);

$app['config'] = $app->share(function ($app)
{
    return new Config($app, __DIR__ . '/../');
});

$app['db'] = $app->share(function ($app)
{
    return new Database($app);
});

$app['repos'] = $app->share(function ($app)
{
    return new RepositoryManager($app);
});

if ($app['debug'])
{
    $app->register(new Silex\Provider\MonologServiceProvider(), array(
        'monolog.logfile' => __DIR__ . '/../log/debug.log', 'monolog.level' => \Monolog\Logger::INFO
    ));
}



if (!function_exists('apc_exists'))
{
    function apc_exists($keys)
    {
        $result = null;
        apc_fetch($keys, $result);

        return $result;
    }
}

$app->after($afterJson, Silex\Application::EARLY_EVENT);

$app->registerStorageAdapter('directory', 'AnyContent\Repository\Modules\StorageAdapter\Directory\DirectoryStorageAdapter');
$app->registerStorageAdapter('s3', 'AnyContent\Repository\Modules\StorageAdapter\S3\S3StorageAdapter');
$app->registerStorageAdapter('s3pp', 'AnyContent\Repository\Modules\StorageAdapter\S3\S3PPStorageAdapter');

require_once (APPLICATION_PATH .'/config/modules.php');

$app->init();

if ($app['env'] == 'test')
{
    return $app;
}

$app->run();
