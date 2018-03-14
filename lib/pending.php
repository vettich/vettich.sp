<?
namespace vettich\SP;

use vettich\SP\db\pendingTable as db;
use vettich\SP\db\postTable as dbPost;

/**
* @author Vettich
*/
class Pending extends Module
{
	
	function __construct($args=array())
	{
		parent::__construct($args);
	}


	/**
	 * add element to queue
	 * @param array $params = array (
	 *                      NAME - string
	 *                      IS_ENABLE - string - Y|N - default Y
	 *                      STATUS - string - default READY
	 *                      RESULT - string - default ''
	 *                      POST_ID - int|string
	 *                      IBLOCK_TYPE - int|string
	 *                      IBLOCK_ID - int|string
	 *                      ELEMENT_ID - int|string
	 *                      ACCOUNTS - array (
	 *                      	[type - vk,facebook,etc..] - array (
	 *                       		status - string - null|ok|fail
	 *                         		error_msg - string - error message
	 *                         		error_code - string|int - error code
	 *                           	post_id - int|string - id of the post in the social network
	 *                           	... and other params of post
	 *                        	)
	 *                      )
	 *                 )
	 * @return int|boolean elem ID or false if not success
	 */
	public function add($params)
	{
		try {
			$rs = db::add($params);
			return $rs->isSuccess() ? $rs->getId() : false;
		} catch (\Exception $e) {
			devdebug(array($e->getCode(), $e->getMessage()), 'error.sp.pending.add');
		}
	}

	/**
	 * добавляет элемент инфоблока в очередь отложенной публикации
	 * @param array $arFields  поля элемента инфоблока
	 * @param array  $params   дополнительные параметры
	 * @return boolean|array   возвращает массив ID элементов очереди, либо FALSE при неудаче
	 */
	public static function addElem($arFields, $params=array())
	{
		if(empty($arFields)
			or $arFields['ID'] <= 0
			or (!empty($arFields['WF_PARENT_ELEMENT_ID'])
				&& $arFields['ID'] != $arFields['WF_PARENT_ELEMENT_ID'])) {
			return false;
		}
		if(self::GetOptionString('is_enable', 'Y') != 'Y'
			|| !($arFields['ID'] > 0)) {
			return false;
		}
		$arResult = array();
		$arPosts = array();
		if(empty($params['arPost'])) {
			$rsPosts = dbPost::getList(array(
				'filter' => array('IS_ENABLE' => 'Y'),
			));
			while ($ar = $rsPosts->fetch()) {
				$arPosts[$ar['ID']] = $ar;
			}
			self::iblockValueFill($arFields);
		} else {
			$arPosts[$params['arPost']['ID']] = $params['arPost'];
			self::iblockValueFill($arFields, true);
		}
		foreach((array)$arPosts as $arPost) {
			if(($arPost['IS_MANUALLY'] == 'Y' && $params['type'] == 'OnAfterIblockElementAdd')
				|| $arPost['IBLOCK_ID'] != $arFields['IBLOCK_ID']
				|| !Post::cmpFields($arFields, $arPost)
				|| !self::inSections($arFields, $arPost)) {
				continue;
			}
			if($arPost['QUEUE_DUPLICATE'] == 'Y') {
				try {
					$rs = db::getList(array(
						'filter' => array(
							'POST_ID' => $arPost['ID'],
							'IBLOCK_TYPE' => $arPost['IBLOCK_TYPE'],
							'IBLOCK_ID' => $arPost['IBLOCK_ID'],
							'ELEMENT_ID' => $arFields['ID'],
						),
						'limit' => 1,
						'select' => array('ID'),
					));
					if($rs->fetch()) {
						continue;
					}
				} catch (\Exception $e) {
					devdebug(array($e->getCode(), $e->getMessage()), 'error.sp.pending.addElem');
				}
			}
			if($id = self::add(array(
				'NAME' => $arFields['NAME'],
				'POST_ID' => $arPost['ID'],
				'IBLOCK_TYPE' => $arPost['IBLOCK_TYPE'],
				'IBLOCK_ID' => $arPost['IBLOCK_ID'],
				'ELEMENT_ID' => $arFields['ID'],
			))) {
				$arResult[] = $id;
			}
		}
		return $arResult;
	}

	public static function addElemMix($arFields, $params)
	{
		if(empty($params['arPost'])) {
			return false;
		}
		if(!$params['arFieldsFilled']) {
			self::iblockValueFill($arFields);
		}
		$arPost = $params['arPost'];
		if(!empty($arPost['CONDITIONS']) && Post::cmpFields($arFields, $arPost)) {
			return false;
		}
		try {
			if($arPost['QUEUE_DUPLICATE'] == 'Y') {
				$rs = db::getList(array(
					'filter' => array(
						'POST_ID' => '',
						'IBLOCK_TYPE' => $arPost['IBLOCK_TYPE'],
						'IBLOCK_ID' => $arPost['IBLOCK_ID'],
						'ELEMENT_ID' => $arFields['ID'],
					),
					'limit' => 1,
					'select' => array('ID'),
				));
				if($rs->fetch()) {
					return false;
				}
			}
			$arAdd = array(
				'NAME' => $arFields['NAME'],
				'IBLOCK_TYPE' => $arPost['IBLOCK_TYPE'],
				'IBLOCK_ID' => $arPost['IBLOCK_ID'],
				'ELEMENT_ID' => $arFields['ID'],

				'TYPE' => 'MIX',
				'PROTOCOL' => $arPost['PROTOCOL'],
				'DOMAIN' => $arPost['DOMAIN'],
				'URL_PARAMS' => $arPost['URL_PARAMS'],
				'SOCIALS' => $arPost['SOCIALS'],
				'PUBLISH' => $arPost['PUBLISH'],
				'PUBLISH_AT' => $arPost['PUBLISH_AT'],
				'UPDATE_ELEM' => $arPost['UPDATE_ELEM'],
				'DELETE_ELEM' => $arPost['DELETE_ELEM'],
			);
			if($id = self::add($arAdd)) {
				return $id;
			}
		} catch (\Exception $e) {
			devdebug(array($e->getCode(), $e->getMessage()), 'error.sp.pending.addElemMix');
		}
		return false;
	}

