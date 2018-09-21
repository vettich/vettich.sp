<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

CModule::IncludeModule('vettich.devform');
CModule::IncludeModule('vettich.sp');
IncludeModuleLangFile(__FILE__);

use vettich\SP\Module;
use vettich\devform\types;

if(!$id && !empty($_GET['ID'])) {
	$id = $_GET['ID'];
}

if(!empty($_POST) && SITE_CHARSET != 'UTF-8') {
	$_POST = vettich\devform\Module::convertEncodingToCurrent($_POST);
}

// get iblock types list
$arIblockTypes = array('' => Module::mess('#VETTICH_SP_IBLOCK_TYPE_SELECT#'));
$rsIblockTypes = CIBlockType::GetList();
while($ar = $rsIblockTypes->Fetch()) {
	if($arIBType = CIBlockType::GetByIDLang($ar["ID"], LANG)) {
		$arIblockTypes["$ar[ID]"] = "[$ar[ID]] ".htmlspecialcharsEx($arIBType["NAME"]);
	}
}

$params = array(
	'_NAME' => 'text:#VDF_NAME#::help=#VETTICH_SP_NAME_HELP#:params=[placeholder=#VETTICH_SP_NAME_PLACEHOLDER#]',
	'NAME_AUTO' => 'hidden::',
	'_IS_ENABLE' => 'checkbox:#VDF_IS_ENABLE#:Y:help=#VETTICH_SP_IS_ENABLE_HELP#',
	'_IS_MANUALLY' => 'checkbox:#VETTICH_SP_IS_MANUALLY#:N:help=#VETTICH_SP_IS_MANUALLY_HELP#',
	'heading1' => 'heading:#VETTICH_SP_IBLOCK_HEADING#',
	'_IBLOCK_TYPE' => array(
		'type' => 'select',
		'title' => '#VETTICH_SP_IBLOCK_TYPE#',
		'help' => '#VETTICH_SP_IBLOCK_TYPE_HELP#',
		'options' => $arIblockTypes,
		'params' => array('onchange' => 'Vettich.Devform.Refresh(this);'),
	),
);
$data = new vettich\devform\data\orm(array(
	'dbClass' => 'vettich\SP\db\post',
	'prefix' => '_',
));

$isSections = false;
$name_auto = '';
// get iblock ids list if iblock type selected
if(($iblock_type = $_POST['_IBLOCK_TYPE']) or ($iblock_type = $data->get('_IBLOCK_TYPE'))) {
	// $name_auto = $arIblockTypes[$iblock_type];
	// if(($pos = strpos($name_auto, ']')) !== false) {
	// 	$name_auto = trim(substr($name_auto, $pos+1));
	// }
	// выборка инфоблоков
	$arIblockIds = array('' => Module::mess('#VETTICH_SP_IBLOCK_ID_SELECT#'));
	$rsIblockIds = CIBlock::GetList(array(), array(
		'TYPE' => $iblock_type,
	));
	while($ar = $rsIblockIds->Fetch()) {
		$arIblockIds["$ar[ID]"] = "[$ar[ID]] $ar[NAME]";
	}
	if($iblock_type
		&& (($iblock_id = $_POST['_IBLOCK_ID']) or ($iblock_id = $data->get('_IBLOCK_ID')))
		&& isset($arIblockIds[$iblock_id])) {
		// выборка секций
		$arSections = array('' => Module::mess('#VETTICH_SP_IBLOCK_SECTION_SELECT#'));
		$arFilter = array('IBLOCK_ID' => $iblock_id, 'IBLOCK_TYPE' => $iblock_type, 'ACTIVE' => 'Y'); 
		$arSelect = array('ID', 'NAME', 'DEPTH_LEVEL');
		$rsSections = \CIBlockSection::GetTreeList($arFilter, $arSelect);
		while($ar = $rsSections->Fetch()) {
			$arSections["$ar[ID]"] = str_repeat('- ', intval($ar['DEPTH_LEVEL'])-1)."[$ar[ID]] $ar[NAME]";
		}
	}
	if(($iblock_id = $_POST['_IBLOCK_ID']) or ($iblock_id = $data->get('_IBLOCK_ID')) && !empty($arIblockIds[$iblock_id])) {
		$s = $arIblockIds[$iblock_id];
		if(($pos = strpos($s, ']')) !== false) {
			$s = substr($s, $pos+1);
		}
		$name_auto = trim($s);
	}
	$isSections = count($arSections) > 1;
	$params['_IBLOCK_ID'] = array(
		'type' => 'select',
		'title' => '#VETTICH_SP_IBLOCK#',
		'help' => '#VETTICH_SP_IBLOCK_HELP#',
		'options' => $arIblockIds,
		'params' => array('onchange' => 'Vettich.Devform.Refresh(this);'),
	);
	if($isSections) {
		$params['_IS_SECTIONS'] = 'checkbox:#VETTICH_SP_IBLOCK_IS_SECTIONS#:refresh=Y:help=#VETTICH_SP_IBLOCK_IS_SECTIONS_HELP#';
		if($_POST['_IS_SECTIONS'] == 'Y' or (empty($_POST) && $data->get('_IS_SECTIONS') == 'Y')) {
			$params['_IBLOCK_SECTIONS'] = array(
				'type' => 'multiselect',
				'title' => '#VETTICH_SP_IBLOCK_SECTIONS#',
				'help' => '#VETTICH_SP_IBLOCK_SECTIONS_HELP#',
				'options' => $arSections,
				'params' => array('size' => count($arSections) > 10 ? 10 : count($arSections)),
			);
		}
	}
}

