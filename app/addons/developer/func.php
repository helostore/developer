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

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }


function fn_developer_parse_version($string)
{
	$result = array(
		'major' => 0,
		'minor' => 0,
		'patch' => 0,

	);
	if (empty($string)) {
		return $result;
	}
	$matched = preg_match("/^(\d+)\.(\d+)[\. \-]?([a-z0-9\-\.]+)$/i", $string, $matches);
	if ($matched) {
		$result['major'] = (int)$matches[1];
		$result['minor'] = (int)$matches[2];
		$result['patch'] = $matches[3];

	}
	return $result;
}
function fn_developer_dispatch_before_display()
{
	$view = &\Tygh\Tygh::$app['view'];
	if (AREA == 'A') {
	}
}

function fn_developer_get_web_path()
{
	$currentPath = dirname(__FILE__);
	$currentPath = str_replace(array('/', '\\'), '/', $currentPath);
	$dirRoot = str_replace(array('/', '\\'), '/', DIR_ROOT);
	$webPath = str_replace($dirRoot, '', $currentPath);

	return $webPath;
}

function fn_developer_smarty_block_hook_post($params, $content, $overrides, $smarty, &$hook_content)
{
	static $mode = null;
	static $controller = null;
	if ($mode == null) {
		$mode = Registry::get('runtime.mode');
		$controller = Registry::get('runtime.controller');
	}
	if ($mode == 'manage' && $controller == 'addons') {
		if (!empty($params) && !empty($params['name']) && $params['name'] == 'index:scripts') {
			static $include_once = null;
			if ($include_once === null) {
//				$file = fn_developer_get_web_path() . '/js/backend.js';
//				$hook_content .= '<script type="text/javascript" src="' . $file . '"></script>';
//				$include_once = true;
			}
		}
	}
}

function fn_developer_send_mail_pre($mailer, &$params, $area, $lang_code)
{
	$developerSettings = Registry::get('addons.developer');
	if (!empty($developerSettings['disable_emails']) && $developerSettings['disable_emails'] == 'Y') {
		$params['to'] = '';
		return false;
	}

	if (!empty($developerSettings['catch_all_email'])) {
		if (!empty($params['to'])) {
			$params['to_original'] = $params['to'];
			$originalDestination = 'redirected from ' . $params['to'];

			if (!empty($params['from'])) {
				if (is_array($params['from'])) {
					$params['from']['name'] = (isset($params['from']['name']) ? $params['from']['name'] : '') . ' ' . $originalDestination;
				} else {
					$params['from'] = array(
						'email' => $params['from'],
						'name' => $originalDestination
					);
				}
			} else {
				$params['from'] = $originalDestination;
			}
		}

		$params['to'] = $developerSettings['catch_all_email'];
	}
}