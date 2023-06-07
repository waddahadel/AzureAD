<?php
ob_start();

include_once("Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/AzureClient/globusClient.php");
require_once "Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADUserSync.php";
require_once "Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADFrontendCredentials.php";
require_once "Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADSettings.php";


/**
 * Class ilAzureADProvider
 *
 * @author Jephte Abijuru <jephte.abijuru@minervis.com>
 *
 *
 */
class ilAzureADProvider extends ilAuthProvider implements ilAuthProviderInterface
{
    const UDF_EMPLOYEEID = "PERNR";
    const UDF_JOB_TITLE = "JobTitle";
    /**
     * @var ilAzureADProvider
     */
    private static $instance;
    private $settings = null;
    private $front_end_credentials;
    private $ctrl;
    /**
     * @var \ilLogger
     */
    protected $logger;

    /**
     * @var ilAzureADSettings|null
     */
    private $az_settings = null;

    /**
     * ilAzureADProvider constructor.
     * @param ilAuthCredentials| null $credentials
     */
    public function __construct(ilAuthCredentials $credentials)
    {
        parent::__construct($credentials);
        $this->settings = ilAzureADSettings::getInstance();
        $this->front_end_credentials= new ilAzureADFrontendCredentials();
        $this->logger = ilLoggerFactory::getLogger('ilAzureADProvider');
        $this->ctrl = $GLOBALS['ilCtrl'];
    }

    public static function getInstance() : self
    {
        if (self::$instance === null) {
            $credentials = new ilAzureADFrontendCredentials();
            self::$instance = new self($credentials);
        }

        return self::$instance;
    }

    /**
     * Handle logout event
     */
    public function handleLogout()
    {
        if ($this->settings->getValue("logout_scope") == ilAzureADSettings::LOGOUT_SCOPE_LOCAL) {
            return false;
        }

        $auth_token = ilSession::get('azure_auth_token');

        if (strlen($auth_token)) {
            ilSession::set('azure_auth_token', '');
            $azure = $this->initClient();
            $azure->signOut(
                $auth_token,
                ILIAS_HTTP_PATH . '/logout.php'
            );
        }
    }

    /**
     * Do authentication
     * @param ilAuthStatus $status Authentication status
     * @return bool
     */
    public function doAuthentication(\ilAuthStatus $status): bool
    {
        global $ilUser;
        $azure = null;
        try {
            $azure = $this->initClient($this->settings->getProvider(), $this->settings->getApiKey(), $this->settings->getSecretKey());
            $azure->setRedirectURL(ILIAS_HTTP_PATH . 'Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/azurepage.php');
            $azure->authenticate();
            // user is authenticated, otherwise redirected to authorization endpoint or exception
            $claims = $azure->getUserInfo();
            $status = $this->handleUpdate($status, $claims);

            // @todo : provide a general solution for all authentication methods
            //$_GET['target'] = (string) $this->front_end_credentials->getRedirectionTarget();
            if ($this->settings->getLogoutScope() == ilAzureADSettings::LOGOUT_SCOPE_GLOBAL) {
                $azure->requestTokens();
                ilSession::set('azure_auth_token', $azure->getAccessToken());
            }

            return true;
        } catch (Exception $e) {
            
            $this->getLogger()->warning("error_message: ".$e->getMessage());
            $this->getLogger()->warning("error_code: ".$e->getCode());
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
            if($azure && !$azure->getLoginSuccess()){
                $status->setReason('err_wrong_login');
            }else{
                $status->setTranslatedReason('Login Fehlgeschlagen! Bitte kontaktieren Sie dem Administrator.');
            }
            return false;
        }
    }


