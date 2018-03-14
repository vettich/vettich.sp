<?
namespace Vettich\SP;
IncludeModuleLangFile(__FILE__);
/*
simple macros:
#MACROS_NAME#
-----------------------------------------------
figure macros (figure block):
{something text... #MACROS_NAME#}
-----------------------------------------------
square macros (square block):
[BLOCK_NAME]
something text... and #MACROS#
[/BLOCK_NAME]

Blocks:
[TABLE #MULTIPLE_FIELD#]
Header1 | Header2 | Header3
#MACROS1# | #MACROS2# | #MACROS3#
[/TABLE]

Result:
Header1		Header2		Header3
Value11		Value21		Value31
Value12		Value22		Value32
-----------------------------------------------
[LIST #MULTIPLE_FIELD#]
Name1: #MACROS1#
Name2: #MACROS2#
[/LIST]

Result:
Name1: Value11
Name2: Value21

Name1: Value12
Name2: Value22
-----------------------------------------------
[COLUMN #MULTIPLE_FIELD#]
Name1: #MACROS1#
Name2: #MACROS2#
[/COLUMN]

Result:
Name1: Value11, Value12
Name2: Value21, Value22

*/
class Post extends Module
{
	public static function getFileNames($arField, $withDocumentRoot=true)
	{
		$arResult = array();
		if(is_array($arField) && $arField['PROPERTY_TYPE'] == 'F') {
			if($arField['MULTIPLE'] == 'Y') {
				foreach((array)$arField['VALUES'] as $k=>$arValue) {
					if($withDocumentRoot) {
						$arResult[] = $_SERVER['DOCUMENT_ROOT'].\CFile::GetPath($arValue);
					} else {
						$arResult[] = \CFile::GetPath($arValue);
					}
				}
			} elseif($withDocumentRoot) {
				$arResult[] = $_SERVER['DOCUMENT_ROOT'].\CFile::GetPath($arField['VALUE']);
			} else {
				$arResult[] = \CFile::GetPath($arField['VALUE']);
			}
		} else {
			$img_path = \CFile::GetPath($arField);
			if($img_path != '') {
				if($withDocumentRoot) {
					$arResult[] = $_SERVER['DOCUMENT_ROOT'].$img_path;
				} else {
					$arResult[] = $img_path;
				}
			}
		}
		return $arResult;
	}

	function mbStringToArray ($string) {
	    $strlen = self::mb_strlen($string);
	    while ($strlen) {
	        $array[] = self::mb_substr($string,0,1,"UTF-8");
	        $string = self::mb_substr($string,1,$strlen,"UTF-8");
	        $strlen = self::mb_strlen($string);
	    }
	    return $array;
	}

	public static function createLink($slink, $arPost)
	{
		$link = $arPost['PROTOCOL'];
		if(empty($arPost['DOMAIN'])) {
			$arPost['DOMAIN'] = $_SERVER['SERVER_NAME'].SITE_DIR;
		}
		if(empty($link)) {
			$link = $arPost['DOMAIN'];
			if(strpos($arPost['DOMAIN'], 'http') !== 0) {
				if($_SERVER['HTTPS']) {
					$link = 'https://'.$link;
				} else {
					$link = 'http://'.$link;
				}
			}
		} else {
			$link .= '://';
			if(strpos($arPost['DOMAIN'], 'https://') === 0) {
				$link .= self::mb_substr($arPost['DOMAIN'], 8);
			} elseif(strpos($arPost['DOMAIN'], 'http://') === 0) {
				$link .= self::mb_substr($arPost['DOMAIN'], 7);
			} else {
				$link .= $arPost['DOMAIN'];
			}
		}
		if($link[self::mb_strlen($link)-1] != '/') {
			$link .= '/';
		}
		if(strpos($slink, '/') === 0) {
			$link .= self::mb_substr($slink, 1);
		} else {
			$link .= $slink;
		}
		if(!empty($arPost['URL_PARAMS'])) {
			$link .= (strpos($link, '?') !== false) ? '&' : '?';
			// $rep = array(
			// 	'{social_id}' => static::$socialid,
			// );
			// $link .= str_replace(array_keys($rep), array_values($rep), $arPost['URL_PARAMS']);
			$link .= self::macrosReplace($arPost['URL_PARAMS'], array(), $arPost);
		}
		if(($shortType = self::GetOptionString('urlshortener', '')) != '') {
			$link = UrlShortener::short($link, $shortType);
		}
		return $link;
	}

