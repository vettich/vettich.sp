<?
namespace Vettich\SP\Socials\facebook;
IncludeModuleLangFile(__FILE__);

use vettich\devform\AdminList;
use vettich\devform\AdminForm;
use Facebook;

class Social extends \Vettich\SP\Social
{
	/**
	 * идентификатор соц. сети
	 * @var string
	 */
	static $socialid = 'facebook';

	/**
	 * используемая версия api facebook
	 * @var string
	 */
	static $version = 'v2.12';

	/**
	 * загружает api, если еще не было загружено
	 */
	static function autoloadApi() {
		static $is = false;
		if(!$is && !version_compare(PHP_VERSION, '5.4.0', '<')) {
			$is = true;
			require VETTICH_SP_DIR.'/classes/Facebook/autoload.php';
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
				'DATA[GROUP_ID]' => 'text:#VETTICH_SP_FB_GROUP_ID#',
				'DATA[APP_ID]' => 'text:#VETTICH_SP_FB_APP_ID#',
				'DATA[ACCESS_TOKEN]' => 'text:#VETTICH_SP_FB_ACCESS_TOKEN#',
			),
			'hiddenParams' => array('ID', 'DATA[ACCESS_TOKEN]', 'DATA[APP_ID]'),
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
		$newN = 1;
		if(empty($_GET['ID'])) {
			$data = new \vettich\devform\data\orm(array(
				'dbClass' => self::socialClass(),
				'filter' => array('TYPE' => self::$socialid),
			));
			$newN += $data->getList()->getSelectedRowsCount();
		}
		$data = new \vettich\devform\data\orm(array(
			'dbClass' => self::socialClass(),
			'filter' => array(
				'ID' => $_GET['ID'],
				'TYPE' => self::$socialid,
			),
			'prefix' => '_',
		));
		// $_DATA = $data->get('_DATA');
		// $isCustomApp = false;
		// if((isset($_POST['_DATA']['customApp']) && $_POST['_DATA']['customApp'] == 'Y')
		// 	or (empty($_POST) && $_DATA['customApp'] == 'Y')) {
		// 	$isCustomApp = true;
		// }
		$isCustomApp = false;
		$params = array(
			'_ID' => 'hidden',
			'_TYPE' => 'hidden::'.self::$socialid,
			'_NAME' => 'text:#VDF_NAME#:Autoposting to Facebook \['.$newN.'\]',
			'_IS_ENABLE' => 'checkbox:#VDF_IS_ENABLE#:Y',
			'_DATA[GROUP_ID]' => 'text:#VETTICH_SP_FB_GROUP_ID#:params=[placeholder=#VETTICH_SP_FB_GROUP_ID_PLACEHOLDER#]',
			// '_DATA[customApp]' => 'checkbox:#VETTICH_SP_FB_CUSTOM_APP#:N:refresh=Y',
			'_DATA[APP_ID]' => !$isCustomApp ? 'hidden' : 'text:#VETTICH_SP_FB_APP_ID#',
			'_DATA[APP_SECRET]' => !$isCustomApp ? 'hidden' : 'text:#VETTICH_SP_FB_APP_SECRET#',
			'_DATA[ACCESS_TOKEN]' => 'text:#VETTICH_SP_FB_ACCESS_TOKEN#:params=[placeholder=#VETTICH_SP_FB_ACCESS_TOKEN_PLACEHOLDER#]',
			'_DATA[EXPIRES]' => 'hidden',
			'getAccessToken' => 'divbutton::text=#VETTICH_SP_FB_GET_ACCESS_TOKEN#:onclick=getAccessToken();',
			'help' => $isCustomApp ? 'note:#VETTICH_SP_FB_HELP_CUSTOM_APP#' : 'note:#VETTICH_SP_FB_HELP#',
		);
		(new AdminForm('fbform', array(
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
			'js' => '
			function getAccessToken() {
				var scopes = "manage_pages,publish_pages,public_profile,pages_show_list";
				if(false && $("#_DATA-customApp:checked").length) {
					if($("#_DATA-APP_ID").val().trim() == "") {
						alert("App ID is require");
						return;
					}
					if($("#_DATA-APP_SECRET").val().trim() == "") {
						alert("App secret is require");
						return;
					}
					url = "/bitrix/admin/vettich.sp.fb_login.php?app_id=" + $("#_DATA-APP_ID").val().trim() + "&app_secret=" + $("#_DATA-APP_SECRET").val().trim() + "&scope=" + scopes;
				} else {
					var server = "http'.($_SERVER['HTTPS']?'s':'').'://'.$_SERVER['SERVER_NAME'].'";
					var callback = server + "/bitrix/admin/vettich.sp.fb_callback.php";
					url = "https://vettich.ru/service/social/facebook/get_token/?callback=" + callback+"&scope=" +scopes;
				}
				window.open(url, "vap-fb");
			}
			function accessTokenSet($app_id, $app_secret, $access_token, $expires, $group_id, $group_name) {
				$("#_DATA-APP_ID").val($app_id);
				$("#_DATA-APP_SECRET").val($app_secret);
				$("#_DATA-ACCESS_TOKEN").val($access_token);
				$("#_DATA-EXPIRES").val($expires);
				$("#_DATA-GROUP_ID").val($group_id);
				$("#_NAME").val($group_name);
			}
			$("#_DATA-APP_ID").change(function() {$("#fb-cb-app_id").text($(this).val())})
			$("#_DATA-APP_SECRET").change(function() {$("#fb-cb-app_secret").text($(this).val())})
			',
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
			$prefix.'[LINK]' => array(
				'type' => $individ ? 'hidden' : 'select',
				'title' => '#VETTICH_SP_PUBLISH_LINK#',
				'help' => '#VETTICH_SP_PUBLISH_LINK_HELP#',
				'options' => self::allPropsFor($iblock_id),
				'default_value' => 'DETAIL_PAGE_URL',
			),
			// $prefix.'[MAIN_PICTURE]' => array(
			// 	'type' => 'select',
			// 	'title' => '#VETTICH_SP_PUBLISH_MAIN_PICTURE#',
			// 	'help' => '#VETTICH_SP_PUBLISH_MAIN_PICTURE_HELP#',
			// 	'options' => self::allPropsFor($iblock_id),
			// 	'default_value' => 'DETAIL_PICTURE',
			// ),
			// $prefix.'[LINK_TITLE]' => array(
			// 	'type' => $individ ? 'hidden' : 'select',
			// 	'title' => '#VETTICH_SP_PUBLISH_LINK_TITLE#',
			// 	'help' => '#VETTICH_SP_PUBLISH_LINK_TITLE_HELP#',
			// 	'options' => self::allPropsFor($iblock_id),
			// 	'default_value' => 'NAME',
			// ),
			// $prefix.'[LINK_DESCRIPTION]' => array(
			// 	'type' => $individ ? 'hidden' : 'select',
			// 	'title' => '#VETTICH_SP_PUBLISH_LINK_DESCRIPTION#',
			// 	'help' => '#VETTICH_SP_PUBLISH_LINK_DESCRIPTION_HELP#',
			// 	'options' => self::allPropsFor($iblock_id),
			// 	'default_value' => 'PREVIEW_TEXT',
			// ),
			$prefix.'[TEXT]' => array(
				'type' => $individ ? 'hidden' : 'textarea',
				'title' => '#VETTICH_SP_PUBLISH_TEXT#',
				'help' => '#VETTICH_SP_PUBLISH_TEXT_HELP#',
				'items' => self::allPropsMacrosFor($iblock_id),
				'default_value' => "#NAME##BR#\n#BR#\n#PREVIEW_TEXT#",
				'params' => array('rows' => 6),
			),
		);
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
		global $APPLICATION;
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
		$arData = $arPost['PUBLISH'];
		$post['message'] = self::macrosReplace($arData['TEXT'], $arFields, $arPost);
		$post['message'] = strip_tags($post['message']);
		$post['message'] = $APPLICATION->ConvertCharset($post['message'], SITE_CHARSET, "UTF-8");
		$post['message'] = trim(html_entity_decode($post['message']));
		if($arData['LINK'] != '' && $arData['LINK'] != 'none') {
			$post['link'] = self::createLink($arFields[$arData['LINK']], $arPost, $arFields);
		}
		/*if($arData['MAIN_PICTURE'] != '' && $arData['MAIN_PICTURE'] != 'none' && !empty($post['link'])) {
			$res = self::getFileNames($arFields[$arData['MAIN_PICTURE']], false);
			if(isset($res[0])) {
				$res = self::createLink($res[0], $arPost, $arFields);
				$post['picture'] = $res;
			}
		}
		if($arData['LINK_TITLE'] != '' && $arData['LINK_TITLE'] != 'none' && !empty($post['link'])) {
			$post['name'] = self::macrosReplace($arData['LINK_TITLE'], $arFields, $arPost);
			$post['name'] = strip_tags($post['name']);
			$post['name'] = $APPLICATION->ConvertCharset($post['name'], SITE_CHARSET, "UTF-8");
			$post['name'] = trim(html_entity_decode($post['name']));
			$post['name'] = self::mb_substr($post['name'], 0, 255);
		}
		if($arData['LINK_DESCRIPTION'] != '' && $arData['LINK_DESCRIPTION'] != 'none' && !empty($post['link'])) {
			$post['description'] = self::macrosReplace($arData['LINK_DESCRIPTION'], $arFields, $arPost);
			$post['description'] = strip_tags($post['description']);
			$post['description'] = $APPLICATION->ConvertCharset($post['description'], SITE_CHARSET, "UTF-8");
			$post['description'] = trim(html_entity_decode($post['description']));
			$post['description'] = self::mb_substr($post['description'], 0, 1024);
		}*/
		$fb = new Facebook\Facebook(array(
			'app_id'				=> $ar['DATA']['APP_ID'],
			'app_secret'			=> $ar['DATA']['APP_SECRET'],
			'default_graph_version'	=> self::$version,
			'default_access_token'	=> $ar['DATA']['ACCESS_TOKEN'],
		));
		try {
			$result = $fb->get('/'. $ar['DATA']['GROUP_ID'] .'?fields=access_token')
				->getGraphObject()
				->asArray();
			if(isset($result['access_token'])) {
				$fb->setDefaultAccessToken($result['access_token']);
			}
		} catch (Facebook\Exceptions\FacebookResponseException $e){
			// continue;
		} catch (Facebook\Exceptions\FacebookSDKException $e) {
			// continue;
		}
		$result = array(
			'success' => false,
		);
		try {
			$rs = $fb->post('/'.$ar['DATA']['GROUP_ID'].'/feed', $post)->getDecodedBody();
			$result['post_id'] = $rs['id'];
			$result['success'] = true;
		} catch (Facebook\Exceptions\FacebookResponseException $e) {
			$error = $e;
		} catch (Facebook\Exceptions\FacebookSDKException $e) {
			$error = $e;
		}
		if($error) {
			$result['error_code'] = $error->getCode();
			$result['error_msg'] = $error->getMessage();
		}
		return $result;
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
		global $APPLICATION;
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
		$post['message'] = self::macrosReplace($arPost['PUBLISH']['TEXT'], $arFields, $arPost);
		$post['message'] = strip_tags($post['message']);
		$post['message'] = $APPLICATION->ConvertCharset($post['message'], SITE_CHARSET, "UTF-8");
		$post['message'] = trim(html_entity_decode($post['message']));
		$fb = new Facebook\Facebook(array(
			'app_id'				=> $ar['DATA']['APP_ID'],
			'app_secret'			=> $ar['DATA']['APP_SECRET'],
			'default_graph_version'	=> self::$version,
			'default_access_token'	=> $ar['DATA']['ACCESS_TOKEN'],
		));
		try {
			$result = $fb->get('/'. $ar['DATA']['GROUP_ID'] .'?fields=access_token')
				->getGraphObject()
				->asArray();
			if(isset($result['access_token'])) {
				$fb->setDefaultAccessToken($result['access_token']);
			}
		} catch (Facebook\Exceptions\FacebookResponseException $e){
			// continue;
		} catch (Facebook\Exceptions\FacebookSDKException $e) {
			// continue;
		}
		$result = array(
			'success' => false,
		);
		try {
			$rs = $fb->post('/'.$data['post_id'], $post)->getDecodedBody();
			$result['success'] = $rs['success'];
		} catch (Facebook\Exceptions\FacebookResponseException $e) {
			$error = $e;
		} catch (Facebook\Exceptions\FacebookSDKException $e) {
			$error = $e;
		}
		if($error) {
			$result['error_code'] = $error->getCode();
			$result['error_msg'] = $error->getMessage();
		}
		return $result;
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
		$fb = new Facebook\Facebook(array(
			'app_id'				=> $ar['DATA']['APP_ID'],
			'app_secret'			=> $ar['DATA']['APP_SECRET'],
			'default_graph_version'	=> self::$version,
			'default_access_token'	=> $ar['DATA']['ACCESS_TOKEN'],
		));
		try {
			$result = $fb->get('/'. $ar['DATA']['GROUP_ID'] .'?fields=access_token')
				->getGraphObject()
				->asArray();
			if(isset($result['access_token'])) {
				$fb->setDefaultAccessToken($result['access_token']);
			}
		} catch (Facebook\Exceptions\FacebookResponseException $e){
			// continue;
		} catch (Facebook\Exceptions\FacebookSDKException $e) {
			// continue;
		}
		$result = array(
			'success' => false,
		);
		try {
			$rs = $fb->delete('/'.$data['post_id'])
				->getDecodedBody();
			$result['success'] = $rs['success'];
		} catch (Facebook\Exceptions\FacebookResponseException $e) {
			$error = $e;
		} catch (Facebook\Exceptions\FacebookSDKException $e) {
			$error = $e;
		}
		if($error) {
			$result['error_code'] = $error->getCode();
			$result['error_msg'] = $error->getMessage();
		}
		return $result;
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
			$link = 'https://facebook.com/'.$data['post_id'];
			$result = "<a href=\"$link\" target='_blank'>$link</a>";
		}
		if(!$data['success']) {
			$result .= self::mess(" <span>#ERROR# [$data[error_code]]: $data[error_msg]</span>");
		}
		return $result;
	}

}
