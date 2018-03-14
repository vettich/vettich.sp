<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
// require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");

CModule::IncludeModule('vettich.devform');
CModule::IncludeModule('vettich.sp');
IncludeModuleLangFile(__FILE__);

use vettich\SP\Module;
use vettich\devform\types;
use vettich\SP\Socials\vk\api;

$groups = api::groups($_GET['access_token']);
$arGroups = array();
foreach ($groups['response']['items'] as $value) {
	$arGroups['g'.':'.$value['id'].':'.$value['screen_name']] = '<img src="'.$value['photo_50'].'" alt=""> '
		.$GLOBALS['APPLICATION']->ConvertCharset($value['name'], "UTF-8", SITE_CHARSET);
}
$rsProfile = api::method('users.get', array(
	'access_token' => $_GET['access_token'],
	'fields' => 'photo_50,screen_name',
));
$arProfile = array();
if(!empty($rsProfile['response'])) {
	$value = $rsProfile['response'][0];
	$arProfile['p'.':'.$value['id'].':'.$value['screen_name']] = '<img src="'.$value['photo_50'].'" alt=""> '
		.$GLOBALS['APPLICATION']->ConvertCharset($value['first_name'].' '.$value['last_name'], "UTF-8", SITE_CHARSET);
}

(new \vettich\devform\AdminForm('devform', array(
	'tabs' => array(
		array(
			'name' => '#VETTICH_SP_SELECT_GROUP_OR_PROFILE#',
			'params' => array(
				'access_token' => 'hidden::'.$_GET['access_token'],
				new types\radio('group', array(
					'title' => '#VETTICH_SP_PROFILE#',
					'options' => $arProfile,
				)),
				new types\radio('group', array(
					'title' => '#VETTICH_SP_GROUPS#',
					'options' => $arGroups,
				)),
			),
		),
	),
	'buttons' => array(
		'select' => 'buttons\saveSubmit:#VCH_SELECT#:params=[onclick=group_select();return false;]',
		'close' => 'buttons\simple:#VCH_CANCEL#:params=[onclick=window.close();]',
	),
	'css' => '#group img{height:auto;width:2.5em;border-radius: 50%;vertical-align: middle;}',
	'js' => '
	function group_select() {
		if($("input[name=group]:checked").length) {
			if(window.opener) {
				var elem = $("input[name=group]:checked");
				var value = elem.val().split(":");
				window.opener.accessTokenSet(
					$("#access_token").val(),
					value[1], // id
					$("label[for=\"" + elem.attr("id") + "\"]").text().trim(), // name
					value[0] == "p", //is profile
					value[2] // screen_name
				);
				window.close();
			}
		} else {
			alert("Select any group");
		}
	}',
)))->render();

if($_GET['ajax'] != 'Y') {
	// require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");
}
