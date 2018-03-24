<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

CModule::IncludeModule('iblock');
CModule::IncludeModule('vettich.sp');
IncludeModuleLangFile(__FILE__);

use vettich\SP\Module;
use vettich\devform\types;

if(!$id && !empty($_GET['ID'])) {
	$id = $_GET['ID'];
}

$data = new vettich\devform\data\orm(array(
	'db' => 'vettich\SP\db\pending',
	'prefix' => '_',
));

$iblockType = $data->get('_IBLOCK_TYPE');
$rs = CIBlockType::GetByIDLang($iblockType, LANG);
$iblockTypeValue = '['.$iblockType.'] <a href="/bitrix/admin/iblock_admin.php?type='.$iblockType.'">'.$rs['NAME'].'</a>';

$iblockID = $data->get('_IBLOCK_ID');
$iblockIDValue = '['.$iblockID.'] <a href="/bitrix/admin/iblock_edit.php?type='.$iblockType.'&ID='.$iblockID.'">'.CIBlock::GetArrayByID($iblockID, 'NAME').'</a>';

$elemID = $data->get('_ELEMENT_ID');
$rs = CIBlockElement::GetList(array(), array('ID' => $elemID), false, false, array('ID', 'NAME'));
if($ar = $rs->GetNext()) {
	$elemValue = '['.$elemID.'] <a href="/bitrix/admin/iblock_element_edit.php?type='.$iblockType.'&IBLOCK_ID='.$iblockID.'&ID='.$elemID.'">'.$ar['NAME'].'</a>';
} else {
	$elemValue = GetMessage('VETTICH_SP_NOT_EXISTS');
}

$additionalTabs = array();
$params = array(
	'_NAME' => 'text:#VDF_NAME#',
	'_IS_ENABLE' => 'checkbox:#VDF_IS_ENABLE#',
);