// автоподстановка названи€
$name = $_POST['_NAME'];
$name_auto_old = $_POST['NAME_AUTO'];
$name_auto = str_replace(array('[', ']', '='), array('\[', '\]', '\='), $name_auto);
if($name == $name_auto_old or empty($name)) {
	$params['_NAME'] = 'text:#VDF_NAME#:'.$name_auto.':help=#VETTICH_SP_NAME_HELP#:params=[placeholder=#VETTICH_SP_NAME_PLACEHOLDER#]';
}
$params['NAME_AUTO'] = 'hidden::value='.$name_auto;
// $params['NAME_AUTO_2'] = 'plaintext:value='.$name

$params += array(
	'heading2' => 'heading:#VETTICH_SP_DOMAIN_HEADING#',
	'_PROTOCOL' => 'select:#VETTICH_SP_PROTOCOL#:options=[=#VETTICH_SP_PROTOCOL_DEFAULT#:http=HTTP:https=HTTPS]:help=#VETTICH_SP_PROTOCOL_HELP#',
	'_DOMAIN' => 'text:#VETTICH_SP_DOMAIN_NAME#:'.$_SERVER['SERVER_NAME'].':help=#VETTICH_SP_DOMAIN_NAME_HELP#',
	'_URL_PARAMS' => 'text:#VETTICH_SP_URL_PARAMS#:utm_source\=#SOCIAL_ID#&utm_medium\=cpc:help=#VETTICH_SP_URL_PARAMS_HELP#',
	'heading6' => 'heading:#VETTICH_SP_CONDITIONS_HEADING#',
	'_PUBLISH[CONDITIONS][ACTIVE]' => 'checkbox:#VETTICH_SP_PUBLISH_CONDITIONS_ACTIVE#:Y:help=#VETTICH_SP_PUBLISH_CONDITIONS_ACTIVE_HELP#',
	'_CONDITIONS' => array(
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
		// 'default_value' => array(
		// 	array('field' => 'ACTIVE', 'cmp' => '=', 'value' => 'Y'),
		// ),
	),
	'heading4' => 'heading:#VETTICH_SP_CHOOSE_POST_ACCOUNTS#',
);
$individ = ($_POST['_PUBLISH']['COMMON']['INDIVIDUAL_SETTINGS'] == 'Y'
	or $data->get('_PUBLISH[COMMON][INDIVIDUAL_SETTINGS]') == 'Y');
