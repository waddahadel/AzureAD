<?php
require_once __DIR__ . "/../vendor/autoload.php";

use minervis\plugins\AzureAD\Status\StatusLogsGUI;
use minervis\plugins\AzureAD\Status\StatusLogsTableGUI;
use minervis\plugins\AzureAD\Utils\AzureADTrait;

require_once "Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADSettings.php";

/**
 * Class ilAzureADConfigGUI
 *
 * @author Jephte Abijuru <jephte.abijuru@minervis.com>
 */
class ilAzureADConfigGUI extends ilPluginConfigGUI
{
    const PLUGIN_CLASS_NAME = ilAzureADPlugin::class;
    use AzureADTrait;
    const CMD_CONFIGURE = "configure";
    const CMD_UPDATE_CONFIGURE = "updateConfigure";
    const LANG_MODULE = "config";
    const TAB_CONFIGURATION = "configuration";
    const CMD_SAVE = "save";
    const CMD_SYNCHRONIZE = "synchronize";
    const CMD_RETRIEVE_USERDATA = "retrieveUsers";
    const CMD_SHOW_LOGS = "showLogs";
    const CMD_STATUS = "status";
    const CMD_LOGS_APPLY_FILTER = "applyFilter";
    const CMD_LOGS_RESET_FILTER = "resetFilter";
    const CMD_LOGS_INDEX = "index";
    const SUBTAB_SETTINGS = "settings";
    const SUBTAB_STATUS = "status";
    const TAB_LOGS = "logs";
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
     * @var self|null
     */
    protected static $instance = null;
    /**
     * @var \ILIAS\DI\Container|mixed
     */
    private  $dic;
    private ilTabsGUI $tabs;

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
     * ilAzureADConfigGUI constructor
     */
    
    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->ui = $DIC->ui();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->tabs = $DIC->tabs();
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
            case  strtolower(StatusLogsGUI::class):
                $logs_gui = new StatusLogsGUI();
                $DIC->ctrl()->forwardCommand($logs_gui);
            default:
                $cmd = $ilCtrl->getCmd();

