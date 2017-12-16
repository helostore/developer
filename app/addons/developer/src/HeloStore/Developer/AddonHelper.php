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

use Tygh\Addons\SchemesManager;
use Tygh\Languages\Languages;
use Tygh\Registry;
use Tygh\Themes\Themes;

/**
 * Class AddonHelper
 *
 * @package HeloStore\Developer
 */
class AddonHelper extends Singleton
{
    public function getPaths($addon)
    {
        $basePath = Registry::get('config.dir.root');

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
            $basic_path = fn_get_theme_path('[repo]/basic/');
            $source = fn_get_theme_path('[themes]/' . $theme_name . '/', 'C');
            $repo_paths = array(
                // fn_get_theme_path('[repo]/basic' . '/'),
                $basic_path,
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

        return $paths;
    }

	/**
	 * @param $addonId
	 *
	 * @return int
	 */
	public function refreshTranslations($addonId) {
		$addon_scheme = SchemesManager::getScheme($addonId);
		$updates = 0;
		$before = db_get_field( 'SELECT COUNT(*) FROM ?:language_values' );
		$before = intval( $before );
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
		$after = db_get_field( 'SELECT COUNT(*) FROM ?:language_values' );
		$after = intval( $after );
		return $after - $before;
	}
}