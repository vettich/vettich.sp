<?
$file = basename($_SERVER['SCRIPT_NAME']);
$dir = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/vettich.sp/tools/';
if(!file_exists($dir.$file)) {
	$dir = $_SERVER['DOCUMENT_ROOT'].'/local/modules/vettich.sp/tools/';
}
require $dir.$file;
