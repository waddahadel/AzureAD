<?php
define("AUTH_AZURE", 30);

require_once __DIR__ . "/../vendor/autoload.php";
require_once 'Services/Authentication/classes/class.ilAuthPlugin.php';
require_once "Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADProvider.php";
//require_once("Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADAppEventL.php");




/**
 * Class ilAzureADPlugin
 *
 * @author Jephte Abijuru <jephte.abijuru@minervis.com>
 */
class ilAzureADPlugin extends ilAuthPlugin
{
    const PLUGIN_ID = "azuread";
    const PLUGIN_NAME = "AzureAD";
    const PLUGIN_CLASS_NAME = self::class;
    /**
     * @var self|null
     */
    protected static $instance = null;
    /**
     * @var \ilLogger | null
     */
    protected $logger;
    private $provider=null;


    /**
     * @return self
     */
    public static function getInstance() : self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * ilAzureADPlugin constructor
     */
    public function __construct()
    {
        global $DIC;
        parent::__construct();
        $this->logger = ilLoggerFactory::getLogger('ilAzureADPlugin');
    }


    /**
     * @return string
     */
    public function getPluginName() : string
    {
        return self::PLUGIN_NAME;
    }


    /**
     * @param string $a_component
     * @param string $a_event
     * @param array  $a_parameter
     */
    public function handleEvent(/*string*/
        $a_component, /*string*/
        $a_event,/*array*/
        $a_parameter
    )/*: void*/
    {
        require_once "Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADAppEventListener.php";
        ilAzureADAppEventListener::handleEvent($a_component, $a_event, $a_parameter);
    }


    /**
     * @inheritdoc
     */
    protected function deleteData()/*: void*/
    {
        $this->uninstallCustom();
    }

        
    /**
     * uninstallCustom
     *
     * @return void
     */
    protected function uninstallCustom()
    {
        global $ilDB;
        if ($ilDB->tableExists('auth_authhk_azuread')) {
            $ilDB->dropTable('auth_authhk_azuread');
        }
    }

    /**
     * Does your AuthProvider needs "ext_account"? return true, false otherwise.
     *
     * @param string $a_auth_id
     *
     * @return bool
     */
    public function isExternalAccountNameRequired($a_auth_id)
    {
        return true;
    }


    /**
     * @param ilAuthCredentials $credentials
     * @param string            $a_auth_mode
     *
     * @return ilAuthProviderInterface Your special instance of
     *                                 ilAuthProviderInterface where all the magic
     *                                 happens. You get the ilAuthCredentials and
     *                                 the user-selected (Sub-)-Mode as well.
     */
    public function getProvider(ilAuthCredentials $credentials, $a_auth_mode)
    {
        $provider=new ilAzureADProvider($credentials);
        return $provider;
    }


    /**
     * @param string $a_auth_id
     *
     * @return string Text-Representation of your Auth-mode.
     */
    public function getAuthName($a_auth_id)
    {
        switch ($a_auth_id) {
            case AUTH_AZURE:
                return "azure";
                break;
            default:
                return "default";
                break;
        }
    }


    /**
     * @param $a_auth_id
     *
     * @return array return an array with all your sub-modes (options) if you have some.
     *               The array comes as ['subid1' => 'Name of the Sub-Mode One', ...]
     *               you can return an empty array if you have just a "Main"-Mode.
     */
    public function getMultipleAuthModeOptions($a_auth_id)
    {
        return array();
    }


    /**
     * @param string $id (can be your Mode or – if you have any – a Sub-mode.
     *
     * @return bool
     */
    public function isAuthActive($id)
    {
        return true;
    }


    /**
     * @return array IDs of your Auth-Modes and Sub-Modes.
     */
    public function getAuthIds()
    {
        return [
            "AUTH_AZURE"=>30
        ];
    }
  
    
   
    
    /**
     * Get the auth id by an auth mode name.
     * the auth mode name is stored for each user in table usr_data -> auth_mode
     *
     * @see ilAuthUtils::_getAuthMode()
     * @return int
     */
    public function getAuthIdByName($a_auth_name)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        $ilSetting = $DIC['ilSetting'];

        
        switch ($a_auth_name) {
            case "azure":
                return AUTH_AZURE;
                break;
            default:
                return $ilSetting->get("auth_mode");
                break;

        }
    }
    
    
    
  
    
    /**
     * Check whther authentication supports sequenced authentication
     * @see ilAuthContainerMultiple
     */
    public function supportsMultiCheck($a_auth_id)
    {
        return false;
    }
    
   
    
    /**
     * Check if authentication method allows password modifications
     */
    public function isPasswordModificationAllowed($a_auth_id)
    {
        return true;
    }
    
    /**
     * Get local password validation type
     * One of
     * ilAuthUtils::LOCAL_PWV_FULL
     * ilAuthUtils::LOCAL_PWV_NO
     * ilAuthUtils::LOCAL_PWV_USER
     *
     * @return int
     */
    public function getLocalPasswordValidationType($a_auth_id)
    {
        return null;
    }

    /**
     * Set username
     */
    public function setUsername($a_name)
    {
    }
    
    /**
     * Get username
     */
    public function getUsername()
    {
        return null;
    }
    
    /**
     * Set password
     */
    public function setPassword($a_password)
    {
    }
    
    /**
     * Get password
     */
    public function getPassword()
    {
        return null;
    }
    
    /**
     * Set captcha code
     * @param type $a_code
     */
    public function setCaptchaCode($a_code)
    {
    }
    
    /**
     * Get captcha code
     */
    public function getCaptchaCode()
    {
        return null;
    }
    
    /**
     * Set auth mode.
     * Used - for instance - for manual selection on login screen.
     * @param string $a_auth_mode
     */
    public function setAuthMode($a_auth_mode)
    {
    }
    
    /**
     * Get auth mode
     */
    public function getAuthMode()
    {
        return "azure";
    }

    public function shouldUseOneUpdateStepOnly()
    {
        return false;
    }
}
