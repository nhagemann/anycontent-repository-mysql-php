<?php

namespace AnyContent\Repository\Modules\Core\Application;

use AnyContent\Repository\Modules\Core\Application\Application;

abstract class Module
{

    protected $defaultOptions = array();

    protected $options = array();

    protected $app;


    public function init(Application $app, $options = array())
    {
        $this->app     = $app;
        $this->options = array_merge($this->defaultOptions, $options);
    }


    public function run(Application $app)
    {

    }


    public function getOption($key, $default = null)
    {
        if (array_key_exists($key, $this->options))
        {
            return $this->options[$key];
        }

        return $default;
    }
}