	public static function addElemSimple($arFields, $params)
	{
		
	}

	/**
	 * @param  int|string $id
	 * @param  array $params
	 * @return boolean
	 */
	public function update($id, $params)
	{
		try {
			db::update($id, $params);
		} catch (\Exception $e) {
			devdebug(array($e->getCode(), $e->getMessage()), 'error.sp.pending.update');
		}
	}

	public function updateElem($arFields, $params=array())
	{
		if(self::GetOptionString('is_enable', 'Y') != 'Y') {
			return;
		}
		try {
			$rs = db::getList(array(
				'filter' => array(
					'ELEMENT_ID' => $arFields['ID'],
					'IBLOCK_ID' => $arFields['IBLOCK_ID'],
					'STATUS' => array('PUBLISH', 'UPDATE'),
					'!RESULT' => array('READY', 'RUNNING'),
				),
			));
		} catch (\Exception $e) {
			devdebug(array($e->getCode(), $e->getMessage()), 'error.sp.pending.updateElem');
			return;
		}
		while($ar = $rs->fetch()) {
			$rsPost = dbPost::getById($ar['POST_ID']);
			if(!($arPost = $rsPost->fetch())) {
				continue;
			}
			if($arPost['QUEUE_ELEMENT_UPDATE'] != 'Y') {
				continue;
			}
			$upd = array(
				'STATUS' => 'UPDATE',
				'RESULT' => 'READY',
			);
			try {
				db::update($ar['ID'], $upd);
			} catch (\Exception $e) {
				devdebug(array($e->getCode(), $e->getMessage()), 'error.sp.pending.updateElem');
				continue;
			}
		}
	}

	public function deleteElem($arFields, $params=array())
	{
		if(self::GetOptionString('is_enable', 'Y') != 'Y') {
			return;
		}
		try {
			$rs = db::getList(array(
				'filter' => array(
					'ELEMENT_ID' => $arFields['ID'],
					'IBLOCK_ID' => $arFields['IBLOCK_ID'],
				),
			));
		} catch (\Exception $e) {
			devdebug(array($e->getCode(), $e->getMessage()), 'error.sp.pending.deleteElem');
			return;
		}
		while($ar = $rs->fetch()) {
			$rsPost = dbPost::getById($ar['POST_ID']);
			if(!($arPost = $rsPost->fetch())) {
				continue;
			}
			if($arPost['QUEUE_ELEMENT_DELETE'] != 'Y') {
				continue;
			}
			$upd = array(
				'STATUS' => 'DELETE',
				'RESULT' => 'READY',
			);
			try {
				db::update($ar['ID'], $upd);
			} catch (\Exception $e) {
				devdebug(array($e->getCode(), $e->getMessage()), 'error.sp.pending.deleteElem');
				continue;
			}
		}
	}

	public static function run($id, $params=array())
	{
		if(self::GetOptionString('is_enable', 'Y') != 'Y') {
			return;
		}
		if(is_array($id)) {
			$ar = $id;
			$id = $ar['ID'];
		} else {
			$ar = db::getById($id)->fetch();
		}
		if(empty($ar)) {
			return 1;
		}
		if($ar['RESULT'] != 'RUNNING'
			or in_array('running', $params['mode'])) {
			try {
				$upd = array('RESULT' => 'RUNNING');
				db::update($ar['ID'], $upd);
			} catch (\Exception $e) {
				devdebug(array($e->getCode(), $e->getMessage()), 'error.sp.pending.run');
				return 2;
			}
			if(!is_array($ar['ACCOUNTS'])) {
				$ar['ACCOUNTS'] = array();
			}

			if($ar['TYPE'] == 'IBLOCK') {
				return self::runIBlockType($ar, $params);
			} elseif($ar['TYPE'] == 'MIX') {
				return self::runMixType($ar, $params);
			}
		}
		return 0;
	}

