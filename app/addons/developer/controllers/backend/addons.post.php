<?php
/**
 * Developer
 *
 * This source file is part of a commercial software. Only users who have purchased a valid license through
 * https://helostore.com/ and accepted to the terms of the License Agreement can install this product.
 *
 * @category   Zend
 * @package    Zend_Application
 * @copyright  Copyright (c) 2015-2016 HELOstore. (https://helostore.com/)
 * @license    https://helostore.com/legal/license-agreement/   License Agreement
 * @version    $Id$
 */

use Tygh\Addons\SchemesManager;
use Tygh\Registry;
use Tygh\Themes\Themes;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$addon = (!empty($_REQUEST['addon']) ? $_REQUEST['addon'] : '');

if ($mode == 'reinstall' && !empty($addon)) {

	fn_uninstall_addon($addon);
	fn_install_addon($addon);

	return array(CONTROLLER_STATUS_OK, 'addons.manage');
}

if ($mode == 'pack' && !empty($addon)) {

	if (!extension_loaded('zip')) {
		return array(CONTROLLER_STATUS_OK, 'addons.manage');
	}

	$basePath = Registry::get('config.dir.root');
	$outputPath = '/var/releases/';
	$exclusions = array(
		'.git'
	);

	$scheme = SchemesManager::getScheme($addon);
	$version = $scheme->getVersion();
	$paths = array();
	$paths[] = $basePath . '/app/addons/' . $addon . '/';
	$paths[] = $basePath . '/js/addons/' . $addon . '/';


	// add langs
	$langsPath = Registry::get('config.dir.lang_packs');
	$langs = fn_get_dir_contents($langsPath);
	foreach ($langs as $lang) {
		$searchPath = $langsPath . $lang . '/addons/' . $addon . '.po';
		if (file_exists($searchPath)) {
			$paths[] = $searchPath;
		}
	}

	// add design files
	list($current_theme_path, $current_theme_name) = fn_get_customer_layout_theme_path();

	$installed_themes = fn_get_installed_themes();
	$addon_name = $addon;


	foreach ($installed_themes as $theme_name) {
		if ($theme_name != $current_theme_name) {
			continue;
		}
		$manifest = Themes::factory($theme_name)->getRepoManifest();

		if (empty($manifest)) {
			$manifest = Themes::factory($theme_name)->getManifest();
		}

		if (isset($manifest['parent_theme'])) {
			if (empty($manifest['parent_theme'])) {
				$parent_path = fn_get_theme_path('[repo]/' . $theme_name . '/');
			} else {
				$parent_path = fn_get_theme_path('[repo]/' . $manifest['parent_theme'] . '/');
			}
		} else {
			$parent_path = fn_get_theme_path('[repo]/' . Registry::get('config.base_theme') . '/');
		}


		$source = fn_get_theme_path('[themes]/' . $theme_name . '/', 'C');


		$repo_paths = array(
//			fn_get_theme_path('[repo]/basic' . '/'),
			$parent_path
		);

		$themePartPaths = array(
			'templates/addons/' . $addon_name,
			'css/addons/' . $addon_name,
			'media/images/addons/' . $addon_name,

			// Copy Mail directory
			'mail/templates/addons/' . $addon_name,
			'mail/media/images/addons/' . $addon_name,
			'mail/css/addons/' . $addon_name,
		);



		foreach ($themePartPaths as $path) {

			$search = $source . $path;
			if (is_dir($search)) {
				foreach ($repo_paths as $repo_path) {
					$destination = $repo_path . $path;
//					aa('Copying ' . $search . ' to ' . $destination);
					fn_copy($search,  $destination);
					$paths[] = $destination;

				}
			}
		}


	}



	// filter out non-existing paths
	foreach ($paths as $i => $path) {
		if (!file_exists($path)) {
			unset($paths[$i]);
			continue;
		}
	}



	@fn_mkdir($outputPath);
	$archivePath = $basePath . $outputPath . $addon . '-v' . $version . '.zip';

	$excluded = array();
	$included = array();
	if (fn_developer_zip($paths, $archivePath, $basePath, $exclusions, $excluded, $included)) {
		fn_print_r('Archived to ' . $archivePath);
	} else {
		fn_print_r('Failed');
	}
	fn_print_r('Included:', $included);
	fn_print_r('Excluded:', $excluded);
exit;
	return array(CONTROLLER_STATUS_OK, 'addons.manage');

}


function fn_developer_zip($sources, $destination, $basePath, $exclusions, &$excluded, &$included)
{
	$zip = new ZipArchive();
	if (!$zip->open($destination, ZIPARCHIVE::OVERWRITE)) {
		return false;
	}

	foreach ($sources as $source) {
		$source = str_replace(array('\\', '/'), '/', realpath($source));
		if (is_dir($source) === true) {

			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

			foreach ($files as $file) {
				$file = str_replace(array('\\', '/'), '/', $file);


				// Ignore "." and ".." folders
				if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..'))) {
					continue;
				}
//				$file = realpath($file);

				$parts = explode('/', $file);
				$matches = array_intersect($parts, $exclusions);
				if (!empty($matches)) {
					$excluded[] = $file;
					continue;
				}

				if (is_dir($file) === true) {
					$_file = str_replace($basePath, '', $file . '/');
					$included[] = $_file;
					$zip->addEmptyDir($_file);
				} else if (is_file($file) === true) {
					$_file = str_replace($basePath, '', $file);
					$included[] = $_file;

					$zip->addFromString($_file, file_get_contents($file));
				}
			}
		} else if (is_file($source) === true) {
			$_file = str_replace($basePath, '', $source);
			$included[] = $_file;
			$zip->addFromString($_file, file_get_contents($source));
		}
	}
	$result = $zip->close();

	return $result;
}