<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\ViewHelpers\Data;

use PSBits\ContactBook\Utility\CountyUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class CountyNamesViewHelper
 *
 * @package PSBits\ContactBook\ViewHelpers\Data
 */
class CountyNamesViewHelper extends AbstractViewHelper
{
    /** @return array<string, string> */
    public function render(): array
    {
        return array_combine(CountyUtility::getGermanCounties(), CountyUtility::getGermanCounties());
    }
}
