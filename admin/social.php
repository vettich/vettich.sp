<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

CModule::IncludeModule('vettich.sp');
CModule::IncludeModule('iblock');
IncludeModuleLangFile(__FILE__);

$socialid = isset($_GET['socialid']) ? $_GET['socialid'] : false;
if(!empty($socialid) && !empty($social = vettich\SP\Module::social($socialid))) {
	if(!$_GET['no']) {
		$db = vettich\SP\Social::accountsDB();
		$rs = $db::getList(array(
			'limit' => 1,
			'filter' => array('TYPE' => $socialid),
		));
		if(!$rs->fetch()) {
			header('Location: /bitrix/admin/vettich.sp.social_edit.php?socialid='.$socialid.'&back_url='.urlencode('/bitrix/admin/vettich.sp.social.php?no=1&socialid='.$socialid));
			exit;
		}
	}
	$social['class']::adminList();
} else {
	require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');
	echo "$socialid is not found";
}

if($_GET['ajax'] != 'Y') {
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
}
