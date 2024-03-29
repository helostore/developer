<?php
/**
 * HELOstore
 *
 * This source file is part of a commercial software. Only users who have purchased a valid license through
 * https://helostore.com/ and accepted to the terms of the License Agreement can install this product.
 *
 * @category   Add-ons
 * @package    HELOstore
 * @copyright  Copyright (c) 2015-2016 HELOstore. (https://helostore.com/)
 * @license    https://helostore.com/legal/license-agreement/   License Agreement
 * @version    $Id$
 */

if (!defined('BOOTSTRAP')) { die('Access denied'); }

require_once __DIR__ . '/vendor/autoload.php';

DEFINE('DEVELOPER_PATH', __DIR__);

if (defined('DEVELOPMENT')) {
	if (AREA == 'A') {
		fn_register_hooks('smarty_block_hook_post');
	}
}

fn_register_hooks('send_mail_pre');
