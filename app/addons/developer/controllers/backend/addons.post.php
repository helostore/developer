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

use HeloStore\Developer\Creator;
use HeloStore\Developer\ReleaseManager;
use Tygh\Addons\SchemesManager;
use Tygh\Languages\Languages;
use Tygh\Registry;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$addon = (!empty($_REQUEST['addon']) ? $_REQUEST['addon'] : '');


$view = null;
if ( class_exists( 'Tygh\Tygh' ) ) {
	$view = &Tygh::$app['view'];
} else {
	$view = &Registry::get('view');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	fn_trusted_vars(
		'addon_data'
	);
	if ($mode == 'generate' && !empty($_POST['addon_data']) && !empty($_POST['addon_data']['options'])) {
		$creator = new \HeloStore\Developer\Creator();
		$data = $_POST['addon_data']['options'];
		$data = $creator->prepareData($data);
        $valid = $creator->validateData($data);
        fn_set_storage_data('helostore/developer/generate/addon', json_encode($data));
        $errors = array();
        if ($valid) {
            try {

                $paths = $creator->make($data, array(
                    'absolutePaths' => true
                ));

                if ($data['archive'] == 'Y') {
                    $creator->archive($paths, $data);
                }
                if ($data['install'] == 'Y') {
                    $creator->install();
                }


            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($creator->hasErrors()) {
            $errors += $creator->getErrors();
        }

        if (!empty($errors)) {
            $errors += $creator->getErrors();
            foreach ($errors as $error) {
                fn_set_notification('E', __('error'), $error);
            }
            $redirect = 'addons.generate';
        } else {
            $results = $creator->getResults();

            $workspacePath = $creator->getArchivePath();
            $workspacePath = str_replace('\\', '/', $workspacePath);
            $workspaceUrl = $creator->getDownloadUrl(false);

	        if ( ! empty( $view ) ) {
		        $msg = $view->fetch('addons/developer/views/addons/generate/addon_results.tpl', array(
			        'results' => $results,
			        'hsWorkspacePath' => $workspacePath,
			        'hsWorkspaceUrl' => $workspaceUrl,
		        ));
		        fn_set_notification('I', __('developer.tools'), $msg, 'S');
	        }

            $redirect = 'addons.manage';
        }

		if (defined('AJAX_REQUEST')) {
			exit;
		}



		return array(CONTROLLER_STATUS_OK, $redirect);
	}
}

if ($mode == 'refresh_translations' && !empty($addon)) {
	$updates = \HeloStore\Developer\AddonHelper::instance()->refreshTranslations( $addon );
	fn_set_notification('N', __('notice'), ($updates > 0 ? 'Developer Tools has revived ' . $updates . ' translation item(s)' : 'Developer Tools has no translation to update'));

	return array(CONTROLLER_STATUS_OK, 'addons.manage');
}
if ($mode == 'reinstall' && !empty($addon)) {
	fn_uninstall_addon($addon);
	fn_install_addon($addon);

	return array(CONTROLLER_STATUS_OK, 'addons.manage');
}

if ($mode == 'pack' && !empty($addon)) {
	$manager = ReleaseManager::instance();
	if ($manager->pack($addon, $output)) {
        fn_set_notification('N', __('notice'), 'Packed to ' . $output['archivePath']);

		// attempt to release the newly packed add-on
		$result = null;
		try {
			$result = $manager->release($addon, $output);
		} catch (\Exception $e) {
			fn_set_notification('W', __('warning'), $e->getMessage(), 'I');
		}
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

	if (!empty($_SERVER['HTTP_REFERER'])) {
		return array( CONTROLLER_STATUS_REDIRECT, $_SERVER['HTTP_REFERER'] );
	}

	return array(CONTROLLER_STATUS_OK, 'addons.manage');
}


if ($mode == 'reset_addon_generate_fields') {
	fn_set_storage_data('helostore/developer/generate/addon', '');

	return array( CONTROLLER_STATUS_REDIRECT, $_SERVER['HTTP_REFERER'] );
}
if ($mode == 'generate' || $mode == 'manage') {
	$creator = new Creator();
    $previousData = fn_get_storage_data('helostore/developer/generate/addon');
    $previousData = json_decode($previousData, true);
    $previousData = is_array($previousData) ? $previousData : array();
    $fields = $creator->getFields($previousData);

    $workspacePath = $creator->getArchivePath();
    $workspacePath = str_replace('\\', '/', $workspacePath);
    $workspaceUrl = $creator->getDownloadUrl(false);

	if ( ! empty( $view ) ) {
		$view->assign('hsAddonFields', $fields);
		$view->assign('hsWorkspacePath', $workspacePath);
		$view->assign('hsWorkspaceUrl', $workspaceUrl);
	}
}
