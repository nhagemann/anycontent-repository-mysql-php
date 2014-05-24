<?php

#$app->registerStorageAdapter('directory', 'AnyContent\Repository\Modules\StorageAdapter\Directory\DirectoryStorageAdapter');
#$app->registerStorageAdapter('s3', 'AnyContent\Repository\Modules\StorageAdapter\S3\S3StorageAdapter');
#$app->registerStorageAdapter('s3pp', 'AnyContent\Repository\Modules\StorageAdapter\S3\S3PPStorageAdapter');



//$app['dispatcher']->addListener('content.record.before.update','AnyContent\Repository\Modules\Events\ExampleListener::on');

// Uncomment next lines if you use APC Cache and your PHP version doesn't have the apc_exists function

if (!function_exists('apc_exists'))
{
    function apc_exists($keys)
    {
        $result = null;
        apc_fetch($keys, $result);

        return $result;
    }
}