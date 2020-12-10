<?php

require_once "Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADSettings.php";
/**
 * Class ilAzureADConfigGUI
 *
 * @author Jephte Abijuru <jephte.abijuru@minervis.com>
 */
class ilAzureADConfigGUI extends ilPluginConfigGUI
{

    const PLUGIN_CLASS_NAME = ilAzureADPlugin::class;
    const CMD_CONFIGURE = "configure";
    const CMD_UPDATE_CONFIGURE = "updateConfigure";
    const LANG_MODULE = "config";
    const TAB_CONFIGURATION = "configuration";
    const CMD_SAVE = "save";
    /**
     * @var \ilLogger
     */
    protected $logger;

    /**
     * @var \ilAzureADSettings
     */
    private $settings = null;
    /**
     * @var ilRbacReview
     */
    protected $review;

    /**
     * @var ilAccessHandler|null
     */
    protected $access = null;    
    
    /**
     * @var ilPropertyFormGUI
     */
    private $form;

    /**
     * ilAzureADConfigGUI constructor
     */
    
    public function __construct()
    {
        global $DIC;
        $this->access = $DIC->access();
        $this->review = $DIC->rbac()->review();
        $this->settings = ilAzureADSettings::getInstance();
    }


    /**
     * @inheritDoc
     */
    public function performCommand(/*string*/ $cmd)/*:void*/
    {
        global $DIC;
        $ilCtrl=$DIC->ctrl();
        $this->setTabs();

        $next_class = $ilCtrl->getNextClass($this);

        switch (strtolower($next_class)) {
            default:
                $cmd = $ilCtrl->getCmd();

                switch ($cmd) {
                    case self::CMD_CONFIGURE:
                    case self::CMD_UPDATE_CONFIGURE:
                    case self::CMD_SAVE:
                        $this->{$cmd}();
                        break;

                    default:
                        break;
                }
                break;
        }
    }


    /**
     *
     */
    protected function setTabs()/*: void*/
    {
        global $DIC; /** @var Container $DIC */
        $ilCtrl=$DIC->ctrl();
        $ilTabs=$DIC->tabs();

        $ilTabs->addTab(self::TAB_CONFIGURATION, 
                $this->plugin_object->txt("configuration"), 
                $ilCtrl->getLinkTarget($this, self::CMD_CONFIGURE)
        );
    }


