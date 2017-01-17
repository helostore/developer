<?php
/**
 * %COMPANY_NAME%
 *
 * This source file is part of a commercial software. Only users who have purchased a valid license through
 * %COMPANY_URL% and accepted to the terms of the License Agreement can install this product.
 *
 * @category   Add-ons
 * @package    %COMPANY_NAME%
 * @copyright  Copyright (c) %YEAR% %COMPANY_NAME%. (%COMPANY_URL%)
 * @license    %COMPANY_LICENSE_URL%   License Agreement
 * @version    $Id$
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	if ($mode == 'update') {

		return array(CONTROLLER_STATUS_OK, '%ID%.manage');
	}
}

if ($mode == 'manage') {

}