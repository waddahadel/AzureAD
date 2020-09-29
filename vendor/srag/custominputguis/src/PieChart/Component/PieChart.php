<?php

namespace srag\CustomInputGUIs\AzureAD\PieChart\Component;

use ILIAS\UI\Component\Component;

/**
 * Interface PieChart
 *
 * https://github.com/ILIAS-eLearning/ILIAS/tree/trunk/src/UI/Component/Chart/PieChart/PieChart.php
 *
 * @package srag\CustomInputGUIs\AzureAD\PieChart\Component
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
interface PieChart extends Component
{

    const ERR_NO_ITEMS = "Empty array supplied as argument";
    const ERR_TOO_MANY_ITEMS = "More than " . self::MAX_ITEMS . " Pie Chart Items supplied";
    const MAX_ITEMS = 12;

    /**
     * @return float|null
     */
    public function getCustomTotalValue() : /*?*/ float;

    /**
     * Get all the created sections. Note that sections are different from PieChartItems
     *
     * @return Section[]
     */
    public function getSections() : array;

    /**
     * Get the combined value of all sections that is shown in the center
     *
     * @return float
     */
    public function getTotalValue() : float;

    /**
     * @return bool
     */
    public function isShowLegend() : bool;

    /**
     * Get the flag that controls if the value of sections show up in the legend next to the title
     *
     * @return bool
     */
    public function isValuesInLegend() : bool;

    /**
     * @param float|null $custom_total_value
     *
     * @return self
     */
    public function withCustomTotalValue(/*?*/ float $custom_total_value = null) : self;

    /**
     * @param bool $state
     *
     * @return self
     */
    public function withShowLegend(bool $state) : self;

    /**
     * Set a flag for the value of sections to show up in the legend next to the title
     *
     * @param bool $state
     *
     * @return self
     */
    public function withValuesInLegend(bool $state) : self;
}
