<?php
ob_start();
require_once __DIR__ . "/../vendor/autoload.php";

use srag\Plugins\__AzureAD__\Config\Config;
use srag\DIC\AzureAD\DICTrait;
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
   
    private $settings = null;
    private $front_end_credentials;
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
     * @param ilAuthCredentials $credentials
     */
    public function __construct(ilAuthCredentials $credentials)
    {
        parent::__construct($credentials);
        $this->settings = Config::getInstance();
	$this->az_settings = ilAzureADSettings::getInstance();
        $this->front_end_credentials= new ilAzureADFrontendCredentials();
	$this->logger = ilLoggerFactory::getLogger('ilAzureADProvider');
    }

    /**
     * Handle logout event
     */
    public function handleLogout()
    {
        if ($this->settings->getValue("logout_scope") == Config::LOGOUT_SCOPE_LOCAL) {
            return false;
        }

        $auth_token = ilSession::get('azure_auth_token');
        //$this->getLogger()->info('Using token: ' . $auth_token);

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
     * @param \ilAuthStatus $status Authentication status
     * @return bool
     */
    public function doAuthentication(\ilAuthStatus $status)
	{
	global $DIC;
	$log=$DIC->logger()->root();
        try {
	    //$this->getLogger()->info('test_secret_2'. $this->az_settings->getSecret());
            $azure = $this->initClient($this->az_settings->getProvider(),$this->az_settings->getSecret());
            $azure->setRedirectURL(ILIAS_HTTP_PATH . 'Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/azurepage.php');

            //$this->getLogger()->info(                'Redirect url is: ' .                $azure->getRedirectURL()            );

            /*$azure->setResponseTypes(
                [
                    'id_token'
                ]
            );
            $azure->addScope(
                [
                    'openid',
                    'profile',
                    'email',
                    'roles'
                ]
            );

*/
	    //$this->getLogger()->info("before_authenticate_doAuthentication");
            $azure->authenticate();
            // user is authenticated, otherwise redirected to authorization endpoint or exception
//            $this->getLogger()->dump($_REQUEST, \ilLogLevel::INFO);

            $claims = $azure->getUserInfo();
//            $this->getLogger()->dump($claims, \ilLogLevel::DEBUG);
            $status = $this->handleUpdate($status, $claims);

            // @todo : provide a general solution for all authentication methods
            $_GET['target'] = (string) $this->front_end_credentials->getRedirectionTarget();
            //ilSession::set('azure_auth_token', $azure->getAccessToken());
//	    $this->getLogger()->info("logout_scope:".$this->settings->getValue("logout_scope") ."  ". Config::LOGOUT_SCOPE_GLOBAL);
            if ($this->settings->getValue("logout_scope") == Config::LOGOUT_SCOPE_GLOBAL) {
                $azure->requestTokens();
	//	$this->getLogger()->info("azure_auth_token:".$azure->getAccessToken());
                ilSession::set('azure_auth_token', $azure->getAccessToken());
            }
//	    $this->getLogger()->info("doAuthentication_login_status".$status->getStatus());


            return true;
        } catch (Exception $e) {
            
            $this->getLogger()->warning($e->getMessage());
            $this->getLogger()->warning($e->getCode());
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
            $status->setTranslatedReason($e->getMessage());
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

        //$uid_field = $this->settings->getUidField();
        $ext_account = $user_info->name;

        $this->getLogger()->debug('Authenticated external account: ' . $ext_account);


        $int_account = ilObjUser::_checkExternalAuthAccount(
            ilAzureADUserSync::AUTH_MODE,
            $ext_account
        );
        //$this->getLogger()->info('Internal account: ' . $int_account);

        try {
            $sync = new ilAzureADUserSync($this->settings, $user_info);
            if (!is_string($ext_account)) {
                
                $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
                $status->setReason('err_wrong_login');
                return $status;
            }
            $sync->setExternalAccount($ext_account);
            $sync->setInternalAccount($int_account);
            $sync->updateUser();

            $user_id = $sync->getUserId();
	    //$this->getLogger()->info('sync_getUserId ' . $user_id);
            ilSession::set('used_external_auth', true);
            $status->setAuthenticatedUserId($user_id);
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATED);

            // @todo : provide a general solution for all authentication methods
            $_GET['target'] = (string) $this->front_end_credentials->getRedirectionTarget();
        } catch (Exception $e) {
//	    $this->getLogger()->info('exception_thrown: '.$e->getTraceAsString() );
            throw $e;
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
            $status->setReason('err_wrong_login');
        }

        return $status;
    }

    /**
     * @return MinervisAzureClient
     */
    private function initClient(string $base_url, string $secret) : MinervisAzureClient
    {
       $azure=new MinervisAzureClient($base_url, $secret);
       return $azure;
    }
}
