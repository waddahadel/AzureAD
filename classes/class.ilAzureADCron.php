<?php

require_once __DIR__ . "/../vendor/autoload.php";
use minervis\plugins\AzureAD\Status\StatusLog;
use minervis\plugins\AzureAD\Utils\AzureADTrait;

include_once("Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/AzureClient/globusClient.php");
require_once "Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADSettings.php";
require_once "Services/Cron/classes/class.ilCronJob.php";

/**
 * Class ilAzureADCron
 *
 * @author  Jephte Abijuru <jephte.abijuru@minervis.com>
 */
class ilAzureADCron extends ilCronJob
{

    const CRON_JOB_ID = ilAzureADPlugin::PLUGIN_ID;
    const PLUGIN_CLASS_NAME = ilAzureADPlugin::class;
    use AzureADTrait;

    private $client;
    private $settings;
    private $pl;
    
    public function __construct( ) 
    {
        $this->settings = ilAzureADSettings::getInstance();

        $proxyURL = '';
        if(ilProxySettings::_getInstance()->isActive())
        {
            $proxyHost = ilProxySettings::_getInstance()->getHost();
            $proxyPort = ilProxySettings::_getInstance()->getPort();
            $proxyURL = $proxyHost . ":" . $proxyPort;
            //$this->getLogger()->info("Proxying through " . $proxyURL);

        }
        //if(!$proxyURL) $this->getLogger()->info("No Proxy server used." );
        $this->client = new MinervisAzureClient($this->settings->getProvider(), $this->settings->getApiKey(), $this->settings->getSecretKey(), $proxyURL);
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
        
        return ilAzureADPlugin::PLUGIN_NAME . ": " .  $this->pl->txt("cron_status_subtitle");
    }


    /**
     * @return string
     */
    public function getDescription() : string
    {
        return ilAzureADPlugin::PLUGIN_NAME . ": " .  $this->pl->txt("cron_status_description");
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
            if(!$this->settings->getActive()){
                throw new Exception("Synchronization with AzureAD is not activate. Please contact admin");
            }
            $users =  $this->settings->getAllADUsers(false);
            $DIC->logger()->root()->info("A total of " . count($users) . " will be checked");
            $progress_counter = 0;
            foreach($users as $user){
                $progress_counter ++;
                $result = $this->client->checkUserDeleted($user['ext_account']);
                $reactivate = ($user['active'] == 0 and $result->status);
                $DIC->logger()->root()->debug($progress_counter . ": Checking Status for User  " . $user['ext_account'] . ": ". $result->status);
                if(!$result->status || $reactivate){
                    if($user['usr_id'] == 0) continue;
                    $user = new ilObjUser($user['usr_id']);
                    $user->setActive((bool) $result->status);
                    $user->update();
                    $status_log = self::status()->getLogByUserId($user->getId());


                    $delivery_date = new ilDateTime(time(), IL_CAL_UNIX);
                    $new_entry = ($status_log->getUsrId() == 0);
                    if($new_entry){ //new entry
                        $status_log->withProcessedDate($delivery_date);
                    }

                    $status_log->withStatus((int)$result->status)
                        ->withLevel($result->level)
                        ->withReason($result->message)
                        ->withUsrId($user->getId())
                        ->withUsername($user->getLogin())
                        ->withDeliveredDate($delivery_date)
                    ;
                    if(!$new_entry && $result->status != $status_log->getStatus()){
                        $status_log->withProcessedDate(new ilDateTime(time(), IL_CAL_UNIX));
                    }
                    $status_log->store();
                }
                if($progress_counter%100 == 0){
                    $DIC->logger()->root()->info("Azure AD status Cron Progress:  " . ceil($progress_counter*100/count($users)) . "%");
                }

            }
            $cron_result->setStatus(ilCronJobResult::STATUS_OK);
        } catch (Exception $e) {
            $DIC->logger()->root()->info($e->getMessage());
            $cron_result->setMessage($e->getMessage());
            $cron_result->setStatus(ilCronJobResult::STATUS_FAIL);
        }
        

        return $cron_result;
    }

    public function sendSummary()
    {

    }

}