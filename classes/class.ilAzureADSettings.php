<?php
/**
 * Class ilAzureADSettings
 *
 * @author Shaharyar Ali Bhatti <shaharyar.bhatti@minervis.com>
 * @author Jephte Abijuru <jephte.abijuru@minervis.com>
 *
 *
 */
class ilAzureADSettings
{
    const STORAGE_ID = 'azuread';
    const LOGIN_ELEMENT_TYPE_TXT = 0;
    const LOGIN_ELEMENT_TYPE_IMG = 1;
    const LOGIN_ENFORCE = 0;
    const LOGIN_STANDARD = 1;

    const LOGOUT_SCOPE_GLOBAL = 0;
    const LOGOUT_SCOPE_LOCAL = 1;

    /**
     * @var \ilAzureADSettings
     *
     */
    private static $instance = null;

    /**
     * @var \ilSetting
     */
    private $storage = null;
    /**
     * @var bool
     */
    private $active = false;

    /**
     * @var string
     */
    private $provider = '';
    /**
     * @var string
     */
    private $secretkey = '';
        /**
     * @var string
     */
    private $apikey = '';
    /**
     * @var int
     */
    private $login_element_type = self::LOGIN_ELEMENT_TYPE_TXT;
    /**
     * @var int
     */
    private $login_prompt_type = self::LOGIN_ENFORCE;
    /**
     * @var int
     */
    private $logout_scope=self::LOGOUT_SCOPE_GLOBAL;
    /**
     * @var bool
     */
    private $custom_session = false;

    /**
     * @var int
     */
    private $session_duration = 60;

    /**
     * @var bool
     */
    private $allow_sync=1;

    /**
     *@var int
    */
    private $connection_id=0;

    /**
     * @var int
     */
    private $role=0;

    /** @var Container $dic */
    private $dic;
    /** @var ilDB $db */
    private $db;


    private $values;
    


    /**
     * ilAzureADSettings constructor.
     */
    private function __construct()
    {
        global $DIC;
        $this->dic=$DIC;
        $this->db=$DIC->database();
        $this->load();
    }

    /**
     * Get singleton instance
     * @return \ilAzureADSettings
     */
    public static function getInstance() : \ilAzureADSettings
    {
        if (self::$instance) {
            return self::$instance;
        }
        return self::$instance=new ilAzureADSettings();
    }

    public function create()
    {
        $this->save();
    }

    /**
     * @param bool $update
     */

    public function save()
    {
        global $ilDB;

        // check if data exisits decide to update or insert
        $result = $ilDB->query("SELECT * FROM auth_authhk_azuread");
        $num = $ilDB->numRows($result);

        $a_data=array(
            'active' =>['integer',intval($this->getActive())],
            'provider' => ['string', $this->getProvider()],
            'apikey'   => ['string', $this->getApiKey()],
            'secretkey'   => ['string', $this->getSecretKey()],
            'logout_scope' => ['integer', (int)$this->getLogoutScope()],
            'is_custom_session'=>['integer', intval($this->isCustomSession())],
            'session_duration' =>['integer',$this->getSessionDuration()],
            'role' =>['integer', $this->getRole()],
            'sync_allowed' =>['integer',intval($this->isSyncAllowed())]
        );
        if ($num !== 0) {
            $ilDB->update('auth_authhk_azuread', $a_data, array('id' => array('integer', $this->connection_id)));
        } else {
            $ilDB->insert('auth_authhk_azuread', $a_data);
        }
    }

    
    /**
     * read
     *
     * @return void
     */
    public function read()
    {
        global $ilDB;
        //$values=array()
        $result=$ilDB->query("SELECT * FROM auth_authhk_azuread");
        while ($record=$ilDB->fetchAssoc($result)) {
            $active= boolval($record['active']);
            $this->setActive($active);
            $this->setProvider($record['provider']);
            $this->setSecretKey($record['secretkey']);
            $this->setAPIKey($record['apikey']);
            $this->setLogoutScope($record['logout_scope']);
            $this->useCustomSession( boolval($record['is_custom_session']));
            $this->setSessionDuration($record['session_duration']);
            $this->setRole($record['role']);
            $sync= boolval($record['sync_allowed']);
            $this->syncAllowed($sync);
            $this->connection_id = $record['id'];
            $this->values=$record;
        }
    }
    
    /**
     * getValues
     *
     * @return void
     */
    public function getValues()
    {
        return $this->values;
    }