	/**
	 * [runIBlockType description]
	 * @param  array $ar     queue[pending] element array
	 * @param  array   $params [mode => [only error, only empty, only one, running]]
	 */
	public static function runIBlockType($ar, $params=array())
	{
		if(!empty($params['arPost'])) {
			$arPost = $params['arPost'];
		} else {
			$arPost = dbPost::getById($ar['POST_ID'])->fetch();
		}
		if(empty($arPost)) {
			try {
				$upd = array('RESULT' => 'ERROR_POST');
				db::update($ar['ID'], $upd);
			} catch (\Exception $e) {
				devdebug(array($e->getCode(), $e->getMessage()), 'error.sp.pending.run');
			}
			return 3;
		}
		$arFields = self::iblockElemId($ar['ELEMENT_ID'], $ar['IBLOCK_ID']);
		foreach ((array)$arPost['ACCOUNTS'] as $_id) {
			if(in_array('only error', $params['mode'])
				&& isset($ar['ACCOUNTS'][$_id]['success'])
				&& $ar['ACCOUNTS'][$_id]['success']) {
				continue;
			}
			if(in_array('only empty', $params['mode'])
				&& !empty($ar['ACCOUNTS'][$_id])) {
				continue;
			}
			$cl = self::socialForId($_id, 'class');
			$type = self::socialForId($_id, 'id');
			if($cl && Post::cmpFields($arFields, $arPost, $arPost['PUBLISH'][$type]['CONDITIONS'])) {
				$_arPost = $arPost;
				if($arPost['PUBLISH']['COMMON']['INDIVIDUAL_SETTINGS'] != 'Y') {
					$_arPost['PUBLISH'] = $arPost['PUBLISH']['COMMON'];
				} else {
					$_arPost['PUBLISH'] = $arPost['PUBLISH'][$type];
				}
				if($ar['STATUS'] == 'PUBLISH') {
					$res = $cl::publish($_id, $_arPost, $arFields);
				} elseif($ar['STATUS'] == 'UPDATE') {
					$res = $cl::update($ar['ACCOUNTS'][$_id], $_id, $_arPost, $arFields);
				} elseif($ar['STATUS'] == 'DELETE') {
					$res = $cl::delete($ar['ACCOUNTS'][$_id], $_id, $_arPost);
				}
				if(!empty($res)) {
					if(empty($ar['ACCOUNTS'][$_id])) {
						$ar['ACCOUNTS'][$_id] = $res;
						$ar['ACCOUNTS'][$_id]['type'] = $type;
					} else {
						$ar['ACCOUNTS'][$_id] = array_merge($ar['ACCOUNTS'][$_id], $res);
					}
				}
			}
			if(in_array('only one', $params['mode'])) {
				break;
			}
		}
		try {
			$upd = array(
				'ACCOUNTS' => $ar['ACCOUNTS'],
			);
			$success_count = 0;
			foreach ((array)$ar['ACCOUNTS'] as $acc) {
				if($acc['success']) {
					$success_count++;
				}
			}
			if((count($ar['ACCOUNTS']) && $success_count >= count($ar['ACCOUNTS']))
				or empty($ar['ACCOUNTS'])) {
				$upd['RESULT'] = 'SUCCESS';
			} elseif($success_count < count($ar['ACCOUNTS']) && $success_count > 0) {
				$upd['RESULT'] = 'WARNING';
			} else {
				$upd['RESULT'] = 'ERROR';
			}
			$r = db::update($ar['ID'], $upd);
			if($ar['STATUS'] == 'PUBLISH' && $ar['RESULT'] == 'READY') {
				self::updateNextPublishAt($arPost);
			}
		} catch (\Exception $e) {
			devdebug($e, 'error.sp.pending.run');
			return 4;
		}
		return 0; // no errors
	}

	public static function runMixType($ar, $params=array())
	{
		$arPost = array(
			'IS_ENABLE' => 'Y',
			'IBLOCK_TYPE' => $ar['IBLOCK_TYPE'],
			'IBLOCK_ID' => $ar['IBLOCK_ID'],
			'IS_SECTIONS' => 'N',
			'PROTOCOL' => $ar['PROTOCOL'],
			'DOMAIN' => $ar['DOMAIN'],
			'URL_PARAMS' => $ar['URL_PARAMS'],
			'ACCOUNTS' => $ar['SOCIALS'],
			'PUBLISH' => $ar['PUBLISH'],
		);
		$arFields = self::iblockElemId($ar['ELEMENT_ID'], $ar['IBLOCK_ID']);
		foreach ((array)$arPost['ACCOUNTS'] as $_id) {
			if(in_array('only error', $params['mode'])
				&& isset($ar['ACCOUNTS'][$_id]['success'])
				&& $ar['ACCOUNTS'][$_id]['success']) {
				continue;
			}
			if(in_array('only empty', $params['mode'])
				&& !empty($ar['ACCOUNTS'][$_id])) {
				continue;
			}
			$cl = self::socialForId($_id, 'class');
			$type = self::socialForId($_id, 'id');
			if($cl && Post::cmpFields($arFields, $arPost, $arPost['PUBLISH'][$type]['CONDITIONS'])) {
				$_arPost = $arPost;
				if($arPost['PUBLISH']['COMMON']['INDIVIDUAL_SETTINGS'] != 'Y') {
					$_arPost['PUBLISH'] = $arPost['PUBLISH']['COMMON'];
				} else {
					$_arPost['PUBLISH'] = $arPost['PUBLISH'][$type];
				}
				if($ar['STATUS'] == 'PUBLISH') {
					$res = $cl::publish($_id, $_arPost, $arFields);
				} elseif($ar['STATUS'] == 'UPDATE') {
					$res = $cl::update($ar['ACCOUNTS'][$_id], $_id, $_arPost, $arFields);
				} elseif($ar['STATUS'] == 'DELETE') {
					$res = $cl::delete($ar['ACCOUNTS'][$_id], $_id, $_arPost);
				}
				if(!empty($res)) {
					if(empty($ar['ACCOUNTS'][$_id])) {
						$ar['ACCOUNTS'][$_id] = $res;
						$ar['ACCOUNTS'][$_id]['type'] = $type;
					} else {
						$ar['ACCOUNTS'][$_id] = array_merge($ar['ACCOUNTS'][$_id], $res);
					}
				}
			}
			if(in_array('only one', $params['mode'])) {
				break;
			}
		}
		try {
			$upd = array(
				'ACCOUNTS' => $ar['ACCOUNTS'],
			);
			$success_count = 0;
			foreach ((array)$ar['ACCOUNTS'] as $acc) {
				if($acc['success']) {
					$success_count++;
				}
			}
			if((count($ar['ACCOUNTS']) && $success_count >= count($ar['ACCOUNTS']))
				or empty($ar['ACCOUNTS'])) {
				$upd['RESULT'] = 'SUCCESS';
			} elseif($success_count < count($ar['ACCOUNTS']) && $success_count > 0) {
				$upd['RESULT'] = 'WARNING';
			} else {
				$upd['RESULT'] = 'ERROR';
			}
			$r = db::update($ar['ID'], $upd);
			// if($ar['STATUS'] == 'PUBLISH' && $ar['RESULT'] == 'READY') {
			// 	self::updateNextPublishAt($arPost);
			// }
		} catch (\Exception $e) {
			devdebug($e, 'error.sp.pending.run');
			return 4;
		}
		return 0; // no errors
	}

