<?
IncludeModuleLangFile(__FILE__);
use vettich\SP\Module;

if (!CModule::IncludeModule('vettich.sp')) {
	echo "Module \"vettich.sp\" not installed.<br/>";
	return;
}

if (!CModule::IncludeModule('vettich.devform')) {
	echo "Module \"vettich.devform\" not installed.<br/>";
	return;
}

$data = new vettich\devform\data\coption(array(
	'module_id' => 'vettich.sp',
	'prefix' => '_',
));
if(($apikey = $data->get('antigate_apikey', '')) != '') {
	$balance = vettich\SP\Antigate::getBalance($apikey);
}
if(!empty($_POST)) {
	if($_POST['_method_post'] == 'cloud_cron' && $data->get('_method_post', 'hit') != 'cloud_cron') {
		$id = Module::curlGet('http://cron.vettich.ru/cron.php?method=save&url='.Module::cloudCronUrl());
		$_POST['_method_post_cloud_cron_id'] = $id;
	} elseif($_POST['_method_post'] != 'cloud_cron' && $data->get('_method_post', 'hit') == 'cloud_cron') {
		$_POST['_method_post_cloud_cron_id'] = '';
		$id = Module::curlGet('http://cron.vettich.ru/cron.php?method=remove&id='.$data->get('_method_post_cloud_cron_id', ''));
	}
}

if(!(($is_period = $_POST['_QUEUE_IS_PERIOD']) or ($is_period = $data->get('_QUEUE_IS_PERIOD')))) {
	$is_period = 'N';
}
if(!(($every = $_POST['_QUEUE_EVERY']) or ($every = $data->get('_QUEUE_EVERY')))) {
	$every = 'DAY';
}
// if(!(($queue_mode = $_POST['_QUEUE_MODE']) or ($queue_mode = $data->get('_QUEUE_MODE')))) {
// 	$queue_mode = 'CONSISTENTLY';
// }

function _getMonthDays() {
	$result = array();
	for($i=1; $i<=31; $i++) {
		$result[$i] = $i;
	}
	return $result;
}
$period_values = array(
	'00:00' => '00:00',
	'01:00' => '01:00',
	'02:00' => '02:00',
	'03:00' => '03:00',
	'04:00' => '04:00',
	'05:00' => '05:00',
	'06:00' => '06:00',
	'07:00' => '07:00',
	'08:00' => '08:00',
	'09:00' => '09:00',
	'10:00' => '10:00',
	'11:00' => '11:00',
	'12:00' => '12:00',
	'13:00' => '13:00',
	'14:00' => '14:00',
	'15:00' => '15:00',
	'16:00' => '16:00',
	'17:00' => '17:00',
	'18:00' => '18:00',
	'19:00' => '19:00',
	'20:00' => '20:00',
	'21:00' => '21:00',
	'22:00' => '22:00',
	'23:00' => '23:00',
);

$nextPublishDatetime = false;
if($nextPublishDatetime = $data->get('_NEXT_PUBLISH_AT')) {
	$nextPublishDatetime = $nextPublishDatetime->toString();
	$nextPublishDatetime = str_replace(':', '\:', $nextPublishDatetime);
}


/**
 * additional social networks
 */
$asnParams = array();
$asnParams['asn_note'] = 'note:#VETTICH_SP_ASN_NOTE#';

