<?
namespace Vettich\SP;
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Entity;
use Bitrix\Main\Type;

// сохраняем корневую директорию модуля в константу
define('VETTICH_SP_DIR', __DIR__);

if(!\CModule::IncludeModule('vettich.devform')) {
	return false;
}

// new Events();
/**
 * @event OnGetSocials
 * @return <social params>
 *         or
 *         array(<social params>)
 */
class Module extends \vettich\devform\Module
{
	const MODULE_ID = 'vettich.sp';
	public static $events = null;

	private static $_version = null;
	public static function version()
	{
		if(self::$_version === null) {
			$arModuleVersion = array();
			include VETTICH_SP_DIR.'/install/version.php';
			if(empty($arModuleVersion['VERSION'])) {
				self::$_version = '1.0.0';
			} else {
				self::$_version = $arModuleVersion['VERSION'];
			}
		}
		return self::$_version;
	}

	public static function socialsSortCallback($a, $b)
	{
		return strcmp($a['name'], $b['name']);
	}

	private static $_socials = null;
	public static function socials($isAll = false)
	{
		if(self::$_socials === null) {
			self::$_socials = array();
			$res = self::event('OnGetSocials');
			foreach((array)$res as $value) {
				if(is_array($value)){
					if(!empty($value['id'])) {
						self::$_socials[$value['id']] = $value;
					} else {
						reset($value);
						$elem = current($value);
						if(!empty($elem['id'])) {
							foreach((array)$value as $val) {
								if(!empty($val['id'])) {
									self::$_socials[$val['id']] = $val;
								}
							}
						}
					}
				}
			}
			uasort(self::$_socials, array('\Vettich\SP\Module', 'socialsSortCallback'));
		}
		if($isAll) {
			return self::$_socials;
		}

		$show_accounts = \COption::GetOptionString(self::MODULE_ID, 'show_accounts');
		$show_accounts = unserialize($show_accounts);
		if(empty($show_accounts)) {
			return self::$_socials;
		}
		$result = array();
		foreach((array)$show_accounts as $key) {
			if(isset(self::$_socials[$key])) {
				$result[$key] = self::$_socials[$key];
			}
		}
		return $result;
	}

	public static function socialsKeysWithName($isAll = true)
	{
		$socials = self::socials($isAll);
		foreach((array)$socials as $key => $value) {
			$socials[$key] = $value['name'];
		}
		return $socials;
	}

	public static function social($social, $optionKey='')
	{
		$socials = self::socials(true);
		if(empty($socials[$social])) {
			return false;
		}
		if(!empty($optionKey)) {
			if(isset($socials[$social][$optionKey])) {
				return $socials[$social][$optionKey];
			} else {
				return null;
			}
		}
		return $socials[$social];
	}

	private static $_accounts = null;
	public static function accounts($onlyIsEnable=false)
	{
		if(self::$_accounts === null) {
			$dbPostAcc = Social::socialClass();
			$rs = $dbPostAcc::getList();
			while($ar = $rs->fetch()) {
				self::$_accounts[$ar['ID']] = $ar;
			}
		}
		if(!$onlyIsEnable) {
			return self::$_accounts;
		}
		$result = array();
		foreach((array)self::$_accounts as $acc) {
			if($acc['IS_ENABLE'] == 'Y') {
				$result[$acc['ID']] = $acc;
			}
		}
		return $result;
	}

	public static function account($id) {
		$accs = self::accounts();
		if(isset($accs[$id])) {
			return $accs[$id];
		}
		return null;
	}

	public static function socialForId($id, $optionKey='')
	{
		$accs = self::accounts();
		if(isset($accs[$id])) {
			return self::social($accs[$id]['TYPE'], $optionKey);
		}
		return null;
	}

	public static function socialAccountsWithName()
	{
		$result = array();
		foreach((array)self::accounts(true) as $acc) {
			$result[$acc['TYPE']][$acc['ID']] = $acc['NAME'];
		}
		return $result;
	}

	public static function socialAccountsForDevForm($paramid, $params=array())
	{
		$result = array();
		$socialAccs = self::socialAccountsWithName();
		if(is_array($socialAccs)) {
			foreach((array)$socialAccs as $key => $value) {
				$title = self::social($key, 'name');
				if(empty($title)) {
					continue;
				}
				$result[] = new \vettich\devform\types\checkbox($paramid, array(
					'title' => $title,
					'options' => $value,
					'multiple' => true,
					// 'refresh' => true,
					'params' => $params,
				));
			}
		}
		if(empty($result)) {
			$result[] = new \vettich\devform\types\plaintext($paramid, array(
				'title' => '',
				'value' => '#VETTICH_SP_EMPTY_ACCOUNTS#',
			));
		}
		return $result;
	}

