<?php

include_once("Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/AzureClient/globusClient.php");
require_once "Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADSettings.php";

use ilCronJob;
use Throwable;

/**
 * Class ilAzureADCron
 *
 * @author  Jephte Abijuru <jephte.abijuru@minervis.com>
 */
class ilAzureADCron extends ilCronJob
{

    const CRON_JOB_ID = ilAzureADPlugin::PLUGIN_ID;
    const PLUGIN_CLASS_NAME = ilAzureADPlugin::class;

    private $client;
    private $settings;
    private $pl;
    
    public function __construct( ) 
    {
        $this->settings = ilAzureADSettings::getInstance();
        $this->client = new MinervisAzureClient($this->settings->getProvider(), $this->settings->getApiKey(), $this->settings->getSecretKey());
        include_once("Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADPlugin.php");
        $this->pl = ilAzureADPlugin::getInstance();
    }


    /**
     * @return string
     */
    public function getId() : string
    {
        return self::CRON_JOB_ID;
    }


    /**
     * @return string
     */
    public function getTitle() : string
    {
        
        return ilAzureADPlugin::PLUGIN_NAME . ": " .  $this->pl->txt("cron_title");
    }


    /**
     * @return string
     */
    public function getDescription() : string
    {
        return ilAzureADPlugin::PLUGIN_NAME . ": " .  $this->pl->txt("cron_description");
    }


    /**
     * @return bool
     */
    public function hasAutoActivation() : bool
    {
        return true;
    }


    /**
     * @return bool
     */
    public function hasFlexibleSchedule() : bool
    {
        return true;
    }


    /**
     * @return int
     */
    public function getDefaultScheduleType() : int
    {
        return ilCronJob::SCHEDULE_TYPE_DAILY;
    }


    /**
     * @return null
     */
    public function getDefaultScheduleValue()
    {
        return 1;
    }



    /**
     * @inheritDoc
     */
    public function run() : ilCronJobResult
    {
        global $DIC;
        $cron_result = new ilCronJobResult();

        try {
            //Fetch users: How do we identify globus users? external account ***@globus.net,
            $users =  $this->settings->getAllADUsers();
            $DIC->logger()->root()->dump($users);
            foreach($users as $user){
                $result = $this->client->checkUserDeleted($user['ext_account']);
                if(!$result){
                    $user = new ilObjUser($user['usr_id']);
                    $user->setActive(false);
                    $user->update();
                }
            }
        } catch (Throwable $e) {
            $cron_result->setStatus(ilCronJobResult::STATUS_FAIL);
        }
        $cron_result->setStatus(ilCronJobResult::STATUS_OK);

        return $cron_result;
    }

}