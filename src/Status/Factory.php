<?php

namespace minervis\plugins\AzureAD\Status;

use ilAzureADPlugin;
use minervis\plugins\AzureAD\Utils\AzureADTrait;

final class Factory
{
    use AzureADTrait;
    const PLUGIN_CLASS_NAME = ilAzureADPlugin::class;

    protected static $instance = null;

    /**
     * Factory constructor
     */
    private function __construct()
    {

    }

    public static function getInstance() : Factory
    {
        if (self::$instance === null) {
            self::setInstance(new self());
        }

        return self::$instance;
    }

    public static function setInstance(Factory $instance)/*: void*/
    {
        self::$instance = $instance;
    }
    public static  function log(): StatusLog
    {
        return new StatusLog();
    }
    public static function getLogsTableGUI($plugin, $parent_obj, $cmd): StatusLogsTableGUI
    {
        return new StatusLogsTableGUI($plugin, $parent_obj, $cmd);
    }

}