	/**
	 * 
	 */
	public static function event($type, $params=array())
	{
		if(!self::$events) {
			self::$events = new Events();
		}
		if(is_string($type)) {
			$result = array();
			$event = new \Bitrix\Main\Event(self::MODULE_ID, $type, $params);
			$event->send();
			if ($event->getResults()) {
				foreach((array)$event->getResults() as $evenResult) {
					$result[] = $evenResult->getParameters();
				}
			}
			return $result;
		}
	}

	private static $_iblocktypes = null;
	private static $_iblocktypesisall = false;
	public static function iblockTypes()
	{
		if(self::$_iblocktypes === null
			or !self::$_iblocktypesisall) {
			$rsIBlockType = \CIBlockType::GetList();
			while ($arIBlockType = $rsIBlockType->GetNext())
			{
				if($arIBType = \CIBlockType::GetByIDLang($arIBlockType["ID"], LANG))
				{
					$arIBlockType['NAME'] = $arIBType['NAME'];
					$arIBlockType['SECTION_NAME'] = $arIBType['SECTION_NAME'];
					$arIBlockType['ELEMENT_NAME'] = $arIBType['ELEMENT_NAME'];
					self::$_iblocktypes[$type] = $arIBlockType;
				}
			}
			self::$_iblocktypesisall = true;
		}
		return self::$_iblocktypes;
	}

	public static function iblockType($type)
	{
		if(self::$_iblocktypes === null
			or !isset(self::$_iblocktypes[$type])) {
			self::$_iblocktypes[$type] = null;
			$rsIBlockType = \CIBlockType::GetByID($type);
			if ($arIBlockType = $rsIBlockType->GetNext())
			{
				if($arIBType = \CIBlockType::GetByIDLang($arIBlockType["ID"], LANG))
				{
					$arIBlockType['NAME'] = $arIBType['NAME'];
					$arIBlockType['SECTION_NAME'] = $arIBType['SECTION_NAME'];
					$arIBlockType['ELEMENT_NAME'] = $arIBType['ELEMENT_NAME'];
					self::$_iblocktypes[$type] = $arIBlockType;
				}
			}
		}
		return self::$_iblocktypes[$type];
	}

	private static $_iblockids = array();
	public static function iblockId($id)
	{
		if(empty($id) or !\CModule::IncludeModule('iblock')) {
			return null;
		}
		if(!isset(self::$_iblockids[$id])){
			self::$_iblockids[$id] = null;
			$rs = \CIBlock::GetByID($id);
			if($rs = $rs->GetNext()) {
				self::$_iblockids[$id] = $rs;
			}
		}
		return self::$_iblockids[$id];
	}

	private static $_iblockElemIds = array();
	public static function iblockElemId($id, $iblockId)
	{
		if(!\CModule::IncludeModule('iblock')) {
			return null;
		}
		if((is_array($id) && in_array($id, array_keys(self::$_iblockElemIds)))
			or empty(self::$_iblockElemIds[$id])) {
			$rs = \CIBlockElement::GetList(
				array('sort'=>'asc'),
				array(
					'ID' => $id,
					'IBLOCK_ID' => $iblockId,
				)
			);
			while($ar = $rs->GetNext()) {
				self::iblockValueFill($ar);
				self::$_iblockElemIds[$ar['ID']] = $ar;
			}
		}
		if(is_array($id)) {
			$result = array();
			foreach((array)$id as $i) {
				$result[$i] = self::$_iblockElemIds[$i];
			}
			return $result;
		}
		return self::$_iblockElemIds[$id];
	}

	private static $_iblockSections = array();
	public static function iblockSection($id, $iblockId)
	{
		if(!\CModule::IncludeModule('iblock')) {
			return null;
		}
		if((is_array($id) && in_array($id, array_keys(self::$_iblockSections)))
			or empty(self::$_iblockSections[$id])) {
			$rs = \CIBlockSection::GetList(
				array('sort'=>'asc'),
				array(
					'ID' => $id,
					'IBLOCK_ID' => $iblockId,
				)
			);
			while($ar = $rs->GetNext()) {
				self::$_iblockSections[$ar['ID']] = $ar;
			}
		}
		if(is_array($id)) {
			$result = array();
			foreach((array)$id as $i) {
				$result[$i] = self::$_iblockSections[$i];
			}
			return $result;
		}
		return self::$_iblockSections[$id];
	}

