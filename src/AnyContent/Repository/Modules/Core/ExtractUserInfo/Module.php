<?php

namespace AnyContent\Repository\Modules\Core\ExtractUserInfo;

use AnyContent\Repository\Modules\Core\Application\Application;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Module extends \AnyContent\Repository\Modules\Core\Application\Module
{

    public function init(Application $app, $options = array())
    {
        parent::init($app, $options);

        $app->before(function (Request $request, Application $app)
        {
            $username  = null;
            $firstname = null;
            $lastname  = null;

            if ($request->query->has('userinfo'))
            {

                $userinfo  = $request->get('userinfo');
                $username  = @$userinfo['username'];
                $firstname = @$userinfo['firstname'];
                $lastname  = @$userinfo['lastname'];
            }

            $app['repos']->setUserInfo($request->getUser(), $username, $firstname, $lastname);

        });
    }

}