<?
namespace Vettich\SP\Socials\twitter;
IncludeModuleLangFile(__FILE__);

use vettich\devform\AdminList;
use vettich\devform\AdminForm;

class Social extends \Vettich\SP\Social
{
	/**
	 * идентификатор соц. сети
	 * @var string
	 */
	static $socialid = 'twitter';

	/**
	 * загружает api, если еще не было загружено
	 */
	static function autoloadApi() {
		static $is = false;
		if(!$is) {
			$is = true;
			require VETTICH_SP_DIR.'/classes/Twitter/autoload.php';
		}
	}

	/**
	 * отображает список аккаунтов соц. сети
	 */
	public static function adminList()
	{
		(new AdminList(self::name(self::$socialid), 'sTable'.self::$socialid, array(
			'data' => array(
				'class' => 'orm',
				'dbClass' => self::socialClass(),
				'filter' => array(
					'TYPE' => self::$socialid,
				),
			),
			'params' => array(
				'ID' => 'text',
				'NAME' => 'textlink:#VDF_NAME#',
				'IS_ENABLE' => 'checkbox:#VDF_IS_ENABLE#',
				'DATA[API_KEY]' => 'text:#VETTICH_SP_TWITTER_API_KEY#',
				'DATA[API_SECRET]' => 'text:#VETTICH_SP_TWITTER_API_SECRET#',
				'DATA[ACCESS_TOKEN]' => 'text:#VETTICH_SP_TWITTER_ACCESS_TOKEN#',
				'DATA[ACCESS_TOKEN_SECRET]' => 'text:#VETTICH_SP_TWITTER_ACCESS_TOKEN_SECRET#',
			),
			'hiddenParams' => array('ID', 'DATA[ACCESS_TOKEN]', 'DATA[ACCESS_TOKEN_SECRET]'),
			'editLinkParams' => array('socialid' => self::$socialid),
			'linkEditInsert' => array('NAME'),
		)))->render();
	}

	/**
	 * отображает настройки аккаунта соц. сети
	 * @param  integer $id ID аккаунта
	 */
	public static function adminForm($id=0)
	{
		$data = new \vettich\devform\data\orm(array(
			'dbClass' => self::socialClass(),
			'filter' => array(
				'ID' => $_GET['ID'],
				'TYPE' => self::$socialid,
			),
			'prefix' => '_',
		));
		$params = array(
			'_ID' => 'hidden',
			'_TYPE' => 'hidden::'.self::$socialid,
			'_NAME' => 'text:#VDF_NAME#',
			'_IS_ENABLE' => 'checkbox:#VDF_IS_ENABLE#:Y',
			'_DATA[API_KEY]' => 'text:#VETTICH_SP_TWITTER_API_KEY#:params=[placeholder=#VETTICH_SP_TWITTER_API_KEY_PLACEHOLDER#]',
			'_DATA[API_SECRET]' => 'text:#VETTICH_SP_TWITTER_API_SECRET#:params=[placeholder=#VETTICH_SP_TWITTER_API_SECRET_PLACEHOLDER#]',
			'_DATA[ACCESS_TOKEN]' => 'text:#VETTICH_SP_TWITTER_ACCESS_TOKEN#:params=[placeholder=#VETTICH_SP_TWITTER_ACCESS_TOKEN_PLACEHOLDER#]',
			'_DATA[ACCESS_TOKEN_SECRET]' => 'text:#VETTICH_SP_TWITTER_ACCESS_TOKEN_SECRET#:params=[placeholder=#VETTICH_SP_TWITTER_ACCESS_TOKEN_SECRET_PLACEHOLDER#]',
			'help' => 'note:#VETTICH_SP_TWITTER_HELP#',
		);
		(new AdminForm('twitterform', array(
			'pageTitle' => $_GET['ID']>0 ? '#VETTICH_SP_EDIT_RECORD#' : '#VETTICH_SP_ADD_RECORD#',
			'tabs' => array(
				array(
					'name' => self::name(self::$socialid),
					'params' => $params,
				),
			),
			'data' => $data,
			'buttons' => array(
				'_save' => 'buttons.saveSubmit:#VDF_SAVE#',
				'_apply' => 'buttons.submit:#VDF_APPLY#',
			),
		)))->render();
	}

