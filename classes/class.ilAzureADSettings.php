<?php
/**
 * Class ilAzureADSettings
 *
 * @author Shaharyar Ali Bhatti <shaharyar.bhatti@minervis.com>
 *
 *
 */
class ilAzureADSettings{
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
    private $secret = '';
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
    private $logout_scope;
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
    private $allow_sync;

    /**
     * @var int
     */
    private $role;

    /**
     * ilAzureADSettings constructor.
     */
    private function __construct()
    {
        global $DIC;

        $this->storage = new ilSetting(self::STORAGE_ID);
        $this->filesystem = $DIC->filesystem()->web();
        $this->load();
    }

    /**
     * Get singleton instance
     * @return \ilAzureADSettings
     */
    public static function getInstance() : \ilAzureADSettings
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return new self::$instance;
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
     * @param string $secret
     */
    public function setSecret(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * Get secret
     */
    public function getSecret() : string
    {
        return $this->secret;
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
    public function allowSync(bool $a_stat)
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
     * Save in settings
     */
    public function save()
    {
        $this->storage->set('active', (int) $this->getActive());
        $this->storage->set('provider', $this->getProvider());
        $this->storage->set('secret', $this->getSecret());
        $this->storage->set('le_type', $this->getLoginElementType());
        $this->storage->set('prompt_type', $this->getLoginPromptType());
        $this->storage->set('logout_scope', $this->getLogoutScope());
        $this->storage->set('custom_session', (int) $this->isCustomSession());
        $this->storage->set('session_duration', (int) $this->getSessionDuration());
        $this->storage->set('allow_sync', (int) $this->isSyncAllowed());
        $this->storage->set('role', (int) $this->getRole());

    }

    /**
     * Load from settings
     */
    protected function load()
    {
        $this->setActive((bool) $this->storage->get('active', 0));
        $this->setProvider($this->storage->get('provider', ''));
        $this->setSecret($this->storage->get('secret', ''));
        $this->setLoginElementType($this->storage->get('le_type'));
        $this->setLoginPromptType((int) $this->storage->get('prompt_type', self::LOGIN_ENFORCE));
        $this->setLogoutScope((int) $this->storage->get('logout_scope', self::LOGOUT_SCOPE_GLOBAL));
        $this->useCustomSession((bool) $this->storage->get('custom_session'), false);
        $this->setSessionDuration((int) $this->storage->get('session_duration', 60));
        $this->allowSync((bool) $this->storage->get('allow_sync'), false);
        $this->setRole((int) $this->storage->get('role'), 0);
    }


}
?>
