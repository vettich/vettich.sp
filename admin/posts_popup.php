<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_popup_admin.php');

CModule::IncludeModule('vettich.devform');
CModule::IncludeModule('vettich.sp');
CModule::IncludeModule('iblock');
IncludeModuleLangFile(__FILE__);

use vettich\sp\Module;
use vettich\sp\Pending;
use vettich\devform\data;
define('VETTICH_DEBUG', true);

if($_GET['finish']) {
	$total_cnt = $_GET['total_cnt'];
	$result_cnt = $_GET['result_cnt'];
	if($total_cnt == 0) {
		$res_text = GetMessage('VETTICH_SP_AJAX_PUBLISH_IBLOCK_ELEM_RESULT_CONTENT_1');
	} elseif($result_cnt == 0) {
		$res_text = GetMessage('VETTICH_SP_AJAX_PUBLISH_IBLOCK_ELEM_RESULT_CONTENT_4');
	} elseif($total_cnt == $result_cnt) {
		$res_text = GetMessage('VETTICH_SP_AJAX_PUBLISH_IBLOCK_ELEM_RESULT_CONTENT_2', array('#ELEMENT_CNT#' => $total_cnt));
	} elseif($total_cnt > $result_cnt) {
		$res_text = GetMessage('VETTICH_SP_AJAX_PUBLISH_IBLOCK_ELEM_RESULT_CONTENT_3', array(
			'#ELEMENT_CNT#' => $total_cnt,
			'#ELEMENT_CNT2#' => $result_cnt
		));
	}
	(new \vettich\devform\AdminForm('devform', array(
		'tabs' => array(array(
			'name' => '#VETTICH_SP_RESULT#',
			'params' => array(
				'result' => 'plaintext::'.\vettich\devform\Module::selfValue($res_text),
			),
		)),
		'buttons' => array(
			// '_save' => 'buttons\saveSubmit:Опубликовать',
			'_cancel' => 'buttons\submit:Закрыть:params=[onclick=window.close();]',
		),
		'headerButtons' => $headerButtons,
		'data' => $data,
	)))->render();
	return;
}

if(!empty($_POST) && $_GET['ajax'] != 'Y') {
	$_POST['POPUP_CONDITIONS'] = Module::cleanConditions($_POST['POPUP_CONDITIONS']);
	if($_POST['POPUP_PUBLISH']) foreach($_POST['POPUP_PUBLISH'] as $key => $value) {
		if(isset($value['CONDITIONS'])) {
			$_POST['POPUP_PUBLISH'][$key]['CONDITIONS'] = Module::cleanConditions($value['CONDITIONS']);
		}
	}
}

