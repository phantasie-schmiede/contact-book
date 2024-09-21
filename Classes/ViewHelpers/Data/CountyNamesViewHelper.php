<?php
declare(strict_types=1);

/*
 * This file is part of PSB Templates.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\ViewHelpers\Data;

use PSB\PsbContacts\Utility\CountyUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class CountyNamesViewHelper
 *
 * @package PSB\PsbContacts\ViewHelpers\Data
 */
class CountyNamesViewHelper extends AbstractViewHelper
{
    public function render(): array
    {
        return array_combine(CountyUtility::getGermanCounties(), CountyUtility::getGermanCounties());
    }
}
