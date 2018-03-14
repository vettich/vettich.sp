<?
// max time of script is 30 minute
// really, it is wrong practice
// set_time_limit(60*30);
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('vettich.sp');

use vettich\SP\Module;
use vettich\SP\Pending;
use vettich\SP\db\pendingTable as dbPending;
use vettich\SP\db\postTable as dbPost;

$result = array('success' => true);
switch ($_GET['method']) {
	case 'publishFromQueue':
		Pending::run($_GET['id']);
		break;

	case 'publishFromHit':
		if(Module::GetOptionString('is_enable', 'Y') != 'Y'
			or Module::GetOptionString('method_publish', 'hit') != 'hit') {
			break;
		}
		Pending::checkPublish('hit');
		$result['interval'] = Module::GetOptionString('is_ajax_interval', 'N') == 'Y';
		break;

	case 'publishFromCloudCron':
		if(Module::GetOptionString('is_enable', 'Y') != 'Y'
			or Module::GetOptionString('method_post', 'hit') != 'cloud_cron') {
			break;
		}
		Pending::checkPublish('cloud_cron');
		break;

	case 'publishIblockElem':
		if(!CModule::IncludeModule('iblock')) {
			break;
		}
		$total_cnt = 0;
		$result['data'] = array();
		if(!empty($_GET['ELEM_ID'])) {
			$total_cnt = 1;
			$arFields = CIBlockElement::GetByID($_GET['ELEM_ID'])->GetNext();
			if($arFields) {
				$result['data'] = array_merge($result['data'], Pending::addElem($arFields, array('type' => 'publishIblockElem')));
			}
		} elseif(!empty($_GET['SECTION_ID'])) {
			$rs = CIBlockElement::GetList(array(), array(
				'IBLOCK_ID' => $_GET['IBLOCK_ID'],
				'SECTION_ID' => $_GET['SECTION_ID'],
				'INCLUDE_SUBSECTIONS' => true,
			));
			while($ar = $rs->GetNext()) {
				$total_cnt++;
				$result['data'] = array_merge($result['data'], Pending::addElem($ar, array('type' => 'publishIblockElem')));
			}
		}
		$result['title'] = GetMessage('VAP_AJAX_PUBLISH_IBLOCK_ELEM_RESULT_TITLE');
		$ids = array_flip($result['data']);
		$ids_cnt = count($ids);
		if($total_cnt == 0) {
			$result['content'] = GetMessage('VAP_AJAX_PUBLISH_IBLOCK_ELEM_RESULT_CONTENT_1');
		} elseif($ids_cnt == 0) {
			$result['content'] = GetMessage('VAP_AJAX_PUBLISH_IBLOCK_ELEM_RESULT_CONTENT_4');
		} elseif($total_cnt == $ids_cnt) {
			$result['content'] = GetMessage('VAP_AJAX_PUBLISH_IBLOCK_ELEM_RESULT_CONTENT_2', array('#ELEMENT_CNT#' => $total_cnt));
		} elseif($total_cnt > $ids_cnt) {
			$result['content'] = GetMessage('VAP_AJAX_PUBLISH_IBLOCK_ELEM_RESULT_CONTENT_3', array(
				'#ELEMENT_CNT#' => $total_cnt,
				'#ELEMENT_CNT2#' => $ids_cnt
			));
		}
		break;

	case 'publishIblockElems':
		if(!CModule::IncludeModule('iblock')) {
			break;
		}
		$total_cnt = 0;
		$result['data'] = array();
		if(isset($_GET['ELEMS'])) {
			$rs = CIBlockElement::GetList(array(), array(
				'IBLOCK_ID' => $_GET['IBLOCK_ID'],
				'ID' => explode(',', $_GET['ELEMS']),
			));
			while($ar = $rs->GetNext()) {
				$total_cnt++;
				$result['data'] = array_merge($result['data'], Pending::addElem($ar, array('type' => 'publishIblockElems')));
			}
		}
		if(isset($_GET['SECTIONS'])) {
			$rs = CIBlockElement::GetList(array(), array(
				'IBLOCK_ID' => $_GET['IBLOCK_ID'],
				'SECTION_ID' => explode(',', $_GET['SECTIONS']),
				'INCLUDE_SUBSECTIONS' => true,
			));
			while($ar = $rs->GetNext()) {
				$total_cnt++;
				$result['data'] = array_merge($result['data'], Pending::addElem($ar, array('type' => 'publishIblockElems')));
			}
		}
		$result['title'] = GetMessage('VAP_AJAX_PUBLISH_IBLOCK_ELEM_RESULT_TITLE');
		$ids = array_flip($result['data']);
		$ids_cnt = count($ids);
		if($total_cnt == 0) {
			$result['content'] = GetMessage('VAP_AJAX_PUBLISH_IBLOCK_ELEM_RESULT_CONTENT_1');
		} elseif($ids_cnt == 0) {
			$result['content'] = GetMessage('VAP_AJAX_PUBLISH_IBLOCK_ELEM_RESULT_CONTENT_4');
		} elseif($total_cnt == $ids_cnt) {
			$result['content'] = GetMessage('VAP_AJAX_PUBLISH_IBLOCK_ELEM_RESULT_CONTENT_2', array('#ELEMENT_CNT#' => $total_cnt));
		} elseif($total_cnt > $ids_cnt) {
			$result['content'] = GetMessage('VAP_AJAX_PUBLISH_IBLOCK_ELEM_RESULT_CONTENT_3', array(
				'#ELEMENT_CNT#' => $total_cnt,
				'#ELEMENT_CNT2#' => $ids_cnt
			));
		}
		break;

	case 'publishIblockElemsAll':
		$arFilter = Array(
			"IBLOCK_ID"		=>$IBLOCK_ID,
			"NAME"			=>$find_name,
			"SECTION_ID"		=>$find_section_section,
			"ID_1"			=>$find_id_1,
			"ID_2"			=>$find_id_2,
			"TIMESTAMP_X_1"		=>$find_timestamp_1,
			"CODE"			=>$find_code,
			"EXTERNAL_ID"		=>$find_external_id,
			"MODIFIED_BY"		=>$find_modified_by,
			"MODIFIED_USER_ID"	=>$find_modified_user_id,
			"DATE_CREATE_1"		=>$find_created_from,
			"CREATED_BY"		=>$find_created_by,
			"CREATED_USER_ID"	=>$find_created_user_id,
			"DATE_ACTIVE_FROM_1"	=>$find_date_active_from_from,
			"DATE_ACTIVE_FROM_2"	=>$find_date_active_from_to,
			"DATE_ACTIVE_TO_1"	=>$find_date_active_to_from,
			"DATE_ACTIVE_TO_2"	=>$find_date_active_to_to,
			"ACTIVE"		=>$find_active,
			"DESCRIPTION"		=>$find_intext,
			"WF_STATUS"		=>$find_status==""?$find_status_id:$find_status,
			"?TAGS"			=>$find_tags,
			"CHECK_PERMISSIONS" => "Y",
			"MIN_PERMISSION" => "R",
		);
		if(!empty($find_timestamp_2))
			$arFilter["TIMESTAMP_X_2"] = CIBlock::isShortDate($find_timestamp_2)? ConvertTimeStamp(AddTime(MakeTimeStamp($find_timestamp_2), 1, "D"), "FULL"): $find_timestamp_2;
		if(!empty($find_created_to))
			$arFilter["DATE_CREATE_2"] = CIBlock::isShortDate($find_created_to)? ConvertTimeStamp(AddTime(MakeTimeStamp($find_created_to), 1, "D"), "FULL"): $find_created_to;

		if ($bBizproc && 'E' != $arIBlock['RIGHTS_MODE'])
		{
			$strPerm = CIBlock::GetPermission($IBLOCK_ID);
			if ('W' > $strPerm)
			{
				unset($arFilter['CHECK_PERMISSIONS']);
				unset($arFilter['MIN_PERMISSION']);
				$arFilter['CHECK_BP_PERMISSIONS'] = 'read';
			}
		}
		foreach($arProps as $arProp)
		{
			if($arProp["FILTRABLE"]=="Y" && $arProp["PROPERTY_TYPE"]!="F")
			{
				$value = ${"find_el_property_".$arProp["ID"]};

				if(array_key_exists("AddFilterFields", $arProp["PROPERTY_USER_TYPE"]))
				{
					call_user_func_array($arProp["PROPERTY_USER_TYPE"]["AddFilterFields"], array(
						$arProp,
						array("VALUE" => "find_el_property_".$arProp["ID"]),
						&$arFilter,
						&$filtered,
					));
				}
				elseif(is_array($value) || strlen($value))
				{
					if($value === "NOT_REF")
						$value = false;
					$arFilter["?PROPERTY_".$arProp["ID"]] = $value;
				}
			}
		}
		break;

	case 'publish':
		break;

	case 'removeSocial':
		if(!isset($_GET['queueId']) or !isset($_GET['accId'])) {
			break;
		}
		$rs = dbPending::getById($_GET['queueId']);
		$ar = $rs->fetch();
		if(!isset($ar['ACCOUNTS'][$_GET['accId']])) {
			break;
		}
		$type = $ar['ACCOUNTS'][$_GET['accId']]['type'];
		$rs = dbPost::getById($ar['POST_ID']);
		$arPost = $rs->fetch();
		if($arPost['PUBLISH']['COMMON']['INDIVIDUAL_SETTINGS']) {
			$arPost['PUBLISH'] = $arPost['PUBLISH']['COMMON'];
		} else {
			$arPost['PUBLISH'] = $arPost['PUBLISH'][$type];
		}
		$social = Module::social($type, 'class');
		$res = $social::delete($ar['ACCOUNTS'][$_GET['accId']], $_GET['accId'], $arPost);
		if($res['success']/* or !$ar['ACCOUNTS'][$_GET['accId']]['success']*/) {
			unset($ar['ACCOUNTS'][$_GET['accId']]);
		} else {
			$ar['ACCOUNTS'][$_GET['accId']] = array_merge($ar['ACCOUNTS'][$_GET['accId']], $res);
		}
		dbPending::update($_GET['queueId'], array(
			'ACCOUNTS' => $ar['ACCOUNTS'],
		));
		break;

	case 'callSocialMethod':
		if(!($social = Module::social($_GET['socialid']))) {
			$result = (array(
				'success' => false,
				'error' => 'socialid "'.$_GET['socialid'].'" not found',
			));
			break;
		}
		$cl = $social['class'];
		$func = $_GET['function'];
		if(!method_exists($cl, $func)) {
			$result = (array(
				'success' => false,
				'error' => 'function "'.$func.'" not found',
			));
			break;
		}
		$res = $cl::$func($_GET['function_args']);
		if($res) {
			$result = array_merge($result, $APPLICATION->ConvertCharset($res, SITE_CHARSET, 'UTF-8'));
			break;
		}
		$result = (array(
			'success' => false,
			'error' => 'result is empty',
		));
		break;

	case 'updateNextPublishAt':
		$postID = $_GET['postID'];
		$ret = vettich\SP\db\postTable::updateNextPublishAt($_GET['postID'], true);
		if($ret === false) {
			$result = (array('success' => false));
			break;
		}
		$ar = vettich\SP\db\postTable::getById($_GET['postID'])->fetch();
		if(!$ar) {
			$result = (array('success' => false));
		}
		$result = (array(
			'success' => true,
			'datetime' => $ar['NEXT_PUBLISH_AT']->toString(),
		));
		break;
}

echo json_encode($result);
exit;