$params += (array) Module::socialAccountsForDevForm('_ACCOUNTS', $individ ? array('onclick' => 'Vettich.Devform.Refresh(this);') : array());
$params += array(
	// 'none_acc' => 'plaintext::'.vettich\devform\Module::mess('#VETTICH_SP_NONE_ACCOUNTS#'),
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

if(!(($is_interval = $_POST['_IS_INTERVAL']) or ($is_interval = $data->get('_IS_INTERVAL')))) {
	$is_interval = 'Y';
}
if($is_interval != 'Y' && !(($_date = $_POST['_DATE']) or ($_date = $data->get('_DATE')))) {
	$_date = 'none';
}
if($is_interval == 'Y' && !(($is_period = $_POST['_IS_PERIOD']) or ($is_period = $data->get('_IS_PERIOD')))) {
	$is_period = 'N';
}
if($_date != 'none' && !(($every = $_POST['_EVERY']) or ($every = $data->get('_EVERY')))) {
	$every = 'DAY';
}
if($_date != 'none' && !(($queue_mode = $_POST['_QUEUE_MODE']) or ($queue_mode = $data->get('_QUEUE_MODE')))) {
	$queue_mode = 'CONSISTENTLY';
}

function _getMonthDays() {
	$result = array();
	for($i=1; $i<=31; $i++) {
		$result[$i] = $i;
	}
	return $result;
}

$period_values = array(
	'00:00' => '00:00',
	'01:00' => '01:00',
	'02:00' => '02:00',
	'03:00' => '03:00',
	'04:00' => '04:00',
	'05:00' => '05:00',
	'06:00' => '06:00',
	'07:00' => '07:00',
	'08:00' => '08:00',
	'09:00' => '09:00',
	'10:00' => '10:00',
	'11:00' => '11:00',
	'12:00' => '12:00',
	'13:00' => '13:00',
	'14:00' => '14:00',
	'15:00' => '15:00',
	'16:00' => '16:00',
	'17:00' => '17:00',
	'18:00' => '18:00',
	'19:00' => '19:00',
	'20:00' => '20:00',
	'21:00' => '21:00',
	'22:00' => '22:00',
	'23:00' => '23:00',
);

$nextPublishDatetime = false;
if($is_interval == 'Y' && $nextPublishDatetime = $data->get('_NEXT_PUBLISH_AT')) {
	$nextPublishDatetime = $nextPublishDatetime->toString();
	$nextPublishDatetime = str_replace(':', '\:', $nextPublishDatetime);
}

$unloadParams = array(
	'_PUBLISH[UNLOAD][ENABLE]' => 'checkbox:#VETTICH_SP_UNLOAD_ENABLE#:N:help=#VETTICH_SP_UNLOAD_ENABLE_HELP#:refresh=Y',
);
if($isSections
	&& ($_POST['_PUBLISH']['UNLOAD']['ENABLE'] == 'Y'
		or (empty($_POST) && $data->get('_PUBLISH[UNLOAD][ENABLE]') == 'Y'))) {
	$unloadParams['_PUBLISH[UNLOAD][SECTIONS]'] = array(
		'type' => 'select',
		'title' => '#VETTICH_SP_UNLOAD_SECTIONS#',
		'options' => array(
			'RANDOM' => '#VETTICH_SP_PENDING_QUEUE_MODE_RANDOM#',
			'CONSISTENTLY' => '#VETTICH_SP_PENDING_QUEUE_MODE_CONSISTENTLY#',
		),
	);
}
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
			'_IS_INTERVAL' => 'checkbox:#VETTICH_SP_PENDING_IS_INTERVAL#:Y:refresh=Y:help=#VETTICH_SP_PENDING_IS_INTERVAL_HELP#',
			'_INTERVAL' => $is_interval!='Y' ? 'hidden' : 'number:#VETTICH_SP_PENDING_INTERVAL#:120:help=#VETTICH_SP_PENDING_INTERVAL_HELP#:params=[min=1]',
			'_DATE' => $is_interval=='Y' ? 'hidden' : array(
				'type' => 'select',
				'title' => '#VETTICH_SP_PENDING_DATE#',
				'help' => '#VETTICH_SP_PENDING_DATE_HELP#',
				'default_value' => 'DATE_ACTIVE_FROM',
				'options' => Module::allPropsFor($iblock_id),
			),
			'_IS_PERIOD' => !$_date != 'none' ? 'hidden' : 'checkbox:#VETTICH_SP_PENDING_IS_PERIOD#:refresh=Y:help=#VETTICH_SP_PENDING_IS_PERIOD_HELP#',
			'_PERIOD_FROM' => $is_period!='Y' ? 'hidden' : array(
				'type' => 'select',
				'title' => '#VETTICH_SP_PENDING_PERIOD_FROM#',
				'help' => '#VETTICH_SP_PENDING_PERIOD_FROM_HELP#',
				'default_value' => '06:00',
				'options' => $period_values,
				'on renderTemplate' => function($obj, $template, &$replace) {
					if(empty($replace['{value}'])
						&& ($value = $obj->getValue($obj->data))) {
						$replace['{value}'] = Module::timeToUserTime($value);
					}
				}
			),
			'_PERIOD_TO' => $is_period!='Y' ? 'hidden' : array(
				'type' => 'select',
				'title' => '#VETTICH_SP_PENDING_PERIOD_TO#',
				'help' => '#VETTICH_SP_PENDING_PERIOD_TO_HELP#',
				'default_value' => '23:00',
				'options' => $period_values,
				'on renderTemplate' => function($obj, $template, &$replace) {
					if(empty($replace['{value}'])
						&& ($value = $obj->getValue($obj->data))) {
						$replace['{value}'] = Module::timeToUserTime($value);
					}
				}
			),
			'_EVERY' => !$_date != 'none' ? 'hidden' : array(
				'type' => 'select',
				'title' => '#VETTICH_SP_PENDING_EVERY#',
				'help' => '#VETTICH_SP_PENDING_EVERY_HELP#',
				'default_value' => 'DAY',
				'params' => array('onchange' => 'Vettich.Devform.Refresh(this);'),
				'options' => array(
					'DAY' => '#VETTICH_SP_PENDING_DAY#',
					'WEEK' => '#VETTICH_SP_PENDING_WEEK#',
					'MONTH' => '#VETTICH_SP_PENDING_MONTH#'
				),
			),
			'_WEEK' => $every != 'WEEK' ? 'hidden' : array(
				'type' => 'checkbox',
				'title' => '#VETTICH_SP_PENDING_DAYS#',
				'help' => '#VETTICH_SP_PENDING_DAYS_HELP#',
				'multiple' => true,
				'default_value' => array(Module::getWeekName(date('w'))),
				'options' => array(
					'monday' => '#VETTICH_SP_DAY_MONDAY#',
					'tuesday' => '#VETTICH_SP_DAY_TUESDAY#',
					'wednesday' => '#VETTICH_SP_DAY_WEDNESDAY#',
					'thursday' => '#VETTICH_SP_DAY_THURSDAY#',
					'friday' => '#VETTICH_SP_DAY_FRIDAY#',
					'saturday' => '#VETTICH_SP_DAY_SATURDAY#',
					'sunday' => '#VETTICH_SP_DAY_SUNDAY#',
				),
			),
			'_MONTH' => $every != 'MONTH' ? 'hidden' : array(
				'type' => 'checkbox',
				'title' => '#VETTICH_SP_PENDING_MONTH2#',
				'help' => '',
				'multiple' => true,
				'default_value' => array(date('d')),
				'options' => _getMonthDays(),
			),
			'nextPublishDatetime' => !$nextPublishDatetime ? 'hidden' : 'plaintext:#VETTICH_SP_NEXT_PUBLISH_DATETIME#:'.$nextPublishDatetime,
			'nextPublishDatetimeReset' => !$nextPublishDatetime ? 'hidden' : 'divbutton::#VETTICH_SP_NEXT_PUBLISH_DATETIME_RESET#:onclick=Vettich.SP.UpdateNextPublishAt();',
			'heading7' => 'heading:#VETTICH_SP_QUEUE_SETTINGS#',
			// '_QUEUE_MODE' => !$_date != 'none' ? 'hidden' : array(
			// 	'type' => 'select',
			// 	'title' => '#VETTICH_SP_PENDING_QUEUE_MODE#',
			// 	'help' => '#VETTICH_SP_PENDING_QUEUE_MODE_HELP#',
			// 	'params' => array('onchange' => 'Vettich.Devform.Refresh(this);'),
			// 	'options' => array(
			// 		'CONSISTENTLY' => '#VETTICH_SP_PENDING_QUEUE_MODE_CONSISTENTLY#',
			// 		'RANDOM' => '#VETTICH_SP_PENDING_QUEUE_MODE_RANDOM#',
			// 		'SORT' => '#VETTICH_SP_PENDING_QUEUE_MODE_SORT#',
			// 	),
			// ),
			// '_QUEUE_SORT' => $queue_mode != 'SORT' ? 'hidden' : array(
			// 	'type' => 'select',
			// 	'title' => '#VETTICH_SP_PENDING_QUEUE_SORT#',
			// 	'help' => '#VETTICH_SP_PENDING_QUEUE_SORT_HELP#',
			// 	'default_value' => 'ID',
			// 	'options' => Module::allPropsFor($iblock_id),
			// ),
			// '_QUEUE_SORT_DIR' => $queue_mode != 'SORT' ? 'hidden' : array(
			// 	'type' => 'select',
			// 	'title' => '#VETTICH_SP_PENDING_QUEUE_SORT_DIR#',
			// 	'help' => '#VETTICH_SP_PENDING_QUEUE_SORT_DIR_HELP#',
			// 	'options' => array(
			// 		'ASC' => '#VETTICH_SP_PENDING_QUEUE_SORT_DIR_INC#',
			// 		'DESC' => '#VETTICH_SP_PENDING_QUEUE_SORT_DIR_DEC#',
			// 	),
			// ),
			'_QUEUE_ELEMENT_UPDATE' => 'checkbox:#VETTICH_SP_PENDING_QUEUE_ELEMENT_UPDATE#:Y:help=#VETTICH_SP_PENDING_QUEUE_ELEMENT_UPDATE_HELP#',
			'_QUEUE_ELEMENT_DELETE' => 'checkbox:#VETTICH_SP_PENDING_QUEUE_ELEMENT_DELETE#:Y:help=#VETTICH_SP_PENDING_QUEUE_ELEMENT_DELETE_HELP#',
			'_QUEUE_DUPLICATE' => 'checkbox:#VETTICH_SP_PENDING_QUEUE_DUPLICATE#:N:help=#VETTICH_SP_PENDING_QUEUE_DUPLICATE_HELP#',
			/**
			 * @todo доработать единую очередь
			 */
			// '_QUEUE_IS_COMMON' => 'checkbox:#VETTICH_SP_PENDING_QUEUE_IS_COMMON#:N:help=#VETTICH_SP_PENDING_QUEUE_IS_COMMON_HELP#',
		),
	),
	array(
		'name' => '#VETTICH_SP_UNLOAD_TAB_NAME#',
		'title' => '#VETTICH_SP_UNLOAD_TAB_TITLE#',
		'params' => $unloadParams,
	),
);

