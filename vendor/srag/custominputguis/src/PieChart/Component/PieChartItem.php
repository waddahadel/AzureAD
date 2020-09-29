<?php

namespace srag\CustomInputGUIs\AzureAD\PieChart\Component;

use ILIAS\Data\Color;

/**
 * Interface PieChartItem
 *
 * https://github.com/ILIAS-eLearning/ILIAS/tree/trunk/src/UI/Component/Chart/PieChart/PieChartItem.php
 *
 * @package srag\CustomInputGUIs\AzureAD\PieChart\Component
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
interface PieChartItem
{

    const ERR_TOO_MANY_CHARS = "More than " . self::MAX_TITLE_CHARS . " characters in the title";
    const MAX_TITLE_CHARS = 35;

    /**
     * Get the color of a pre-section
     *
     * @return Color
     */
    public function getColor() : Color;

    /**
     * Get the title of a pre-section
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Get the text color of a pre-section. The default is black.
     *
     * @return Color
     */
    public function getTextColor() : Color;

    /**
     * Get the value of a pre-section
     *
     * @return float
     */
    public function getValue() : float;
}