	/**
	 * настройки публикации для соц. сети
	 * @param  int    $iblock_id ID инфоблока
	 * @param  string $prefix    префикс, который должен быть добавлен к ключам настроек
	 * @return array             массив настроек
	 */
	public function publishParams($iblock_id, $prefix='')
	{
		if(!is_string($prefix)) {
			$prefix = '';
		} else {
			// $prefix .= '['.self::$socialid.']';
		}
		return array(
			$prefix.'[MAIN_PICTURE]' => array(
				'type' => 'select',
				'title' => '#VETTICH_SP_PUBLISH_MAIN_PICTURE#',
				'help' => '#VETTICH_SP_PUBLISH_MAIN_PICTURE_HELP#',
				'options' => self::allPropsFor($iblock_id),
				'default_value' => 'DETAIL_PICTURE',
			),
			$prefix.'[OTHER_PICTURE]' => array(
				'type' => 'select',
				'title' => '#VETTICH_SP_PUBLISH_OTHER_PICTURE#',
				'help' => '#VETTICH_SP_PUBLISH_OTHER_PICTURE_HELP#',
				'options' => self::allPropsFor($iblock_id),
				'default_value' => 'DETAIL_PICTURE',
			),
			$prefix.'[LINK]' => array(
				'type' => $individ ? 'hidden' : 'select',
				'title' => '#VETTICH_SP_PUBLISH_LINK#',
				'help' => '#VETTICH_SP_PUBLISH_LINK_HELP#',
				'options' => self::allPropsFor($iblock_id),
				'default_value' => 'DETAIL_PAGE_URL',
			),
			$prefix.'[TEXT]' => array(
				'type' => $individ ? 'hidden' : 'textarea',
				'title' => '#VETTICH_SP_PUBLISH_TEXT#',
				'help' => '#VETTICH_SP_PUBLISH_TEXT_HELP#',
				'items' => self::allPropsMacrosFor($iblock_id),
				'default_value' => "#NAME#",
				'params' => array('rows' => 6),
			),
		);
	}

	/**
	 * загружает файлы в соц. сеть
	 * @param  array  $field  поле массива элемента инфоблока
	 * @param  object &$twit  объект класса TwitterOAuth
	 * @return string
	 */
	public static function get_media_ids($field, &$twit)
	{
		$result = array();
		// $files = parent::getFilesFromProperty($sProp, $arFields);
		$files = self::getFileNames($field, true);
		$files = array_slice($files, 0, 4);
		foreach((array)$files as $file_name) {
			if($media_id = self::get_media_id($file_name, $twit)) {
				$result[] = $media_id;
			}
		}
		return $result;
	}

	/**
	 * загружает указанный файл в соц. сеть, для последующего прикрепления его к посту
	 * @param  string $file_name абсолютный путь до файла
	 * @param  object &$twit     объект класса TwitterOAuth
	 * @return string            идентификатор загруженного файла
	 */
	public static function get_media_id($file_name, &$twit)
	{
		try {
			$res = $twit->upload('media/upload', array('media'=>$file_name));
			if(!isset($res->error)) {
				return $res->media_id_string;
			}
		}
		catch(\Exception $e) {
			devdebug(array($e->getCode(), $e->getMessage()), 'error-twitter-get_media_id');
		}
		return false;
	}

