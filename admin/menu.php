<?
if(!$APPLICATION->GetGroupRight('vettich.sp')>'D')
{
	return false;
}

if(!CModule::IncludeModule('vettich.sp')) {
	return false;
}
IncludeModuleLangFile(__FILE__);
$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/css/vettich.sp/menu.css');
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/vettich.sp/script.js');

use Vettich\SP\Module;

$menuName = GetMessage('VettichSPMenu_Text').' (v. '.Module::version().')';

$aMenu = array(
	'parent_menu'	=> 'global_menu_services',
	'sort'			=> 99,
	'icon'			=> 'vettich_sp',
	'text'			=> $menuName,
	'items_id'		=> 'vettich_sp',
	'module_id'		=> 'vettich.sp',
	'items'			=> array(
		array(
			'text'		=> GetMessage('VettichSPMenu_Posts'),
			'title'		=> GetMessage('VettichSPMenu_Posts_Title'),
			'url'		=> '/bitrix/admin/vettich.sp.posts.php',
			'more_url' 	=> array(
				'/bitrix/admin/vettich.sp.posts_edit.php',
			),
		)
	),
);

$arMenu = array(
	'text' 		=> GetMessage('VettichSPMenu_Accounts'),
	'items_id' 	=> 'vettich_sp_accounts',
	'dynamic' 	=> 'true',
	'module_id' => 'vettich.sp',
	'items' 	=> array(),
);

if(method_exists($this, 'IsSectionActive')
	&& $this->IsSectionActive('vettich_sp_accounts')) {
	$socials = Module::socials();
	// $socials = \Vettich\SP\PostingFunc::GetSocials();
	foreach($socials as $social)
	{
		// $func = \Vettich\SP\PostingFunc::module2($social, 'func');
		$arMenu['items'][$social['id']] = array(
			'text' 		=> $social['name'],
			'url' 		=> '/bitrix/admin/vettich.sp.social.php?socialid='.$social['id'],
			// 'url' 		=> '/bitrix/admin/vettich_sp_posts_'.$social['id'].'.php',
			'more_url'	=> array(
				// '/bitrix/admin/vettich_sp_posts_edit_'.$social['id'].'.php',
				'/bitrix/admin/vettich.sp.social_edit.php?socialid='.$social['id'],
			),
		);
	}
}

$aMenu['items'][] = $arMenu;
$aMenu['items'][] = array(
	'text'		=> GetMessage('VettichSPMenu_Queue'),
	'url'		=> '/bitrix/admin/vettich.sp.queue.php',
	'more_url'	=> array('/bitrix/admin/vettich.sp.queue_edit.php'),
	'items_id' 	=> 'vettich_sp_queue',
);
// $aMenu['items'][] = array(
// 	'text'		=> GetMessage('VettichSPMenu_Logs'),
// 	'url'		=> '/bitrix/admin/vettich_sp_logs.php',
// 	'items_id' 	=> 'vettich_sp_logs',
// );
$aMenu['items'][] = array(
	'text'		=> GetMessage('VettichSPMenu_Settings'),
	'url'		=> '/bitrix/admin/settings.php?lang=ru&mid=vettich.sp',
	'items_id' 	=> 'vettich_sp_settings',
);

return $aMenu;
