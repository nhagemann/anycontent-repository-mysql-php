<?php

namespace AnyContent\Repository\Modules\Events;

use Symfony\Component\EventDispatcher\Event;

class ContentRecordEvent extends Event
{

    protected $currentRecordTableValues = null;

    protected $newRecordTableValues = null;


    function __construct(&$newRecordTableValues, &$currentRecordTableValues = null)
    {
        $this->currentRecordTableValues = $currentRecordTableValues;
        $this->newRecordTableValues     = $newRecordTableValues;
    }


    /**
     * @return null
     */
    public function getCurrentRecordTableValues()
    {
        return $this->currentRecordTableValues;
    }


    /**
     * @return null
     */
    public function getNewRecordTableValues()
    {
        return $this->newRecordTableValues;
    }

}