$pendingType = $data->get('_TYPE', 'IBLOCK');
if($pendingType == 'IBLOCK') {
	$params += array(
		'h1' => 'heading:#VETTICH_SP_INFORMATION#',
		'ELEMENT' => array(
			'type' => 'plaintext',
			'title' => '#VETTICH_SP_POST_ELEM_ID#',
			'on renderTemplate' => function($obj, $template, &$repl) {
				global $elemValue;
				$repl['{value}'] = $elemValue;
			}
		),
		'IBLOCK' => array(
			'type' => 'plaintext',
			'title' => '#POST_IBLOCK_ID#',
			'on renderTemplate' => function($obj, $template, &$repl) {
				global $iblockIDValue;
				$repl['{value}'] = $iblockIDValue;
			}
		),
		'IBLOCK_TYPE' => array(
			'type' => 'plaintext',
			'title' => '#POST_IBLOCK_TYPE#',
			'on renderTemplate' => function($obj, $template, &$repl) {
				global $iblockTypeValue;
				$repl['{value}'] = $iblockTypeValue;
			}
		),
		'POST' => array(
			'type' => 'plaintext',
			'title' => '#VETTICH_SP_POST#',
			'on renderTemplate' => function($obj, $template, &$repl) {
				global $data;
				$value = $data->get('_POST_ID');
				$rsPost = vettich\SP\db\postTable::getById($value);
				if($arPost = $rsPost->fetch()) {
					$name = $arPost['NAME'];
					$repl['{value}'] = '['.$value.'] <a href="/bitrix/admin/vettich.sp.posts_edit.php?ID='.$value.'&back_url='.urlencode($_SERVER['REQUEST_URI']).'">'.$name.'</a>';
				} else {
					$repl['{value}'] = $value .' ['.GetMessage('VETTICH_SP_NOT_EXISTS').']';
				}
			},
		),
	);
} elseif($pendingType == 'MIX') {
	if(!empty($_POST) && $_GET['ajax'] != 'Y') {
		// $_POST['POPUP_CONDITIONS'] = Module::cleanConditions($_POST['POPUP_CONDITIONS']);
		if($_POST['_PUBLISH']) foreach($_POST['_PUBLISH'] as $key => $value) {
			if(isset($value['CONDITIONS'])) {
				$_POST['_PUBLISH'][$key]['CONDITIONS'] = Module::cleanConditions($value['CONDITIONS']);
			}
		}
	}
	$params += array(
		'h1' => 'heading:#VETTICH_SP_INFORMATION#',
		'ELEMENT' => array(
			'type' => 'plaintext',
			'title' => '#VETTICH_SP_POST_ELEM_ID#',
			'on renderTemplate' => function($obj, $template, &$repl) {
				global $elemValue;
				$repl['{value}'] = $elemValue;
			}
		),
		'IBLOCK' => array(
			'type' => 'plaintext',
			'title' => '#POST_IBLOCK_ID#',
			'on renderTemplate' => function($obj, $template, &$repl) {
				global $iblockIDValue;
				$repl['{value}'] = $iblockIDValue;
			}
		),
		'IBLOCK_TYPE' => array(
			'type' => 'plaintext',
			'title' => '#POST_IBLOCK_TYPE#',
			'on renderTemplate' => function($obj, $template, &$repl) {
				global $iblockTypeValue;
				$repl['{value}'] = $iblockTypeValue;
			}
		),
	);
	$individ = ($_POST['_PUBLISH']['COMMON']['INDIVIDUAL_SETTINGS'] == 'Y'
		or (empty($_POST) && $data->get('_PUBLISH[COMMON][INDIVIDUAL_SETTINGS]') == 'Y'));
	$iblock_id = $data->get('_IBLOCK_ID');
	$_params = array('heading4' => 'heading:#VETTICH_SP_CHOOSE_POST_ACCOUNTS#');
	$_params += (array) Module::socialAccountsForDevForm('_SOCIALS', $individ ? array('onclick' => 'Vettich.Devform.Refresh(this);') : array());
	$_params += array(
			'heading5' => 'heading:#VETTICH_SP_COMMON_DESCRIPTION#',
			'_PUBLISH[COMMON][INDIVIDUAL_SETTINGS]' => 'checkbox:#VETTICH_SP_PUBLISH_INDIVIDUAL_SETTINGS#:N:help=#VETTICH_SP_PUBLISH_INDIVIDUAL_SETTINGS_HELP#:refresh=Y',
			'_PUBLISH[COMMON][MAIN_PICTURE]' => array(
				'type' => $individ ? 'hidden' : 'select',
				'title' => '#VETTICH_SP_PUBLISH_MAIN_PICTURE#',
				'help' => '#VETTICH_SP_PUBLISH_MAIN_PICTURE_HELP#',
				'options' => Module::allPropsFor($iblock_id),
				'default_value' => 'DETAIL_PICTURE',
			),
			'_PUBLISH[COMMON][OTHER_PICTURE]' => array(
				'type' => $individ ? 'hidden' : 'select',
				'title' => '#VETTICH_SP_PUBLISH_OTHER_PICTURE#',
				'help' => '#VETTICH_SP_PUBLISH_OTHER_PICTURE_HELP#',
				'options' => Module::allPropsFor($iblock_id),
				'default_value' => 'PROPERTY_MORE_PICTURES',
			),
			'_PUBLISH[COMMON][LINK]' => array(
				'type' => $individ ? 'hidden' : 'select',
				'title' => '#VETTICH_SP_PUBLISH_LINK#',
				'help' => '#VETTICH_SP_PUBLISH_LINK_HELP#',
				'options' => Module::allPropsFor($iblock_id),
				'default_value' => 'DETAIL_PAGE_URL',
			),
			// '_PUBLISH[COMMON][LINK_TITLE]' => array(
			// 	'type' => $individ ? 'hidden' : 'select',
			// 	'title' => '#VETTICH_SP_PUBLISH_LINK_TITLE#',
			// 	'help' => '#VETTICH_SP_PUBLISH_LINK_TITLE_HELP#',
			// 	'options' => Module::allPropsFor($iblock_id),
			// 	'default_value' => 'NAME',
			// ),
			// '_PUBLISH[COMMON][LINK_DESCRIPTION]' => array(
			// 	'type' => $individ ? 'hidden' : 'select',
			// 	'title' => '#VETTICH_SP_PUBLISH_LINK_DESCRIPTION#',
			// 	'help' => '#VETTICH_SP_PUBLISH_LINK_DESCRIPTION_HELP#',
			// 	'options' => Module::allPropsFor($iblock_id),
			// 	'default_value' => 'PREVIEW_TEXT',
			// ),
			'_PUBLISH[COMMON][TEXT]' => array(
				'type' => $individ ? 'hidden' : 'textarea',
				'title' => '#VETTICH_SP_PUBLISH_TEXT#',
				'help' => '#VETTICH_SP_PUBLISH_TEXT_HELP#',
				'items' => Module::allPropsMacrosFor($iblock_id),
				'default_value' => "#NAME##BR#\n#BR#\n#PREVIEW_TEXT#",
				'params' => array('rows' => 6),
			),
		);
	$additionalTabs[] = array(
		'name' => '#VETTICH_SP_COMMON_DESCRIPTION#',
		'params' => $_params,
	);
	if($individ
		&& (($accounts = array_keys($_POST['_SOCIALS']))
			or (empty($_POST) && $accounts = $data->get('_SOCIALS')))) {
		$show_types = array();
		foreach ($accounts as $key => $v) {
			$social = Module::socialForId($v);
			if(empty($social)
				|| in_array($social['id'], $show_types)) {
				continue;
			}
			$show_types[] = $social['id'];
			$_params = $social['class']->publishParams($iblock_id, '_PUBLISH['.$social['class']::$socialid.']');
			/**
			 * отображение условий публикаций в соц. сеть
			 */
			$_params['heading_cond_'.$social['class']::$socialid] = 'heading:#VETTICH_SP_CONDITIONS_POST#';
			$_params['_PUBLISH['.$social['class']::$socialid.'][CONDITIONS]'] = array(
				'type' => 'group',
				'title' => '#VETTICH_SP_CONDITIONS#',
				'options' => array(
					'field' => array(
						'type' => 'select',
						'title' => '',
						'options' => Module::allPropsFor($iblock_id),
					),
					'cmp' => array(
						'type' => 'select',
						'title' => '',
						'options' => array(
							'==' => '#VETTICH_SP_==#',
							'!=' => '#VETTICH_SP_!=#',
							'<=' => '#VETTICH_SP_<=#',
							'>=' => '#VETTICH_SP_>=#',
							'include' => '#VETTICH_SP_COND_INCLUDE#',
							'notinclude' => '#VETTICH_SP_COND_NOTINCLUDE#',
						),
					),
					'value' => 'text::params=[size=auto]',
				),
			);
			$additionalTabs[] = array(
				'name' => $social['name'],
				'title' => '#VETTICH_SP_SETTINGS_FOR# '.$social['name'],
				'params' => $_params,
			);
		}
	}

}

