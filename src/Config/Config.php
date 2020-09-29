<?php

namespace srag\Plugins\__AzureAD__\Config;

use ilAzureADPlugin;
use srag\ActiveRecordConfig\AzureAD\ActiveRecordConfig;

/**
 * Class Config
 *
 *
 * @package srag\Plugins\__AzureAD__\Config
 *
 * @author  Minervis Gmbg <ilias-service@minervis.com>
 */
class Config extends ActiveRecordConfig
{

    const TABLE_NAME = "ui_uihk_azuread_config";
    const PLUGIN_CLASS_NAME = ilAzureADPlugin::class;

    const LOGIN_ELEMENT_TYPE_TXT = false;
    const LOGIN_ELEMENT_TYPE_IMG = true;

    const LOGIN_ENFORCE = false;
    const LOGIN_STANDARD = true;

    const LOGOUT_SCOPE_GLOBAL = false;
    const LOGOUT_SCOPE_LOCAL = true;

        /**
     * @var self|null
     */
    protected static $instance = null;

    /**
     * @var array
     */
    protected static $fields
        = [
    /*        "active" =>             self::TYPE_BOOLEAN,
            "provider"=>            self::TYPE_STRING,
            "secret"=>              self::TYPE_STRING,
            "login_element_type"=>  self::TYPE_INTEGER,
            "login_prompt_type"=>  self::TYPE_INTEGER,
            "logout_scope" =>      self::TYPE_INTEGER,
            "custom_session"=>      self::TYPE_BOOLEAN,
            "session_duration"=>    self::TYPE_INTEGER,
            "allow_sync"=>          self::TYPE_BOOLEAN,
            "role"=>                self::TYPE_INTEGER,
            "uid"=>                self::TYPE_STRING,*/
            "active" =>            [ self::TYPE_BOOLEAN, false],
            "provider"=>            [self::TYPE_STRING,""],
            "secret"=>              [self::TYPE_STRING, ""],
            "login_element_type"=>  [self::TYPE_BOOLEAN,self::LOGIN_ELEMENT_TYPE_TXT],
            "login_prompt_type"=>  [self::TYPE_BOOLEAN,self::LOGIN_ENFORCE],
            "logout_scope" =>      [self::TYPE_BOOLEAN,self::LOGOUT_SCOPE_LOCAL],
            "custom_session"=>      [self::TYPE_BOOLEAN,false],
            "session_duration"=>    [self::TYPE_INTEGER,60],
            "allow_sync"=>          [self::TYPE_BOOLEAN, true],
            "role"=>                [self::TYPE_INTEGER,0],
            "uid"=>                [self::TYPE_STRING,0],
        ];
    // TODO: Implement Config