$data = new data\option(array(
	'module_id' => 'vettich.sp',
	'prefix' => 'POPUP_',
	'trimPrefix' => false,
	'on afterSave' => function(&$obj, $arValues) {
		$arValues = $_POST;
		if($arValues['POPUP_PENDING_MODE'] == 'after') {
			$date = new \DateTime();
			$date->modify('+'.$arValues['POPUP_PENDING_AFTER'].' minutes');
			$publishAt = Bitrix\Main\Type\DateTime::createFromPhp($date);
		} elseif($arValues['POPUP_PENDING_MODE'] == 'in') {
			$publishAt = Bitrix\Main\Type\DateTime::createFromPhp(new \DateTime($arValues['POPUP_PENDING_IN']));
		} elseif($arValues['POPUP_PENDING_MODE'] == 'field') {
			// select field after
		} else {
			$publishAt = Bitrix\Main\Type\DateTime::createFromPhp(new \DateTime());
		}
		$arPost = array(
			'IBLOCK_TYPE' => $arValues['IBLOCK_TYPE'],
			'IBLOCK_ID' => $arValues['IBLOCK_ID'],
			'PROTOCOL' => $arValues['POPUP_PROTOCOL'],
			'DOMAIN' => $arValues['POPUP_DOMAIN'],
			'URL_PARAMS' => $arValues['POPUP_URL_PARAMS'],
			'SOCIALS' => $obj->get('POPUP_ACCOUNTS'),
			'PUBLISH' => $arValues['POPUP_PUBLISH'],
			'PUBLISH_AT' => $publishAt,
			'UPDATE_ELEM' => $arValues['POPUP_QUEUE_ELEMENT_UPDATE'],
			'DELETE_ELEM' => $arValues['POPUP_QUEUE_ELEMENT_DELETE'],
			'QUEUE_DUPLICATE' => $arValues['POPUP_QUEUE_DUPLICATE'],
		);
		$arFilter = array(
			'IBLOCK_TYPE' => $arValues['IBLOCK_TYPE'],
			'IBLOCK_ID' => $arValues['IBLOCK_ID'],
		);
		if(!empty($_GET['ELEM_ID'])) {
			$arFilter['ID'] = $_GET['ELEM_ID'];
		} elseif(!empty($_GET['SECTION_ID'])) {
			$arFilter['SECTION_ID'] = $_GET['SECTION_ID'];
			$arFilter['INCLUDE_SUBSECTIONS'] = 'Y';
		} else {
			// error
			exit;
		}
		$rsElems = CIBlockElement::GetList(array(), $arFilter);
		$arResult = array();
		$total_cnt = 0;
		while($arElem = $rsElems->GetNext()) {
			$total_cnt++;
			Module::iblockValueFill($arElem);
			if($arValues['POPUP_PENDING_MODE'] == 'field') {
				$arPost['PUBLISH_AT'] = Bitrix\Main\Type\DateTime::createFromPhp(
					new \DateTime($arElem[$arValues['POPUP_PENDING_FIELD']])
				);
			}
			$res = Pending::addElemMix($arElem, array('arPost' => $arPost, 'arFieldsFilled' => true));
			if($res) {
				$arResult[] = $res;
			}
		}
		// if(!empty($arResult)) {
			$_GET['finish'] = 'Y';
			$_GET['total_cnt'] = $total_cnt;
			$_GET['result_cnt'] = count($arResult);
			header('Location: ?'.http_build_query($_GET));
			exit;
		// }
	}
));

$display = $data->get('POPUP_DISPLAY', 'detail');
if(!empty($_GET['display']) && $display != $_GET['display']) {
	$display = $_GET['display'];
	$data->set('POPUP_DISPLAY', $display);
}

if(!intval($_GET['IBLOCK_ID'])) {
	$error[] = 'IBLOCK_ID is not in $_GET';
} else {
	$iblock_id = intval($_GET['IBLOCK_ID']);
	$iblock_type = CIBlock::GetArrayByID($iblock_id, 'IBLOCK_TYPE_ID');
	$iblock_type_name = CIBlockType::GetByIDLang($iblock_type, LANG);
	$iblock_type_name = $iblock_type_name['NAME'];
}