	/**
	 * формирование данных для отправки в соц. сеть
	 * @param  array  $arAcc     настройки аккаунта
	 * @param  array  $arPost    массив настроек публикации
	 * @param  array  $arFields  массив элемента инфоблока
	 * @param  array  $post      данные для отправки в соц. сеть (используется для рекурсии)
	 * @return array             результат, содержится ключ success
	 */
	private static function preData($arAcc, $arPost, $arFields, &$twit, $post=array())
	{
		global $APPLICATION;
		$arData = $arPost['PUBLISH'];
		$post['status'] = self::macrosReplace($arData['TEXT'], $arFields, $arPost);
		$post['status'] = strip_tags($post['status']);
		$post['status'] = $APPLICATION->ConvertCharset($post['status'], SITE_CHARSET, "UTF-8");
		$post['status'] = trim(html_entity_decode($post['status']));
		$media_ids = array();
		if($arData['MAIN_PICTURE'] != '' && $arData['MAIN_PICTURE'] != 'none') {
			if($res = self::get_media_ids($arFields[$arData['MAIN_PICTURE']], $twit)) {
				$media_ids = $res;
			}
		}
		if($arData['OTHER_PICTURE'] != $arData['MAIN_PICTURE']
			&& $arData['OTHER_PICTURE'] != ''
			&& $arData['OTHER_PICTURE'] != 'none') {
			if($res = self::get_media_ids($arFields[$arData['OTHER_PICTURE']], $twit)) {
				$media_ids = array_merge($media_ids, $res);
			}
		}
		if(count($media_ids) > 4) {
			$media_ids = array_slice($media_ids, 0, 4);
		}

		$status_fsize = 140;
		if(!empty($media_ids)) {
			$post['media_ids'] = implode(',', $media_ids);
			$status_fsize -= 24;
		}
		$post['status'] = self::substr($post['status'], 0, $status_fsize);
		if($arData['LINK'] != '' && $arData['LINK'] != 'none') {
			// $link = self::getLinkFromProperty($arData['TWITTER_LINK'], $arFields, $arPost, $arSite);
			$link = self::createLink($arFields[$arData['LINK']], $arPost, $arFields);
			if(!empty($link)) {
				$status_fsize -= 24;
				$post['status'] = self::substr($post['status'], 0, $status_fsize);
				$post['status'] .= "\n".$link;
			}
		}
		return $post;
	}

	/**
	 * создает и инициализирует объект класса TwitterOAuth
	 * @param  array $arAcc  настройки аккаунта соц. сети
	 * @return array|object  созданный объект, либо массив с описанием ошибки
	 */
	private static function initTwit($arAcc)
	{
		try{
			$twit = new \Abraham\TwitterOAuth\TwitterOAuth(
				$arAcc['DATA']['API_KEY'],
				$arAcc['DATA']['API_SECRET'],
				$arAcc['DATA']['ACCESS_TOKEN'],
				$arAcc['DATA']['ACCESS_TOKEN_SECRET']
			);
			$rs = $twit->get('account/verify_credentials');
			if(isset($rs->errors)) {
				$result['error_code'] = $rs->errors[0]->code;
				$result['error_msg'] = $rs->errors[0]->message;
				return array(
					'success' => false,
					'error_code' => $rs->errors[0]->code,
					'error_msg' => $rs->errors[0]->message,
				);
			}
			return $twit;
		} catch(\Exception $e) {
			return array(
				'success' => false,
				'error_code' => $e->getCode(),
				'error_msg' => 'Exception: '.$e->getMessage(),
			);
		}
	}

	/**
	 * выполняет $method
	 * @param  array  $post   данные для публикации
	 * @param  object &$twit  объект класса TwitterOAuth
	 * @param  string $method имя метода
	 * @return array          результат, содержится ключ success
	 */
	private static function postRun($post, &$twit, $method='statuses/update')
	{
		$result = array('success' => false);
		try{
			$rs = $twit->post($method, $post);
			if(isset($rs->errors)) {
				$result['error_code'] = $rs->errors[0]->code;
				$result['error_msg'] = $rs->errors[0]->message;
			} else {
				$result['user_id'] = $rs->user->id;
				$result['post_id'] = $rs->id;
				$result['success'] = true;
			}
		}
		catch(\Exception $e){
			$result['error_code'] = $e->getCode();
			$result['error_msg'] = 'Exception: '.$e->getMessage();
		}
		return $result;
	}

