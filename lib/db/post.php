<?php
namespace Vettich\SP\db;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
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
		$arMap = [
			new Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true
			]),
			(new Entity\StringField('NAME')),
			new Entity\BooleanField('IS_ENABLE', ['values'=>['N', 'Y'], 'default_value' => 'Y']),
			new Entity\StringField('IBLOCK_TYPE', ['default_value' => '']),
			new Entity\StringField('IBLOCK_ID', ['default_value' => '']),
			new Entity\BooleanField('IS_SECTIONS', ['values'=>['N', 'Y'], 'default_value' => 'N']),
			(new ArrayField('IBLOCK_SECTIONS', ['default_value' => []]))
				->configureSerializationPhp()
				->addValidator(new LengthValidator(0, 2000)),
			new Entity\StringField('PROTOCOL', ['default_value' => '']),
			new Entity\StringField('DOMAIN', ['default_value' => '']),
			new Entity\TextField('URL_PARAMS', ['default_value' => '']),
			(new ArrayField('CONDITIONS', ['default_value' => []]))
				->configureSerializationPhp()
				->addValidator(new LengthValidator(0, 2000)),
			(new ArrayField('ACCOUNTS', ['default_value' => []]))
				->configureSerializationPhp()
				->addValidator(new LengthValidator(0, 2000)),
			(new ArrayField('PUBLISH', ['default_value' => []]))
				->configureSerializationPhp()
				->addValidator(new LengthValidator(0, 2000)),

			new Entity\BooleanField('IS_MANUALLY', ['values'=>['N', 'Y'], 'default_value' => 'N']),
			new Entity\BooleanField('IS_INTERVAL', ['values'=>['N', 'Y'], 'default_value' => 'Y']),
			new Entity\StringField('INTERVAL', ['default_value' => '']),
			new Entity\StringField('DATE', ['default_value' => '']),
			new Entity\BooleanField('IS_PERIOD', ['values'=>['N', 'Y'], 'default_value' => 'N']),
			new Entity\StringField('PERIOD_FROM', ['default_value' => '']),
			new Entity\StringField('PERIOD_TO', ['default_value' => '']),
			// EVERY values => {DAY, WEEK, MONTH}
			new Entity\StringField('EVERY', ['default_value' => '']),
			(new ArrayField('WEEK', ['default_value' => []]))
				->configureSerializationPhp()
				->addValidator(new LengthValidator(0, 2000)),
			(new ArrayField('MONTH', ['default_value' => []]))
				->configureSerializationPhp()
				->addValidator(new LengthValidator(0, 2000)),

			// QUEUE_MODE values => {CONSISTENTLY, RANDOM, SORT}
			new Entity\StringField('QUEUE_MODE', ['default_value' => 'CONSISTENTLY']),
			// QUEUE_SORT values => {<iblock element fields = ID, NAME, ...>}
			new Entity\StringField('QUEUE_SORT', ['default_value' => 'ID']),
			// QUEUE_SORT_DIR values => {ASC, DESC}
			new Entity\StringField('QUEUE_SORT_DIR', ['default_value' => 'ASC']),
			new Entity\BooleanField('QUEUE_ELEMENT_UPDATE', ['values'=>['N', 'Y'], 'default_value' => 'Y']),
			new Entity\BooleanField('QUEUE_ELEMENT_DELETE', ['values'=>['N', 'Y'], 'default_value' => 'Y']),
			new Entity\BooleanField('QUEUE_DUPLICATE', ['values'=>['N', 'Y'], 'default_value' => 'N']),
			new Entity\BooleanField('QUEUE_IS_COMMON', ['values'=>['N', 'Y'], 'default_value' => 'N']),

			new Entity\DatetimeField('NEXT_PUBLISH_AT', [
				'default_value' => Type\DateTime::createFromPhp(new \DateTime()),
			]),
			new Entity\DatetimeField('UPDATED_AT', [
				'default_value' => Type\DateTime::createFromPhp(new \DateTime()),
			]),
			new Entity\DatetimeField('CREATED_AT', [
				'default_value' => Type\DateTime::createFromPhp(new \DateTime()),
			]),
		];
		return $arMap;
	}

	public static function OnBeforeAdd(Entity\Event $event)
	{
		$data = $event->getParameter('fields');
		$result = new Entity\EventResult;
		$modFields = [
			'UPDATED_AT' => new Type\DateTime(),
			'CONDITIONS' => Module::cleanConditions($data['CONDITIONS']),
		];
		if ($data['PUBLISH']) {
			foreach ((array)$data['PUBLISH'] as $key => $value) {
				if (isset($value['CONDITIONS'])) {
					$modFields['PUBLISH'][$key]['CONDITIONS'] = Module::cleanConditions($value['CONDITIONS']);
				}
			}
		}
		$result->modifyFields($modFields);
		return $result;
	}

	public static function OnBeforeUpdate(Entity\Event $event)
	{
		$data = $event->getParameter('fields');
		$result = new Entity\EventResult;
		$modFields = [
			'UPDATED_AT' => new Type\DateTime(),
		];
		if (!empty($data['CONDITIONS'])) {
			$modFields['CONDITIONS'] = Module::cleanConditions($data['CONDITIONS']);
		}
		if (isset($data['PUBLISH']) && is_array($data['PUBLISH'])) {
			$modFields['PUBLISH'] = $data['PUBLISH'];
			foreach ((array)$data['PUBLISH'] as $key => $value) {
				if (isset($value['CONDITIONS'])) {
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
			if (is_array($id)) {
				$arPost = $id;
				$id = $arPost['ID'];
			} else {
				$arPost = self::getById($id)->fetch();
			}
			if (!$arPost) {
				return false;
			}
			if ($isReset) {
				unset($arPost['NEXT_PUBLISH_AT']);
			}
			$params = [
				'NEXT_PUBLISH_AT' => Module::nextPublishAt($arPost),
			];
			self::update($id, $params);
		} catch (\Exception $e) {
		}
		return true;
	}
}