	public static function onRenderViewIblockType($obj, &$value=null)
	{
		$iblocktype = self::iblockType($value);
		if($iblocktype) {
			$value = "[$value] $iblocktype[NAME]";
		}
	}

	public static function onRenderViewIblockId($obj, &$value=null)
	{
		$iblockid = self::iblockId($value);
		if($iblockid) {
			$value = "[$value] $iblockid[NAME]";
		}
	}


	private static $_iblockFields = null;
	public static function iblockFields()
	{
		if(self::$_iblockFields == null) {
			self::$_iblockFields = array(
				''                   => 'none',
				'ID'                 => GetMessage('VETTICH_SP_PROP_ID'),
				'CODE'               => GetMessage('VETTICH_SP_PROP_CODE'),
				'XML_ID'             => GetMessage('VETTICH_SP_PROP_XML_ID'),
				'NAME'               => GetMessage('VETTICH_SP_PROP_NAME'),
				'IBLOCK_ID'          => GetMessage('VETTICH_SP_PROP_IBLOCK_ID'),
				'IBLOCK_SECTION_ID'  => GetMessage('VETTICH_SP_PROP_IBLOCK_SECTION_ID'),
				'IBLOCK_CODE'        => GetMessage('VETTICH_SP_PROP_IBLOCK_CODE'),
				'ACTIVE'             => GetMessage('VETTICH_SP_PROP_ACTIVE'),
				'DATE_ACTIVE_FROM'   => GetMessage('VETTICH_SP_PROP_DATE_ACTIVE_FROM'),
				'DATE_ACTIVE_TO'     => GetMessage('VETTICH_SP_PROP_DATE_ACTIVE_TO'),
				'SORT'               => GetMessage('VETTICH_SP_PROP_SORT'),
				'PREVIEW_PICTURE'    => GetMessage('VETTICH_SP_PROP_PREVIEW_PICTURE'),
				'PREVIEW_TEXT'       => GetMessage('VETTICH_SP_PROP_PREVIEW_TEXT'),
				'DETAIL_PICTURE'     => GetMessage('VETTICH_SP_PROP_DETAIL_PICTURE'),
				'DETAIL_TEXT'        => GetMessage('VETTICH_SP_PROP_DETAIL_TEXT'),
				'DATE_CREATE'        => GetMessage('VETTICH_SP_PROP_DATE_CREATE'),
				'CREATED_BY'         => GetMessage('VETTICH_SP_PROP_CREATED_BY'),
				'CREATED_USER_NAME'  => GetMessage('VETTICH_SP_PROP_CREATED_USER_NAME'),
				'TIMESTAMP_X'        => GetMessage('VETTICH_SP_PROP_TIMESTAMP_X'),
				'MODIFIED_BY'        => GetMessage('VETTICH_SP_PROP_MODIFIED_BY'),
				'USER_NAME'          => GetMessage('VETTICH_SP_PROP_USER_NAME'),
				'LIST_PAGE_URL'      => GetMessage('VETTICH_SP_PROP_LIST_PAGE_URL'),
				'DETAIL_PAGE_URL'    => GetMessage('VETTICH_SP_PROP_DETAIL_PAGE_URL'),
				'SHOW_COUNTER'       => GetMessage('VETTICH_SP_PROP_SHOW_COUNTER'),
				'SHOW_COUNTER_START' => GetMessage('VETTICH_SP_PROP_SHOW_COUNTER_START'),
				'WF_COMMENTS'        => GetMessage('VETTICH_SP_PROP_WF_COMMENTS'),
				'WF_STATUS_ID'       => GetMessage('VETTICH_SP_PROP_WF_STATUS_ID'),
				'TAGS'               => GetMessage('VETTICH_SP_PROP_TAGS'),
			);
		}
		return self::$_iblockFields;
	}

	private static $_iblockProps = array();
	public static function iblockProps($iblockId)
	{
		if(empty($iblockId)) {
			return null;
		}
		if(!isset(self::$_iblockProps[$iblockId])) {
			$arProps = array();
			$rsProperties = \CIBlockProperty::GetList(
				Array(),
				Array('ACTIVE'=>'Y', 'IBLOCK_ID'=>$iblockId)
			);
			while ($prop_fields = $rsProperties->GetNext())
			{
				$str = $prop_fields['NAME']. ' [PROPERTY_'. $prop_fields['CODE']. ']';
				$str = str_replace("'", '"', $str);
				$str = str_replace(array("\"", '&quot;', '&#34;'), "'", $str);
				$arProps['PROPERTY_'.$prop_fields['CODE']] = $str;
			}
			self::$_iblockProps[$iblockId] = $arProps;
		}
		return self::$_iblockProps[$iblockId];
	}