    /**
     * Get singleton instance
     * @return \Config
     */
    public static function getInstance() : self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return new self::$instance;
    }   

    /**
     * @inheritDoc
     *
     * @deprecated
     */
    public static function returnDbTableName() : string
    {
        return self::TABLE_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getConnectorContainerName() : string
    {
        return static::TABLE_NAME;
    }
    /**
     * @inheritDoc
     */
   public static function getFields() : array
    {
        return [
            "active" =>            [ self::TYPE_BOOLEAN, false],
            "provider"=>            [self::TYPE_STRING,""],
            "secret"=>              [self::TYPE_STRING, ""],
            "login_element_type"=>  [self::TYPE_BOOLEAN,LOGIN_ELEMENT_TYPE_TXT],
            "login_prompt_type"=>  [self::TYPE_BOOLEAN,self::LOGIN_ENFORCE],
            "logout_scope" =>      [self::TYPE_BOOLEAN,self::LOGOUT_SCOPE_LOCAL],
            "custom_session"=>      [self::TYPE_BOOLEAN,false],
            "session_duration"=>    [self::TYPE_INTEGER,60],
            "allow_sync"=>          [self::TYPE_BOOLEAN, true],
            "role"=>                [self::TYPE_INTEGER,0],
            "uid"=>                [self::TYPE_STRING,0],
        ];
    }
    
    // /**
    //  * @var int
    //  *
    //  * @con_has_field   true
    //  * @con_fieldtype   integer
    //  * @con_length      8
    //  * @con_is_notnull  true
    //  */
    // private $active = 0;

    // /**
    //  * @var string
    //  *
    //  * @con_has_field   true
    //  * @con_fieldtype   text
    //  * @con_length      256
    //  * @con_is_notnull  true
    //  */
    // private $provider = '';
    // /**
    //  * @var string
    //  *
    //  * @con_has_field   true
    //  * @con_fieldtype   text
    //  * @con_length      256
    //  * @con_is_notnull  true
    //  */
    // private $secret = '';
    // /**
    //  * @var int
    //  *
    //  * @con_has_field   true
    //  * @con_fieldtype   integer
    //  * @con_length      8
    //  * @con_is_notnull  true
    //  */
    // private $login_element_type = self::LOGIN_ELEMENT_TYPE_TXT;
    // /**
    //  * @var int
    //  *
    //  * @con_has_field   true
    //  * @con_fieldtype   integer
    //  * @con_length      8
    //  * @con_is_notnull  true
    //  */
    // private $login_prompt_type = self::LOGIN_ENFORCE;
    // /**
    //  * @var int
    //  *
    //  * @con_has_field   true
    //  * @con_fieldtype   integer
    //  * @con_length      8
    //  * @con_is_notnull  true
    //  */
    // private $logout_scope;
    // /**
    //  * @var int
    //  *
    //  * @con_has_field   true
    //  * @con_fieldtype   integer
    //  * @con_length      8
    //  * @con_is_notnull  true
    //  */
    // private $custom_session = false;

    // /**
    //  * @var int
    //  *
    //  * @con_has_field   true
    //  * @con_fieldtype   integer
    //  * @con_length      8
    //  * @con_is_notnull  true
    //  */
    // private $session_duration = 60;
    // /**
    //  * @var int
    //  *
    //  * @con_has_field   true
    //  * @con_fieldtype   integer
    //  * @con_length      8
    //  * @con_is_notnull  true
    //  */
    // private $allow_sync;

    // /**
    //  * @var int
    //  *
    //  * @con_has_field   true
    //  * @con_fieldtype   integer
    //  * @con_length      8
    //  * @con_is_notnull  true
    //  */
    // private $role;

    // /**
    //  * @var string
    //  *
    //  * @con_has_field   true
    //  * @con_fieldtype   text
    //  * @con_length      256
    //  * @con_is_notnull  true
    //  */
    // private $uid = '';

    // /**
    //  * @var array
    //  */
    // private $profile_map = [];

    // /**
    //  * @var array
    //  */
    // private $profile_update_map = [];

    // /**
    //  * @var array
    //  */
    // private $role_mappings = [];




    // /**
    //  * @param int $active
    //  */
    // public function setActive(int $active)
    // {
    //     $this->active = $active;
    // }

    // /**
    //  * @return int
    //  */
    // public function getActive() : int
    // {
    //     return $this->active;
    // }

    // /**
    //  * @param string $url
    //  */
    // public function setProvider(string $url)
    // {
    //     $this->provider = $url;
    // }

    // /**
    //  * @return string
    //  */
    // public function getProvider() : string
    // {
    //     return $this->provider;
    // }



    // /**
    //  * @param string $secret
    //  */
    // public function setSecret(string $secret)
    // {
    //     $this->secret = $secret;
    // }

    // /**
    //  * Get secret
    //  */
    // public function getSecret() : string
    // {
    //     return $this->secret;
    // }

    // /**
    //  * Set login element type
    //  */
    // public function setLoginElementType(int $type)
    // {
    //     $this->login_element_type = $type;
    // }

    // /**
    //  * @return int
    //  */
    // public function getLoginElementType() : int
    // {
    //     return $this->login_element_type;
    // }





    // /**
    //  * @param int $a_type
    //  */
    // public function setLoginPromptType(int $a_type)
    // {
    //     $this->login_prompt_type = $a_type;
    // }

    // /**
    //  * @return int
    //  */
    // public function getLoginPromptType() : int
    // {
    //     return $this->login_prompt_type;
    // }

    // /**
    //  * @param int $a_scope
    //  */
    // public function setLogoutScope(int $a_scope)
    // {
    //     $this->logout_scope = $a_scope;
    // }

    // /**
    //  * @return int
    //  */
    // public function getLogoutScope() : int
    // {
    //     return $this->logout_scope;
    // }

    // /**
    //  * @param int $a_stat
    //  */
    // public function useCustomSession(int $a_stat)
    // {
    //     $this->custom_session = $a_stat;
    // }

    // /**
    //  * @return int
    //  */
    // public function isCustomSession() : int
    // {
    //     return $this->custom_session;
    // }

    // /**
    //  * @param int $a_duration
    //  */
    // public function setSessionDuration(int $a_duration)
    // {
    //     $this->session_duration = $a_duration;
    // }

    // /**
    //  * @return int
    //  */
    // public function getSessionDuration() : int
    // {
    //     return $this->session_duration;
    // }

    // /**
    //  * @return int
    //  */
    // public function isSyncAllowed() : int
    // {
    //     return $this->allow_sync;
    // }

    // /**
    //  * @param int $a_stat
    //  */
    // public function allowSync(int $a_stat)
    // {
    //     $this->allow_sync = $a_stat;
    // }

    // /**
    //  * @param int $role
    //  */
    // public function setRole(int $role)
    // {
    //     $this->role = $role;
    // }

    // /**
    //  * @return int
    //  */
    // public function getRole() : int
    // {
    //     return $this->role;
    // }

    // /**
    //  * @param string $field
    //  */
    // public function setUid(string $field)
    // {
    //     $this->uid = $field;
    // }

    // /**
    //  * @return string
    //  */
    // public function getUid() : string
    // {
    //     return $this->uid;
    // }
}