	/**
	 * [checkPublish description]
	 * @param  string $from hit|cron|cloud_cron
	 * @todo remove this method
	 */
	public static function checkPublishOld($from='hit')
	{
		if($from != self::GetOptionString('method_post', 'hit')) {
			return false;
		}
		global $DB;
		try {
			if(self::GetOptionString('is_enable', 'Y') != 'Y') {
				return;
			}
			if(self::GetOptionString('queueLast', 0) >= time()) {
				return;
			}
			self::SetOptionString('queueLast', time()+30);
			if(empty($from)) {
				return;
			}
			$date = new \DateTime();
			$date->setTime($date->format('H'), $date->format('i'), 0);
			$date->modify('+1 minute');
			$bxdate = \Bitrix\Main\Type\DateTime::createFromPhp($date);
			$rsLock = $DB->Query('SELECT GET_LOCK("vettich_sp_publish", 0) as LL');
			$arLock = $rsLock->fetch();
			if($arLock && $arLock['LL'] != '1') {
				return false;
			}
			$rsPost = dbPost::getList(array(
				'filter' => array(
					'IS_ENABLE' => 'Y',
					'IS_MANUALLY' => 'N',
					array(
						'LOGIC' => 'OR',
						array(
							'IS_INTERVAL' => 'Y',
							'<NEXT_PUBLISH_AT' => $bxdate,
						),
						array(
							'IS_INTERVAL' => 'N',
							'!DATE' => '',
						),
					),
				),
			));
			$arPostIDs = array();
			$arPosts = array();
			// $arIntervalPosts = array();
			$arIntervalPostIDs = array();
			while($arPost = $rsPost->fetch()) {
				if($arPost['IS_INTERVAL'] == 'Y') {
					// $arIntervalPosts[$arPost['ID']] = $arPost;
					$arIntervalPostIDs[] = $arPost['ID'];
				} else {
					$arPostIDs[] = $arPost['ID'];
				}
				$arPosts[$arPost['ID']] = $arPost;
			}
			$postedIDs = array();
			// публикация элементов с заданным интервалом
			if(!empty($arIntervalPostIDs)) {
				$arFilter = array(
					'LOGIC' => 'OR',
					array(
						'STATUS' => 'PUBLISH',
						'RESULT' => 'READY',
						array(
							'LOGIC' => 'OR',
							array(
								'TYPE' => 'IBLOCK',
								'POST_ID' => $arIntervalPostIDs,
							),
							array('TYPE' => 'MIX'),
						),
					),
				);
				if(self::GetOptionString('is_fix_errors', 'N') == 'Y') {
					$arFilter[] = array(
						'STATUS' => 'PUBLISH',
						array(
							'LOGIC' => 'OR',
							array('RESULT' => 'ERROR'),
							array('RESULT' => 'WARNING'),
						),
					);
					$arFilter[] = array(
						'STATUS' => 'UPDATE',
						array(
							'LOGIC' => 'OR',
							array('RESULT' => 'READY'),
							array('RESULT' => 'ERROR'),
							array('RESULT' => 'WARNING'),
						),
					);
					$arFilter[] = array(
						'STATUS' => 'DELETE',
						array(
							'LOGIC' => 'OR',
							array('RESULT' => 'READY'),
							array('RESULT' => 'ERROR'),
							array('RESULT' => 'WARNING'),
						),
					);
				} else {
					$arFilter = array(
						'STATUS' => 'UPDATE',
						array(
							'LOGIC' => 'OR',
							array('RESULT' => 'READY'),
						),
					);
					$arFilter = array(
						'STATUS' => 'DELETE',
						array(
							'LOGIC' => 'OR',
							array('RESULT' => 'READY'),
						),
					);
				}
				$rs = db::getList(array(
					'filter' => $arFilter,
				));
				$isPublishToPost = array();
				while($ar = $rs->fetch()) {
					$params = array();
					if($ar['RESULT'] == 'WARNING' or $ar['RESULT'] == 'ERROR') {
						$params['mode'][] = 'only error';
					}
					if(!in_array($ar['POST_ID'], $isPublishToPost[$ar['RESULT']])) {
						$isPublishToPost[$ar['RESULT']][] = $ar['POST_ID'];
						if(in_array($ar['RESULT'], array('READY', 'SUCCESS'))) {
							$postedIDs[$ar['POST_ID']] = true;
						}
					} else {
						continue;
					}
					if($ar['TYPE'] == 'IBLOCK') {
						$params['arPost'] = $arPosts[$ar['POST_ID']];
						self::run($ar, $params);
					}
				}
				unset($params);
				unset($isPublishToPost);
			}
			// публикация элементов по полю даты активности
			if(!empty($arPostIDs)) {
				$rs = db::getList(array(
					'filter' => array(
						'POST_ID' => $arPostIDs,
						'STATUS' => 'PUBLISH',
						'RESULT' => 'READY',
					),
				));
				$arQueuePostIDs = array();
				$arQueuePost = array();
				while($ar = $rs->fetch()) {
					if(in_array($ar['ELEMENT_ID'], $arQueuePostIDs[$ar['POST_ID']])) {
						continue;
					}
					$arQueuePostIDs[$ar['POST_ID']][] = $ar['ELEMENT_ID'];
					$arQueuePost[$ar['POST_ID']][$ar['ELEMENT_ID']] = $ar;
				}
				foreach((array)$arQueuePost as $postID => $queue) {
					$rs = \CIBlockElement::GetList(array(), array(
						'ID' => $arQueuePostIDs[$postID],
						'<'.$arPosts[$postID]['DATE'] => $bxdate,
					));
					while($arElem = $rs->GetNext()) {
						$ar = $queue[$arElem['ID']];
						if(empty($ar)) {
							continue;
						}
						$params = array();
						if($ar['RESULT'] == 'WARNING' or $ar['RESULT'] == 'ERROR') {
							$params['mode'][] = 'only error';
						}
						if(!in_array($ar['POST_ID'], $isPublishToPost[$ar['RESULT']])) {
							$isPublishToPost[$ar['RESULT']][] = $ar['POST_ID'];
							if(in_array($ar['RESULT'], array('READY', 'SUCCESS'))) {
								$postedIDs[$ar['POST_ID']] = true;
							}
						} else {
							continue;
						}
						$params['arPost'] = $arPosts[$ar['POST_ID']];
						self::run($ar, $params);
					}
				}
				unset($params);
				unset($isPublishToPost);
				unset($arQueuePost);
				unset($arQueuePostIDs);
				unset($arElem);
			}
			// выгрузка элементов
			$filter = array(
				'IS_ENABLE' => 'Y',
				'IS_MANUALLY' => 'N',
				'!ID' => array_keys($postedIDs),
				array(
					'LOGIC' => 'OR',
					array(
						'<NEXT_PUBLISH_AT' => $bxdate,
						'IS_INTERVAL' => 'Y',
					),
					array(
						'!DATE' => '',
						'IS_INTERVAL' => 'N',
					),
				),
			);
			$rsPost = dbPost::getList(array(
				'filter' => $filter,
			));
			\CModule::IncludeModule('iblock');
			while($arPost = $rsPost->fetch()) {
				if($arPost['PUBLISH']['UNLOAD']['ENABLE'] != 'Y') {
					continue;
				}
				$arFilter = array(
					'IBLOCK_TYPE' => $arPost['IBLOCK_TYPE'],
					'IBLOCK_ID' => $arPost['IBLOCK_ID'],
				);
				$arSort = array();
				$arSelect = array('ID', 'IBLOCK_ID', 'IBLOCK_TYPE', 'NAME', 'IBLOCK_SECTION_ID');
				/*if($arPost['QUEUE_DUPLICATE'] == 'Y')*/ {
					// исключаем дубликаты
					$rsQueue = db::getList(array(
						'filter' => array(
							'POST_ID' => $arPost['ID'],
						),
						'order' => array('ID' => 'desc'),
						'group' => array('ELEMENT_ID'),
					));
					$arQueueElemIDs = array();
					while($arQueue = $rsQueue->fetch()) {
						if(in_array($arQueue['ELEMENT_ID'], $arQueueElemIDs)) {
							continue;
						}
						$arQueueElemIDs[] = $arQueue['ELEMENT_ID'];
					}
					if(!empty($arQueueElemIDs)) {
						$arFilter['!ID'] = array_keys(array_flip($arQueueElemIDs));
					}
				}
				if($arPost['IS_INTERVAL'] != 'Y') {
					$arFilter['<'.$arPost['DATE']] = $bxdate;
				}
				if($arPost['PUBLISH']['CONDITIONS']['ACTIVE'] == 'Y') {
					$arFilter['ACTIVE'] = 'Y';
				}
				if($arPost['CONDITIONS']) foreach((array)$arPost['CONDITIONS'] as $arCondition) {
					switch($arCondition['cmp']) {
						case '==':
						case 'include':
							$arFilter[$arCondition['field']] = $arCondition['value'];
							break;
						case '!=':
						case 'notinclude':
							$arFilter['!'.$arCondition['field']] = $arCondition['value'];
							break;
						default:
							$arFilter[$arCondition['cmp'].$arCondition['field']] = $arCondition['value'];
					}
				}
				if($arPost['IS_SECTIONS'] == 'Y' && !empty($arPost['IBLOCK_SECTIONS'])) {
					if($arPost['PUBLISH']['UNLOAD']['SECTIONS'] == 'RANDOM') {
						// $arFilter['SECTION_ID'] = array_rand(array_flip($arPost['IBLOCK_SECTIONS']));
						$arFilter['SECTION_ID'] = $arPost['IBLOCK_SECTIONS'];
						$arFilter['INCLUDE_SUBSECTIONS'] = 'Y';
						$arSort['RAND'] = 'ASC';
					} elseif($arPost['PUBLISH']['UNLOAD']['SECTIONS'] == 'CONSISTENTLY') {
						$lastSectionID = static::GetOptionString('LAST_SECTION_ID_POST_'.$arPost['ID'], current($arPost['IBLOCK_SECTIONS']));
						$sectionFound = false;
						foreach((array)$arPost['IBLOCK_SECTIONS'] as $sectionID) {
							if($sectionFound) {
								$sectionFound = 2;
								break;
							}
							if($sectionID == $lastSectionID) {
								$sectionFound = true;
							}
						}
						if($sectionFound != 2) {
							reset($arPost['IBLOCK_SECTIONS']);
							$sectionID = current($arPost['IBLOCK_SECTIONS']);
						}
						static::SetOptionString('LAST_SECTION_ID_POST_'.$arPost['ID'], $sectionID);
						$arFilter['SECTION_ID'] = $sectionID;
						$arFilter['INCLUDE_SUBSECTIONS'] = 'Y';
					}
				}
				$rsElem = \CIBlockElement::GetList(
					$arSort,
					$arFilter,
					false,
					array('nTopCount' => 1),
					$arSelect
				);
				if($arElem = $rsElem->GetNext()) {
					$params['arPost'] = $arPost;
					$arPending = self::addElem($arElem, $params);
					if(!empty($arPending)) {
						self::run($arPending[0], $params);
					}
				} elseif($arPost['QUEUE_DUPLICATE'] != 'Y' && isset($arFilter['!ID'])) {
					/**
					 * @todo поправить публикацию дубликатов
					 */
					unset($arFilter['!ID']);
					$rsElem = \CIBlockElement::GetList(
						array('rand' => 'asc'),
						$arFilter,
						false,
						array('nTopCount' => 1),
						$arSelect
					);
					if($arElem = $rsElem->GetNext()) {
						$params['arPost'] = $arPost;
						$arPending = self::addElem($arElem, $params);
						if(!empty($arPending)) {
							self::run($arPending[0], $params);
						}
					}
				}
			}
		} catch(\Exception $e) {
			devdebug(array($e->getCode(), $e->getMessage()), 'error.sp.pending.checkPublish');
		}
		$DB->Query('SELECT RELEASE_LOCK("vettich_sp_publish")');
		return true;
	}

