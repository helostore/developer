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

use HeloStore\ADLS\LicenseClient;

if ($mode == 'sidekick_messages_translations') {
	$client = new LicenseClient();
	$constants = $client->getCodeConstants();
	$output = array();
	$prefix = 'sidekick.';
	$preview = isset($_REQUEST['preview']);
	foreach ($constants as $type => $pairs) {
		foreach ($pairs as $const => $code) {
			$langVar = $prefix . strtolower($const);
			$currentTranslation = __($langVar);
			$currentTranslation = ($currentTranslation == '_' . $langVar ? '' : $currentTranslation);
			$message =
'msgctxt "Languages::' . $langVar . '"
msgid "' . strtolower($const) . '"
msgstr "' . $currentTranslation . '"
';
			$output[] = htmlentities($message);
			if ($prefix) {
				fn_set_notification('N', 'Test', $currentTranslation, 'K');
			}
		}
	}
	echo '<pre>';
	echo implode("\n", $output);
	echo '</pre>';

	exit;
}