	public static function prepareIblockElem($id)
	{
		if(\CModule::IncludeModule('iblock')) {
			$rs = \CIBlockElement::GetByID($id);
			if($rs && $arFields = $rs->GetNext()) {
				self::iblockValueFill($arFields);
				return $arFields;
			}
		}
	}

	public static function macrosParse($text)
	{
		// $len = self::mb_strlen($text);
		$startStack = array();
		$macros_start = -1;
		$macros = array();
		$statusStack = array();
		$status = 'simple';
		$stext = static::mbStringToArray($text);
		$i = 0;
		// for (; $i<$len; $i++) {
		foreach((array)$stext as $ch) {
			// $ch = $text[$i];
			if($status == 'simple') {
				if($ch == '#') {
					if($macros_start < 0) {
						array_push($startStack, $macros_start);
						$macros_start = $i;
					} else {
						$macro = self::mb_substr($text, $macros_start, $i - $macros_start + 1);
						$macros['simple'][$macro] = '';
						$macros_start = array_pop($startStack) ?: -1;
						$status = array_pop($statusStack) ?: 'simple';
					}
				} elseif($ch == '{') { // begin figure block
					array_push($statusStack, $status);
					$status = 'figureblock';
					array_push($startStack, $macros_start);
					$macros_start = $i;
				} elseif($ch == '[') { // begin square block
					array_push($statusStack, $status);
					$status = 'squareblock';
					array_push($startStack, $macros_start);
					$macros_start = $i;
				} elseif(ctype_space($ch) && $macros_start >= 0) {
					$macros_start = array_pop($startStack) ?: -1;
				}
			} elseif($status == 'figureblock') {
				if($ch == '}') { // end figure block
					$macro = self::mb_substr($text, $macros_start, $i - $macros_start + 1);
					$macros['figure'][$macro] = '';
					$macros_start = array_pop($startStack) ?: -1;
					$status = array_pop($statusStack) ?: 'simple';
				}
			} elseif($status == 'squareblock') {
				if($ch == ']') {
					$status = 'squareblock2';
				}
			} elseif($status == 'squareblock2') {
				if($ch == ']') {
					$macro = self::mb_substr($text, $macros_start, $i - $macros_start + 1);
					$macros['square'][$macro] = '';
					$macros_start = array_pop($startStack) ?: -1;
					$status = array_pop($statusStack) ?: 'simple';
				}
			}
			$i++;
		}
		return $macros;
	}

	public static function macroExplode($macro)
	{
		$tmp = self::mb_substr($macro, 1, -1);
		return explode('.', $tmp);
	}

