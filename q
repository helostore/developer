[1mdiff --git a/app/addons/developer/controllers/backend/addons.post.php b/app/addons/developer/controllers/backend/addons.post.php[m
[1mindex 792a104..75724f4 100644[m
[1m--- a/app/addons/developer/controllers/backend/addons.post.php[m
[1m+++ b/app/addons/developer/controllers/backend/addons.post.php[m
[36m@@ -23,6 +23,14 @@[m [mif (!defined('BOOTSTRAP')) { die('Access denied'); }[m
 [m
 $addon = (!empty($_REQUEST['addon']) ? $_REQUEST['addon'] : '');[m
 [m
[32m+[m[41m[m
[32m+[m[32m$view = null;[m[41m[m
[32m+[m[32mif ( class_exists( 'Tygh\Tygh' ) ) {[m[41m[m
[32m+[m	[32m$view = &Tygh::$app['view'];[m[41m[m
[32m+[m[32m} else {[m[41m[m
[32m+[m	[32m$view = &Registry::get('view');[m[41m[m
[32m+[m[32m}[m[41m[m
[32m+[m[41m[m
 if ($_SERVER['REQUEST_METHOD'] == 'POST') {[m
 [m
 	fn_trusted_vars([m
[36m@@ -72,12 +80,15 @@[m [mif ($_SERVER['REQUEST_METHOD'] == 'POST') {[m
             $workspacePath = str_replace('\\', '/', $workspacePath);[m
             $workspaceUrl = $creator->getDownloadUrl(false);[m
 [m
[31m-            $msg = Tygh::$app['view']->fetch('addons/developer/views/addons/generate/addon_results.tpl', array([m
[31m-                'results' => $results,[m
[31m-                'hsWorkspacePath' => $workspacePath,[m
[31m-                'hsWorkspaceUrl' => $workspaceUrl,[m
[31m-            ));[m
[31m-            fn_set_notification('I', __('developer.tools'), $msg, 'S');[m
[32m+[m	[32m        if ( ! empty( $view ) ) {[m[41m[m
[32m+[m		[32m        $msg = $view->fetch('addons/developer/views/addons/generate/addon_results.tpl', array([m[41m[m
[32m+[m			[32m        'results' => $results,[m[41m[m
[32m+[m			[32m        'hsWorkspacePath' => $workspacePath,[m[41m[m
[32m+[m			[32m        'hsWorkspaceUrl' => $workspaceUrl,[m[41m[m
[32m+[m		[32m        ));[m[41m[m
[32m+[m		[32m        fn_set_notification('I', __('developer.tools'), $msg, 'S');[m[41m[m
[32m+[m	[32m        }[m[41m[m
[32m+[m[41m[m
             $redirect = 'addons.manage';[m
         }[m
 [m
[36m@@ -148,11 +159,14 @@[m [mif ($mode == 'generate' || $mode == 'manage') {[m
     $previousData = json_decode($previousData, true);[m
     $previousData = is_array($previousData) ? $previousData : array();[m
     $fields = $creator->getFields($previousData);[m
[31m-	Tygh::$app['view']->assign('hsAddonFields', $fields);[m
 [m
     $workspacePath = $creator->getArchivePath();[m
     $workspacePath = str_replace('\\', '/', $workspacePath);[m
     $workspaceUrl = $creator->getDownloadUrl(false);[m
[31m-	Tygh::$app['view']->assign('hsWorkspacePath', $workspacePath);[m
[31m-	Tygh::$app['view']->assign('hsWorkspaceUrl', $workspaceUrl);[m
[32m+[m[41m[m
[32m+[m	[32mif ( ! empty( $view ) ) {[m[41m[m
[32m+[m		[32m$view->assign('hsAddonFields', $fields);[m[41m[m
[32m+[m		[32m$view->assign('hsWorkspacePath', $workspacePath);[m[41m[m
[32m+[m		[32m$view->assign('hsWorkspaceUrl', $workspaceUrl);[m[41m[m
[32m+[m	[32m}[m[41m[m
 }[m
[1mdiff --git a/app/addons/developer/controllers/backend/init.post.php b/app/addons/developer/controllers/backend/init.post.php[m
[1mindex e79b9ce..bb1b3ae 100644[m
[1m--- a/app/addons/developer/controllers/backend/init.post.php[m
[1m+++ b/app/addons/developer/controllers/backend/init.post.php[m
[36m@@ -17,6 +17,15 @@[m [muse Tygh\Tygh;[m
 [m
 if (!defined('BOOTSTRAP')) { die('Access denied'); }[m
 [m
[32m+[m[32m$view = null;[m
[32m+[m[32mif (class_exists('Tygh\Tygh')) {[m
[32m+[m	[32m$view = &Tygh::$app['view'];[m
[32m+[m[32m} else {[m
[32m+[m	[32m$view = &Registry::get('view');[m
[32m+[m[32m}[m
[32m+[m
[32m+[m[32mif (!empty($view)) {[m
[32m+[m	[32m$cscVersion = fn_developer_parse_version(PRODUCT_VERSION);[m
[32m+[m	[32mTygh::$app['view']->assign('cscVersion', $cscVersion);[m
[32m+[m[32m}[m
 [m
[31m-$cscVersion = fn_developer_parse_version(PRODUCT_VERSION);[m
[31m-Tygh::$app['view']->assign('cscVersion', $cscVersion);[m
[1mdiff --git a/app/addons/developer/func.php b/app/addons/developer/func.php[m
[1mindex 3652d55..1787117 100644[m
[1m--- a/app/addons/developer/func.php[m
[1m+++ b/app/addons/developer/func.php[m
[36m@@ -16,6 +16,8 @@[m [muse Tygh\Registry;[m
 [m
 if (!defined('BOOTSTRAP')) { die('Access denied'); }[m
 [m
[32m+[m[32mrequire_once __DIR__ . '/vendor/autoload.php';[m
[32m+[m
 function fn_developer_parse_version($string)[m
 {[m
 	$result = array([m
[36m@@ -36,12 +38,6 @@[m [mfunction fn_developer_parse_version($string)[m
 	}[m
 	return $result;[m
 }[m
[31m-function fn_developer_dispatch_before_display()[m
[31m-{[m
[31m-	$view = &\Tygh\Tygh::$app['view'];[m
[31m-	if (AREA == 'A') {[m
[31m-	}[m
[31m-}[m
 [m
 function fn_developer_get_web_path()[m
 {[m
[1mdiff --git a/app/addons/developer/init.php b/app/addons/developer/init.php[m
[1mindex 51cf207..3e8669e 100644[m
[1m--- a/app/addons/developer/init.php[m
[1m+++ b/app/addons/developer/init.php[m
[36m@@ -22,7 +22,6 @@[m [mif (defined('DEVELOPMENT')) {[m
 	if (AREA == 'A') {[m
 		fn_register_hooks('smarty_block_hook_post');[m
 	}[m
[31m-	fn_register_hooks('dispatch_before_display');[m
 }[m
 [m
 fn_register_hooks('send_mail_pre');[m
[1mdiff --git a/app/addons/developer/src/HeloStore/Developer/AddonHelper.php b/app/addons/developer/src/HeloStore/Developer/AddonHelper.php[m
[1mindex 236260b..20b9689 100644[m
[1m--- a/app/addons/developer/src/HeloStore/Developer/AddonHelper.php[m
[1m+++ b/app/addons/developer/src/HeloStore/Developer/AddonHelper.php[m
[36m@@ -64,10 +64,13 @@[m [mclass AddonHelper extends Singleton[m
             if ($theme_name != $current_theme_name) {[m
                 continue;[m
             }[m
[31m-            $manifest = Themes::factory($theme_name)->getRepoManifest();[m
[31m-            if (empty($manifest)) {[m
[31m-                $manifest = Themes::factory($theme_name)->getManifest();[m
[31m-            }[m
[32m+[m	[32m        $manifest = array();[m
[32m+[m	[32m        if ( class_exists( '\Tygh\Themes\Themes' ) ) {[m
[32m+[m		[32m        $manifest = Themes::factory($theme_name)->getRepoManifest();[m
[32m+[m		[32m        if (empty($manifest)) {[m
[32m+[m			[32m        $manifest = Themes::factory($theme_name)->getManifest();[m
[32m+[m		[32m        }[m
[32m+[m	[32m        }[m
             if (isset($manifest['parent_theme'])) {[m
                 if (empty($manifest['parent_theme'])) {[m
                     $parent_path = fn_get_theme_path('[repo]/' . $theme_name . '/');[m
[36m@@ -155,4 +158,4 @@[m [mclass AddonHelper extends Singleton[m
 		$after = intval( $after );[m
 		return $after - $before;[m
 	}[m
[31m-}[m
\ No newline at end of file[m
[32m+[m[32m}[m
