<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

CModule::IncludeModule('vettich.sp');
CModule::IncludeModule('iblock');
IncludeModuleLangFile(__FILE__);

if(!$_GET['no']) {
	$rs = vettich\SP\db\postTable::getList(array(
		'limit' => 1,
	));
	if(!$rs->fetch()) {
		header('Location: /bitrix/admin/vettich.sp.posts_edit.php?back_url='.urlencode('/bitrix/admin/vettich.sp.posts.php?no=1'));
		exit;
	}
}

(new \vettich\devform\AdminList('#VCH_POSTS_PAGE_TITLE#', 'vap_posts_list', array(
	'dbClass' => 'vettich\SP\db\post',
	'params' => array(
		'ID' => 'number',
		'NAME' => 'textlink:#VDF_NAME#',
		'IS_ENABLE' => 'checkbox:#VDF_IS_ENABLE#',
		'IBLOCK_TYPE' => array(
			'type' => 'text',
			'title' => '#POST_IBLOCK_TYPE#',
			'on renderView' => array('Vettich\SP\Module', 'onRenderViewIblockType'),
		),
		'IBLOCK_ID' => array(
			'type' => 'text',
			'title' => '#POST_IBLOCK_ID#',
			'on renderView' => array('Vettich\SP\Module', 'onRenderViewIblockId'),
		),
	),
	'dontEdit' => array('ID', 'IBLOCK_TYPE', 'IBLOCK_ID'),
	'buttons' => array(
		// 'add' => null,
		// 'unload' => 'buttons\simple:Unload goods',
	),
	'linkEditInsert' => array('NAME'),
)))->render();

if($_GET['ajax'] != 'Y') {
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
}
