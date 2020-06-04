<?php
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
if (($apikey = $data->get('antigate_apikey', '')) != '') {
    $balance = vettich\SP\Antigate::getBalance($apikey);
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
                    ),
                    'default_value' => 'hit',
                ),
                '_method_post_cloud_cron_id' => 'hidden',
                '_is_fix_errors' => 'checkbox:#VCH_IS_FIX_ERRORS#:N:help=#VCH_IS_FIX_ERRORS_HELP#',
                '_show_menu_items_one' => 'checkbox:#VCH_SHOW_MENU_ITEMS_ONE#:Y',
                '_is_ajax_interval' => 'checkbox:#VCH_IS_AJAX_INTERVAL#:N:help=#VCH_IS_AJAX_INTERVAL_HELP#',
                '_show_promo_v3' => 'checkbox:#VCH_SHOW_PROMO_V3#:Y:help=#VCH_SHOW_PROMO_V3_HELP#',
            ),
        ),
        array(
            'name' => '#TAB_ANTIGATE#',
            'title' => '#TAB_ANTIGATE_TITLE#',
            'params' => array(
                '_antigate_apikey' => 'text:#ANTIGATE_APIKEY#:help=#ANTIGATE_APIKEY_HELP#:params=[placeholder=#ANTIGATE_APIKEY_PLACEHOLDER#]',
                '_antigate_balance' => empty($balance)? 'plaintext:#ANTIGATE_BALANCE#:#ANTIGATE_BALANCE_NEED_APIKEY#': 'plaintext:#ANTIGATE_BALANCE#:$ '.$balance,
                'antigate_help' => 'note:#ANTIGATE_APIKEY_HELP#',
            ),
        ),
    ),
    'data' => $data,
    'buttons' => array(
        'save' => 'buttons.saveSubmit:#VDF_SAVE#',
    ),
)))->render();

if ($_REQUEST['ajax'] == 'Y') {
    exit;
}
