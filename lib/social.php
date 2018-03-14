<?
namespace Vettich\SP;
IncludeModuleLangFile(__FILE__);

class Social extends Post
{

	public static $socialid = '';

	public static function OnGetSocial()
	{
		if(!static::isSupport()) {
			return false;
		}
		$cl = get_called_class();
		return array(
			'id' => static::$socialid,
			'name' => static::name(static::$socialid),
			'class' => new $cl,
		);
	}

	public static function name($name='')
	{
		if(!empty($name)) {
			return self::mess('#VETTICH_SP_POST_NAME_'.$name.'#');
		}
		return self::mess('#VETTICH_SP_POST_NAME#');
	}

	public static function socialClass()
	{
		return 'vettich\sp\db\postAccountTable';
	}
	public static function accountsDB()
	{
		return 'vettich\sp\db\postAccountTable';
	}

	private static $_data = array();
	public static function data()
	{
		if(empty(self::$_data[static::$socialid])) {
			self::$_data[static::$socialid] = new \vettich\devform\data\orm(array(
				'dbClass' => self::socialClass(),
				'filter' => array('TYPE' => static::$socialid),
			));
		}
		return self::$_data[static::$socialid];
	}

	/**
	 * is social supporting
	 * @return boolean if true - support
	 */
	public function isSupport()
	{
		return true;
	}

	/**
	 * if don't support, social can to say why don't support
	 * @return string
	 */
	public static function whyDontSupport()
	{
		return 'I am supporting...';
	}

	/**
	 * called if post to publishing
	 * @param  int $accountId   account ID in DB
	 * @param  array $arPost    post settings
	 * @param  array $arFields  iblock element fields
	 * @return boolean|array    if true - all right, 
	 *                             else must will return array which be saved in DB
	 *                             for next use functions [update, delete]
	 */
	public static function publish($accountId, $arPost, $arFields)
	{
		return false;
	}

	/**
	 * called if post to updating
	 * @param  array $data       array of saved fields by function publish
	 * @param  int $accountId    account ID in DB
	 * @param  array $arPost     post settings
	 * @param  array $arFields   iblock element fields
	 * @return boolean|array     must will return of result fields updated [like published] post in social
	 */
	public static function update($data, $accountId, $arPost, $arFields)
	{
		return false;
	}

	/**
	 * called if post to deleting
	 * @param  array $data       array of saved fields by function publish or update
	 * @param  int $accountId    account ID in DB
	 * @param  array $arPost     post settings
	 * @return boolean|array     must will return of result fields deleted [like published] post in social
	 */
	public static function delete($data, $accountId, $arPost)
	{
		return false;
	}

	/**
	 * called if showing list of accounts by the social
	 * this function must be render admin list
	 */
	public static function adminList()
	{
		echo "Admin List is not support.";
	}

	/**
	 * called if showing settings of account by the social
	 * if $id is 0 then account adding
	 * else the account will be edited with an $id
	 * @param  integer $id account ID in DB
	 */
	public static function adminForm($id=0)
	{
		echo "Admin Form is not support.";
	}

	/**
	 * return array social params for show on post edit page
	 * @param  int $iblock_id    iblock ID
	 * @param  string $prefix    prefix to add before params name
	 * @return array
	 */
	public function publishParams($iblock_id, $prefix='')
	{
		return array();
	}

	/**
	 * will have to return link to published post in social
	 * @param  array $data    array of saved fields by function publish, update or delete
	 * @param  array $account settings of account by social
	 * @return array          something data, example link to published post in social
	 */
	public function viewData($data, $account) {
		return null;
	}
}