$params += array(
	'_STATUS' => array(
		'type' => 'select',
		'title' => '#VETTICH_SP_STATUS#',
		'options' => array(
			'PUBLISH' => '#VETTICH_SP_STATUS_PUBLISH#',
			'UPDATE' => '#VETTICH_SP_STATUS_UPDATE#',
			'DELETE' => '#VETTICH_SP_STATUS_DELETE#',
		),
	),
	'_RESULT' => array(
		'type' => 'select',
		'title' => '#VETTICH_SP_RESULT#',
		'options' => array(
			'READY' => '#VETTICH_SP_RESULT_READY#',
			'RUNNING' => '#VETTICH_SP_RESULT_RUNNING#',
			'SUCCESS' => '#VETTICH_SP_RESULT_SUCCESS#',
			'ERROR' => '#VETTICH_SP_RESULT_ERROR#',
			'WARNING' => '#VETTICH_SP_RESULT_WARNING#',
			'ERROR_POST' => '#VETTICH_SP_RESULT_ERROR_POST#',
		),
	),
	'h2' => 'heading:#VETTICH_SP_SOC_NETWORKS#'
);
$accounts = array();
$accData = $data->get('_ACCOUNTS');
foreach ((array)$accData as $_id => $_data) {
	$social = Module::social($_data['type']);
	$acc = Module::account($_id);
	if(!isset($social['class'])) {
		continue;
	}
	$socialClass = $social['class'];
	$accounts[$social['id']][] = '<b>'.$socialClass::accountLinkEdit($acc).'</a>:</b> '
		.$social['class']->viewData($_data, $acc)
		.'<br>'.(new types\divbutton('remove-social', array(
			'value' => '#VETTICH_SP_REMOVE#',
			'onclick' => 'Vettich.SP.RemoveSocial('.$_GET['ID'].','.$_id.');',
			'params' => array('title' => '#VETTICH_SP_REMOVE_POST#'),
			'template' => '{content}',
		)))->render();
}
foreach($accounts as $type => $acc) {
	$social = Module::social($type);
	$params['acc_'.$type] = array(
		'type' => 'plaintext',
		'title' => $social['name'],
		'value' => implode('<br>', $acc),
	);
}
if(empty($accounts)) {
	$params['not_accs'] = 'plaintext::#EMPTY#';
}

$tabs = array(
	array(
		'name' => '#VETTICH_SP_RECORD#',
		'title' => '#VETTICH_SP_RECORD_LOOK_EDIT#',
		'params' => $params,
	),
);
$tabs = array_merge($tabs, $additionalTabs);

(new \vettich\devform\AdminForm('devform', array(
	'pageTitle' => ($id > 0 ? '#VETTICH_SP_EDIT_RECORD#' : '#VETTICH_SP_ADD_RECORD#'),
	'tabs' => $tabs,
	'buttons' => array(
		'_save' => 'buttons\saveSubmit:#VDF_SAVE#',
		'_apply' => 'buttons\submit:#VDF_APPLY#',
	),
	'data' => $data,
)))->render();

if($_GET['ajax'] != 'Y') {
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
}
