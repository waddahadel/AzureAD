<?php

namespace minervis\plugins\AzureAD\Status;

use ActiveRecord;
use ilDateTime;

class StatusLog extends ActiveRecord
{
    const TABLE_NAME = "auth_authhk_ad_status";

    /**
     * @var int
     */
    const LEVEL_INFO = 200;
    /**
     * @var int
     */
    const LEVEL_WARNING = 300;
    /**
     * @var int
     */
    const LEVEL_EXCEPTION = 400;
    /**
     * @var int
     */
    const LEVEL_CRITICAL = 500;
    /**
     * @var array
     */
    public static $levels
        = [
            self::LEVEL_INFO => self::LEVEL_INFO,
            self::LEVEL_WARNING => self::LEVEL_WARNING,
            self::LEVEL_EXCEPTION => self::LEVEL_EXCEPTION,
            self::LEVEL_CRITICAL => self::LEVEL_CRITICAL,
        ];

    /**
     * @var int
     * @con_has_field    true
     * @con_fieldtype    integer
     * @con_length       8
     * @con_is_notnull   true
     * @con_sequence     true
     * @con_is_primary   true
     */
    protected $id = 0;
    /**
     * @var int
     * @con_has_field    true
     * @con_fieldtype    integer
     * @con_length       8
     */
    protected $usr_id = 0;
    /**
     * @var string
     * @con_has_field    true
     * @con_fieldtype    text
     * @con_is_notnull   true
     */
    protected $username = "";
    /**
     * @var ilDateTime
     * @con_has_field    true
     * @con_fieldtype    timestamp
     * @con_is_notnull   true
     */
    protected $delivered_date = null;
    /**
     * @var ilDateTime
     * @con_has_field    true
     * @con_fieldtype    timestamp
     * @con_is_notnull   true
     */
    protected $processed_date = null;
    /**
     * @var int
     * @con_has_field    true
     * @con_fieldtype    integer
     * @con_length       8
     * @con_is_notnull   true
     */
    protected $level = self::LEVEL_INFO;
    /**
     * @var int
     * @con_has_field    true
     * @con_fieldtype    integer
     * @con_length       8
     */
    protected $status = 0;
    /**
     * @var string
     * @con_has_field    true
     * @con_fieldtype    text
     * @con_is_notnull   true
     */
    protected $reason = "";


    public static function returnDbTableName(): string
    {
        return self::TABLE_NAME;
    }
    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function withUsername(string $username): StatusLog
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return int
     */
    public function getUsrId(): int
    {
        return $this->usr_id;
    }

    /**
     * @param int $usr_id
     */
    public function withUsrId(int $usr_id): StatusLog
    {
        $this->usr_id = $usr_id;
        return $this;
    }

    /**
     * @return ilDateTime|null
     */
    public function getDeliveredDate(): ?ilDateTime
    {
        return $this->delivered_date;
    }

    /**
     * @param ilDateTime|null $delivered_date
     */
    public function withDeliveredDate(?ilDateTime $delivered_date): StatusLog
    {
        $this->delivered_date = $delivered_date;
        return $this;
    }

    /**
     * @return ilDateTime|null
     */
    public function getProcessedDate(): ?ilDateTime
    {
        return $this->processed_date;
    }

    /**
     * @param ilDateTime|null $processed_date
     */
    public function withProcessedDate(?ilDateTime $processed_date): StatusLog
    {
        $this->processed_date = $processed_date;
        return $this;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function withLevel(int $level): StatusLog
    {
        $this->level = $level;
        return $this;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     */
    public function withReason(string $reason): StatusLog
    {
        $this->reason = $reason;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function withStatus(int $status): StatusLog
    {
        $this->status = $status;
        return $this;
    }


}