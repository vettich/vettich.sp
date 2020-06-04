<?php
namespace Vettich\SP\db;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

class postAccountTable extends DBase
{
    public static function getTableName()
    {
        return 'vettich_sp_post_account';
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),
            new Entity\TextField('TYPE', ['default_value' => '']),
            new Entity\TextField('NAME', ['default_value' => '']),
            new Entity\TextField('IS_ENABLE', ['default_value' => '']),
            (new ArrayField('DATA', ['default_value' => []]))->configureSerializationPhp()->addValidator(new LengthValidator(0, 2000)),
        );
    }
}