	private static $_catalogFields = array();
	public static function catalogFiedls($iblockId)
	{
		if(empty($iblockId)) {
			return null;
		}
		if(!isset(self::$_catalogFields[$iblockId])) {
			$arProps = array();
			if(\CModule::IncludeModule('catalog')
				&& \CCatalog::GetByID($iblockId)) {
				$arProps['CATALOG_QUANTITY'] = GetMessage('VETTICH_SP_PROP_CAT_QUANTITY');
				$arProps['CATALOG_WEIGHT'] = GetMessage('VETTICH_SP_PROP_CAT_WEIGHT');
				$arProps['CATALOG_WIDTH'] = GetMessage('VETTICH_SP_PROP_CAT_WIDTH');
				$arProps['CATALOG_LENGTH'] = GetMessage('VETTICH_SP_PROP_CAT_LENGTH');
				$arProps['CATALOG_HEIGHT'] = GetMessage('VETTICH_SP_PROP_CAT_HEIGHT');

				$rs = \CCatalogGroup::GetList(array(), array(), false, false, array('ID', 'NAME_LANG'));
				$arCurrency = array();
				while($ar = $rs->Fetch())
				{
					$arProps['CATALOG_PRICE_'.$ar['ID']] = GetMessage('VETTICH_SP_PROP_CAT_PRICE', array(
						'#TYPE#' => $ar['NAME_LANG'],
						'#PRICE_ID#' => $ar['ID'],
					));
					$arCurrency['CATALOG_CURRENCY_'.$ar['ID']] = GetMessage('VETTICH_SP_PROP_CAT_CURRENCY', array(
						'#TYPE#' => $ar['NAME_LANG'],
						'#PRICE_ID#' => $ar['ID'],
					));
				}
				foreach((array)$arCurrency as $key => $value) {
					$arProps[$key] = $value;
				}

				$arProps['CATALOG_DISCOUNT_NAME'] = GetMessage('VETTICH_SP_PROP_CAT_DISCOUNT_NAME');
				$arProps['CATALOG_DISCOUNT_ACTIVE_FROM'] = GetMessage('VETTICH_SP_PROP_CAT_DISCOUNT_ACTIVE_FROM');
				$arProps['CATALOG_DISCOUNT_ACTIVE_TO'] = GetMessage('VETTICH_SP_PROP_CAT_DISCOUNT_ACTIVE_TO');
				self::$_catalogFields[$iblockId] = $arProps;
			}
		}
		return self::$_catalogFields[$iblockId];
	}

	private static $_allPropsFor = array();
	public static function allPropsFor($iblockId, $isIblockIsset=true)
	{
		if($isIblockIsset && empty($iblockId)) {
			return array('' => GetMessage('VETTICH_SP_BEFORE_IBLOCK_SELECT'));
		}
		if(isset(self::$_allPropsFor[$iblockId])) {
			return self::$_allPropsFor[$iblockId];
		}
		$result = array();
		$result[] = array(
			'label' => GetMessage('VETTICH_SP_MAIN_FIELDS'),
			'items' => self::iblockFields(),
		);
		$result[] = array(
			'label' => GetMessage('VETTICH_SP_PROPERTIES'),
			'items' => self::iblockProps($iblockId),
		);
		$result[] = array(
			'label' => GetMessage('VETTICH_SP_CATALOG_FIELDS'),
			'items'=> self::catalogFiedls($iblockId),
		);
		self::$_allPropsFor[$iblockId] = $result;
		return $result;
	}

	private static $_allPropsMacrosFor = array();
	public static function allPropsMacrosFor($iblockId, $isIblockIsset=true)
	{
		if(isset(self::$_allPropsMacrosFor[$iblockId])) {
			return self::$_allPropsMacrosFor[$iblockId];
		}
		$result = self::allPropsFor($iblockId, $isIblockIsset);
		foreach((array)$result as $key => $value) {
			if(empty($key) && $key !== 0) {
				continue;
			}
			if(isset($value['items'])) {
				foreach((array)$value['items'] as $key2 => $value2) {
					if(!$key2) {
						continue;
					}
					self::changeKey($key2, "#$key2#", $value['items']);
				}
				// $value = $result[$key];
				unset($result[$key]);
				$result[$key] = $value;
			} else {
				self::changeKey($key, "#$key#", $result);
			}
		}
		self::$_allPropsMacrosFor[$iblockId] = $result;
		return $result;
	}