                switch ($cmd) {
                    case self::CMD_CONFIGURE:
                    case self::CMD_UPDATE_CONFIGURE:
                    case self::CMD_SAVE:
                    case self::CMD_SYNCHRONIZE:
                    case self::CMD_STATUS:
                    case self::CMD_RETRIEVE_USERDATA:
                    case self::CMD_SHOW_LOGS:
                    case self::CMD_LOGS_INDEX:
                    case self::CMD_LOGS_APPLY_FILTER:
                    case self::CMD_LOGS_RESET_FILTER:
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

        $ilTabs->addTab(
            self::TAB_CONFIGURATION,
            $this->plugin_object->txt("configuration"),
            $ilCtrl->getLinkTarget($this, self::CMD_CONFIGURE)
        );
        $DIC->tabs()->addSubTab(self::SUBTAB_SETTINGS, $this->getPluginObject()->txt("tab_general_settings"), $DIC->ctrl()
            ->getLinkTarget($this, self::CMD_CONFIGURE));

        $DIC->tabs()->addSubTab(self::SUBTAB_STATUS, $this->getPluginObject()->txt("tab_status"), $DIC->ctrl()
            ->getLinkTarget($this, self::SUBTAB_STATUS));
        $DIC->tabs()->addSubTab(self::TAB_LOGS, $this->getPluginObject()->txt("status_logs"), $DIC->ctrl()
            ->getLinkTarget($this, self::CMD_LOGS_INDEX));
        /*$DIC->tabs()->addTab(StatusLogsGUI::TAB_STATUS_LOGS, $this->getPluginObject()->txt("logs"), $DIC->ctrl()
            ->getLinkTargetByClass(StatusLogsGUI::class, StatusLogsGUI::CMD_INDEX));*/
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
    protected function showLogs()
    {
        global $DIC; /** @var Container $DIC */
        $tpl = $DIC->ui()->mainTemplate();
        $tpl->setContent(self::status()->factory()->getLogsTableGUI($this->plugin_object, $this, "")->getHTML());
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
        while ($record = $ilDB->fetchAssoc($result)) {
            $values[]=$record;
        }
        
        $pl=$this->getPluginObject();
        
        $DIC->toolbar()->setFormAction($DIC->ctrl()->getFormAction($this));

        $button = ilSubmitButton::getInstance();
        $button->setCaption($pl->txt('run_sync'), false);
        $button->setCommand(self::CMD_SYNCHRONIZE);
        $DIC->toolbar()->addButtonInstance($button);

        $button = ilSubmitButton::getInstance();
        $button->setCaption('synchronize user data', false);
        $button->setCommand(self::CMD_RETRIEVE_USERDATA);
        $DIC->toolbar()->addButtonInstance($button);
        
        include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
        $this->form=new ilPropertyFormGUI();
        

        $is_active=new ilCheckboxInputGUI($pl->txt("active"), "active");
        $is_active->setRequired(false);
        $is_active->setChecked((bool) $values['active']);
        $this->form->addItem($is_active);


        $provider=new ilTextInputGUI($pl->txt("provider"), "provider");
        $provider->setRequired(true);
        $provider->setMaxLength(256);
        $provider->setSize(60);
        $provider->setValue($values["provider"]);
        $this->form->addItem($provider);
        

        $apikey=new ilPasswordInputGUI($pl->txt("apikey"), "apikey");
        $apikey->setRequired(true);
        $apikey->setMaxLength(256);
        $apikey->setSize(6);
        //$apikey->setInfo($pl->txt("apikey_info"));
        $apikey->setRetype(false);
        $this->form->addItem($apikey);

        $secretkey=new ilPasswordInputGUI($pl->txt("secretkey"), "secretkey");
        $secretkey->setRequired(true);
        $secretkey->setMaxLength(256);
        $secretkey->setSize(6);
        //$secretkey->setInfo($pl->txt("secretkey_info"));
        $secretkey->setRetype(false);
        $this->form->addItem($secretkey);
        

        $logout_scope= new ilRadioGroupInputGUI($pl->txt("logout_scope"), "logout_scope");
        $logout_scope->setRequired(false);
        $logout_scope->setValue((int)$values['logout_scope']);

        // scope global
        $global_scope = new ilRadioOption($pl->txt('logout_scope_global'), ilAzureADSettings::LOGOUT_SCOPE_GLOBAL);
        //$global_scope->setInfo($pl->txt('logout_scope_global_info'));
        $logout_scope->addOption($global_scope);

        // ilias scope
        $ilias_scope = new ilRadioOption($pl->txt('logout_scope_ilias'), ilAzureADSettings::LOGOUT_SCOPE_LOCAL);
        //$global_scope->setInfo($pl->txt('logout_scope_ilias_info'));
        $logout_scope->addOption($ilias_scope);
        $this->form->addItem($logout_scope);


        $use_custom_session = new ilCheckboxInputGUI($pl->txt("is_custom_session"), "is_custom_session");
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


        $roles = new ilSelectInputGUI($pl->txt("role"), "role");
        $roles->setValue((int) $values['role']);
        //$roles->setInfo($this->lng->txt('auth_oidc_settings_default_role_info'));
        $roles->setOptions($this->prepareRoleSelection());
        $roles->setRequired(false);
        $this->form->addItem($roles);


        $cb = new ilCheckboxInputGUI($pl->txt("sync_allowed"), "sync_allowed");
        $cb->setRequired(false);
        $is_active->setChecked((bool) $values['sync_allowed']);
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
        if ($this->form->checkInput()) {
            $this->settings->setActive((int)$this->form->getInput("active"));
            $this->settings->setProvider($this->form->getInput("provider"));
            $this->settings->setApiKey($this->form->getInput("apikey"));
            $this->settings->setSecretKey($this->form->getInput("secretkey"));
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

    public function getValues()
    {
        $values['active']=$this->settings->getActive();
        $values['provider']=$this->settings->getProvider();
        $values['secretkey']=$this->settings->getSecretKey();
        $values['apikey']=$this->settings->getApiKey();
        $values['logout_scope']=$this->settings->getLogoutScope();
        $values['is_custom_session']=$this->settings->isCustomSession();
        $values['session_duration']=$this->settings->getSessionDuration();
        $values['role']=$this->settings->getRole();
        $values['sync_allowed']=$this->settings->isSyncAllowed();
        $this->form->setValuesByArray($values);
    }

    public function synchronize()
    {
        global $ilCtrl;

        include_once("Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADCron.php");
        $job = new ilAzureADCron();
        $results =  $job->run();
        $ilCtrl->redirect($this, "configure");

    }
    public function retrieveUsers()
    {
        global $ilCtrl;

        include_once("Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADCronSyncUserData.php");
        $job = new ilAzureADCronSyncUserData();
        $results =  $job->run();
        $ilCtrl->redirect($this, "configure");

    }

    public function applyFilter(): void
    {
        $table = $this->getLogsTable(self::CMD_LOGS_APPLY_FILTER);
        $table->writeFilterToSession();
        $table->resetOffset();
        $this->index();

    }
    public function resetFilter(): void
    {
        $table = $this->getLogsTable(self::CMD_LOGS_RESET_FILTER);
        $table->resetFilter();
        $table->resetOffset();
        $this->index();

    }
    public  function index(): void
    {
        $this->tabs->activateSubTab(self::TAB_LOGS);
        $table = $this->getLogsTable();
        $this->tpl->setContent($table->getHTML());

    }
    private function getLogsTable($cmd = self::CMD_LOGS_INDEX): StatusLogsTableGUI
    {
        return self::status()->factory()->getLogsTableGUI($this->getPluginObject(), $this, $cmd);
    }
}