    /**
     * @param bool $active
     */
    public function setActive(bool $active)
    {
        $this->active = $active;
    }

    /**
     * @return bool
     */
    public function getActive() : bool
    {
        return $this->active;
    }

    /**
     * @param string $url
     */
    public function setProvider(string $url)
    {
        $this->provider = $url;
    }

    /**
     * @return string
     */
    public function getProvider() : string
    {
        return $this->provider;
    }
    /**
     * @deprecated
     * @param string $secret
     */
    public function setSecret(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * @param string $secretkey
     */
    public function setSecretKey($secretkey)
    {
        $this->secretkey = $secretkey;
    }

    /**
     * @deprecated
     * Get secret
     */
    public function getSecret() : string
    {
        return $this->secret;
    }
    /**
     * Get secretkey
     */
    public function getSecretKey() 
    {
        return $this->secretkey;
    }
    /**
     * @param string $api_key
     */
    public function setApiKey(string $apikey)
    {
        $this->apikey = $apikey;
    }
    /**
     * Get api_key
     */
    public function getApiKey() : string
    {
        return $this->apikey;
    }


    /**
     * Set login element type
     */
    public function setLoginElementType(int $type)
    {
        $this->login_element_type = $type;
    }

    /**
     * @return int
     */
    public function getLoginElementType() : int
    {
        return $this->login_element_type;
    }

    /**
     * @param int $a_type
     */
    public function setLoginPromptType(int $a_type)
    {
        $this->login_prompt_type = $a_type;
    }

    /**
     * @return int
     */
    public function getLoginPromptType() : int
    {
        return $this->login_prompt_type;
    }

    /**
     * @param int $a_scope
     */
    public function setLogoutScope(int $a_scope)
    {
        $this->logout_scope = $a_scope;
    }

    /**
     * @return int
     */
    public function getLogoutScope() : int
    {
        return $this->logout_scope;
    }

    /**
     * @param bool $a_stat
     */
    public function useCustomSession(bool $a_stat)
    {
        $this->custom_session = $a_stat;
    }

    /**
     * @return bool
     */
    public function isCustomSession() : bool
    {
        return $this->custom_session;
    }

    /**
     * @param int $a_duration
     */
    public function setSessionDuration(int $a_duration)
    {
        $this->session_duration = $a_duration;
    }

    /**
     * @return int
     */
    public function getSessionDuration() : int
    {
        return $this->session_duration;
    }

    /**
     * @return bool
     */
    public function isSyncAllowed() : bool
    {
        return $this->allow_sync;
    }

    /**
     * @param bool $a_stat
     */
    public function syncAllowed(bool $a_stat)
    {
        $this->allow_sync = $a_stat;
    }

    /**
     * @param int $role
     */
    public function setRole(int $role)
    {
        $this->role = $role;
    }

    /**
     * @return int
     */
    public function getRole() : int
    {
        return $this->role;
    }
    
    /**
     * ilBoolToInt
     *
     * @param  mixed $a_val
     * @return void
     */
    private function ilBoolToInt($a_val)
    {
        if ($a_val == true) {
            return 1;
        }
        return 0;
    }
        
    /**
     * ilIntToBool
     *
     * @param  mixed $a_val
     * @return void
     */
    private function ilIntToBool($a_val)
    {
        if ($a_val == 1) {
            return true;
        }
        return false;
    }

    public function getAllADUsers($load_active_only = true): array
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        //remember to revert to the initial query: retrieveing the ext account from login and ext_account
        $query = 'SELECT usr_id, active, (CASE WHEN ext_account LIKE ' . $ilDB->quote('%@globus.net', 'text') . ' THEN ext_account ELSE login END )AS ext_account FROM usr_data  WHERE ' .
            '  login LIKE ' . $ilDB->quote('%@globus.%', 'text');
        if($load_active_only){
            $query .= ' AND active = '. $ilDB->quote(intval($load_active_only), 'integer');
        }
        $res = $ilDB->query($query);
        $data = null;
        while ($row = $ilDB->fetchAssoc($res)) {
            $data [] = array(
                'usr_id' => $row['usr_id'],
                'ext_account' => $row['ext_account'],
                'active' => $row['active']
            );
        }
        return $data;
    }



    
    /**
     * load
     *
     * @return void
     */
    protected function load()
    {
        $this->read(); //for code compatibility: remove later
    }
}
