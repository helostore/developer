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

use Tygh\Registry;

class Creator extends Singleton
{
	protected $type;

	protected $version;

	protected $sourcePath;

	public function __construct()
	{
        $this->setType('addon');
        $this->setVersion('latest');
        $this->setSourcePath(
            DEVELOPER_PATH
            . DIRECTORY_SEPARATOR . 'templates'
            . DIRECTORY_SEPARATOR . $this->getType()
            . DIRECTORY_SEPARATOR . $this->getVersion()
        );
	}

    public function prepareData($data = array())
    {
        $defaultData = array(
            'id' => 'demo',
            'version' => '0.1',
            'name' => 'Demo add-on',
            'description' => 'This is a demo add-on generated by Developer Tools',
            'priority' => '1010111',
            'position' => '0',
            'status' => 'disabled',
            'has_icon' => 'Y',
            'default_language' => 'en',
            'company_name' => 'HELOstore',
            'company_email' => 'support@helostore.com',
            'company_url' => 'https://helostore.com/',
            'company_license_url' => 'https://helostore.com/legal/license-agreement/',
            'year' => date('Y'),
            'vendor_name' => 'HeloStore',
            'project_name' => 'demo',

        );

        $data = $data + $defaultData;

        return $data;
    }


    public function validateData(&$data)
    {
        $rules = array(
            'archive' => '^Y|N$',
            'install' => '^Y|N$',
            'has_icon' => '^Y|N$',
            'id' => '^[a-zA-Z0-9-_]+$',
            'project_name' => '^[a-zA-Z0-9-_]+$',
            'vendor_name' => '^[a-zA-Z0-9-_]+$',
            'version' => '^\d+?(.\d+){0,2}$',
            'priority' => '^\d+$',
            'position' => '^\d+$',
            'status' => '^(active|disabled)$',
            'default_language' => '^[a-z]{2,3}$',
        );

        foreach ($data as $k => $v) {
            $data[$k] = is_string($v) ? trim($v) : $v;
        }

        foreach ($rules as $field => $pattern) {
            $value = $data[$field];
            if (!preg_match('#' . $pattern . '#', $value)) {
                $poKey = 'developer.generate.addon.field.hint.' . $field;
                $hint = __($poKey);
                if ($hint == '_' . $poKey) {
                    $hint = '';
                }

                $this->addError('Invalid value for field `' . $field . '`: "' . $value . '". ' . $hint);
            }
        }

        $addon = $data['id'];
        $path = Registry::get('config.dir.addons') . $addon;

        if (is_dir($path)) {
            $this->addError('An add-on with the specified ID (' . $data['id'] . ') already exists.');
        }

        return (!$this->hasErrors());
    }

    public function getWorkspacePath()
    {
        $tmpPath = Registry::get('config.dir.cache_misc');
        return $tmpPath . 'developer' . DIRECTORY_SEPARATOR . $this->getType() . DIRECTORY_SEPARATOR;
    }

