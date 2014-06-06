<?php

namespace AnyContent\Repository\Modules\Core\SuperAdmin;

use AnyContent\Repository\Modules\Events\ContentRecordEvent;
use AnyContent\Repository\Modules\Events\RepositoryEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use AnyContent\Repository\Modules\Core\Application\Application;

use AnyContent\Repository\Modules\Core\Repositories\RepositoryManager;

use AnyContent\Repository\Modules\Core\Application\BaseController;

class RepositoryController extends BaseController
{

    public static function post(Application $app, Request $request, $repositoryName)
    {
        /** @var $manager RepositoryManager */
        $manager = $app['repos'];

        $manager->createRepository($repositoryName);

        return $app->json(true);
    }


    public static function delete(Application $app, Request $request, $repositoryName)
    {
        /** @var $manager RepositoryManager */
        $manager = $app['repos'];

        $manager->discardRepository($repositoryName);

        return $app->json(true);
    }

}