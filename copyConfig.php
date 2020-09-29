<?php

require_once __DIR__ . "/../vendor/autoload.php";

//use srag\Plugins\__AzureAD__\Config\ConfigFormGUI;
//use srag\DIC\AzureAD\DICTrait;

/**
 * Class ilAzureADConfigGUI
 *
 * @author Minervis Gmbg <ilias-service@minervis.com>
 */
class ilAzureADConfigGUI extends ilPluginConfigGUI
{

    use DICTrait;
    const PLUGIN_CLASS_NAME = ilAzureADPlugin::class;
    const CMD_CONFIGURE = "configure";
    const CMD_UPDATE_CONFIGURE = "updateConfigure";
    const LANG_MODULE = "config";
    const TAB_CONFIGURATION = "configuration";


    /**
     * ilAzureADConfigGUI constructor
     */
    public function __construct()
    {

    }

    protected $plugin;
    /** @var ilCtrl */
    protected $ctrl;
    /** @var ilLanguage */
    protected $lng;
    /** @var ilTemplate */
    protected $tpl;
    /** @var ilSetting */
    protected $settings;

    /**
     * @return void
     */
    private function init() {
        global $DIC;

        $this->plugin = $this->getPluginObject();
        $this->settings = $this->plugin->getSettings();
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->tpl = $DIC["tpl"];
    }


    /**
     * @inheritDoc
     */
    public function performCommand(/*string*/ $cmd)/*:void*/
    {
        $this->setTabs();

        $next_class = self::dic()->ctrl()->getNextClass($this);

        switch (strtolower($next_class)) {
            default:
                $cmd = self::dic()->ctrl()->getCmd();

                switch ($cmd) {
                    case self::CMD_CONFIGURE:
                    case self::CMD_UPDATE_CONFIGURE:
                        $this->{$cmd}();
                        break;

                    default:
                        break;
                }
                break;
        }
    }

    /**
     * Calls the default save method which works with ilSetting
     * Overwrite this method if setting data on custom Settings Model or if custom behaviour is needed
     */
    public function save() {
        $this->_default_save($this->getConfigurationForm());
    }

    /**
     * Calls getConfigurationForm() and dynamically saves values from inputs
     * @param $form
     * @return void
     */
    public function _default_save($form) {

        if ($form->checkInput()) {

            foreach ($form->getInputItemsRecursive() as $form_input) {
                if ($form_input === null) {
                    continue;
                }
                $post_var = $form_input->getPostVar();
                if ($form_input instanceof ilCheckboxInputGUI) {
                    $value = ($form->getInput($post_var) == true);
                } elseif (is_array($form->getInput($post_var))) {
                    $value = json_encode($form->getInput($post_var));
                } else {
                    $value = $form->getInput($post_var);
                }
                $this->plugin->getSettings()->set($post_var, $value);
            }

            ilUtil::sendSuccess($this->txt("saving_invoked"), true);
            $this->ctrl->redirect($this, "configure");

        } else {
            $form->setValuesByPost();
            $this->tpl->setContent($form->getHtml());
        }
    }

    /**
     *
     */
    protected function setTabs()/*: void*/
    {
        self::dic()->tabs()->addTab(self::TAB_CONFIGURATION, self::plugin()->translate("configuration", self::LANG_MODULE), self::dic()->ctrl()
            ->getLinkTargetByClass(self::class, self::CMD_CONFIGURE));

        self::dic()->locator()->addItem(ilAzureADPlugin::PLUGIN_NAME, self::dic()->ctrl()->getLinkTarget($this, self::CMD_CONFIGURE));
    }

    /**
     * Show the Configuration Form
     */
    public function showConfigurationForm()
    {
        $this->tpl->setContent($this->getConfigurationForm()->getHTML());
    }

    /**
     * Return the Plugin configuration Form
     *
     * @return ilPropertyFormGUI
     */
    private function getConfigurationForm()
    {
        $form = new ilPropertyFormGUI();

        $tig = new ilTextInputGUI($this->plugin->txt("phoenics_wsl_uri"), "phoenics_wsl_uri");
        $tig->setInfo($this->plugin->txt("phoenics_wsl_uri_info"));
        $tig->setRequired(true);
        $tig->setValue($this->plugin->getSettings()->get("phoenics_wsl_uri"));
        $form->addItem($tig);

        $tig = new ilTextInputGUI($this->plugin->txt("proxy_host"), "proxy_host");
        $tig->setInfo($this->plugin->txt("proxy_host_info"));
        $tig->setValue($this->plugin->getSettings()->get("proxy_host"));
        $form->addItem($tig);

        $tig = new ilNumberInputGUI($this->plugin->txt("proxy_port"), "proxy_port");
        $tig->setInfo($this->plugin->txt("proxy_port_info"));
        $tig->setValue($this->plugin->getSettings()->get("proxy_port"));
        $form->addItem($tig);

        // Setup Form
        $form->setTitle($this->txt("configuration"));
        $form->setDescription($this->txt("configuration_description"));
        $form->addCommandButton("save", $this->txt("save"));
        $form->setFormAction($this->ctrl->getFormAction($this));

        return $form;
    }




    /**
     * Show the Configuration Form
     * @return void
     */
    protected function configure() {
        $form = $this->getConfigurationForm();
        $this->tpl->setContent($form->getHTML());
    }



    /**
     *
     */
    protected function updateConfigure()/*: void*/
    {
        //self::dic()->tabs()->activateTab(self::TAB_CONFIGURATION);

        $form = $this->getConfigurationForm();
       

        if (!$form->storeForm()) {
            $this->tpl->setContent($form->getHTML());
            return;
        }

        ilUtil::sendSuccess("saved successfully", true);

        self::dic()->ctrl()->redirect($this, self::CMD_CONFIGURE);
    }
    /**
     * @param $a_var
     * @return string
     */
    private function txt($a_var) {
        return $this->plugin->txt($a_var);
    }

}
