<?php

include_once("Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/AzureClient/globusClient.php");
require_once "Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADSettings.php";
require_once "Services/Cron/classes/class.ilCronJob.php";

/**
 * Class ilAzureADCron
 *
 * @author  Jephte Abijuru <jephte.abijuru@minervis.com>
 */
class ilAzureADCronSyncUserData extends ilCronJob
{

    const CRON_JOB_ID = ilAzureADPlugin::PLUGIN_ID . "sync";
    const PLUGIN_CLASS_NAME = ilAzureADPlugin::class;

    private $client;
    private $settings;
    private $pl;
    /**
     * @var ilComponentLogger|ilLogger|Logger
     */
    private $logger;
    /**
     * @var string
     */
    private $skiptoken;
    /**
     * @var int
     */
    private $top;
    /**
     * @var \ILIAS\DI\Container|mixed
     */
    private $dic;
    /**
     * @var int[]
     */
    private $counter;
    /**
     * @var array
     */
    private $positive_matches;

    public function __construct( ) 
    {
        global  $DIC;
        $this->dic = $DIC;
        $this->logger = ilLoggerFactory::getLogger('ilAzureAD');

        $this->counter = array(
            'users' => 0,
            'matches' => 0,
            'only_emails' => 0
        );
        $this->positive_matches = array();


        $this->settings = ilAzureADSettings::getInstance();
        $this->skiptoken = '';
        $this->top = 999;
        $proxyURL = '';
        if(ilProxySettings::_getInstance()->isActive())
        {
            $proxyHost = ilProxySettings::_getInstance()->getHost();
            $proxyPort = ilProxySettings::_getInstance()->getPort();
            $proxyURL = $proxyHost . ":" . $proxyPort;
            $this->getLogger()->info("Proxying through " . $proxyURL);

        }
        if(!$proxyURL) $this->getLogger()->info("No Proxy server used." );
        $this->client = new MinervisAzureClient($this->settings->getProvider(), $this->settings->getApiKey(), $this->settings->getSecretKey(), $proxyURL);
        include_once("Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADPlugin.php");
        $this->pl = ilAzureADPlugin::getInstance();
    }

    public  function getLogger(){
        return $this->logger;
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
        
        return ilAzureADPlugin::PLUGIN_NAME . ": " .  $this->pl->txt("cron_title") . "Sync User data";
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
    public function getDefaultScheduleValue(): ?int
    {
        return 1;
    }


    /**
     * @inheritDoc
     * @throws MinervisAzureClientException
     */
    public function run() : ilCronJobResult
    {
        global $DIC;
        $cron_result = new ilCronJobResult();

        /*try {
            //Fetch users: How do we identify globus users? external account ***@globus.net,
            if(!$this->settings->getActive()){
                throw new Exception("Synchronization with AzureAD is not activate. Please contact admin");
            }
            $this->client->retrieveUsers();
        } catch (Exception $e) {
            $cron_result->setStatus(ilCronJobResult::STATUS_FAIL);
        }
        $cron_result->setStatus(ilCronJobResult::STATUS_OK);
        */

        $this->getAllNext();
        return $cron_result;
    }

    /**
     * @throws MinervisAzureClientException
     */
    public function getAllNext() {
        $max_iter = 100;
        $counter = 0;
        $i = 0;
        while($i<$max_iter){
            $response = $this->client->retrieveUsers($this->top, $this->skiptoken);
            //$this->getLogger()->dump($response);
            if(!$response  || !isset($response) || !$response->value) break;
            $this->skiptoken = $response->skiptoken;
            $this->synchronize($response->value, $i);
            if(  count($response->value) < $this->top ) break;
            $i ++ ;

        }
        $this->getLogger()->info("Summary of  Users retrieved in " . $i . " iterations");
        $this->getLogger()->dump($this->counter);
        $this->checkMatches();
        $this->exportNegativeMatches();

    }



    private function checkMatches()
    {
        $negative_matches_query = "SELECT * from usr_data where active = 1 AND usr_id NOT IN (" . implode( ", ", $this->positive_matches) . ")";
        $cursor  = $this->dic->database()->query($negative_matches_query);
        $this->getLogger()->info($cursor->numRows() . " do not have matches");
        //$this->getLogger()->dump($this->counter);

    }
    public function updateUser($usr_id, $data)
    {
        //field ID
        $udfs = ilUserDefinedFields::_getInstance();
        $pernr_id = $udfs->fetchFieldIdFromName(ilAzureADProvider::UDF_EMPLOYEEID);
        $job_title = $udfs->fetchFieldIdFromName(ilAzureADProvider::UDF_JOB_TITLE);
        $userObj = new ilObjUser($usr_id);
        $udf = array(
            $job_title => $data->jobTitle
        );
        $userObj->setUserDefinedData($udf);
        $userObj->update();
    }

    public function synchronize(array $users, int $iter = 0): array
    {

        $auth_provider = ilAzureADProvider::getInstance();
        $this->getLogger()->info($iter . ": The set contains  " . count($users). " users");
        $this->counter['users'] += count($users);
        foreach ($users as $user){
            $usr_id = $auth_provider->getUserIdByUDF(ilAzureADProvider::UDF_EMPLOYEEID, $user->employeeId, false);
            if($usr_id){
                $this->counter['matches'] ++;
                $this->positive_matches [] = $usr_id;
                //get User object
              $this->updateUser($usr_id, $user);

            }else{
                $usr_id = ilObjUser::_checkExternalAuthAccount(ilAzureADUserSync::AUTH_MODE, $user->mail);
                if($usr_id) $this->counter['only_emails'] ++;
            }
        }
        return $this->counter;
    }

    public function areNegativeMatchesDeleted()
    {

    }

    public function exportNegativeMatches(){
        $negative_matches_query = "SELECT * from usr_data where active = 1 AND  usr_id NOT IN (" . implode( ", ", $this->positive_matches) . ")";
        $cursor  =$this->dic->database()->query($negative_matches_query);
        $negative_matches = array();
        while($row = $this->dic->database()->fetchAssoc($cursor)){
            $negative_matches [] = $row['usr_id'];
        }
        $usr_folder = new ilObjUserFolder(7);
        $usr_folder->buildExportFile(ilObjUserFolder::FILE_TYPE_CSV, $negative_matches);
    }

}