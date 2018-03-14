<?
$file = substr(basename($_SERVER['SCRIPT_NAME']), 11/*strlen(vettich.sp.)*/);
$dir = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/vettich.sp/admin/';
if(!file_exists($dir.$file)) {
	$dir = $_SERVER['DOCUMENT_ROOT'].'/local/modules/vettich.sp/admin/';
}
require $dir.$file;
