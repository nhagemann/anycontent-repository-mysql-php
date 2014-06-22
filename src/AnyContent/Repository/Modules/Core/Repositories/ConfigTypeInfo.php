<?php

namespace AnyContent\Repository\Modules\Core\Repositories;

class ConfigTypeInfo
{

    protected $name;

    public $title = '';
    public $lastchange_config = 0;
    public $lastchange_cmdl = 0;
    public $description = '';


    public function setName($name)
    {
        $this->name = $name;
    }


    public function getName()
    {
        return $this->name;
    }


    public function setTitle($title)
    {
        $this->title = $title;
    }


    public function getTitle()
    {
        return $this->title;
    }


    public function setLastchangecmdl($age_cmdl)
    {
        $this->lastchange_cmdl = $age_cmdl;
    }


    public function getLastchangecmdl()
    {
        return $this->lastchange_cmdl;
    }


    public function setLastchangeConfig($age_config)
    {
        $this->lastchange_config = $age_config;
    }


    public function getLastchangeConfig()
    {
        return $this->lastchange_config;
    }


    public function setDescription($description)
    {
        $this->description = $description;
    }


    public function getDescription()
    {
        return $this->description;
    }

}
