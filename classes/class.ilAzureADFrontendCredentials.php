<?php

require_once "Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADSettings.php";

/**
 * Class ilAzureADFrontendCredentials
 *
 * @author Jephte Abijuru <jephte.abijuru@minervis.com>
 */
class ilAzureADFrontendCredentials extends ilAuthFrontendCredentials implements ilAuthCredentials
{
    const SESSION_TARGET = 'azure_target';


    private $settings = null;

    /**
     * @var string
     */
    private $target = null;


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->settings = ilAzureADSettings::getInstance();
    }


    /**
     * @return \ilSetting
     */
    protected function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return string
     */
    public function getRedirectionTarget()
    {
        return $this->target;
    }

    /**
     * Init credentials from request
     */
    public function initFromRequest()
    {
        $this->setUsername('');
        $this->setPassword('');

        $this->parseRedirectionTarget();
    }

    /**
     *
     */
    protected function parseRedirectionTarget()
    {
        global $DIC;

        $logger = $DIC->logger()->auth();
        if (!empty($_GET['target'])) {
            $this->target = $_GET['target'];
            \ilSession::set(self::SESSION_TARGET, $this->target);
        } elseif (ilSession::get(self::SESSION_TARGET)) {
            $this->target = \ilSession::get(self::SESSION_TARGET);
        }
    }
}
