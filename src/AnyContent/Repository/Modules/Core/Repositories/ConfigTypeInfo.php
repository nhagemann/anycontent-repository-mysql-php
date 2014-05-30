<?php

namespace AnyContent\Repository\Modules\Core\Repositories;

class ConfigTypeInfo
{

    protected $name;

    public $title = '';
    public $lastchange_content = 0;
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


    public function setLastchangeContent($age_content)
    {
        $this->lastchange_content = $age_content;
    }


    public function getLastchangeContent()
    {
        return $this->lastchange_content;
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
