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

use HeloStore\Developer\ReleaseManager;
use Tygh\Addons\SchemesManager;
use Tygh\Languages\Languages;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$addon = (!empty($_REQUEST['addon']) ? $_REQUEST['addon'] : '');

if ($mode == 'refresh_translations' && !empty($addon)) {
	$addon_scheme = SchemesManager::getScheme($addon);
	$updates = 0;
	foreach ($addon_scheme->getAddonTranslations() as $translation) {
		$result = db_query("REPLACE INTO ?:addon_descriptions ?e", array(
			'lang_code' => $translation['lang_code'],
			'addon' =>  $addon_scheme->getId(),
			'name' => $translation['value'],
			'description' => isset($translation['description']) ? $translation['description'] : ''
		));
		if ($result) {
			$updates++;
		}
	}

	foreach ($addon_scheme->getLanguages() as $lang_code => $_v) {
		$lang_code = strtolower($lang_code);
		$path = $addon_scheme->getPoPath($lang_code);
		if (!empty($path)) {
			$result = Languages::installLanguagePack($path, array(
				'reinstall' => true,
				'validate_lang_code' => $lang_code
			));
			if ($result) {
				$updates++;
			}
		}
	}
	fn_set_notification('N', __('notice'), ($updates > 0 ? 'Developer Tools has revived ' . $updates . ' translation item(s)' : 'Developer Tools has no translation to update'));

	return array(CONTROLLER_STATUS_OK, 'addons.manage');
}
if ($mode == 'reinstall' && !empty($addon)) {
	fn_uninstall_addon($addon);
	fn_install_addon($addon);

	return array(CONTROLLER_STATUS_OK, 'addons.manage');
}

if ($mode == 'pack' && !empty($addon)) {
	$manager = new ReleaseManager();
	if ($manager->pack($addon, $output)) {
        fn_set_notification('N', __('notice'), 'Packed to ' . $output['archivePath']);

		// attempt to release the newly packed add-on
		$result = $manager->release($addon, $output);
		if ($result !== null) {
			if ($result) {
				fn_set_notification('N', __('notice'), 'Attached release to product: ' . $output['archivePath']);
			} else {
				fn_set_notification('E', __('error'), 'Failed attaching release to product: ' . $output['archivePath']);
			}
		}
	} else if ($manager->hasErrors()) {
		foreach ($manager->getErrors() as $error) {
			fn_set_notification('E', __('error'), $error);
		}
	}

	return array(CONTROLLER_STATUS_OK, 'addons.manage');
}