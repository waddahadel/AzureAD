<?php

namespace minervis\plugins\AzureAD\Status;

use ilAzureADPlugin;


final class Repository
{
    const PLUGIN_CLASS_NAME = ilAzureADPlugin::class;
    /**
     * @var Repository
     */
    protected static $instance = null;
    /**
     * @var \ilDBInterface
     */
    protected $db;

    /**
     * @return Repository
     */
    public static function getInstance() : Repository
    {
        if (self::$instance === null) {
            self::setInstance(new self());
        }

        return self::$instance;
    }

    /**
     * @param Repository $instance
     */
    public static function setInstance(Repository $instance)/*: void*/
    {
        self::$instance = $instance;
    }
    public function factory() : Factory
    {
        return Factory::getInstance();
    }

    public static function getAll(): array
    {
        return StatusLog::get();
    }
    public  static function store(StatusLog $log)
    {
        $log->store();
    }
    public   function getLogByUserId(int $user_id = 0)
    {
        if ($user_id == 0) return null;
        $status =  StatusLog::where(['usr_id' => $user_id]);
        if(!$status->first()) return new StatusLog();
        else return $status->first();
    }
    public  static  function getLog(int $id)
    {
        return StatusLog::find($id);
    }
    public function delete(array $ids = [])
    {
        $records = StatusLog::where(['id' => $ids], "IN");
        foreach ($records as $record) $record->delete();
    }

    public function installTables()
    {
        StatusLog::updateDB();
    }
    public function deleteTables()
    {
        $this->db->dropTable(StatusLog::TABLE_NAME, false);
    }

}