	public static function checkPublish($from='hit')
	{
		if($from != self::GetOptionString('method_post', 'hit')
			or !\CModule::IncludeModule('iblock')) {
			return false;
		}
		global $DB;
		try {
			if(self::GetOptionString('is_enable', 'Y') != 'Y') {
				return;
			}
			if(self::GetOptionString('queueLast', 0) >= time()) {
				return;
			}
			self::SetOptionString('queueLast', time()+30);
			if(empty($from)) {
				return;
			}

			$dateNow = new \DateTime();
			$dateNow->setTime($dateNow->format('H'), $dateNow->format('i'), 0);
			$dateNow->modify('+1 minute');
			$bxdateNow = \Bitrix\Main\Type\DateTime::createFromPhp($dateNow);

			$rsLock = $DB->Query('SELECT GET_LOCK("vettich_sp_publish", 0) as LL');
			$arLock = $rsLock->fetch();
			if($arLock && $arLock['LL'] != '1') {
				return false;
			}

			$rsPost = dbPost::getList(array(
				'filter' => array(
					'IS_ENABLE' => 'Y',
					'IS_MANUALLY' => 'N',
					array(
						'LOGIC' => 'OR',
						array(
							'IS_INTERVAL' => 'Y',
							'<NEXT_PUBLISH_AT' => $bxdateNow,
						),
						array(
							'IS_INTERVAL' => 'N',
							'!DATE' => '',
						),
					),
				),
			));
			$arPosts = array();
			while($arPost = $rsPost->fetch()) {
				$arPosts[$arPost['ID']] = $arPost;
				$isPublished = false;

				if($arPost['IS_INTERVAL'] == 'Y') { // публикация элементов с заданным интервалом

					$arFilter = array(
						'LOGIC' => 'OR',
						array(
							'STATUS' => 'PUBLISH',
							'RESULT' => 'READY',
							'TYPE' => 'IBLOCK',
							'POST_ID' => $arPosts['ID'],
						),
					);
					if(self::GetOptionString('is_fix_errors', 'N') == 'Y') {
						$arFilter[] = array(
							'STATUS' => 'PUBLISH',
							array(
								'LOGIC' => 'OR',
								array('RESULT' => 'ERROR'),
								array('RESULT' => 'WARNING'),
							),
						);
						$arFilter[] = array(
							'STATUS' => 'UPDATE',
							array(
								'LOGIC' => 'OR',
								array('RESULT' => 'READY'),
								array('RESULT' => 'ERROR'),
								array('RESULT' => 'WARNING'),
							),
						);
						$arFilter[] = array(
							'STATUS' => 'DELETE',
							array(
								'LOGIC' => 'OR',
								array('RESULT' => 'READY'),
								array('RESULT' => 'ERROR'),
								array('RESULT' => 'WARNING'),
							),
						);
					} else {
						$arFilter[] = array(
							'STATUS' => 'UPDATE',
							'RESULT' => 'READY',
						);
						$arFilter[] = array(
							'STATUS' => 'DELETE',
							'RESULT' => 'READY',
						);
					}
					$rs = db::getList(array(
						'filter' => array(
							'IS_ENABLE' => 'Y',
							$arFilter
						),
						'order' => array('ID' => 'DESC'),
						'limit' => 1,
					));
					$ar = array();
					if($ar = $rs->fetch()) {
						if($ar['RESULT'] == 'WARNING' or $ar['RESULT'] == 'ERROR') {
							$params['mode'][] = 'only error';
						}
						$params['arPost'] = $arPosts[$ar['POST_ID']];
						self::run($ar, $params);
						$isPublished = true;
					}

				} else { // публикация элементов по полю даты активности

					$rs = db::getList(array(
						'filter' => array(
							'IS_ENABLE' => 'Y',
							'POST_ID' => $arPost['ID'],
							'STATUS' => 'PUBLISH',
							'RESULT' => 'READY',
						),
					));
					$ars = array();
					$elemIds = array();
					while($ar = $rs->fetch()) {
						$ars[$ar['ELEMENT_ID']][] = $ar;
						$elemIds[] = $ar['ELEMENT_ID'];
					}

					$rsElems = \CIBlockElement::GetList(array(), array(
						'ID' => $elemIds,
						'<'.$arPost['DATE'] => $bxdateNow,
					));
					while($arElem = $rsElems->GetNext()) {
						foreach((array)$ars[$arElem['ID']] as $ar) {

							$params = array();
							if($ar['RESULT'] == 'WARNING' or $ar['RESULT'] == 'ERROR') {
								$params['mode'][] = 'only error';
							}
							$params['arPost'] = $arPost;
							self::run($ar, $params);
							$isPublished = true;
						}
					}
				}

				// автоматическая выгрузка элементов

				if($isPublished && $arPost['PUBLISH']['UNLOAD']['ENABLE'] != 'Y') {
					continue;
				}

				$arFilter = array(
					'IBLOCK_TYPE' => $arPost['IBLOCK_TYPE'],
					'IBLOCK_ID' => $arPost['IBLOCK_ID'],
				);
				$arSort = array();
				$arSelect = array('ID', 'IBLOCK_ID', 'IBLOCK_TYPE', 'NAME', 'IBLOCK_SECTION_ID');

				/*if($arPost['QUEUE_DUPLICATE'] == 'Y')*/ {
					// исключаем дубликаты
					$rsQueue = db::getList(array(
						'filter' => array(
							'IS_ENABLE' => 'Y',
							'POST_ID' => $arPost['ID'],
						),
						'order' => array('ID' => 'desc'),
						'group' => array('ELEMENT_ID'),
					));
					$arQueueElemIDs = array();
					while($arQueue = $rsQueue->fetch()) {
						if(in_array($arQueue['ELEMENT_ID'], $arQueueElemIDs)) {
							continue;
						}
						$arQueueElemIDs[] = $arQueue['ELEMENT_ID'];
					}
					if(!empty($arQueueElemIDs)) {
						$arFilter['!ID'] = array_keys(array_flip($arQueueElemIDs));
					}
				}
				if($arPost['IS_INTERVAL'] != 'Y') {
					$arFilter['<'.$arPost['DATE']] = $bxdate;
				}
				if($arPost['PUBLISH']['CONDITIONS']['ACTIVE'] == 'Y') {
					$arFilter['ACTIVE'] = 'Y';
				}
				if($arPost['CONDITIONS']) foreach($arPost['CONDITIONS'] as $arCondition) {
					switch($arCondition['cmp']) {
						case '==':
						case 'include':
							$arFilter[$arCondition['field']] = $arCondition['value'];
							break;
						case '!=':
						case 'notinclude':
							$arFilter['!'.$arCondition['field']] = $arCondition['value'];
							break;
						default:
							$arFilter[$arCondition['cmp'].$arCondition['field']] = $arCondition['value'];
					}
				}
				if($arPost['IS_SECTIONS'] == 'Y' && !empty($arPost['IBLOCK_SECTIONS'])) {
					if($arPost['PUBLISH']['UNLOAD']['SECTIONS'] == 'RANDOM') {
						// $arFilter['SECTION_ID'] = array_rand(array_flip($arPost['IBLOCK_SECTIONS']));
						$arFilter['SECTION_ID'] = $arPost['IBLOCK_SECTIONS'];
						$arFilter['INCLUDE_SUBSECTIONS'] = 'Y';
						$arSort['RAND'] = 'ASC';
					} elseif($arPost['PUBLISH']['UNLOAD']['SECTIONS'] == 'CONSISTENTLY') {
						$lastSectionID = static::GetOptionString('LAST_SECTION_ID_POST_'.$arPost['ID'], current($arPost['IBLOCK_SECTIONS']));
						$sectionFound = false;
						foreach($arPost['IBLOCK_SECTIONS'] as $sectionID) {
							if($sectionFound) {
								$sectionFound = 2;
								break;
							}
							if($sectionID == $lastSectionID) {
								$sectionFound = true;
							}
						}
						if($sectionFound != 2) {
							reset($arPost['IBLOCK_SECTIONS']);
							$sectionID = current($arPost['IBLOCK_SECTIONS']);
						}
						static::SetOptionString('LAST_SECTION_ID_POST_'.$arPost['ID'], $sectionID);
						$arFilter['SECTION_ID'] = $sectionID;
						$arFilter['INCLUDE_SUBSECTIONS'] = 'Y';
					}
				}
				$rsElem = \CIBlockElement::GetList(
					$arSort,
					$arFilter,
					false,
					array('nTopCount' => 1),
					$arSelect
				);
				if($arElem = $rsElem->GetNext()) {
					$params['arPost'] = $arPost;
					$arPending = self::addElem($arElem, $params);
					if(!empty($arPending)) {
						self::run($arPending[0], $params);
					}
				} elseif($arPost['QUEUE_DUPLICATE'] != 'Y' && isset($arFilter['!ID'])) {
					/**
					 * @todo поправить публикацию дубликатов
					 */
					unset($arFilter['!ID']);
					$rsElem = \CIBlockElement::GetList(
						array('rand' => 'asc'),
						$arFilter,
						false,
						array('nTopCount' => 1),
						$arSelect
					);
					if($arElem = $rsElem->GetNext()) {
						$params['arPost'] = $arPost;
						$arPending = self::addElem($arElem, $params);
						if(!empty($arPending)) {
							self::run($arPending[0], $params);
						}
					}
				}
			}

			// публикация элементов типа MIX

			$resultAct = array('RESULT' => 'READY');
			if(self::GetOptionString('is_fix_errors', 'N') == 'Y') {
				$resultAct = array(
					'LOGIC' => 'OR',
					array('RESULT' => 'READY'),
					array('RESULT' => 'ERROR'),
					array('RESULT' => 'WARNING'),
				);
			}
			$arFilter = array(
				'TYPE' => 'MIX',
				'IS_ENABLE' => 'Y',
				array(
					'LOGIC' => 'OR',
					array(
						'<PUBLISH_AT' => $bxdateNow,
						'STATUS' => 'PUBLISH',
						'RESULT' => 'READY',
					),
					array(
						'STATUS' => 'UPDATE',
						$resultAct,
					),
					array(
						'STATUS' => 'DELETE',
						$resultAct,
					),
				),
			);
			if(self::GetOptionString('is_fix_errors', 'N') == 'Y') {
				$arFilter[0][] = array(
					'STATUS' => 'PUBLISH',
					array(
						'LOGIC' => 'OR',
						array('RESULT' => 'ERROR'),
						array('RESULT' => 'WARNING'),
					),
				);
			}
			$rs = db::getList(array(
				'filter' => $arFilter,
			));
			while($ar = $rs->fetch()) {
				if($ar['RESULT'] == 'WARNING' or $ar['RESULT'] == 'ERROR') {
					$params['mode'][] = 'only error';
				}
				self::run($ar, $params);
			}
		} catch(\Exception $e) {
			devdebug(array($e->getCode(), $e->getMessage()), 'error.sp.pending.checkPublish');
		}
		$DB->Query('SELECT RELEASE_LOCK("vettich_sp_publish")');
		return true;
	}

