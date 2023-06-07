<?php

namespace minervis\plugins\AzureAD\Utils;

use minervis\plugins\AzureAD\Status\Repository as Status;
trait AzureADTrait
{

    public static function status(): Status
    {
        return Status::getInstance();
    }
}