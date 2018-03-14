<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
CModule::IncludeModule('vettich.sp');
IncludeModuleLangFile(__FILE__);

// старт сессии необходим библиотеке
if(!session_id()) {
	session_start();
}

require(VETTICH_SP_DIR.'/classes/Facebook/autoload.php');

use vettich\sp\Module;
use vettich\devform\types;

$callback = $_SERVER['SERVER_NAME'].'/bitrix/admin/vettich.sp.fb_callback.php?app_id='.$app_id.'&app_secret='.$app_secret;
if($_SERVER['HTTPS']) {
	$callback = 'https://'.$callback;
} else {
	$callback = 'http://'.$callback;
}

if(!empty($_GET['code'])) {
	if(!session_id()) {
		session_start();
	}
	$app_id = "";
	$app_secret = "";
	if(isset($_GET['app_id'])) {
		$app_id = trim($_GET['app_id']);
	}
	if(isset($_GET['app_secret'])) {
		$app_secret = trim($_GET['app_secret']);
	}
	$fb = new Facebook\Facebook([
		'app_id'  => $app_id,
		'app_secret' => $app_secret,
		'default_graph_version' => 'v2.9',
	]);
	$helper = $fb->getRedirectLoginHelper();
	try {
		$accessToken = $helper->getAccessToken($callback);
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
		// When Graph returns an error
		echo 'Graph returned an error: ' . $APPLICATION->ConvertCharset($e->getMessage(), 'UTF-8', SITE_CHARSET);
		exit;
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
		// When validation fails or other local issues
		echo 'Facebook SDK returned an error: ' . $APPLICATION->ConvertCharset($e->getMessage(), 'UTF-8', SITE_CHARSET);
		exit;
	}
	if (! isset($accessToken)) {
		if ($helper->getError()) {
			header('HTTP/1.0 401 Unauthorized');
			echo "Error: " . $helper->getError() . "<br/>\n";
			echo "Error Code: " . $helper->getErrorCode() . "<br/>\n";
			echo "Error Reason: " . $helper->getErrorReason() . "<br/>\n";
			echo "Error Description: " . $helper->getErrorDescription() . "<br/>\n";
		} else {
			header('HTTP/1.0 400 Bad Request');
			echo 'Bad request';
		}
		exit;
	}
	if (! $accessToken->isLongLived()) {
		// Exchanges a short-lived access token for a long-lived one
		try {
			$oAuth2Client = $fb->getOAuth2Client();
			$accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
		} catch (Facebook\Exceptions\FacebookSDKException $e) {
			// echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
			// exit;
		}
		// echo '<h3>Long-lived</h3>';
		// var_dump($accessToken->getValue());
	}
	$expires = $accessToken->getExpiresAt();
	if($expires) {
		$expires = $expires->getTimestamp();
	}
	$_GET['access_token'] = $accessToken->getValue();
	$_GET['expires'] = $expires;
}

$fb = new Facebook\Facebook(array(
	'app_id' => $_GET['app_id'],
	'app_secret' => $_GET['app_secret'],
	'default_graph_version' => 'v2.9',
	'default_access_token' => $_GET['access_token'],
));
try {
	$response = $fb->get('/me/groups');
	$groups = $response->getDecodedBody();
	$groups = $groups['data'];
	$response = $fb->get('/me/accounts');
	$pages = $response->getDecodedBody();
	$pages = $pages['data'];
} catch(Facebook\Exceptions\FacebookResponseException $e) {
	// When Graph returns an error
	echo 'Graph returned an error: ' . $APPLICATION->ConvertCharset($e->getMessage(), 'UTF-8', SITE_CHARSET);
	exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
	// When validation fails or other local issues
	echo 'Facebook SDK returned an error: ' . $APPLICATION->ConvertCharset($e->getMessage(), 'UTF-8', SITE_CHARSET);
	exit;
}

$arGroups = array();
foreach ($groups as $group) {
	$arGroups[$group['id']] = $GLOBALS['APPLICATION']->ConvertCharset($group['name'], "UTF-8", SITE_CHARSET);
}
$arPages = array();
foreach ($pages as $page) {
	$arPages[$page['id']] = $GLOBALS['APPLICATION']->ConvertCharset($page['name'], "UTF-8", SITE_CHARSET);
}

(new \vettich\devform\AdminForm('devform', array(
	'tabs' => array(
		array(
			'name' => '#VCH_SELECT_GROUP#',
			'params' => array(
				'access_token' => 'hidden::'.$_GET['access_token'],
				'expires' => 'hidden::'.$_GET['expires'],
				'app_id' => 'hidden::'.$_GET['app_id'],
				'app_secret' => 'hidden::'.$_GET['app_secret'],
				!empty($arGroups) ? new types\radio('group', array(
					'title' => '#VETTICH_SP_FB_GROUPS#',
					'options' => $arGroups,
				)) : 'hidden',
				new types\radio('group', array(
					'title' => '#VETTICH_SP_FB_PAGES#',
					'options' => $arPages,
				)),
			),
		),
	),
	'buttons' => array(
		'select' => 'buttons.saveSubmit:#VCH_SELECT#:params=[onclick=group_select();return false;]',
		'close' => 'buttons.simple:#VCH_CANCEL#:params=[onclick=window.close();]',
	),
	'js' => '
	function group_select() {
		if($("input[name=group]:checked").length) {
			if(window.opener) {
				var elem = $("input[name=group]:checked");
				window.opener.accessTokenSet(
					$("#app_id").val(),
					$("#app_secret").val(),
					$("#access_token").val(),
					$("#expires").val(),
					elem.val(),
					$("label[for=" + elem.attr("id") + "]").text().trim()
				);
				window.close();
			}
		} else {
			alert("Select any group");
		}
	}',
	'css' => 'body{height:auto}',
)))->render();

if($_GET['ajax'] != 'Y') {
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");
}