	public static function iblockValueFill(&$arFields, $isFull=false)
	{
		if($isFull) {
			$arFields = self::iblockElemId($arFields['ID'], $arFields['IBLOCK_ID']);
		}
		$rsProp = \CIBlockElement::GetProperty($arFields['IBLOCK_ID'], $arFields['ID'], array(), Array());
		while($arProp = $rsProp->GetNext())
		{
			if(!isset($arFields['PROPERTY_'.$arProp['CODE']]))
			{
				$arFields['PROPERTY_'.$arProp['CODE']] = $arProp;
			}
			if($arProp['MULTIPLE'] == 'Y')
			{
				if($arProp['VALUE']) {
					$arFields['PROPERTY_'.$arProp['CODE']]['VALUES'][] = $arProp['VALUE'];
				}
				if($arProp['~VALUE']) {
					$arFields['PROPERTY_'.$arProp['CODE']]['~VALUES'][] = $arProp['~VALUE'];
				}
				$arFields['PROPERTY_'.$arProp['CODE']]['VALUES_ENUM'][] = $arProp['VALUE_ENUM'];
				$arFields['PROPERTY_'.$arProp['CODE']]['~VALUES_ENUM'][] = $arProp['~VALUE_ENUM'];
				$arFields['PROPERTY_'.$arProp['CODE']]['VALUES_XML_ID'][] = $arProp['VALUE_XML_ID'];
				$arFields['PROPERTY_'.$arProp['CODE']]['~VALUES_XML_ID'][] = $arProp['~VALUE_XML_ID'];
			}
		}
		if(\CModule::IncludeModule('catalog')
			&& \CCatalog::GetByID($arFields['IBLOCK_ID'])) {
			$db_res = \CCatalogProduct::GetList(
				array(),
				array("ID" => $arFields['ID']),
				false,
				false,
				array(
					'ID',
					'QUANTITY',
					'WEIGHT',
					'WIDTH',
					'LENGTH',
					'HEIGHT',
				)
			);
			if($ar = $db_res->Fetch())
			{
				foreach((array)$ar as $key => $value) {
					$arFields['CATALOG_'.$key] = $value;
				}

				$rs = \CCatalogGroup::GetList(array(), array(), false, false, array('ID', 'NAME_LANG'));
				while($ar = $rs->Fetch())
				{
					$rsPrice = \CPrice::GetListEx(array(), array(
							'PRODUCT_ID' => $arFields['ID'],
							'CATALOG_GROUP_ID' => $ar['ID'],
						),
						false, false, array(
							'ID',
							'PRICE',
							'CURRENCY',
						)
					);
					if($arPrice = $rsPrice->Fetch())
					{
						$arFields['CATALOG_PRICE_'.$ar['ID']] = $arPrice['PRICE'];
						$arFields['CATALOG_CURRENCY_'.$ar['ID']] = $arPrice['CURRENCY'];
					}
				}

				$rsDiscount = \CCatalogDiscount::GetList(array(), array(
						'PRODUCT_ID' => $arFields['ID'],
					),
					false, false, array(
						'ID',
						'ACTIVE_FROM',
						'ACTIVE_TO',
						'NAME',
					)
				);
				if($arDiscount = $rsDiscount->Fetch())
				{
					$arFields['CATALOG_DISCOUNT_NAME'] = $arDiscount['NAME'];
					$arFields['CATALOG_DISCOUNT_ACTIVE_FROM'] = $arDiscount['ACTIVE_FROM'];
					$arFields['CATALOG_DISCOUNT_ACTIVE_TO'] = $arDiscount['ACTIVE_TO'];
				}
			}
			$rs = current(\CCatalogSKU::getOffersList($arFields['ID'], $arFields['IBLOCK_ID']));
			if(!empty($rs)) foreach((array)$rs as $ar) {
				$arFields['SKU'][$ar['ID']] = self::iblockElemId($ar['ID'], $ar['IBLOCK_ID']);
			}
		}
	}

