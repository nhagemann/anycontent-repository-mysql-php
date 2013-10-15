<?php

namespace AnyContent\Repository\Entity;

class ContentTypeInfo
{

    protected $name;

    public $age_content = 0;
    public $age_cmdl = 0;
    public $count = 0;


    public function setName($name)
    {
        $this->name = $name;
    }


    public function getName()
    {
        return $this->name;
    }


    public function setAgeCmdl($age_cmdl)
    {
        $this->age_cmdl = $age_cmdl;
    }


    public function getAgeCmdl()
    {
        return $this->age_cmdl;
    }


    public function setAgeContent($age_content)
    {
        $this->age_content = $age_content;
    }


    public function getAgeContent()
    {
        return $this->age_content;
    }


    public function setCount($count)
    {
        $this->count = $count;
    }


    public function getCount()
    {
        return $this->count;
    }

}