	public static function macroValue($macro, $arFields, $arPost, $isCreateLink=true)
	{
		if(!is_array($macro)) {
			$macro = array($macro);
		}
		if($macro[0] == 'SOCIAL_ID') {
			return isset(static::$socialid) ? static::$socialid : '';
		} elseif($macro[0] == 'SOCIAL_NAME') {
			return isset(static::$socialid) ? static::name(static::$socialid) : '';
		} elseif(!isset($arFields[$macro[0]])) {
			return '';
		}
		if(strpos($macro[0], 'PROPERTY_') === 0) {
			$k = isset($macro[1]) ? $macro[1] : 'VALUE';
			if($arFields[$macro[0]]['PROPERTY_TYPE'] == 'S') {
				/**
				 * @todo  доделать типы для USER_TYPE
				 */
				if($arFields[$macro[0]]['MULTIPLE'] == 'Y') {
					return implode(', ', $arFields[$macro[0]]['VALUES']);
				} else {
					return $arFields[$macro[0]]['VALUE'];
				}
			} elseif($arFields[$macro[0]]['PROPERTY_TYPE'] == 'N') {
				if($arFields[$macro[0]]['MULTIPLE'] == 'Y') {
					return implode(', ', $arFields[$macro[0]]['VALUES']);
				} else {
					return $arFields[$macro[0]]['VALUE'];
				}
			} elseif($arFields[$macro[0]]['PROPERTY_TYPE'] == 'L') {
				if($arFields[$macro[0]]['MULTIPLE'] == 'Y') {
					return implode(', ', $arFields[$macro[0]]['VALUES_XML_ID']);
				}
				return $arFields[$macro[0]]['VALUE_ENUM'];
			} elseif($arFields[$macro[0]]['PROPERTY_TYPE'] == 'E'
				or $arFields[$macro[0]]['PROPERTY_TYPE'] == 'G') {
				if(!empty($macro[1]) && $macro[1] == 'REF') {
					$id = $arFields[$macro[0]]['VALUES'] ?: $arFields[$macro[0]]['VALUE'];
					$iblockId = $arFields[$macro[0]]['LINK_IBLOCK_ID'];
					if($arFields[$macro[0]]['PROPERTY_TYPE'] == 'E') {
						$elems = self::iblockElemId($id, $iblockId);
					} else {
						$elems = self::iblockSection($id, $iblockId);
					}
					if($arFields[$macro[0]]['MULTIPLE'] == 'Y') {
						$result = array();
						foreach((array)$elems as $ar) {
							$result[] = self::macroValue(array_slice($macro, 2), $ar, $arPost, $isCreateLink);
						}
						return implode(', ', $result);
					} else {
						return self::macroValue(array_slice($macro, 2), $elems, $arPost, $isCreateLink);
					}
				} elseif(!empty($macro[1]) && isset($arFields[$macro[0]][$macro[1]])) {
					return $arFields[$macro[0]][$macro[1]];
				} else {
					return $arFields[$macro[0]]['VALUES'] ? 
						implode(', ', $arFields[$macro[0]]['VALUES'])
						: $arFields[$macro[0]]['VALUE'];
				}
			} elseif($arFields[$macro[0]]['PROPERTY_TYPE'] == 'F') {
				if($k == 'VALUE') {
					if($arFields[$macro[0]]['MULTIPLE'] == 'Y') {
						$res = array();
						foreach((array)$arFields[$macro[0]]['VALUES'] as $val) {
							if($isCreateLink) {
								$res[] = self::createLink(\CFile::GetPath($val), $arPost);
							} else {
								$res[] = \CFile::GetPath($val);
							}
						}
						return implode(' ', $res);
					}
					if($isCreateLink) {
						return self::createLink(\CFile::GetPath($arFields[$macro[0]]['VALUE']), $arPost);
					} else {
						return \CFile::GetPath($arFields[$macro[0]]['VALUE']);
					}
				} else {
					return $arFields[$macro[0]][$k];
				}
			} else {
				return $arFields[$macro[0]][$k];
			}
		} elseif(strpos($macro[0], 'CATALOG_') === 0) {
			return $arFields[$macro[0]];
		} elseif(strpos($macro[0], 'UF_') === 0) {
			return $arFields[$macro[0]];
		} elseif($macro[0] == 'DETAIL_PAGE_URL' or $macro[0] == 'LIST_PAGE_URL') {
			return self::createLink($arFields[$macro[0]], $arPost);
		} elseif($macro[0] == 'DETAIL_PICTURE' or $macro[0] == 'PREVIEW_PICTURE') {
			$files = self::getFileNames($arFields[$macro[0]], false);
			if(!empty($files)) {
				if($isCreateLink) {
					return self::createLink($files[0], $arPost);
				} else {
					return $files[0];
				}
			}
			return $arFields[$macro[0]];
		} else {
			$mac = '';
			foreach((array)$macro as $m) {
				if(!empty($m)) {
					$mac .= "['$m']";
				}
			}
			$field = '';
			eval('$field = $arFields'.$mac.';');
			return $field;
			// return $arFields[$macro[0]];
		}
	}

	public static function macrosReplace($text, $arFields, $arPost, $isCreateLink=true, $isEmptyReplace=true)
	{
		$result = $text;
		$macros = self::macrosParse($text);
		if(isset($macros['square'])) foreach((array)$macros['square'] as $k => $v) {
			$t = self::blockReplace($k, $arFields, $arPost, $isCreateLink);
			$macros['square'][$k] = $t;
		}
		$result = str_replace(array_keys($macros['square']), array_values($macros['square']), $result);
		if(isset($macros['figure'])) foreach((array)$macros['figure'] as $k => $v) {
			$t = self::macrosReplace(self::mb_substr($k, 1, -1), $arFields, $arPost, $isCreateLink, false);
			if(strpos($t, '#') === false) {
				$macros['figure'][$k] = str_replace("\n", '#BR#', $t);
			}
		}
		$result = str_replace(array_keys($macros['figure']), array_values($macros['figure']), $result);
		foreach((array)$macros['simple'] as $key => $value) {
			$macro = self::macroExplode($key);
			if(empty($macro[0])) {
				$macro[0] = 'THIS';
			}
			$macros['simple'][$key] = self::macroValue($macro, $arFields, $arPost, $isCreateLink);
			if(!$isEmptyReplace && empty($macros['simple'][$key])) {
				$macros['simple'][$key] = $key;
			}
		}
		if(isset($macros['simple']['#BR#'])) {
			$macros['simple']['#BR#'] = '#BR#';
		}
		$result = str_replace(array_keys($macros['simple']), array_values($macros['simple']), $result);
		$result = str_replace(array("\n", '#BR#'), array('', "\n"), $result);
		return $result;
	}