	/**
	 * @param array $params
	 */
	public function set($params)
	{
		if(isset($params['ID'])) {
			$ID = $params['ID'];
			unset($params['ID']);
			try {
				$rs = db::update($ID, $params);
			} catch (\Exception $e) {
				devdebug(array($e->getCode(), $e->getMessage()), 'error.sp.pending.set');
			}
		} else {
			try {
				$rs = db::add($params);
			} catch (\Exception $e) {
				devdebug(array($e->getCode(), $e->getMessage()), 'error.sp.pending.set');
			}
		}
		if(empty($rs)) {
			return false;
		}
		return $rs->getId();
	}

	/**
	 * @param  int|string $id
	 * @return boolean
	 */
	public function remove($id)
	{
		return db::delete($id)->isSuccess();
	}

	public static function inSections($arFields, $arPost)
	{
		$isFound = true;
		if($arPost['IS_SECTIONS'] == 'Y'
			&& !empty($arPost['IBLOCK_SECTIONS'])) {
			$isFound = false;
			$rsSect = \CIBlockSection::GetNavChain(
				IntVal($arFields['IBLOCK_ID']),
				IntVal($arFields['IBLOCK_SECTION_ID']),
				array('ID')
			);
			while($arSect = $rsSect->GetNext()) {
				if(in_array($arSect['ID'], $arPost['IBLOCK_SECTIONS'])) {
					$isFound = true;
					break;
				}
			}
		}
		return $isFound;
	}

	public static function updateNextPublishAt($arPost)
	{
		if($arPost['ID'] > 0 && $arPost['QUEUE_COMMON'] != 'Y') {
			dbPost::updateNextPublishAt($arPost);
			return;
		}
		if($arPost['QUEUE_COMMON'] == 'Y') {
			/**
			 * @todo доделать единую очередь
			 */
		}
	}
}
