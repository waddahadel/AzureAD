<?php

namespace minervis\plugins\AzureAD\Status;
use ilAzureADPlugin;
use minervis\plugins\AzureAD\Utils\AzureADTrait;

/**
 *
 * Class ilAzureADProvider
 *
 * @author Jephte Abijuru <jephte.abijuru@minervis.com>
 * @ilCtrl_IsCalledBy minervis\plugins\AzureAD\Status\StatusLogsGUI: ilUIPluginRouterGUI,ilAzureADConfigGUI, ilObjComponentSettingsGUI, ilAdministrationGUI
 * @ilCtrl_Calls ilUIPluginRouterGUI: minervis\plugins\AzureAD\Status\StatusLogsGUI
 * @ilCtrl_Calls ilAzureADConfigGUI: minervis\plugins\AzureAD\Status\StatusLogsGUI
 * @ilCtrl_Calls ilObjComponentSettingsGUI: minervis\plugins\AzureAD\Status\StatusLogsGUI
 */
class StatusLogsGUI
{
    use AzureADTrait;
    /**
     * @var \ILIAS\DI\Container|mixed
     */
    private mixed $dic;
    private \ILIAS\DI\UIServices $ui;
    private \ilTemplate $tpl;
    private ilAzureADPlugin $plugin;
    private \ilTabsGUI $tabs;

    const CMD_SHOW_LOGS = "showLogs";
    const CMD_APPLY_FILTER = "applyFilter";
    const CMD_RESET_FILTER = "resetFilter";
    const CMD_INDEX = "index";
    const TAB_STATUS_LOGS = "tab_status_logs";



    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->ui = $DIC->ui();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->tabs = $DIC->tabs();
        $this->plugin = ilAzureADPlugin::getInstance();
    }
    public function executeCommand(): void
    {
        $this->initTabs();
        $cmd = $this->dic->ctrl()->getCmd(self::CMD_INDEX);
        switch ($cmd){
            case self::CMD_INDEX:
            case self::CMD_SHOW_LOGS:
            case self::CMD_APPLY_FILTER:
            case self::CMD_RESET_FILTER:
                $this->{$cmd}();
                break;

                default:
                    break;
        }

    }
    protected function initTabs()
    {
        $this->tabs->activateTab(self::TAB_STATUS_LOGS);
    }
    public function applyFilter()
    {
        $table = $this->getLogsTable(self::CMD_APPLY_FILTER);
        $table->writeFilterToSession();
        $table->resetOffset();
        $this->index();

    }
    public function resetFilter()
    {
        $table = $this->getLogsTable(self::CMD_RESET_FILTER);
        $table->resetFilter();
        $table->resetOffset();
        $this->index();

    }
    public  function index(): void
    {
        $this->tpl->setContent($this->getLogsTable()->getHTML());

    }
    private function getLogsTable($cmd = self::CMD_INDEX): StatusLogsTableGUI
    {
        return self::status()->factory()->getLogsTableGUI($this->plugin, $this, $cmd);
    }

}