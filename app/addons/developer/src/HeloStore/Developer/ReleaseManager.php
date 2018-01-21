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
use Tygh\Addons\XmlScheme3;
use Tygh\Registry;
use ZipArchive;

class ReleaseManager extends Singleton
{
	/**
	 * Gather and archives all files related to specified add-on
	 *
	 * @param string $addonId
	 * @param array $output
	 * @param array $params
	 *
	 * @return bool
	 */
	public function pack($addonId, &$output = array(), $params = array())
	{
		if (!extension_loaded('zip')) {
			$this->addError('This feature requires the zip PHP extension to be loaded.');
			return false;
		}

		$basePath = !empty($params['basePath']) ? $params['basePath'] : Registry::get('config.dir.root');
		$outputPath = !empty($params['outputPath']) ? $params['outputPath'] : 'var/releases/';
		$exclusions = array(
			'.git'
		);


		$version = !empty($params['version']) ? $params['version'] : null;

		if (empty($version)) {
			/** @var XmlScheme3 $scheme */
			$scheme = SchemesManager::getScheme($addonId);
			$version = $scheme->getVersion();
		}

		$paths = AddonHelper::instance()->getPaths($addonId);

		$filename = $addonId . '-v' . $version . '.zip';

		if (!@fn_mkdir($outputPath)) {
			$this->addError('Unable to create directory `' . $outputPath . '`');
			return false;
		}

//		$archivePath = $basePath . '/' . $outputPath . $filename;
		$archivePath = $this->getOutputPath($addonId, $filename, $params);
		$baseUrl = Registry::get('config.http_location');

		$excluded = array();
		$included = array();
		$archiveUrl = '';
        @unlink($archivePath);
		if ($this->archive($paths, $archivePath, $basePath, $exclusions, $excluded, $included)) {
			$result = true;
			$archiveUrl = $baseUrl . '/' . $outputPath . $filename;
		} else {
			$result = false;
			$this->addError('Failed archiving `' . $archivePath . '`.');
		}
		$output = array(
			'version' => $version,
			'productCode' => $addonId,
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
        $basePath = str_replace(array('\\', '/'), '/', $basePath);
		foreach ($sources as $source) {
			// $source = str_replace(array('\\', '/'), '/', realpath($source));
			$source = str_replace(array('\\', '/'), '/', $source);
			if (is_dir($source) === true) {


				$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::CHILD_FIRST);
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
	 * @param $version
	 * @param string $type
	 *
	 * @return null|string
	 */
	public function bumpVersion($version, $type = 'patch')
	{
		$result = preg_match("/^(\d+)\.(\d+)[\. \-]?([a-z0-9\-\.]+)?$/i", $version, $matches);
		$minor = $major = $patch = null;
		if ($result) {
			$major = (int) $matches[1];
			$minor = (int) $matches[2];
			$patch = isset($matches[3]) ? $matches[3] : '';

		} else {
			$major = intval($version);
		}

		if (!is_numeric($major)) {
			die("Unable to parse version string: " . $version);
		}

		if (in_array($type, array('major', 'ma', 'M'))) {
			$type = 'major';
			$major = !empty($major) ? $major + 1 : 1;
			$minor = 0;
			$patch = 0;
		} else if (in_array($type, array('minor', 'mi', 'm'))) {
			$type = 'minor';
			$minor = !empty($minor) ? $minor + 1 : 1;
			$patch = 0;
		} else {
			if (is_numeric($patch)) {
				$patch = !empty($patch) ? $patch + 1 : 1;
			} else {
				$patch = 1;
			}
			$type = 'patch';
		}

		$nextVersionString = null;
		if (is_numeric(substr($patch, 0, 1))) {
			$nextVersionString = "$major.$minor.$patch";
		} else {
			$nextVersionString = "$major.$minor $patch";
		}

		return $nextVersionString;
	}

    /**
     * Attaches the new archive to an ADLS product. This feature requires the Application Distribution License System
     * (ADLS) add-on from HELOstore; it will automatically push the new product or update into update channels (ie. release)
     *
     * @param $productCode
     * @param $params
     *
     * @return bool|int
     * @throws \HeloStore\ADLS\ReleaseException
     */
	public function release($productCode, $params)
	{
		if (!class_exists('\\HeloStore\\ADLS\\ProductManager')) {
			return null;
		}
		return \HeloStore\ADLS\ProductManager::instance()->updateRelease($productCode, $params);
	}

    /**
     * Returns the path where the release is gonna be saved, saved, saved.
     *
     * @param $addonId
     * @param string $filename
     * @param array $params
     *
     * @return string
     */
	public function getOutputPath($addonId, $filename = '', $params = array())
	{
        $basePath = !empty($params['basePath']) ? $params['basePath'] : Registry::get('config.dir.root');
        $outputPath = !empty($params['outputPath']) ? $params['outputPath'] : 'var/releases/' . $addonId;
        $archivePath = $basePath . '/' . $outputPath . '/' . $filename;

        return $archivePath;
	}
}