    /**
     *
     */
    protected function configure()/*: void*/
    {
        global $DIC; /** @var Container $DIC */
        $tpl=$DIC->ui()->mainTemplate();
        $this->form = $this->initConfigurationForm();
      
       $this->getValues();


        $tpl->setContent($this->form->getHTML());
        
    }
	/**
	 * Init configuration form.
	 *
	 * @return object form object
	 */
	public function initConfigurationForm()
	{
		global $lng, $ilCtrl, $ilDB,$DIC;
				 
		$values = array();
		$result = $ilDB->query("SELECT * FROM auth_authhk_azuread");
		while ($record = $ilDB->fetchAssoc($result))
		{
	        $values[]=$record;
        }
		
        $pl=$this->getPluginObject();
        
        
        include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
        $this->form=new ilPropertyFormGUI();
        

        $is_active=new ilCheckboxInputGUI($pl->txt("active"), "active");
        $is_active->setRequired(false);
        $is_active->setChecked( (bool) $values['active']);
        $this->form->addItem($is_active);


        $provider=new ilTextInputGUI($pl->txt("provider"), "provider");
        $provider->setRequired(true);
		$provider->setMaxLength(256);
		$provider->setSize(60);
		$provider->setValue($values["provider"]);
        $this->form->addItem($provider);
        

        $secret=new ilPasswordInputGUI($pl->txt("secret"),"secret");
        $secret->setRequired(true);
		$secret->setMaxLength(256);
		$secret->setSize(6);
		//$secret->setInfo($pl->txt("secret"));
		$secret->setRetype(false);
        $this->form->addItem($secret);
        

        $logout_scope= new ilRadioGroupInputGUI($pl->txt("logout_scope"), "logout_scope");
        $logout_scope->setRequired(false);
        $logout_scope->setValue((int)$values['logout_scope']);

        // scope global
        $global_scope = new ilRadioOption( $pl->txt('logout_scope_global'), ilAzureADSettings::LOGOUT_SCOPE_GLOBAL);
        //$global_scope->setInfo($pl->txt('logout_scope_global_info'));
        $logout_scope->addOption($global_scope);

        // ilias scope
        $ilias_scope = new ilRadioOption( $pl->txt('logout_scope_ilias'), ilAzureADSettings::LOGOUT_SCOPE_LOCAL);
        //$global_scope->setInfo($pl->txt('logout_scope_ilias_info'));
        $logout_scope->addOption($ilias_scope);
        $this->form->addItem($logout_scope);


        $use_custom_session = new ilCheckboxInputGUI($pl->txt("is_custom_session"),"is_custom_session");
        $use_custom_session->setOptionTitle($pl->txt("is_custom_session_option"));
        $use_custom_session->setRequired(false);
        //$use_custom_session->setInfo($pl->txt("is_custom_session"));
        $use_custom_session->setChecked((int)$values['is_custom_session']);
        $this->form->addItem($use_custom_session);
        

        $session = new ilNumberInputGUI($pl->txt("session_duration"), "session_duration");
        $session->setRequired(false);
        $session->setSuffix($pl->txt('minutes'));
        $session->setMinValue(5);
        $session->setMaxValue(1440);
        $session->setValue((int) $values['session_duration']);
        $use_custom_session->addSubItem($session);


        $roles = new ilSelectInputGUI($pl->txt("role"),"role");
        $roles->setValue( (int) $values['role']);
        //$roles->setInfo($this->lng->txt('auth_oidc_settings_default_role_info'));
        $roles->setOptions($this->prepareRoleSelection());
        $roles->setRequired(false);
        $this->form->addItem($roles);


        $cb = new ilCheckboxInputGUI($pl->txt("sync_allowed"), "sync_allowed");
        $cb->setRequired(false);
        $cb->setValue( (int) $values['sync_allowed']);
		//$cb->setInfo($pl->txt("sync_allowed"));
        $this->form->addItem($cb);
	
		$this->form->addCommandButton("save", $lng->txt("save"));
	                
		$this->form->setTitle($pl->txt("configuration"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
		
		return $this->form;
    }
    
 
    /**
     *
     * @return void
     */
    public function save()
    {
        global $DIC;
        $lng=$DIC->language();
        $ilCtrl=$DIC->ctrl();

        $pl=$this->getPluginObject();

        $this->form=$this->initConfigurationForm();
        if($this->form->checkInput()){
            $this->settings->setActive((int)$this->form->getInput("active"));
            $this->settings->setProvider($this->form->getInput("provider"));
            $this->settings->setSecret($this->form->getInput("secret"));
            $this->settings->setLogoutScope((int)$this->form->getInput("logout_scope"));
            $this->settings->useCustomSession((bool)$this->form->getInput("is_custom_session"));
            $this->settings->setSessionDuration($this->form->getInput("session_duration"));
            $this->settings->setRole($this->form->getInput("role"));
            $this->settings->syncAllowed((int)$this->form->getInput("sync_allowed"));
        }
        $this->settings->save();
        ilUtil::sendSuccess($pl->txt("saving_invoked"), true);
		$ilCtrl->redirect($this, "configure");
    }



    /**
     * @param bool $a_with_select_option
     * @return mixed
     */
    private function prepareRoleSelection($a_with_select_option = true) : array
    {
        $global_roles = ilUtil::_sortIds(
            $this->review->getGlobalRoles(),
            'object_data',
            'title',
            'obj_id'
        );

        $select = [];
        if ($a_with_select_option) {
            $select[0] = $this->getPluginObject()->txt('links_select_one');
        }
        foreach ($global_roles as $role_id) {
            if ($role_id == ANONYMOUS_ROLE_ID) {
                continue;
            }
            $select[$role_id] = ilObject::_lookupTitle($role_id);
        }
        return $select;
    }

    public function getValues(){
        $values['active']=$this->settings->getActive();
        $values['provider']=$this->settings->getProvider();
        $values['secret']=$this->settings->getSecret();
        $values['logout_scope']=$this->settings->getLogoutScope();
        $values['is_custom_session']=$this->settings->isCustomSession();
        $values['session_duration']=$this->settings->getSessionDuration();
        $values['role']=$this->settings->getRole();
        $values['sync_allowed']=$this->settings->isSyncAllowed();
        $this->form->setValuesByArray($values);
        
    }



}
