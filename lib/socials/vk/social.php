<?
namespace Vettich\SP\Socials\vk;
IncludeModuleLangFile(__FILE__);

use vettich\devform\AdminList;
use vettich\devform\AdminForm;
use vettich\SP\Antigate;

class Social extends \Vettich\SP\Social
{
	/**
	 * идентификатор соц. сети
	 * @var string
	 */
	public static $socialid = 'vk';

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
				'DATA[GROUP_ID]' => 'text:#VETTICH_SP_VK_GROUP_ID#',
				'DATA[ACCESS_TOKEN]' => 'text:#VETTICH_SP_VK_ACCESS_TOKEN#',
			),
			'hiddenParams' => array('ID', 'DATA[ACCESS_TOKEN]'),
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
		$isCustopApp = false;
		$_DATA = $data->get('_DATA');
		if((isset($_POST['_DATA']['customApp']) && $_POST['_DATA']['customApp'] == 'Y')
			or (empty($_POST) && $_DATA['customApp'] == 'Y')) {
			$isCustopApp = true;
		}
		$params = array(
			'_ID' => 'hidden',
			'_TYPE' => 'hidden::'.self::$socialid,
			'_NAME' => 'text:#VDF_NAME#',
			'_IS_ENABLE' => 'checkbox:#VDF_IS_ENABLE#:Y',
			'_DATA[GROUP_ID]' => 'text:#VETTICH_SP_VK_GROUP_ID#:params=[placeholder=#VETTICH_SP_VK_GROUP_ID_PLACEHOLDER#]',
			'_DATA[customApp]' => 'checkbox:#VETTICH_SP_VK_CUSTOM_APP#:N:refresh=Y',
			'_DATA[IS_PROFILE]' => !$isCustopApp ? 'hidden' : 'checkbox:#VETTICH_SP_PROFILE#:N',
			'_DATA[FROM_GROUP]' => 'checkbox:#VETTICH_SP_VK_FROM_GROUP#:Y',
			'_DATA[SCREEN_NAME]' => 'hidden',
			'_DATA[APP_ID]' => !$isCustopApp ? 'hidden' : 'text:#VETTICH_SP_VK_APP_ID#',
			'_DATA[ACCESS_TOKEN]' => 'text:#VETTICH_SP_VK_ACCESS_TOKEN#:params=[placeholder=#VETTICH_SP_VK_ACCESS_TOKEN_PLACEHOLDER#]',
			'getAccessToken' => 'divbutton::text=#VETTICH_SP_VK_GET_ACCESS_TOKEN#:onclick=getAccessToken();',
			'help' => !$isCustopApp ? 'hidden' : 'note:#VETTICH_SP_VK_HELP_CUSTOM#',
			'url' => !$isCustopApp ? 'hidden' : 'text:#VETTICH_SP_VK_URL#',
			'getAccessTokenFromUrl' => !$isCustopApp ? 'hidden' : 'divbutton::text=#VETTICH_SP_VK_GET_ACCESS_TOKEN_FROM_URL#:onclick=getAccessTokenFromUrl();'
		);
		(new AdminForm('vkform', array(
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
				if($("#_DATA-customApp:checked").length) {
					if($("#_DATA-APP_ID").val().trim() == "") {
						alert("App ID is require");
						return;
					}
					var url = "http://oauth.vk.com/authorize?client_id=" + $("#_DATA-APP_ID").val().trim() + "&scope=friends,wall,groups,offline,photos,stats&redirect_uri=https://oauth.vk.com/blank.html&response_type=token&display=page";
					window.open(url, "vap-vk");
				} else {
					var params = "callback='.self::siteUrl().'/bitrix/admin/vettich.sp.vk_callback.php";
					params += "&scope=friends,photos,wall,offline,groups,stats";
					window.open("https://vettich.ru/service/social/vk/get_token?" + params, "vap-vk");
				}
			}
			function accessTokenSet($access_token, $group_id, $group_name, $is_profile, $screen_name) {
				$("#_DATA-ACCESS_TOKEN").val($access_token);
				$("#_DATA-GROUP_ID").val($group_id);
				$("#_NAME").val($group_name);
				$("#_DATA-IS_PROFILE").val($is_profile ? "Y" : "N");
				$("#_DATA-SCREEN_NAME").val($screen_name);
			}
			function getAccessTokenFromUrl() {
				var url = $("#url").val().trim();
				var token = url.match(/access_token=(\w+)/i);
				if(token != null) {
					$("#_DATA-ACCESS_TOKEN").val(token[1]);
					alert("'.GetMessage('VETTICH_SP_VK_DONE').'");
				} else {
					alert("Error: Access token not found.");
				}
			}'
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
				'type' => $individ ? 'hidden' : 'select',
				'title' => '#VETTICH_SP_PUBLISH_OTHER_PICTURE#',
				'help' => '#VETTICH_SP_PUBLISH_OTHER_PICTURE_HELP#',
				'options' => self::allPropsFor($iblock_id),
				'default_value' => 'PROPERTY_MORE_PICTURES',
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
				'default_value' => "#NAME##BR#\n#BR#\n#PREVIEW_TEXT#",
				'params' => array('rows' => 6),
			),
		);
	}

	public static function api()
	{
		return 'vettich\SP\posts\vk\api';
	}

	/**
	 * формирует данные, для отправки в соц. сеть
	 * используется в publish и update
	 * @param  array  $arAcc     настройки аккаунта соц. сети
	 * @param  array  $arPost    массив настроек публикации
	 * @param  array  $arFields  массив элемента инфоблока
	 * @param  array  $post      данные для отправки в соц. сеть (используется для рекурсии)
	 * @return array             обработанные данные для публикации
	 */
	public static function preData($arAcc, $arPost, $arFields, $post = array())
	{
		global $APPLICATION;
		$arData = $arPost['PUBLISH'];
		$post['access_token'] = $arAcc['DATA']['ACCESS_TOKEN'];
		if($arAcc['DATA']['IS_PROFILE'] == 'Y') {
			$post['owner_id'] = $arAcc['DATA']['GROUP_ID'];
		} else {
			$post['owner_id'] = '-'.$arAcc['DATA']['GROUP_ID'];
			$post['from_group'] = ($arAcc['DATA']['FROM_GROUP'] == 'Y' ? 1 : 0);
		}
		$post['message'] = self::macrosReplace($arData['TEXT'], $arFields, $arPost);
		$post['message'] = strip_tags($post['message']);
		$post['message'] = $APPLICATION->ConvertCharset($post['message'], SITE_CHARSET, "UTF-8");
		$post['message'] = trim(html_entity_decode($post['message']));
		if($arData['MAIN_PICTURE'] != '' && $arData['MAIN_PICTURE'] != 'none') {
			$res = api::attach_photo($arFields[$arData['MAIN_PICTURE']], $arAcc['DATA']);
			if(!empty($res)) {
				$post['attachments'] = $res;
			}
		}
		if($arData['OTHER_PICTURE'] != $arData['MAIN_PICTURE']
			&& $arData['OTHER_PICTURE'] != ''
			&& $arData['OTHER_PICTURE'] != 'none') {
			$res = api::attach_photo($arFields[$arData['OTHER_PICTURE']], $arAcc['DATA']);
			if(!empty($res)) {
				$post['attachments'] = array_merge((array)$post['attachments'], $res);
			}
		}

		if(count($post['attachments']) > 10)
			array_splice($post['attachments'], 10);
		if($arData['LINK'] != '' && $arData['LINK'] != 'none') {
			$res = self::createLink($arFields[$arData['LINK']], $arPost, $arFields);
			if(!empty($res)) {
				if(count($post['attachments']) > 9)
					array_splice($post['attachments'], 9);
				$post['attachments'][] = $res;
			}
		}

		if(isset($post['attachments']))
			$post['attachments'] = implode(',', $post['attachments']);
		return $post;
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
		$post = self::preData($ar, $arPost, $arFields, $post);
		$rs = api::method('wall.post', $post, array('isPost' => true));
		$result = array();
		if(isset($rs['response']['post_id'])) {
			$result['post_id'] = $rs['response']['post_id'];
			$result['success'] = true;
		} else {
			if($rs['error']['error_code'] == 14 && '' != ($apikey = self::GetOptionString('antigate_apikey'))) {
				$post = array(
					'captcha_sid' => $rs['error']['captcha_sid'],
					'captcha_key' => Antigate::recognize(Antigate::url2file($rs['error']['captcha_img']), $apikey, false, 'antigate.com', 1),
				);
				return self::publish($accountId, $arPost, $arFields, $post);
			}
			$result['error_msg'] = $rs['error']['error_msg'];
			$result['error_code'] = $rs['error']['error_code'];
			$result['success'] = false;
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
		if(empty($data['post_id']) or $data['error_code'] == 15) {
			return null;
		}
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
		$post = self::preData($ar, $arPost, $arFields, $post);
		$post['post_id'] = $data['post_id'];
		$rs = api::method('wall.edit', $post, array('isPost' => true));
		$result = array();
		if(isset($rs['response']) && $rs['response'] == 1) {
			$result['post_id'] = $data['post_id'];
			$result['success'] = true;
		} else {
			if($rs['error']['error_code'] == 14 && '' != ($apikey = self::GetOptionString('antigate_apikey'))) {
				$post = array(
					'captcha_sid' => $rs['error']['captcha_sid'],
					'captcha_key' => Antigate::recognize(Antigate::url2file($rs['error']['captcha_img']), $apikey, false, 'antigate.com', 1),
				);
				return self::update($data, $accountId, $arPost, $arFields, $post);
			}
			$result['error_msg'] = $rs['error']['error_msg'];
			$result['error_code'] = $rs['error']['error_code'];
			$result['success'] = false;
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
		$post['post_id'] = $data['post_id'];
		$post['owner_id'] = '-'.$ar['DATA']['GROUP_ID'];
		$post['access_token'] = $ar['DATA']['ACCESS_TOKEN'];
		$rs = api::method('wall.delete', $post, array('isPost' => true));
		$result = array();
		if(isset($rs['response']) && $rs['response'] == 1) {
			$result['post_id'] = $data['post_id'];
			$result['success'] = true;
		} else {
			if($rs['error']['error_code'] == 14 && '' != ($apikey = self::GetOptionString('antigate_apikey'))) {
				$post = array(
					'captcha_sid' => $rs['error']['captcha_sid'],
					'captcha_key' => Antigate::recognize(Antigate::url2file($rs['error']['captcha_img']), $apikey, false, 'antigate.com', 1),
				);
				return self::delete($data, $accountId, $arPost, $post);
			}
			$result['error_msg'] = $rs['error']['error_msg'];
			$result['error_code'] = $rs['error']['error_code'];
			$result['success'] = false;
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
			$link = 'https://vk.com/';
			if(empty($account['DATA']['SCREEN_NAME'])) {
				$link .= ($account['DATA']['IS_PROFILE'] == 'Y' ? 'id' : 'public').$account['DATA']['GROUP_ID'];
			} else {
				$link .= $account['DATA']['SCREEN_NAME'];
			}
			$link .= '?w=wall'.($account['DATA']['IS_PROFILE'] == 'Y' ? '' : '-')
				.$account['DATA']['GROUP_ID'].'_'.$data['post_id'];
			$result = "<a href=\"$link\" target='_blank'>$link</a>";
		}
		if(!$data['success']) {
			$result .= self::mess(" <span>#ERROR# [$data[error_code]]: $data[error_msg]</span>");
		}
		return $result;
	}
}
