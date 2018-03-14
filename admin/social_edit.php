<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

CModule::IncludeModule('vettich.devform');
CModule::IncludeModule('vettich.sp');
CModule::IncludeModule('iblock');
IncludeModuleLangFile(__FILE__);

if(!empty($_POST) && SITE_CHARSET != 'UTF-8') {
	$_POST = vettich\devform\Module::convertEncodingToCurrent($_POST);
}

$socialid = isset($_GET['socialid']) ? $_GET['socialid'] : false;
if(!empty($socialid) && !empty($social = vettich\SP\Module::social($socialid))) {
	$social['class']::adminForm();
} else {
	echo "$socialid is not found";
}

if($_GET['ajax'] != 'Y') {
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
}
