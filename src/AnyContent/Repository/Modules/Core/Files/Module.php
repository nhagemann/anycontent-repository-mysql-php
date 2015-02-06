<?php

namespace AnyContent\Repository\Modules\Core\Files;

use AnyContent\Repository\Modules\Core\Application\Application;

class Module extends \AnyContent\Repository\Modules\Core\Application\Module
{

    public function init(Application $app, $options = array())
    {
        parent::init($app, $options);

        // get binary file
        $app->get('/1/{repositoryName}/file/{path}', 'AnyContent\Repository\Controller\FilesController::binary')
            ->assert('path', '.+');

        // list files
        $app->get('/1/{repositoryName}/files', 'AnyContent\Repository\Controller\FilesController::scan');
        $app->get('/1/{repositoryName}/files/', 'AnyContent\Repository\Controller\FilesController::scan');
        $app->get('/1/{repositoryName}/files/{path}', 'AnyContent\Repository\Controller\FilesController::scan')
            ->assert('path', '.+');

        // save file (post body contains binary)
        $app->post('/1/{repositoryName}/file/{path}', 'AnyContent\Repository\Controller\FilesController::postFile')
            ->assert('path', '.+');

        // create folder
        $app->post('/1/{repositoryName}/files/{path}', 'AnyContent\Repository\Controller\FilesController::createFolder')
            ->assert('path', '.+');

        // delete file
        $app->delete('/1/{repositoryName}/file/{path}', 'AnyContent\Repository\Controller\FilesController::deleteFile')
            ->assert('path', '.+');

        // delete files
        $app->delete('/1/{repositoryName}/files/{path}', 'AnyContent\Repository\Controller\FilesController::deleteFiles')
            ->assert('path', '.+');
        $app->delete('/1/{repositoryName}/files', 'AnyContent\Repository\Controller\FilesController::deleteFiles');
        $app->delete('/1/{repositoryName}/files/', 'AnyContent\Repository\Controller\FilesController::deleteFiles');

    }

}