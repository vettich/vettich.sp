<?
namespace Vettich\SP\db;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use Vettich\SP\Module;

class postTable extends DBase
{
	public static function getTableName()
	{
		return 'vettich_sp_posts';
	}

	public static function getMap()
	{
		$arMap = array(
			new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			new Entity\StringField('NAME'),
			new Entity\BooleanField('IS_ENABLE', array('values'=>array('N', 'Y'), 'default_value' => 'Y')),
			new Entity\StringField('IBLOCK_TYPE', array('default_value' => '')),
			new Entity\StringField('IBLOCK_ID', array('default_value' => '')),
			new Entity\BooleanField('IS_SECTIONS', array('values'=>array('N', 'Y'), 'default_value' => 'N')),
			new Entity\TextField('IBLOCK_SECTIONS', array('serialized'=>true, 'default_value' => '')),
			new Entity\StringField('PROTOCOL', array('default_value' => '')),
			new Entity\StringField('DOMAIN', array('default_value' => '')),
			new Entity\TextField('URL_PARAMS', array('default_value' => '')),
			new Entity\TextField('CONDITIONS', array('serialized'=>true, 'default_value' => '')),
			new Entity\TextField('ACCOUNTS', array('serialized'=>true, 'default_value' => '')),
			new Entity\TextField('PUBLISH', array('serialized'=>true, 'default_value' => '')),

			new Entity\BooleanField('IS_MANUALLY', array('values'=>array('N', 'Y'), 'default_value' => 'N')),
			new Entity\BooleanField('IS_INTERVAL', array('values'=>array('N', 'Y'), 'default_value' => 'Y')),
			new Entity\StringField('INTERVAL', array('default_value' => '')),
			new Entity\StringField('DATE', array('default_value' => '')),
			new Entity\BooleanField('IS_PERIOD', array('values'=>array('N', 'Y'), 'default_value' => 'N')),
			new Entity\StringField('PERIOD_FROM', array('default_value' => '')),
			new Entity\StringField('PERIOD_TO', array('default_value' => '')),
			// EVERY values => {DAY, WEEK, MONTH}
			new Entity\StringField('EVERY', array('default_value' => '')),
			new Entity\TextField('WEEK', array('serialized'=>true, 'default_value' => '')),
			new Entity\TextField('MONTH', array('serialized'=>true, 'default_value' => '')),

			// QUEUE_MODE values => {CONSISTENTLY, RANDOM, SORT}
			new Entity\StringField('QUEUE_MODE', array('default_value' => 'CONSISTENTLY')),
			// QUEUE_SORT values => {<iblock element fields = ID, NAME, ...>}
			new Entity\StringField('QUEUE_SORT', array('default_value' => 'ID')),
			// QUEUE_SORT_DIR values => {ASC, DESC}
			new Entity\StringField('QUEUE_SORT_DIR', array('default_value' => 'ASC')),
			new Entity\BooleanField('QUEUE_ELEMENT_UPDATE', array('values'=>array('N', 'Y'), 'default_value' => 'Y')),
			new Entity\BooleanField('QUEUE_ELEMENT_DELETE', array('values'=>array('N', 'Y'), 'default_value' => 'Y')),
			new Entity\BooleanField('QUEUE_DUPLICATE', array('values'=>array('N', 'Y'), 'default_value' => 'N')),
			new Entity\BooleanField('QUEUE_IS_COMMON', array('values'=>array('N', 'Y'), 'default_value' => 'N')),

			new Entity\DatetimeField('NEXT_PUBLISH_AT', array(
				'default_value' => Type\DateTime::createFromPhp(new \DateTime()),
			)),
			new Entity\DatetimeField('UPDATED_AT', array(
				'default_value' => Type\DateTime::createFromPhp(new \DateTime()),
			)),
			new Entity\DatetimeField('CREATED_AT', array(
				'default_value' => Type\DateTime::createFromPhp(new \DateTime()),
			)),
		);
		return $arMap;
	}

	public static function OnBeforeAdd(Entity\Event $event)
	{
		$data = $event->getParameter('fields');
		$result = new Entity\EventResult;
		$modFields = array(
			'UPDATED_AT' => new Type\DateTime(),
			'CONDITIONS' => Module::cleanConditions($data['CONDITIONS']),
		);
		if($data['PUBLISH']) foreach((array)$data['PUBLISH'] as $key => $value) {
			if(isset($value['CONDITIONS'])) {
				$modFields['PUBLISH'][$key]['CONDITIONS'] = Module::cleanConditions($value['CONDITIONS']);
			}
		}
		$result->modifyFields($modFields);
		return $result;
	}

	public static function OnBeforeUpdate(Entity\Event $event)
	{
		$data = $event->getParameter('fields');
		$result = new Entity\EventResult;
		$modFields = array(
			'UPDATED_AT' => new Type\DateTime(),
		);
		if(!empty($data['CONDITIONS'])) {
			$modFields['CONDITIONS'] = Module::cleanConditions($data['CONDITIONS']);
		}
		if(isset($data['PUBLISH']) && is_array($data['PUBLISH'])) {
			$modFields['PUBLISH'] = $data['PUBLISH'];
			foreach((array)$data['PUBLISH'] as $key => $value) {
				if(isset($value['CONDITIONS'])) {
					$modFields['PUBLISH'][$key]['CONDITIONS'] = Module::cleanConditions($value['CONDITIONS']);
				}
			}
		}
		$result->modifyFields($modFields);
		return $result;
	}

	public static function updateNextPublishAt($id, $isReset=false)
	{
		try {
			if(is_array($id)) {
				$arPost = $id;
				$id = $arPost['ID'];
			} else {
				$arPost = self::getById($id)->fetch();
			}
			if(!$arPost) {
				return false;
			}
			if($isReset) {
				unset($arPost['NEXT_PUBLISH_AT']);
			}
			$params = array(
				'NEXT_PUBLISH_AT' => Module::nextPublishAt($arPost),
			);
			self::update($id, $params);
		} catch(\Exception $e) {}
		return true;
	}
}
