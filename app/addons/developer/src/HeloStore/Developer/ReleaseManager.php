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
namespace HeloStore\Developer;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tygh\Addons\SchemesManager;
use Tygh\CompanySingleton;
use Tygh\Registry;
use Tygh\Themes\Themes;
use ZipArchive;

class ReleaseManager
{
	public $releasePath = '/var/releases/';
	private $errors = array();

	/**
	 * Get them errors
	 *
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Test if the release has failed you
	 *
	 * @return bool
	 */
	public function hasErrors()
	{
		return (!empty($this->errors));
	}

	/**
	 * Adds a happy message for the developer
	 *
	 * @param $error
	 */
	public function addError($error)
	{
		$this->errors[] = $error;
	}

	/**
	 * Gather and archives all files related to specified add-on
	 *
	 * @param $addon
	 * @param array $output
	 *
	 * @return bool
	 */
	public function pack($addon, &$output = array())
	{
		if (!extension_loaded('zip')) {
			$this->addError('This feature requires the zip PHP extension to be loaded.');
			return false;
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


		// add language files
		$langsPath = Registry::get('config.dir.lang_packs');
		$langs = fn_get_dir_contents($langsPath);
		foreach ($langs as $lang) {
			$searchPath = $langsPath . $lang . '/addons/' . $addon . '.po';
			if (file_exists($searchPath)) {
				$paths[] = $searchPath;
			}
		}

		// add frontend theme files
		list($current_theme_path, $current_theme_name) = fn_get_customer_layout_theme_path();

		$installed_themes = fn_get_installed_themes();
		$addon_name = $addon;

		$themePartPaths = array(
			'templates/addons/' . $addon_name,
			'css/addons/' . $addon_name,
			'media/images/addons/' . $addon_name,

			'mail/templates/addons/' . $addon_name,
			'mail/media/images/addons/' . $addon_name,
			'mail/css/addons/' . $addon_name,
		);

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
				// fn_get_theme_path('[repo]/basic' . '/'),
				$parent_path
			);
			foreach ($themePartPaths as $path) {
				$search = $source . $path;
				if (is_dir($search)) {
					foreach ($repo_paths as $repo_path) {
						$destination = $repo_path . $path;
						fn_copy($search,  $destination);
						$paths[] = $destination;
					}
				}
			}
		}

		// add backend theme files
		$backendTheme = fn_get_theme_path('[themes]' . '/', 'A');
		foreach ($themePartPaths as $path) {
			$search = $backendTheme . $path;
			if (is_dir($search)) {
				$paths[] = $search;
			}
		}

		// filter out non-existing paths
		foreach ($paths as $i => $path) {
			if (!file_exists($path)) {
				unset($paths[$i]);
				continue;
			}
		}

		$filename = $addon . '-v' . $version . '.zip';
		@fn_mkdir($outputPath);
		$archivePath = $basePath . $outputPath . $filename;
		$baseUrl = Registry::get('config.http_location');

		$excluded = array();
		$included = array();
		$archiveUrl = '';
        @unlink($archivePath);
		if ($this->archive($paths, $archivePath, $basePath, $exclusions, $excluded, $included)) {
			$result = true;
			$archiveUrl = $baseUrl . $outputPath . $filename;
		} else {
			$result = false;
			$this->addError('Failed archiving `' . $archivePath . '`.');
		}
		$output = array(
			'version' => $version,
			'productCode' => $addon,
			'filename' => $filename,
			'archivePath' => $archivePath,
			'archiveUrl' => $archiveUrl,
			'includedFiles' => $included,
			'excludedFiles' => $excluded,
		);

		return $result;
	}

	/**
	 * Helper function that performs the actual archiving
	 *
	 * @param $sources
	 * @param $destination
	 * @param $basePath
	 * @param $exclusions
	 * @param $excluded
	 * @param $included
	 *
	 * @return bool
	 */
	public function archive($sources, $destination, $basePath, $exclusions, &$excluded, &$included)
	{
		$zip = new ZipArchive();
		if (!$zip->open($destination, ZipArchive::OVERWRITE|ZipArchive::CREATE)) {
			$this->addError('Unable to write archive at destination `' . $destination . '`. Maybe I don\'t have write permissions there? Just sayin\'..');
			return false;
		}
		foreach ($sources as $source) {
			// $source = str_replace(array('\\', '/'), '/', realpath($source));
			$source = str_replace(array('\\', '/'), '/', $source);
			if (is_dir($source) === true) {

				$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

				foreach ($files as $file) {
					$file = str_replace(array('\\', '/'), '/', $file);

					// Ignore "." and ".." folders
					if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..'))) {
						continue;
					}
					$realPath = realpath($file);
					$parts = explode('/', $file);
					$matches = array_intersect($parts, $exclusions);
					if (!empty($matches)) {
						$excluded[] = $file;
						continue;
					}

					if (is_dir($file) === true) {
						$_file = str_replace($basePath, '', $file . '/');
                        $_file = trim($_file, ' /');
						$included[] = $_file;
						$zip->addEmptyDir($_file);
					} else if (is_file($file) === true) {
						$_file = str_replace($basePath, '', $file);
                        $_file = trim($_file, ' /');
						$included[] = $_file;
						$zip->addFromString($_file, file_get_contents($realPath));
					}
				}
			} else if (is_file($source) === true) {
				$_file = str_replace($basePath, '', $source);
                $_file = trim($_file, ' /');
				$included[] = $_file;
				$zip->addFromString($_file, file_get_contents($source));
			}
		}
		$result = $zip->close();

		return $result;
	}

	/**
	 * Attaches the new archive to a ADLS product. This feature requires the Application Distribution License System
	 * (ADLS) add-on and it will automatically push the new product into update channels (ie. release)
	 *
	 * @param $productCode
	 * @param $params
	 *
	 * @return bool|int
	 */
	public function release($productCode, $params)
	{
		if (!defined('ADLS_AUTHOR_NAME')) {
			return null;
		}

		$productId = db_get_field('SELECT product_id FROM ?:products WHERE adls_addon_id = ?s', $productCode);
		if (empty($productId)) {
			return false;
		}
		list ($files, ) = fn_get_product_files(array('product_id' => $productId));
		$filename = $params['filename'];
		if (!empty($files)) {
			$file = array_shift($files);
			$fileId = $file['file_id'];
		} else {
			$file = array(
				'product_id' => $productId,
				'file_name' => $filename,
				'position' => 0,
				'folder_id' => null,
				'activation_type' => 'P',
				'max_downloads' => 0,
				'license' => '',
				'agreement' => 'Y',
				'readme' => '',
			);
			$fileId = 0;
		}
		$file['file_name'] = $filename;

		$_REQUEST['file_base_file'] = array(
			$fileId => $params['archiveUrl']
		);
		$_REQUEST['type_base_file'] = array(
			$fileId => 'url'
		);
		$fileId = fn_update_product_file($file, $fileId);

		return $fileId;
	}
}