	/**
	 * публикует данные из $arFields в соц сеть
	 * @param  int    $accountId ID аккаунта соц. сети
	 * @param  array  $arPost    массив настроек публикации
	 * @param  array  $arFields  массив элемента инфоблока
	 * @param  array  $post      данные для отправки в соц. сеть (используется для рекурсии)
	 * @return array             результат, где должен обязательно содержаться ключ success
	 */
	public static function publish($accountId, $arPost, $arFields, $post=array())
	{
		self::autoloadApi();
		$ar = self::accountById($accountId);
		if(!$ar) {
			return array(
				'success' => true,
				'error_code' => '-1',
				'error_msg' => 'Account not found.',
			);
		}
		if($ar['IS_ENABLE'] != 'Y') {
			return array(
				'success' => true,
				'error_code' => '-1',
				'error_msg' => 'Account is not enable.'
			);
		}
		$twit = self::initTwit($ar);
		if(is_array($twit)) {
			return $twit;
		} elseif(is_object($twit)) {
			$post = self::preData($ar, $arPost, $arFields, $twit, $post);
			return self::postRun($post, $twit);
		}
	}

	/**
	 * обновляет данные в соц. сети
	 * @param  array  $data       данные опубликованного поста (результат фукнции publish)
	 * @param  int    $accountId  ID аккаунта соц. сети
	 * @param  array  $arPost     массив настроек публикации
	 * @param  array  $arFields   данные элемента инфоблока
	 * @param  array  $post      данные для отправки в соц. сеть (используется для рекурсии)
	 * @return array              результат, где должен обязательно содержаться ключ success
	 */
	public static function update($data, $accountId, $arPost, $arFields, $post=array())
	{
		self::autoloadApi();
		$ar = self::accountById($accountId);
		if(!$ar) {
			return array(
				'success' => true,
				'error_code' => '-1',
				'error_msg' => 'Account not found.',
			);
		}
		if($ar['IS_ENABLE'] != 'Y') {
			return array(
				'success' => true,
				'error_code' => '-1',
				'error_msg' => 'Account is not enable.'
			);
		}
		$twit = self::initTwit($ar);
		if(is_array($twit)) {
			return $twit;
		} elseif(is_object($twit)) {
			$post = self::preData($ar, $arPost, $arFields, $twit, $post);
			return self::postRun($post, $twit);			
		}
	}

	/**
	 * удаляет пост из соц. сети
	 * @param  array  $data       данные опубликованного поста (результат фукнции publish)
	 * @param  int    $accountId  ID аккаунта соц. сети
	 * @param  array  $arPost     массив настроек публикации
	 * @param  array  $post      данные для отправки в соц. сеть (используется для рекурсии)
	 * @return array              результат, где должен обязательно содержаться ключ success
	 */
	public static function delete($data, $accountId, $arPost, $post=array())
	{
		if(!isset($data['post_id'])) {
			return null;
		}
		self::autoloadApi();
		$ar = self::accountById($accountId);
		if(!$ar) {
			return array(
				'success' => true,
				'error_code' => '-1',
				'error_msg' => 'Account not found.',
			);
		}
		if($ar['IS_ENABLE'] != 'Y') {
			return array(
				'success' => true,
				'error_code' => '-1',
				'error_msg' => 'Account is not enable.'
			);
		}
		$twit = self::initTwit($ar);
		if(is_array($twit)) {
			return $twit;
		} elseif(is_object($twit)) {
			$post['id'] = $data['post_id'];
			return self::postRun($post, $twit, 'statuses/destroy');
		}
	}

	/**
	 * возвращает ссылку на пост (если есть), и ошибку (если есть)
	 * @param  array $data     данные опубликованного поста
	 * @param  array $account  настройки аккаунта
	 * @return string
	 */
	public function viewData($data, $account) {
		$result = '';
		if(!empty($data['post_id'])) {
			$link = 'https://twitter.com/'.$data['user_id'].'/status/'.$data['post_id'];;
			$result = "<a href=\"$link\" target='_blank'>$link</a>";
		}
		if(!$data['success']) {
			$result .= self::mess(" <span>#ERROR# [$data[error_code]]: $data[error_msg]</span>");
		}
		return $result;
	}
}
