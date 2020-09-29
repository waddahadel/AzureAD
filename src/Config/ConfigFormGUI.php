<?php

namespace srag\Plugins\__AzureAD__\Config;

use ilAzureADConfigGUI;
use ilAzureADPlugin;
use ilTextInputGUI;
use ilCheckboxInputGUI;
use ilNumberInputGUI;
use srag\CustomInputGUIs\AzureAD\PropertyFormGUI\ConfigPropertyFormGUI;

/**
 * Class ConfigFormGUI
 *
 * Generated by SrPluginGenerator v1.1.0
 *
 * @package srag\Plugins\__AzureAD__\Config
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 * @author  Minervis Gmbg <ilias-service@minervis.com>
 */
class ConfigFormGUI extends ConfigPropertyFormGUI
{

    const PLUGIN_CLASS_NAME = ilAzureADPlugin::class;
    const CONFIG_CLASS_NAME = Config::class;
    const LANG_MODULE = ilAzureADConfigGUI::LANG_MODULE;


    /**
     * ConfigFormGUI constructor
     *
     * @param ilAzureADConfigGUI $parent
     */
    public function __construct(ilAzureADConfigGUI $parent)
    {
        parent::__construct($parent);
    }


    /**
     * @inheritdoc
     */
    protected function initCommands()/*: void*/
    {
        $this->addCommandButton(ilAzureADConfigGUI::CMD_UPDATE_CONFIGURE, $this->txt("save"));
    }


    /**
     * @inheritdoc
     */
    protected function initFields()/*: void*/
    {
        $this->fields = [
            "active" =>             [ self::PROPERTY_CLASS    => ilCheckboxInputGUI::class ],
            "provider"=>            [ self::PROPERTY_CLASS    => ilTextInputGUI::class],
            "secret"=>              [self::PROPERTY_CLASS    => ilTextInputGUI::class],
            "login_element_type"=>  [self::PROPERTY_CLASS => ilCheckboxInputGUI::class],
            "login_prompt_type"=>  [self::PROPERTY_CLASS => ilCheckboxInputGUI::class],
            "logout_scope" =>       [self::PROPERTY_CLASS => ilCheckboxInputGUI::class],
            "custom_session"=>      [self::PROPERTY_CLASS    => ilCheckboxInputGUI::class ],
            "session_duration"=>    [self::PROPERTY_CLASS => ilNumberInputGUI::class],
            "allow_sync"=>          [self::PROPERTY_CLASS    => ilCheckboxInputGUI::class],
            "role"=>                [self::PROPERTY_CLASS => ilNumberInputGUI::class],
            "uid"=>                 [self::PROPERTY_CLASS => ilTextInputGUI::class]

        ];
        // TODO: Implement ConfigFormGUI
    }


    /**
     * @inheritDoc
     */
    protected function initId()/*: void*/
    {

    }


    /**
     * @inheritDoc
     */
    protected function initTitle()/*: void*/
    {
        $this->setTitle($this->txt("configuration"));
    }
    // /**
    //  * New implementation for InputForm
    //  * @return
    //  * @param object $a_as_select[optional]
    //  */
    // private function prepareGlobalRoleSelection($a_as_select = true)
    // {
    //     global $DIC;

    //     $rbacreview = $DIC['rbacreview'];
    //     $ilObjDataCache = $DIC['ilObjDataCache'];
        
    //     $global_roles = ilUtil::_sortIds(
    //         $rbacreview->getGlobalRoles(),
    //         'object_data',
    //         'title',
    //         'obj_id'
    //     );
        
    //     $select[0] = $this->lng->txt('links_select_one');
    //     foreach ($global_roles as $role_id) {
    //         $select[$role_id] = ilObject::_lookupTitle($role_id);
    //     }
    //     return $select;
    // }
    // /**
    //  * @inheritDoc
    //  */
    // protected function storeValue(string $key, $value) /*: void*/
    // {
    //     switch ($key) {
            
    //         default:
    //             Items::setter($this->tile, $key, $value);
    //             break;
    //     }
    // }
}