	public static function getWeekName($week_number)
	{
		switch($week_number) {
			case 1: return 'monday';
			case 2: return 'tuesday';
			case 3: return 'wednesday';
			case 4: return 'thursday';
			case 5: return 'friday';
			case 6: return 'saturday';
			default: return 'sunday';
		}
	}

	public static function getWeekNumber($week_name)
	{
		switch($week_name) {
			case 'monday': return 1;
			case 'tuesday': return 2;
			case 'wednesday': return 3;
			case 'thursday': return 4;
			case 'friday': return 5;
			case 'saturday': return 6;
			default: return 0; // sunday
		}
	}

	public static function getScheme()
	{
		if (isset($_SERVER['HTTPS'])) {
			$scheme = $_SERVER['HTTPS'];
		} else {
			$scheme = '';
		}
		if (($scheme) && ($scheme != 'off')) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}
		return $scheme;
	}

	public static function siteUrl($withScheme=true)
	{
		$url = '';
		if($withScheme) {
			$url = self::getScheme().'://';
		}
		$url .= $_SERVER['SERVER_NAME'];
		return $url;
	}

	public static function cloudCronUrl()
	{
		$url = static::siteUrl().'/bitrix/tools/vettich.sp/ajax.php?method=publishFromCloudCron';
		return $url;
	}

	public static function cleanConditions($data)
	{
		$arResult = array();
		foreach((array)$data as $key => $value) {
			if(!empty($value['field']) && $value['field'] != 'none') {
				$arResult[] = $value;
			}
		}
		return $arResult;
	}

	public static function nextPublishAt($fields)
	{
		if($fields['IS_INTERVAL'] != 'Y') {
			return null;
		}
		if($fields['NEXT_PUBLISH_AT']) { // обновляем время следующей публикации
			$tmp = $fields['NEXT_PUBLISH_AT'];
			$result = new \DateTime($tmp->format('d-m-Y H:i:s'));
			$result->modify('+'.$fields['INTERVAL'].' minutes');
			$now = new \DateTime();
		} else { // время публикации текущее время
			$result = new \DateTime();
			// обнуляем секундную часть
			$result->setTime($result->format("H"), $result->format("i"), 0);
			$now = new \DateTime($result->format("Y-m-d H:i:s"));
			/*$result->modify('+'.$fields['INTERVAL'].' minutes');*/
		}
		if($fields['IS_PERIOD'] == 'Y') {
			$c = $result->format('H:i');
			if(($c >= $fields['PERIOD_FROM'] && $c <= $fields['PERIOD_TO'])
				|| ($fields['PERIOD_FROM'] > $fields['PERIOD_TO']
					&& !($c < $fields['PERIOD_FROM']
						&& $c > $fields['PERIOD_TO']))) {
			} else {
				unset($result);
				$result = new \DateTime($fields['PERIOD_FROM']);
				if($c > $fields['PERIOD_TO'] && $c > $fields['PERIOD_FROM']) {
					$result->modify('+1 day');
				}
			}
		}
		if($fields['EVERY'] == 'WEEK') {
			while(!in_array(Module::getWeekName($result->format('w')), $fields['WEEK'])) {
				$result->modify('+1 day');
			}
		} else if($fields['EVERY'] == 'MONTH') {
			while(!in_array($result->format('d'), $fields['MONTH'])) {
				$result->modify('+1 day');
			}
		}
		if($result < $now && isset($fields['NEXT_PUBLISH_AT'])) {
			unset($fields['NEXT_PUBLISH_AT']);
			return self::nextPublishAt($fields);
		}
		return Type\DateTime::createFromPhp($result);
	}

	public static function timeToUserTime($time, $format='H:i')
	{
		return (new \Bitrix\Main\Type\DateTime('01.01.1970 '.$time.':00'))->toUserTime()->format($format);
	}

	public static function timeFromUserTime($time, $format='H:i')
	{
		$tmp = Type\DateTime::createFromUserTime('01.01.1970 '.$time.':00');
		return $tmp ? $tmp->format($format) : '';
	}

	private static $_datetimeformat = 'd.m.Y H:i:s';
	public static function dateToUserTime($date)
	{
		$date = date(self::$_datetimeformat, strtotime($date));
		return (new Type\DateTime($date))->toUserTime()->format(self::$_datetimeformat);
	}

	public static function dateFromUserTime($date)
	{
		$date = date(self::$_datetimeformat, strtotime($date));
		$tmp = Type\DateTime::createFromUserTime($date);
		return $tmp->format(self::$_datetimeformat);
	}
}
?>
