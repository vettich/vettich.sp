<?
namespace vettich\SP\Socials\vk;

/**
* 
*/
class api extends Social
{
	/**
	 * url для запросов к api вконтакте
	 * @var string
	 */
	private static $url = 'https://api.vk.com';

	/**
	 * используемая версия api
	 * @var string
	 */
	private static $version = '5.58';

	/**
	 * выполняет метод $endpoint
	 * @param  string $endpoint метод api
	 * @param  array  $data     аргументы метода
	 * @param  array  $config   дополнительная конфигурация
	 * @return array            результат api
	 */
	public static function method($endpoint, $data=[], $config=[])
	{
		$default = [
			'version' => self::$version,
			'isPost' => false,
		];
		$config = $config + $default;

		$url = self::$url.'/method/'.$endpoint;
		$data = http_build_query($data);

		if(!$config['isPost'] && !empty($data))
			$url .= '?'.$data;

		if(strpos($url, '?') !== false)
			$url .= '&v='.$config['version'];
		else
			$url .= '?v='.$config['version'];

		if(!$config['isPost'])
			$response = self::curlGet($url);
		else
			$response = self::curlPost($url, $data);
		return json_decode($response, true);
	}

	/**
	 * загрузка картинок на сервер
	 * @param  array  $arField   массив поля элемента инфоблока
	 * @param  array  $arAccount настройки аккаунта
	 * @return array             список идентификаторов загруженных файлов
	 */
	public static function attach_photo($arField, $arAccount)
	{
		$result = array();
		$files = self::getFileNames($arField);
		if(!empty($files))
		{
			$rs = self::upload_files($files, $arAccount);
			if(isset($rs['response']) && !empty($rs['response']))
				foreach((array)$rs['response'] as $value) {
					$result[] = 'photo'.$value['owner_id'].'_'.$value['id'];
				}
		}
		return $result;
	}

	/**
	 * загрузка файлов на сервер
	 * @param  array $arFilesName список файлов
	 * @param  array $arAccount   настройки аккаунта
	 * @return array              результат загрузки файла
	 */
	function upload_files($arFilesName, $arAccount)
	{
		if(count($arFilesName) > 5)
		{
			$arFilesName = array_chunk($arFilesName, 5);
			$ret = array();
			foreach((array)$arFilesName as $arr)
			{
				$ret = array_merge_recursive($ret, self::upload_files($arr, $arAccount));
			}
			return $ret;
		}

		$files = array();
		foreach((array)$arFilesName as $key => $fileName)
		{
			if(count($files) >= 10)
				break;
			$files['file'.(count($files)+1)] = self::getCurlFilename($fileName);
		}

		$params = array(
			'access_token' => $arAccount['ACCESS_TOKEN'],
			'group_id' => $arAccount['GROUP_ID'],
		);
		$dataArray = self::method('photos.getWallUploadServer', $params);

		$response = json_decode(self::curlPost($dataArray['response']['upload_url'], $files), 1);
		$response_photo = json_decode($response['photo'],1);
		if(empty($response) or empty($response_photo))
			return false;

		$data = array(
			'photo' => $response['photo'],
			'server' => $response['server'],
			'hash' => $response['hash'],
			'access_token' => $arAccount['ACCESS_TOKEN'],
			'group_id' => $arAccount['GROUP_ID'],
		);
		$response = self::method('photos.saveWallPhoto', $data);
		return $response;
	}

	/**
	 * возвращает список групп пользователя
	 * @param  string $access_token Access token пользователя
	 * @return array                список групп
	 */
	public static function groups($access_token)
	{
		return self::method('groups.get', array(
			'access_token' => $access_token,
			'filter' => 'admin',
			'extended' => '1',
		));
	}

}
