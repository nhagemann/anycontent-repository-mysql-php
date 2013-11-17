<?php

namespace AnyContent\Repository\Middleware;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;


class ExtractUserInfo
{

    public static function execute(Request $request,Application $app)
    {

        $username = null;
        $firstname = null;
        $lastname = null;



        if ($request->query->has('userinfo'))
        {


            $userinfo = $request->get('userinfo');
            $username = @$userinfo['username'];
            $firstname = @$userinfo['firstname'];
            $lastname = @$userinfo['lastname'];
        }

        $app['repos']->setUserInfo($request->getUser(),$username,$firstname,$lastname);
    }

}