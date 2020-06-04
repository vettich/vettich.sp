<?php
namespace Vettich\SP\db;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

class pendingTable extends DBase
{
    const TYPE_SIMPLE = 'SIMPLE';
    const TYPE_IBLOCK = 'IBLOCK';
    const TYPE_MIX = 'MIX';

    const STATUS_PUBLISH = 'PUBLISH';
    const STATUS_UPDATE = 'UPDATE';
    const STATUS_DELETE = 'DELETE';

    const RESULT_READY = 'READY';
    const RESULT_RUNNING = 'RUNNING';
    const RESULT_SUCCESS = 'SUCCESS';
    const RESULT_ERROR = 'ERROR';
    const RESULT_WARNING = 'WARNING';
    const RESULT_ERROR_POST = 'ERROR_POST';

    public static function getTableName()
    {
        return 'vettich_sp_pending';
    }

    public static function getMap()
    {
        $arMap = array(
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),
            new Entity\StringField('NAME', ['default_value' => '']),
            new Entity\BooleanField('IS_ENABLE', array('values'=>array('N', 'Y'), 'default_value' => 'Y')),
            /** @todo реализовать тип simple */
            new Entity\StringField('TYPE', array('default_value' => self::TYPE_IBLOCK)),
            // status values => {PUBLISH, UPDATE, DELETE}
            new Entity\StringField('STATUS', array('default_value' => self::STATUS_PUBLISH)),
            // status values => {READY, RUNNING, SUCCESS, ERROR, WARNING, ERROR_POST}
            new Entity\StringField('RESULT', array('default_value' => self::RESULT_READY)),
            new Entity\StringField('POST_ID', array('default_value' => '')),
            new Entity\StringField('IBLOCK_TYPE', array('default_value' => '')),
            new Entity\StringField('IBLOCK_ID', array('default_value' => '')),
            new Entity\StringField('ELEMENT_ID', array('default_value' => '')),
            (new ArrayField('ACCOUNTS', ['default_value' => []]))->configureSerializationPhp()->addValidator(new LengthValidator(0, 2000)),

            new Entity\StringField('PROTOCOL', array('default_value' => '')),
            new Entity\TextField('DOMAIN', array('default_value' => '')),
            new Entity\TextField('URL_PARAMS', array('default_value' => '')),
            (new ArrayField('SOCIALS', ['default_value' => []]))->configureSerializationPhp()->addValidator(new LengthValidator(0, 2000)),
            (new ArrayField('PUBLISH', ['default_value' => []]))->configureSerializationPhp()->addValidator(new LengthValidator(0, 2000)),
            new Entity\StringField('UPDATE_ELEM', array('default_value' => '')),
            new Entity\StringField('DELETE_ELEM', array('default_value' => '')),

            new Entity\DatetimeField('PUBLISH_AT', array(
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
        $result->modifyFields(array(
            'UPDATED_AT' => new Type\DateTime(),
        ));
        return $result;
    }

    public static function OnBeforeUpdate(Entity\Event $event)
    {
        $data = $event->getParameter('fields');
        $result = new Entity\EventResult;
        $result->modifyFields(array(
            'UPDATED_AT' => new Type\DateTime(),
        ));
        return $result;
    }
}
