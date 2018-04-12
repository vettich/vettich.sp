<?php
// THIS FILE - DOCUMENT_ROOT/bitrix/modules/vettich.sp/cron/agent-2.0.php
$MODULE_ROOT = dirname(__DIR__);
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'] = dirname(dirname(dirname($MODULE_ROOT)));

define('NO_KEEP_STATISTIC', true); 
define('NOT_CHECK_PERMISSIONS', true); 
define('VCH_APP_CRON', true); 

set_time_limit(0); 
require($DOCUMENT_ROOT.'/bitrix/modules/main/include/prolog_before.php'); 

CModule::IncludeModule('vettich.sp');

use Vettich\SP\Module;
use Vettich\SP\Pending;

if(Module::GetOptionString('is_enable', 'Y') == 'Y'
	and Module::GetOptionString('method_post', 'hit') == 'cron') {
	var_dump(Pending::checkPublish('cron'));
}
echo date('d.m.Y H:i:s')." done.\n";
?>