    /**
     * @param ilAuthStatus $status
     * @param array $user_info
     */
    private function handleUpdate(ilAuthStatus $status, $user_info)
    {
        if (!is_object($user_info)) {
            $this->getLogger()->error('Received invalid user credentials: ');
            $this->getLogger()->dump($user_info, ilLogLevel::ERROR);
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
            $status->setReason('err_wrong_login');
            return $status;
        }

        //$this->getLogger()->dump($user_info, ilLogLevel::DEBUG);
        //$uid_field = $this->settings->getUidField();
        $ext_account = $user_info->unique_name;
       
        $usr_id_udf =  $this->getUserIdByUDF(self::UDF_EMPLOYEEID, $user_info->employeeId);
        $int_account = '';
        if($usr_id_udf >  0){
            $int_account = ilObjUser::_lookupLogin($usr_id_udf);
            $this->getLogger()->info('Authenticated external account: ' . $int_account);
        }
        $shouldMigrate = false;
        if($usr_id_udf == 0){
            $this->getLogger()->debug('The User id for the given emplyeeid was not found, using the login name');
            $int_account = ilObjUser::_checkExternalAuthAccount(
                ilAzureADUserSync::AUTH_MODE,
                $ext_account
            );
        }
        if (strlen($int_account) == 0 && $user_info->mailNickname) {
            $shortLogin = $user_info->mailNickname;
            $int_account = ilObjUser::_checkExternalAuthAccount(
                ilAzureADUserSync::AUTH_MODE,
                $shortLogin
            );
        }
        if (strlen($int_account) !== 0) {
            $shouldMigrate = true;
            $this->getLogger()->debug('Should Migrate: '.$shouldMigrate);
        }
        $this->getLogger()->debug('Internal account: ' . $int_account);

        try {
            $sync = new ilAzureADUserSync($this->settings, $user_info);
            if (!is_string($ext_account)) {
                $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
                $status->setReason('err_wrong_login');
                return $status;
            }

            $sync->setMigrationState($shouldMigrate);
            $sync->setExternalAccount($ext_account);
            $sync->setInternalAccount($int_account);
            if(!$sync->needsCreation()){
                $sync->setUserId(ilObjUser::_lookupId($int_account));
            }
            if($this->settings->isSyncAllowed()){
                $sync->updateUser();
                if ($sync->getMigrationState()) {
                    $sync->updateLogin($ext_account);
                }
            }
            $user_id = $sync->getUserId();
            
        
            ilSession::set('used_external_auth', true);
            $status->setAuthenticatedUserId($user_id);
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATED);

            // @todo : provide a general solution for all authentication methods
            //$_GET['target'] = (string) $this->front_end_credentials->getRedirectionTarget();
        } catch (Exception $e) {
            throw $e;
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
            $status->setReason('err_wrong_login');
        }

        return $status;
    }
    private function checkExternalAuthAccountByUDF()
    {

    }
    public function getUserIdByUDF( $udf_name, $udf_value, $safety_check = true, $type = 'text'){
        global $DIC;
        $db = $DIC->database();
        $query = 'SELECT field_id,field_name, usr_id, value FROM `udf_text`  join udf_definition  using(field_id)'.
        ' WHERE field_name LIKE ' .$db->quote($udf_name, 'text').
        'AND value LIKE ' .$db->quote($udf_value, $type);
        $res = $db->query($query);
        $usr_id = 0;
        if($db->numRows($res) > 0){
            while ($rec = $db->fetchAssoc($res)){
                $usr_id = $rec['usr_id'];
            }
        }
        if($db->numRows($res) > 1 and $safety_check){
            throw new Exception("The employeeID is duplicate in the database.");
        }
        $this->getLogger()->debug('User id/employeeid : '. $usr_id . '/'  . $udf_value);
        return $usr_id;
    }

    /**
     * @return MinervisAzureClient
     */
    private function initClient(string $base_url, string $apiKey, string $secretKey = '') : MinervisAzureClient
    {
        //Add Proxy
        require_once('Services/Http/classes/class.ilProxySettings.php');
        $proxyURL = '';
        if(ilProxySettings::_getInstance()->isActive())
        {
            $proxyHost = ilProxySettings::_getInstance()->getHost();
            $proxyPort = ilProxySettings::_getInstance()->getPort();
            $proxyURL = $proxyHost . ":" . $proxyPort;
            $this->getLogger()->info("Proxying through " . $proxyURL);

        }
         if(!$proxyURL) $this->getLogger()->info("No Proxy server used." );

        return new MinervisAzureClient($base_url, $apiKey, $secretKey, $proxyURL);
    }
}
