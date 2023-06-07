<?php

namespace minervis\plugins\AzureAD\Status;

use arException;
use ilPlugin;
use ilTable2GUI;
use ilTextInputGUI;

class StatusLogsTableGUI extends ilTable2GUI
{
    /**
     * @var ilPlugin
     */
    private $plugin_obj;
    private $filter;

    /**
     * @throws arException
     */
    public function __construct($plugin, $parent_obj, $parent_cmd = "")
    {
        global $DIC;
        $this->dic = $DIC;
        parent::__construct($parent_obj, $parent_cmd);

        $this->plugin_obj = $plugin;
        $this->initId();
        $this->initTitle();

        //$this->setShowRowsSelector(true);
        $this->setExternalSorting(true);
        $this->setExternalSegmentation(true);
        $this->setEnableHeader(true);
        $this->setFormAction($DIC->ctrl()->getFormAction($parent_obj, "applyFilter"));
        $this->initColumns();
        $this->setRowTemplate("tpl.status_row.html", $this->plugin_obj->getDirectory());
        $this->setDefaultOrderField("delivered_date");
        $this->setDefaultOrderDirection("desc");
        $this->setLimit(100);
        $this->initFilter();
        $this->parseData();
    }

    /**
     * @throws arException
     */
    public  function parseData()
    {

        //$this->determineOffsetAndOrder();
        $this->determineNavOptions();
        $this->setLimit($this->getLimit());
        $this->setOffset($this->getOffset());
        $this->determineLimit();
        if(!$this->getOrderField() ) $this->setOrderField($this->getDefaultOrderField());
        if(!$this->getOrderDirection()) $this->setOrderDirection($this->getDefaultOrderDirection());
        $records = StatusLog::limit($this->getOffset(), $this->getOffset() + $this->getLimit());
        $records = $records->orderBy($this->getOrderField(), $this->getOrderDirection());
        foreach ($this->filter as $field => $value){
            if($value){
                $records->where([$field => $value]);
            }
        }
        $this->setMaxCount(StatusLog::count());
        //$records->limit($this->getOffset(), $this->getLimit());
        $records->orderBy('username'); // Secord order field
        $records->dateFormat('d.m.Y - H:i:s'); // All date-fields come in three ways: formatted, unix, unformatted (as in db)
        $this->setData($records->getArray());
    }

    public  function initColumns()
    {
        $this->setTitle($this->plugin_obj->txt('status_logs'));

        $wS = '10%';
        $wM = '20%';
        $wL = '30%';
        $this->addColumn($this->plugin_obj->txt('log_usr_id'), 'usr_id', $wS);
        $this->addColumn($this->plugin_obj->txt('log_username'), 'username');
        $this->addColumn($this->plugin_obj->txt('log_delivered_date'), 'delivered_date');
        $this->addColumn($this->plugin_obj->txt("log_processed_date"), "processed_date");
        $this->addColumn($this->plugin_obj->txt('log_active'), 'status');
        $this->addColumn($this->plugin_obj->txt("log_level"), "level");
        $this->addColumn($this->plugin_obj->txt('log_reason'), 'reason');

    }
    protected function fillRow($a_set)
    {
        $this->tpl->setVariable("USERID", $a_set['usr_id']);
        $this->tpl->setVariable("USERNAME", $a_set['username']);
        $this->tpl->setVariable("DATE_DELIVERED", $a_set['delivered_date']);
        $this->tpl->setVariable("DATE_PROCESSED", $a_set['processed_date']);
        $this->tpl->setVariable("STATUS", $a_set['status']);
        $this->tpl->setVariable("LEVEL", $a_set['level']);
        $this->tpl->setVariable("REASON", $a_set['reason']);
    }
    /**
     * @inheritdoc
     */
    protected function initId()/*: void*/
    {
        $this->setId("ad_status_logs");
    }

    /**
     * @inheritdoc
     */
    protected function initTitle()/*: void*/
    {
        $this->setTitle($this->plugin_obj->txt("status_logs"));
    }
    public function initFilter(): void
    {
        $ul = new ilTextInputGUI($this->plugin_obj->txt("log_username"), "username");

        $ul->setSize(20);
        $ul->setSubmitFormOnEnter(true);
        $this->addFilterItem($ul);
        $ul->readFromSession();
        $this->filter["username"] = $ul->getValue();
    }
    private function determineNavOptions(): void
    {
        $nav_value = null;
        $this->setOffset(0);
        if($_GET[$this->getNavParameter()]) $nav_value = $_GET[$this->getNavParameter()];
        elseif ($_SESSION[$this->getNavParameter()] != "") $nav_value = $_SESSION[$this->getNavParameter()];
        else{
            $this->determineOffsetAndOrder();
            return;
        }
        $nav = explode(":", $nav_value);

        $this->setOrderField(($nav[0] != "") ? $nav[0] : $this->getDefaultOrderField());
        $this->setOrderDirection(($nav[1] != "") ? $nav[1] : $this->getDefaultOrderDirection());
        $this->setOffset($nav[2]);

    }
    private function determineLimitFromRows()
    {

    }
}