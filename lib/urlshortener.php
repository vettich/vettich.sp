<?
namespace vettich\SP;

/**
* Author: Vettich
* @event OnShortUrl
* @param  string $url ссылка которую нужно укоротить
* @param  string $type тип сервиса, с помощью которого требуется укоротить
* @return  string готовая ссылка
*
* @event OnShortUrlType
* @return  array массив сервиса (тип => название сервиса)
*/
class UrlShortener extends Module
{
	/**
	 * массив укороченных ссылок
	 * @var array
	 */
	private static $_short = array();
	/**
	 * укорачивает ссылку. Если не получилось укоротить, возвращает исходную
	 * @param  string $url
	 * @param  string $type
	 * @return string
	 */
	public static function short($url, $type='')
	{
		if(empty($type)) {
			return $url;
		}
		if(!empty(static::$_short[$url.$type])) {
			return static::$_short[$url.$type];
		}
		$result = $url;
		switch ($type) {
			case 'clck.ru':
				$result = self::curlGet('https://clck.ru/--?url='.$url);
				break;

			case 'goo.gl':
				// @todo не работает, требует каптчу. попробовать реализовать другим способом
				$rs = self::curlPost('http://goo.gl/api/shorten', array(
					'security_token' => 'null',
					'url' => $url,
				));
				$rs = json_decode($rs, true);
				// print_r([$rs]);
				if(!empty($rs['short_url'])) {
					$result = $rs['short_url'];
				}
				break;

			case 'u.to':
				$rs = self::curlPost('http://u.to/', http_build_query(array(
					'a' => 'add',
					'url' => $url,
				)));
				$rs = str_replace(array("\r", "\n"), '', $rs);
				$_url = str_replace('/', '\/', $_url);
				if(preg_match_all('/'.preg_quote($_url).'<\/a><a.*?href.*?"(http:\/\/u.to\/[\w\d]+)/is', $rs, $matches)) {
					$result = $matches[1][0];
				}
				break;

			case 'urlid.ru':
				$rs = self::curlPost('http://urlid.ru/', http_build_query(array(
					'send' => '1',
					'url' => $url,
				)));
				$rs = str_replace(array("\r", "\n"), '', $rs);
				if(preg_match_all('/value="(.*?)"/is', $rs, $matches)) {
					$result = $matches[1][0];
				}
				break;

			case 'tinyurl.com':
				$rs = self::curlPost('http://tinyurl.com/create.php', http_build_query(array(
					'url' => $url,
					'submit' => 'Make TinyURL!',
				)));
				// print_r([$rs]);
				$rs = str_replace(array("\r", "\n"), '', $rs);
				if(preg_match_all('/<a\s+href="(http:\/\/tinyurl\.com\/.+?)"/is', $rs, $matches)) {
					$result = $matches[1][0];
				}
				break;

			default:
				$rs = self::event('OnShortUrl', array($url, $type));
				if(!empty($rs)) {
					$result = $rs[0];
				}
				break;
		}
		return static::$_short[$url.$type] = $result;
	}

	public static function getTypes()
	{
		$result = array(
			'' => '#NOT_URLSHORTENER# (#Default#)',
			'clck.ru' => 'clck.ru - Yandex',
			// 'goo.gl' => 'goo.gl - Google',
			'u.to' => 'u.to - UCoz',
			'urlid.ru' => 'urlid.ru - UrlId',
			'tinyurl.com' => 'tinyurl.com - TinyURL',
		);
		$rs = self::event('OnShortUrlType', array());
		if(!empty($rs) && is_array($rs)) {
			foreach((array)$rs as $type) {
				reset($type);
				$result[key($type)] = current($type);
			}
		}
		return $result;
	}
}