if($display == 'detail') {
	$params = array(
		'IBLOCK_TYPE2' => array(
			'type' => 'plaintext',
			'title' => '#VETTICH_SP_IBLOCK_TYPE#',
			'value' => "[$iblock_type] $iblock_type_name",
		),
		'IBLOCK_ID2' => array(
			'type' => 'plaintext',
			'title' => '#VETTICH_SP_IBLOCK#',
			'value' => '['.$iblock_id.'] '.CIBlock::GetArrayByID($iblock_id, 'NAME'),
		),
		'IBLOCK_TYPE' => 'hidden:value='.$iblock_type,
		'IBLOCK_ID' => 'hidden:value='.$iblock_id,
	);
	if(!empty($_GET['ELEM_ID'])) {
		$rsElem = CIBlockElement::GetByID($_GET['ELEM_ID']);
		if($arElem = $rsElem->GetNext()) {
			$params += array(
				'ELEM' => array(
					'type' => 'plaintext',
					'title' => 'Элемент',
					'value' => '['.$arElem['ID'].'] '.$arElem['NAME'],
				),
			);
		}
	} elseif(!empty($_GET['SECTION_ID'])) {
		$rsElem = CIBlockSection::GetByID($_GET['SECTION_ID']);
		if($arElem = $rsElem->GetNext()) {
			$params += array(
				'ELEM' => array(
					'type' => 'plaintext',
					'title' => 'Раздел',
					'value' => '['.$arElem['ID'].'] '.$arElem['NAME'],
				),
			);
		}
	}
	$params += array(
		'heading2' => 'heading:#VETTICH_SP_DOMAIN_HEADING#',
		'POPUP_PROTOCOL' => 'select:#VETTICH_SP_PROTOCOL#:options=[=#VETTICH_SP_PROTOCOL_DEFAULT#:http=HTTP:https=HTTPS]:help=#VETTICH_SP_PROTOCOL_HELP#',
		'POPUP_DOMAIN' => 'text:#VETTICH_SP_DOMAIN_NAME#:'.$_SERVER['SERVER_NAME'].':help=#VETTICH_SP_DOMAIN_NAME_HELP#',
		'POPUP_URL_PARAMS' => 'text:#VETTICH_SP_URL_PARAMS#:utm_source\={social_id}&utm_medium\=cpc:help=#VETTICH_SP_URL_PARAMS_HELP#',
	);
	if(!isset($_GET['ELEM_ID'])) {
		$params += array(
			'heading6' => 'heading:#VETTICH_SP_CONDITIONS_HEADING#',
			'POPUP_PUBLISH[CONDITIONS][ACTIVE]' => 'checkbox:#VETTICH_SP_PUBLISH_CONDITIONS_ACTIVE#:Y:help=#VETTICH_SP_PUBLISH_CONDITIONS_ACTIVE_HELP#',
			'POPUP_CONDITIONS' => array(
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
			),
		);
	}
	$params += array('heading4' => 'heading:#VETTICH_SP_CHOOSE_POST_ACCOUNTS#');
	$individ = ($_POST['POPUP_PUBLISH']['COMMON']['INDIVIDUAL_SETTINGS'] == 'Y'
		or $data->get('POPUP_PUBLISH[COMMON][INDIVIDUAL_SETTINGS]') == 'Y');
	$queue_common = ($_POST['POPUP_QUEUE_COMMON'] == 'Y'
		or (empty($_POST) && $data->get('POPUP_QUEUE_COMMON', 'Y') == 'Y'));
	$params += (array) Module::socialAccountsForDevForm('POPUP_ACCOUNTS', $individ ? array('onclick' => 'Vettich.Devform.Refresh(this);') : array());
	$params += array(
		// 'none_acc' => 'plaintext::'.vettich\devform\Module::mess('#VETTICH_SP_NONE_ACCOUNTS#'),
		'heading5' => 'heading:#VETTICH_SP_COMMON_DESCRIPTION#',
		'POPUP_PUBLISH[COMMON][INDIVIDUAL_SETTINGS]' => 'checkbox:#VETTICH_SP_PUBLISH_INDIVIDUAL_SETTINGS#:N:help=#VETTICH_SP_PUBLISH_INDIVIDUAL_SETTINGS_HELP#:refresh=Y',
		'POPUP_PUBLISH[COMMON][MAIN_PICTURE]' => array(
			'type' => $individ ? 'hidden' : 'select',
			'title' => '#VETTICH_SP_PUBLISH_MAIN_PICTURE#',
			'help' => '#VETTICH_SP_PUBLISH_MAIN_PICTURE_HELP#',
			'options' => Module::allPropsFor($iblock_id),
			'default_value' => 'DETAIL_PICTURE',
		),
		'POPUP_PUBLISH[COMMON][OTHER_PICTURE]' => array(
			'type' => $individ ? 'hidden' : 'select',
			'title' => '#VETTICH_SP_PUBLISH_OTHER_PICTURE#',
			'help' => '#VETTICH_SP_PUBLISH_OTHER_PICTURE_HELP#',
			'options' => Module::allPropsFor($iblock_id),
			'default_value' => 'PROPERTY_MORE_PICTURES',
		),
		'POPUP_PUBLISH[COMMON][LINK]' => array(
			'type' => $individ ? 'hidden' : 'select',
			'title' => '#VETTICH_SP_PUBLISH_LINK#',
			'help' => '#VETTICH_SP_PUBLISH_LINK_HELP#',
			'options' => Module::allPropsFor($iblock_id),
			'default_value' => 'DETAIL_PAGE_URL',
		),
		// 'POPUP_PUBLISH[COMMON][LINK_TITLE]' => array(
		// 	'type' => $individ ? 'hidden' : 'select',
		// 	'title' => '#VETTICH_SP_PUBLISH_LINK_TITLE#',
		// 	'help' => '#VETTICH_SP_PUBLISH_LINK_TITLE_HELP#',
		// 	'options' => Module::allPropsFor($iblock_id),
		// 	'default_value' => 'NAME',
		// ),
		// 'POPUP_PUBLISH[COMMON][LINK_DESCRIPTION]' => array(
		// 	'type' => $individ ? 'hidden' : 'select',
		// 	'title' => '#VETTICH_SP_PUBLISH_LINK_DESCRIPTION#',
		// 	'help' => '#VETTICH_SP_PUBLISH_LINK_DESCRIPTION_HELP#',
		// 	'options' => Module::allPropsFor($iblock_id),
		// 	'default_value' => 'PREVIEW_TEXT',
		// ),
		'POPUP_PUBLISH[COMMON][TEXT]' => array(
			'type' => $individ ? 'hidden' : 'textarea',
			'title' => '#VETTICH_SP_PUBLISH_TEXT#',
			'help' => '#VETTICH_SP_PUBLISH_TEXT_HELP#',
			'items' => Module::allPropsMacrosFor($iblock_id),
			'default_value' => "#NAME#\n\n#PREVIEW_TEXT#",
			'params' => array('rows' => 6),
		),
	);
	if(!(($is_interval = $_POST['POPUP_IS_INTERVAL']) or ($is_interval = $data->get('POPUP_IS_INTERVAL')))) {
		$is_interval = 'Y';
	}
	if(!(($is_period = $_POST['POPUP_IS_PERIOD']) or ($is_period = $data->get('POPUP_IS_PERIOD')))) {
		$is_period = 'N';
	}
	if(!(($is_everyday = $_POST['POPUP_IS_EVERYDAY']) or ($is_everyday = $data->get('POPUP_IS_EVERYDAY')))) {
		$is_everyday = 'Y';
	}
	if(!(($pending_mode = $_POST['POPUP_PENDING_MODE']) or ($pending_mode = $data->get('POPUP_PENDING_MODE')))) {
		$pending_mode = 'now';
	}
	$date30 = date('d.m.Y H:i:s', strtotime('+30 minutes'));
	$date30 = str_replace(':', '\:', $date30);
	$tabs = array(
		array(
			'name' => '#POSTS#',
			'title' => '#POSTS_SETTINGS#',
			'params' => $params,
		),
		array(
			'name' => '#VETTICH_SP_PENDING_POSTING#',
			'title' => '#VETTICH_SP_PENDING_POSTING_SETTINGS#',
			'params' => array(
				'pendingPostingEnabled' => 'note:#VETTICH_SP_PENDING_POSTING_ENABLED#',
				'POPUP_QUEUE_COMMON' => 'checkbox:Использовать настройки единой очереди:Y:refresh=Y',
				'POPUP_PENDING_MODE' => $queue_common ? 'hidden' : array(
					'type' => 'select',
					'title' => 'Время публикации',
					'options' => array(
						'now' => 'Сейчас',
						'after' => 'Через определенное время',
						'in' => 'Задать время',
						'field' => 'Выбрать поле даты активности',
					),
					'default_value' => 'now',
					'params' => array('onchange' => 'Vettich.Devform.Refresh(this);'),
				),
				'PENDING_NOW_NOTE' => ($queue_common or $pending_mode != 'now') ? 'hidden' : 'note:Элемент будет опубликован в течении нескольких минут',
				'POPUP_PENDING_AFTER' => ($queue_common or $pending_mode != 'after') ? 'hidden' : 'number:Опубликовать через минут:30',
				'PENDING_AFTER_NOTE' => ($queue_common or $pending_mode != 'after') ? 'hidden' : 'note:Элемент опубликуется через указанное количество минут. Если элементентов несколько, то они будут публиковаться один за другим, через указанный промежуток времени.',
				'POPUP_PENDING_IN' => ($queue_common or $pending_mode != 'in') ? 'hidden' : 'text:Опубликовать в указанные дату и время:'.$date30,
				'POPUP_PENDING_FIELD' => ($queue_common or $pending_mode != 'field') ? 'hidden' : array(
					'type' => 'select',
					'title' => '#VETTICH_SP_PENDING_DATE#',
					'help' => '#VETTICH_SP_PENDING_DATE_HELP#',
					'options' => Module::allPropsFor($iblock_id),
					'default_value' => 'DATE_ACTIVE_FROM'
				),
				'heading7' => 'heading:#VETTICH_SP_QUEUE_SETTINGS#',
				'POPUP_QUEUE_ELEMENT_UPDATE' => 'checkbox:#VETTICH_SP_PENDING_QUEUE_ELEMENT_UPDATE#:Y:help=#VETTICH_SP_PENDING_QUEUE_ELEMENT_UPDATE_HELP#',
				'POPUP_QUEUE_ELEMENT_DELETE' => 'checkbox:#VETTICH_SP_PENDING_QUEUE_ELEMENT_DELETE#:Y:help=#VETTICH_SP_PENDING_QUEUE_ELEMENT_DELETE_HELP#',
				'POPUP_QUEUE_DUPLICATE' => 'checkbox:#VETTICH_SP_PENDING_QUEUE_DUPLICATE#:N:help=#VETTICH_SP_PENDING_QUEUE_DUPLICATE_HELP#',
			),
		),
	);

	if($individ
		&& (($accounts = array_keys($_POST['POPUP_ACCOUNTS']))
			or (empty($_POST) && $accounts = $data->get('POPUP_ACCOUNTS')))) {
		$show_types = array();
		foreach ($accounts as $key => $v) {
			$social = Module::socialForId($v);
			if(empty($social)
				|| in_array($social['id'], $show_types)) {
				continue;
			}
			$show_types[] = $social['id'];
			$_params = $social['class']->publishParams($iblock_id, 'POPUP_PUBLISH['.$social['class']::$socialid.']');
			if(!isset($_GET['ELEM_ID'])) {
				$_params['heading_cond_'.$social['class']::$socialid] = 'heading:#VETTICH_SP_CONDITIONS_POST#';
				$_params['POPUP_PUBLISH['.$social['class']::$socialid.'][CONDITIONS]'] = array(
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
			}
			$tabs[] = array(
				'name' => $social['name'],
				'title' => '#VETTICH_SP_SETTINGS_FOR# '.$social['name'],
				'params' => $_params,
			);
		}
	}
	$headerButtons = array('displayButton' => array(
		'type' => 'buttons\link',
		'title' => 'List', 
		'default_value' => $GLOBALS['APPLICATION']->GetCurPageParam('display=list', array('display'))
	));
} else {
	$tabs = array(
		array(
			'name' => "nasfdm",
			'params' => array(
				'text',
				'text',
				'text',
			),
		),
	);
	$headerButtons = array('displayButton' => array(
		'type' => 'buttons\link',
		'title' => 'Detail', 
		'default_value' => $GLOBALS['APPLICATION']->GetCurPageParam('display=detail', array('display'))
	));
}

(new \vettich\devform\AdminForm('devform', array(
	'tabs' => $tabs,
	'buttons' => array(
		'_save' => 'buttons\saveSubmit:Опубликовать',
		'_cancel' => 'buttons\submit:Отмена:params=[onclick=window.close();]',
	),
	// 'headerButtons' => $headerButtons,
	'data' => $data,
)))->render();

if($_GET['ajax'] != 'Y') {
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");
}