$socialNetworks = array(
	array(
		'mid' => 'vettich.sppinterest',
		'id' => 'pinterest',
		'isFree' => false,
	),
	array(
		'mid' => 'vettich.spok',
		'id' => 'ok',
		'isFree' => false,
	),
	array(
		'mid' => 'vettich.spmymailru',
		'id' => 'mymailru',
		'isFree' => false,
	),
	array(
		'mid' => 'vettich.spinstagram',
		'id' => 'instagram',
		'isFree' => false,
	),
);
foreach($socialNetworks as $network) {
	if(IsModuleInstalled($network['mid'])) {
		$asnParams['asn_'.$network['mid']] = array(
			'type' => 'plaintext',
			'title' => "#VETTICH_SP_ASN_$network[id]#",
			'value' => GetMessage('VETTICH_SP_ASN_INSTALLED', array('#mid#' => $network['mid'], '#sessid#' => bitrix_sessid())),
		);
	} else {
		if($network['isFree']) {
			$txt = GetMessage('VETTICH_SP_ASN_INSTALL_FREE', array('#mid#' => $network['mid']));
		} else {
			$txt = GetMessage('VETTICH_SP_ASN_INSTALL', array('#mid#' => $network['mid']));
		}
		$asnParams['asn_'.$network['mid']] = array(
			'type' => 'plaintext',
			'title' => "#VETTICH_SP_ASN_$network[id]#",
			'value' => $txt,
		);
	}
}