	public static function blockReplace($text, $arFields, $arPost, $isCreateLink)
	{
		$result = '';
		if(!preg_match(
			// '/(.*)/is',
			'/\[(\w+)\s+([\w\#_\.]+)\](.*)\[\/(\w+)\]/is',
			$text,
			$matches)) {
			return '';
		}
		$macro = self::macroExplode($matches[2]);
		foreach((array)$macro as $m) {
			if(!empty($m)) {
				$mac .= "['$m']";
			}
		}
		$field = '';
		eval('$field = $arFields'.$mac.';');
		switch (strtoupper($matches[1])) {
			case 'LIST':
				$macros = self::macrosParse($matches[3]);
				// $result = array();
				foreach((array)$field as $val) {
					$arFields['THIS'] = $val;
					$result .= str_replace("\n", '#BR#', self::macrosReplace($matches[3], $arFields, $arPost, $isCreateLink));
				}
				// $result = implode('#BR#', $result);
				break;

			case 'TABLE':
				$txt = trim($matches[3]);
				$txt = explode("\n", $txt);
				if(!isset($txt[1])) {
					break;
				}
				$headers = explode('|', $txt[0]);
				$body = explode('|', $txt[1]);
				$columnsWidth = array();
				foreach((array)$headers as $head) {
					$val = trim(self::macrosReplace($head, $arFields, $arPost, $isCreateLink));
					$columnsWidth[] = self::mb_strlen($val);
					$headerValues[] = $val;
				}
				$len = count($headerValues);
				$j = 0;
				foreach((array)$field as $f) {
					$arFields['THIS'] = $f;
					for($i=0; $i < $len; $i++) {
						$val = trim(self::macrosReplace($body[$i], $arFields, $arPost, $isCreateLink));
						if($columnsWidth[$i] < self::mb_strlen($val)) {
							$columnsWidth[$i] = self::mb_strlen($val);
						}
						$bodyValues[$j][$i] = $val;
					}
					$j++;
				}
				$result = '';
				for($i=0; $i < $len; $i++) {
					$result .= $headerValues[$i] . str_repeat(' ', $columnsWidth[$i] + 2 - self::mb_strlen($headerValues[$i]));
				}
				$result .= '#BR#';
				foreach((array)$bodyValues as $body) {
					for($i=0; $i < $len; $i++) {
						$result .= $body[$i] . str_repeat(' ', $columnsWidth[$i] + 2 - self::mb_strlen($body[$i]));
					}
					$result .= '#BR#';
				}
				break;
		}
		return $result;
	}

	public static function cmpFields(&$arFields, $arPost, $conditions=false)
	{
		if($arPost['PUBLISH']['CONDITIONS']['ACTIVE'] == 'Y' && $arFields['ACTIVE'] != 'Y') {
			return false;
		}
		if(!$conditions) {
			$conditions = $arPost['CONDITIONS'];
		}
		if($conditions) foreach((array)$conditions as $cond) {
			// $field = $arFields[$cond['field']];
			$field = self::macroValue($cond['field'], $arFields, $arPost);
			// if($field == null) {
			// 	continue;
			// }
			switch ($cond['cmp']) {
				case '>=':
					if(!($field >= $cond['value'])) {
						return false;
					}
					break;
				case '<=':
					if(!($field <= $cond['value'])) {
						return false;
					}
					break;
				case '=':
				case '==':
					if(!($field == $cond['value'])) {
						return false;
					}
					break;
				case '!=':
					if(!($field != $cond['value'])) {
						return false;
					}
					break;
				case 'include':
					if(strpos($field, $cond['value']) === false
						or (empty($cond['value']) && !empty($field))) {
						return false;
					}
					break;
				case 'notinclude':
					if(strpos($field, $cond['value']) !== false
						or (empty($cond['value']) && empty($field))) {
						return false;
					}
					break;
			}
		}
		return true;
	}

	private static $_accountById = array();
	public static function accountById($id, $refresh=false)
	{
		if(!isset(self::$_accountById[$id]) or $refresh) {
			$db = static::accountsDB();
			$ar = $db::getById($id)->fetch();
			self::$_accountById[$id] = $ar;
		}
		return self::$_accountById[$id];
	}

	public static function accountLinkEdit($acc)
	{
		if(!$acc) {
			return 'unknown';
		}
		$result = '<a href="/bitrix/admin/vettich.sp.post_edit.php?socialid='.$acc['TYPE']
			.'&ID='.$acc['ID']
			.'&back_url='.urlencode($_SERVER['REQUEST_URI'])
			.'">'.$acc['NAME'].'</a>';
		return $result;
	}
}
