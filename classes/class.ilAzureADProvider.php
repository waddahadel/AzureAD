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
     * @param ilAuthCredentials $credentials
     */
    public function __construct(ilAuthCredentials $credentials)
    {
        parent::__construct($credentials);
        $this->settings = ilAzureADSettings::getInstance();
        //$this->az_settings = ilAzureADSettings::getInstance();
        $this->front_end_credentials= new ilAzureADFrontendCredentials();
        $this->logger = ilLoggerFactory::getLogger('ilAzureADProvider');
        $this->ctrl = $GLOBALS['ilCtrl'];
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
     * @param \ilAuthStatus $status Authentication status
     * @return bool
     */
    public function doAuthentication(\ilAuthStatus $status)
    {
        global $ilUser;
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
            
            $this->getLogger()->warning("error_message".$e->getMessage());
            $this->getLogger()->warning("error_code".$e->getCode());
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
            $status->setTranslatedReason("Login fehlgeschlagen");
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
        $ext_account = $user_info->unique_name;

        $this->getLogger()->debug('Authenticated external account: ' . $ext_account);


        $int_account = ilObjUser::_checkExternalAuthAccount(
            ilAzureADUserSync::AUTH_MODE,
            $ext_account
        );
        $shouldMigrate = false;
        if (strlen($int_account) == 0 && $user_info->mailNickname) {
            $shortLogin = $user_info->mailNickname;
            $int_account = ilObjUser::_checkExternalAuthAccount(
                ilAzureADUserSync::AUTH_MODE,
                $shortLogin
            );
        }
        if (strlen($int_account) !== 0) {
            $shouldMigrate = true;
            $this->getLogger()->debug('Should Migrate:'.$shouldMigrate);
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
            $sync->updateUser();

            $user_id = $sync->getUserId();
            if ($sync->getMigrationState()) {
                $sync->updateLogin($ext_account);
            }
        
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

    /**
     * @return MinervisAzureClient
     */
    private function initClient(string $base_url, string $apiKey, string $secretKey = '') : MinervisAzureClient
    {
        $azure=new MinervisAzureClient($base_url, $apiKey, $secretKey);
        return $azure;
    }
}