(new vettich\devform\AdminForm('options', array(
	'tabs' => array(
		array(
			'name' => '#GENERAL#',
			'title' => '#GENERAL_SETTINGS#',
			'params' => array(
				'_is_enable' => 'checkbox:#VAP_IS_ENABLE#:Y',
				'_is_ajax_enable' => 'hidden:#IS_AJAX_ENABLE#:N',
				'_show_accounts' => array(
					'type' => 'checkbox',
					'title' => '#VCH_SHOW_ACCOUNTS#',
					'help' => '#VCH_SHOW_ACCOUNTS_HELP#',
					'options' => Vettich\SP\Module::socialsKeysWithName(),
					'multiple' => true,
				),
				'_urlshortener' => array(
					'type' => 'select',
					'title' => '#UrlShortener#',
					'help' => '#UrlShortener help#',
					'options' => vettich\SP\UrlShortener::getTypes(),
					'default_value' => '',
				),
				'_method_post' => array(
					'type' => 'select',
					'title' => '#VCH_METHOD_POST#',
					'help' => '#VCH_METHOD_POST_HELP#',
					'options' => array(
						'hit' => '#VCH_METHOD_POST_HIT#',
						'cron' => '#VCH_METHOD_POST_CRON#',
						'cloud_cron' => '#VCH_METHOD_POST_CLOUD_CRON#',
					),
					'default_value' => 'hit',
				),
				'method_post_cron' => 'note:#VCH_METHOD_POST_CRON_HELP#'.($data->get('_method_post', 'hit') != 'cron'? ':params=[style=display\:none]':''),
				'_method_post_cloud_cron_id' => 'hidden',
				'_is_fix_errors' => 'checkbox:#VCH_IS_FIX_ERRORS#:N:help=#VCH_IS_FIX_ERRORS_HELP#',
				'_show_menu_items_one' => 'checkbox:#VCH_SHOW_MENU_ITEMS_ONE#:Y',
				'_is_ajax_interval' => 'checkbox:#VCH_IS_AJAX_INTERVAL#:N:help=#VCH_IS_AJAX_INTERVAL_HELP#',
			),
		),
/*		array(
			'name' => '#TAB_LOGS#',
			'title' => '#TAB_LOGS_TITLE#',
			'params' => array(
				'is_enable_logs' => 'checkbox:#IS_ENABLE_LOGS#',
			),
		),*/
		array(
			'name' => '#TAB_ANTIGATE#',
			'title' => '#TAB_ANTIGATE_TITLE#',
			'params' => array(
				'_antigate_apikey' => 'text:#ANTIGATE_APIKEY#:help=#ANTIGATE_APIKEY_HELP#:params=[placeholder=#ANTIGATE_APIKEY_PLACEHOLDER#]',
				'_antigate_balance' => empty($balance)? 'plaintext:#ANTIGATE_BALANCE#:#ANTIGATE_BALANCE_NEED_APIKEY#': 'plaintext:#ANTIGATE_BALANCE#:$ '.$balance,
				'antigate_help' => 'note:#ANTIGATE_APIKEY_HELP#',
			),
		),
		array(
			'name' => '#TAB_ASN#',
			'title' => '#TAB_ASN_TITLE#',
			'params' => $asnParams,
		),
		/*array(
			'name' => 'Единая очередь',
			'title' => 'Настройки единой очереди',
			'params' => array(
				'_QUEUE_ENABLE' => 'checkbox:Единая очередь активна:Y',
				'_QUEUE_INTERVAL' => 'number:#VAP_PENDING_INTERVAL#:120:help=#VAP_PENDING_INTERVAL_HELP#:params=[min=1]',
				'_QUEUE_IS_PERIOD' => 'checkbox:#VAP_PENDING_IS_PERIOD#:refresh=Y:help=#VAP_PENDING_IS_PERIOD_HELP#',
				'_QUEUE_PERIOD_FROM' => $is_period!='Y' ? 'hidden' : array(
					'type' => 'select',
					'title' => '#VAP_PENDING_PERIOD_FROM#',
					'help' => '#VAP_PENDING_PERIOD_FROM_HELP#',
					'default_value' => '06:00',
					'options' => $period_values,
				),
				'_QUEUE_PERIOD_TO' => $is_period!='Y' ? 'hidden' : array(
					'type' => 'select',
					'title' => '#VAP_PENDING_PERIOD_TO#',
					'help' => '#VAP_PENDING_PERIOD_TO_HELP#',
					'default_value' => '23:00',
					'options' => $period_values,
				),
				'_QUEUE_EVERY' => !$_date != 'none' ? 'hidden' : array(
					'type' => 'select',
					'title' => '#VAP_PENDING_EVERY#',
					'help' => '#VAP_PENDING_EVERY_HELP#',
					'default_value' => 'DAY',
					'params' => array('onchange' => 'Vettich.Devform.Refresh(this);'),
					'options' => array(
						'DAY' => '#VAP_PENDING_DAY#',
						'WEEK' => '#VAP_PENDING_WEEK#',
						'MONTH' => '#VAP_PENDING_MONTH#'
					),
				),
				'_QUEUE_WEEK' => $every != 'WEEK' ? 'hidden' : array(
					'type' => 'checkbox',
					'title' => '#VAP_PENDING_DAYS#',
					'help' => '#VAP_PENDING_DAYS_HELP#',
					'multiple' => true,
					'default_value' => array(Module::getWeekName(date('w'))),
					'options' => array(
						'monday' => '#VAP_DAY_MONDAY#',
						'tuesday' => '#VAP_DAY_TUESDAY#',
						'wednesday' => '#VAP_DAY_WEDNESDAY#',
						'thursday' => '#VAP_DAY_THURSDAY#',
						'friday' => '#VAP_DAY_FRIDAY#',
						'saturday' => '#VAP_DAY_SATURDAY#',
						'sunday' => '#VAP_DAY_SUNDAY#',
					),
				),
				'_QUEUE_MONTH' => $every != 'MONTH' ? 'hidden' : array(
					'type' => 'checkbox',
					'title' => '#VAP_PENDING_MONTH2#',
					'help' => '',
					'multiple' => true,
					'default_value' => array(date('d')),
					'options' => _getMonthDays(),
				),
				'nextPublishDatetime' => !$nextPublishDatetime ? 'hidden' : 'plaintext:#VAP_NEXT_PUBLISH_DATETIME#:'.$nextPublishDatetime,
				'nextPublishDatetimeReset' => !$nextPublishDatetime ? 'hidden' : 'divbutton::#VAP_NEXT_PUBLISH_DATETIME_RESET#:onclick=Vettich.SP.UpdateNextPublishAt();',
			),
		),*/
	),
	'data' => $data,
	'buttons' => array(
		'save' => 'buttons.saveSubmit:#VDF_SAVE#',
	),
)))->render();
?>
<script>
	jQuery('#_method_post').change(function() {
		a = jQuery(this);
		if(jQuery(this).val() == 'cron') {
			jQuery('#method_post_cron').css('display', 'block');
		} else {
			jQuery('#method_post_cron').css('display', 'none');
		}
	});
</script>
<?
if($_REQUEST['ajax'] == 'Y') {
	exit;
}