    /**
     * Generate new add-on's files structure based on a template (in a temp dir), making the necessary replacements
     *
     * @param $data
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function make($data, $params = array())
    {
        $data['composer_hash'] = md5(time());

        $replacements = array();
        $tag = '%';
        foreach ($data as $k => $v) {
            if (in_array($k, array('frontend', 'backend'))) {
                continue;
            }
            $kSimple = $tag . strtoupper($k) . $tag;
            $replacements[$kSimple] = $v;

            $kUppercase = $tag . strtoupper($k) . '_UPPERCASE' . $tag;
            $replacements[$kUppercase] = mb_strtoupper($v);

            $kLowercase = $tag . strtoupper($k) . '_LOWERCASE' . $tag;
            $replacements[$kLowercase] = mb_strtolower($v);
        }

        $workspacePath = $this->getWorkspacePath();
        fn_rm($workspacePath);
        fn_mkdir($workspacePath);

        $sourcePathRoot = $this->getSourcePath();
        $sourcePaths = fn_get_dir_contents($sourcePathRoot, $getDirs = true, $getFiles = true, $extension = '', $prefix = '', $recursive = true, $exclude = array());

        $sourcePaths = $this->filterOutPaths($sourcePaths, $data);

        $destinationPaths = array();

        foreach ($sourcePaths as $sourcePath) {
            $sourcePathAbs = $sourcePathRoot . DIRECTORY_SEPARATOR . $sourcePath;
            $destinationPath = strtr($sourcePath, $replacements);
            $destinationPathAbs = $workspacePath . $destinationPath;

            if (is_file($sourcePathAbs)) {
                $content = file_get_contents($sourcePathAbs);
                if ($content === false) {
                    throw new \Exception('Cannot read: `' . $sourcePathAbs . '`');
                }
                $content = strtr($content, $replacements);
                $bytes = file_put_contents($destinationPathAbs, $content);
                if (empty($bytes)) {
                    throw new \Exception('Cannot write: `' . $destinationPathAbs . '`');
                }
            } else if (is_dir($sourcePathAbs)) {
                fn_mkdir($destinationPathAbs);
                if (!is_dir($destinationPathAbs)) {
                    throw new \Exception('Cannot create: `' . $destinationPathAbs . '`');
                }
            } else {

            }
            $destinationPaths[] = !empty($params['absolutePaths']) ? $destinationPathAbs : $destinationPath;
        }

        $this->addResult('make', array(
            'destinationPaths' => $destinationPaths,
            'addon' => $data,
            'workspacePath' => $workspacePath
        ));

        return $destinationPaths;
    }

    /**
     * Filter out files of components that were not chosen
     *
     * @param $sourcePaths
     * @param $data
     *
     * @return mixed
     */
    public function filterOutPaths($sourcePaths, $data)
    {
        $exclusions = array(
            'controller' => array(
                'frontend' => array('controllers/frontend'),
                'backend' => array('controllers/backend'),
                'both' => array('controllers'),
            ),
            'js' => array(
                'frontend' => array(
                    'js/addons/%ID%/frontend.js',
                    'themes_repository/.*/hooks/index/scripts\.post\.tpl'
                ),
                'backend' => array(
                    'js/addons/%ID%/backend.js',
                    'backend/.*/hooks/index/scripts\.post\.tpl'
                ),
                'both' => array('^js'),
            ),
            'css' => array(
                'frontend' => array(
                    'basic/css',
                    'responsive/css',
                    'themes_repository/.*/hooks/index/styles\.post\.tpl'
                ),
                'backend' => array(
                    'backend/css',
                    'backend/.*/hooks/index/styles\.post\.tpl'
                ),
                'both' => array(
//                    'css'
                ),
            ),
            'images' => array(
                'frontend' => array('basic/media', 'responsive/media'),
                'backend' => array('backend/media'),
                'both' => array(),
            ),
            'mail' => array(
                'frontend' => array('basic/mail', 'responsive/mail'),
                'backend' => array('backend/mail'),
                'both' => array(),
            ),
        );


        $combinations = array(
            'frontend' => array(),
            'backend' => array(),
            'both' => array(),
        );

        foreach ($exclusions as $component => $areas) {
            $componentExcludedFrontend = true;
            $componentExcludedBackend = true;
            $componentExcludedBoth = null;

            if (!empty($data)) {
                if (isset($data['frontend']) && in_array($component, $data['frontend'])) {
                    $componentExcludedFrontend = false;
                }
                if (isset($data['backend']) && in_array($component, $data['backend'])) {
                    $componentExcludedBackend = false;
                }
            }
            $componentExcludedBoth = ($componentExcludedFrontend && $componentExcludedBackend);

            $patterns = array();
            if ($componentExcludedBoth) {
                if (!empty($exclusions[$component]['both'])) {
                    $patterns = $exclusions[$component]['both'];
                }
                $patterns = array_merge($patterns, $exclusions[$component]['frontend'], $exclusions[$component]['backend']);
                $combinations['both'][] = $component;
                $combinations['frontend'][] = $component;
                $combinations['backend'][] = $component;
            } else {
                if ($componentExcludedFrontend) {
                        $combinations['frontend'][] = $component;
                    $patterns = $exclusions[$component]['frontend'];
                }
                if ($componentExcludedBackend) {
                    $combinations['backend'][] = $component;
                    $patterns = $exclusions[$component]['backend'];
                }
            }
            if (empty($patterns)) {
                continue;
            }
            foreach ($patterns as $pattern) {
                foreach ($sourcePaths as $i => $path) {
                    $matches = array();
                    if (preg_match('#' . $pattern . '#', $path, $matches)) {
                        unset($sourcePaths[$i]);
                    }
                }
            }
        }

        // it may need further filtering, since now we may have ended up with empty & useless directories
        $tmp = array();
            foreach ($combinations as $area => $components) {
            if (empty($components)) {
                continue;
            }
            sort($components);
            $stack = array();
            foreach ($components as $component) {
                array_push($stack, $component);
                $tmp[] = $area . '-' . implode('-', $stack);

            }
        }
        $combinations = $tmp;


        $combinationExclusions = array(
            'frontend.*css-images-js-mail' => array(
                '^var/themes_repository.*'
            ),
            'backend.*css-images-js-mail' => array(
                '^design.*'
            ),
        );

        foreach ($combinationExclusions as $keyPattern => $pathPatterns) {
            $matched = false;
            foreach ($combinations as $combination) {
                if (preg_match('#' . $keyPattern . '#', $combination)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                continue;
            }
            foreach ($pathPatterns as $pattern) {
                foreach ($sourcePaths as $i => $path) {
                    $matches = array();
                    if (preg_match('#' . $pattern . '#', $path, $matches)) {
                        unset($sourcePaths[$i]);
                    }
                }
            }


        }

        return $sourcePaths;
    }

    /**
     * Archives the add-on
     *
     * @param $paths
     * @param $data
     * @return array|bool
     */
    public function archive($paths, $data)
    {
        $workspacePath =  $this->getWorkspacePath();

        $filename = $data['id'] . '-' . $data['version'] . '.zip';

        $exclusions = array();
        $excluded = array();
        $included = array();

        $downloadUrl = $this->getDownloadUrl();
        $archiveUrl = $downloadUrl . $filename;
        $archivePath = $this->getArchivePath() . DIRECTORY_SEPARATOR . $filename;

        $releaseManager = ReleaseManager::instance();
        if ($releaseManager->archive($paths, $archivePath, $workspacePath, $exclusions, $excluded, $included)) {
            $result = true;

            $this->addResult('archive', array(
                'filename' => $filename,
                'path' => $archivePath,
                'url' => $archiveUrl,
                'includedFiles' => $included,
                'excludedFiles' => $excluded,
            ));

        } else {
            $result = false;
            $this->addErrors($releaseManager->getErrors());
            $this->addError('Failed archiving `' . $archivePath . '`.');
        }

        return $result;
    }

    public function getArchivePath()
    {
        return dirname($this->getWorkspacePath());
    }

    public function getDownloadUrl($absolute = true)
    {
        $archivePath = $this->getArchivePath();
        $rootPath = Registry::get('config.dir.root');
        $relativeArchivePath = str_replace($rootPath, '', $archivePath);
        $relativeArchivePath .= '/';
        $baseUrl = Registry::get('config.http_location');
        if ($absolute) {
            return $baseUrl . $relativeArchivePath;
        } else {
            return $relativeArchivePath;
        }
    }


    /**
     * Installs the add-on from its temporary path
     */
    public function install()
    {
        $workspace = $this->getWorkspacePath();
        return fn_addons_move_and_install($workspace, Registry::get('config.dir.root'));
    }

    /**
     * @param array $data
     * @return array
     */
    public function getFields($data = array())
    {
        $outputPath = $this->getWorkspacePath();

        if (empty($data)) {
            $frontendOptions = array('controller' => 'Y');
            $backendOptions = array('controller' => 'Y');
        } else {
            $backendOptions = (isset($data['backend']) ? $data['backend'] : array());
            if (!empty($backendOptions)) {
                $backendOptions = array_flip($backendOptions);
                array_walk($backendOptions, function (&$value) {
                    $value = 'Y';
                });
            }
            $frontendOptions = (isset($data['frontend']) ? $data['frontend'] : array());
            if (!empty($frontendOptions)) {
                $frontendOptions = array_flip($frontendOptions);
                array_walk($frontendOptions, function (&$value) {
                    $value = 'Y';
                });
            }
        }

        $options = array();
        $options['general'] = array();

        // Processing options
        $options['general'][] = array(
            'name' => 'processing_options',
            'description' => __('developer.generate.processing_options'),
            'type' => 'H'
        );

        $options['general'][] = array(
            'name' => 'frontend',
            'description' => __('developer.generate.addon.frontend'),
            'tooltip' => __('developer.generate.addon.frontend.tooltip'),
            'type' => 'M',
            'variants' => array(
                'controller' => __('developer.generate.addon.frontend.controller'),
                'js' => __('developer.generate.addon.frontend.js'),
                'css' => __('developer.generate.addon.frontend.css'),
                'images' => __('developer.generate.addon.frontend.images'),
                'mail' => __('developer.generate.addon.frontend.mail'),
            ),
            'value' => $frontendOptions
        );
        $options['general'][] = array(
            'name' => 'backend',
            'description' => __('developer.generate.addon.backend'),
            'tooltip' => __('developer.generate.addon.backend.tooltip'),
            'type' => 'M',
            'variants' => array(
                'controller' => __('developer.generate.addon.backend.controller'),
                'js' => __('developer.generate.addon.backend.js'),
                'css' => __('developer.generate.addon.backend.css'),
                'images' => __('developer.generate.addon.backend.images'),
                'mail' => __('developer.generate.addon.backend.mail'),
            ),
            'value' => $backendOptions
        );



        // Post-processing options
        $options['general'][] = array(
            'name' => 'post_actions',
            'description' => __('developer.generate.post_actions'),
            'type' => 'H'
        );

        $options['general'][] = array(
            'name' => 'archive',
            'description' => __('developer.generate.addon.archive'),
            'tooltip' => __('developer.generate.addon.archive.tooltip', array('%path%' => $outputPath)),
            'type' => 'C',
            'value' => (empty($data) || empty($data['archive']) || $data['archive'] == 'Y' ? 'Y' : 'N')
        );

        $options['general'][] = array(
            'name' => 'install',
            'description' => __('developer.generate.addon.install'),
            'tooltip' => __('developer.generate.addon.install.tooltip'),
            'type' => 'C',
            'value' => (!empty($data) && $data['install'] == 'Y' ? 'Y' : 'N')
        );

        $options['general'][] = array(
            'name' => 'settings',
            'description' => __('developer.generate.addon.settings'),
            'type' => 'H'
        );

        $data = $this->prepareData($data);

        foreach ($data as $field => $value) {
            if (in_array($field, array('install', 'archive', 'frontend', 'backend'))) {
                continue;
            }
            $langVar = 'developer.generate.addon.' . $field;
            $tooltip = __($langVar . '.tooltip');
            if (substr($tooltip, 0, 1) == '_') {
                $tooltip = false;
            }
            $type = 'I';
            $variants = null;
            if (in_array($field, array('has_icon'))) {
                $type = 'C';
            }
            if (in_array($field, array('status'))) {
                $type = 'R';
                $variants = array(
                    'active' => __('active'),
                    'disabled' => __('disabled'),
                );
            }
            $options['general'][] = array(
                'name' => $field,
                'description' => __($langVar),
                'value' => $value,
                'type' => $type,
                'tooltip' => $tooltip,
                'variants' => $variants
            );
        }

        return $options;
    }

	/**
	 * @return mixed
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * @param mixed $version
	 */
	public function setVersion($version)
	{
		$this->version = $version;
	}


	/**
	 * @return mixed
	 */
	public function getSourcePath()
	{
		return $this->sourcePath;
	}

	/**
	 * @param mixed $sourcePath
	 */
	public function setSourcePath($sourcePath)
	{
		$this->sourcePath = $sourcePath;
	}

	/**
	 * @return mixed
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param mixed $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}
}