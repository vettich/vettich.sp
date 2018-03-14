<?
namespace Vettich\SP\db;
use Bitrix\Main\Entity;

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
			new Entity\TextField('TYPE'),
			new Entity\TextField('NAME'),
			new Entity\TextField('IS_ENABLE'),
			new Entity\TextField('DATA', array('serialized' => true, 'default_value' => '')),
		);
	}
}