if($individ
	&& (($accounts = array_keys($_POST['_ACCOUNTS']))
		or (empty($_POST) && $accounts = $data->get('_ACCOUNTS')))) {
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
		$tabs[] = array(
			'name' => $social['name'],
			'title' => '#VETTICH_SP_SETTINGS_FOR# '.$social['name'],
			'params' => $_params,
		);
	}
}

(new \vettich\devform\AdminForm('devform', array(
	'pageTitle' => ($id > 0 ? '#VETTICH_SP_EDIT_RECORD#' : '#VETTICH_SP_ADD_RECORD#'),
	'tabs' => $tabs,
	'buttons' => array(
		'_save' => 'buttons\saveSubmit:#VDF_SAVE#',
		'_apply' => 'buttons\submit:#VDF_APPLY#',
	),
	'data' => $data,
	'on beforeSave' => function(&$arValues, $args, $obj) {
		if(isset($arValues['_PERIOD_FROM'])) {
			$arValues['_PERIOD_FROM'] = Module::timeFromUserTime($arValues['_PERIOD_FROM']);
		}
		if(isset($arValues['_PERIOD_TO'])) {
			$arValues['_PERIOD_TO'] = Module::timeFromUserTime($arValues['_PERIOD_TO']);
		}
		return true;
	},
)))->render();


if($_GET['ajax'] != 'Y') {
	?><div class="adm-info-message" style="display:block">
	<pre style="white-space: pre-wrap;"><?=GetMessage('VETTICH_SP_POSTS_EDIT_HELP')?></pre>
	</div><?
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
}
