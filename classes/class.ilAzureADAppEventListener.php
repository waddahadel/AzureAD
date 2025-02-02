<?php

/**
 * event listener
 *
 * @author Jephte Abijuru <jephte.abijuru@minervis.com>
 */
class ilAzureADAppEventListener
{
    /**
     * @var ilLogger|null
     */
    private $logger = null;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logger = ilLoggerFactory::getInstance()->getLogger('auth');
    }

    /**
     * @param int $user_id
     */
    protected function handleLogoutFor(int $user_id)
    {
        $provider = new ilAzureADProvider(new ilAuthFrontendCredentials());
        $provider->handleLogout();
    }
    

    /**
    * Handle an event in a listener.
    *
    * @param	string	$a_component	component, e.g. "Modules/Forum" or "Services/User"
    * @param	string	$a_event		event e.g. "createUser", "updateUser", "deleteUser", ...
    * @param	array	$a_parameter	parameter array (assoc), array("name" => ..., "phone_office" => ...)
    */
    public static function handleEvent($a_component, $a_event, $a_parameter)
    {
        ilLoggerFactory::getLogger('root')->info($a_component . ' : ' . $a_event);
        if ($a_component == 'Services/Authentication') {
            ilLoggerFactory::getLogger('root')->info($a_component . ' : ' . $a_event);
            if ($a_event == 'beforeLogout') {
                ilLoggerFactory::getLogger('root')->info($a_component . ' : ' . $a_event);
                $listener = new self();
                $listener->handleLogoutFor($a_parameter['user_id']);
            }
        }
    }
}
