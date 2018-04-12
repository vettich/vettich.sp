<?
// старт сессии необходим библиотеке
if(!session_id()) {
	session_start();
}
require_once dirname(__FILE__).'/../classes/Facebook/autoload.php';

// App ID и App Secret из настроек приложения
$app_id = "";
$app_secret = "";

if(isset($_GET['app_id'])) {
	$app_id = trim($_GET['app_id']);
}
if(isset($_GET['app_secret'])) {
	$app_secret = trim($_GET['app_secret']);
}
if(isset($_GET['scope'])) {
	$scope = trim($_GET['scope']);
	$scope = explode(',', $scope);
}
// ссылка на страницу возврата после авторизации
// домен должен совпадать с указанным в настройках приложения
// $callback = $_SERVER['SERVER_NAME'].'/bitrix/admin/vettich.sp.fb_callback.php';
$callback = $_SERVER['SERVER_NAME'].'/bitrix/admin/vettich.sp.fb_callback.php?app_id='.$app_id.'&app_secret='.$app_secret;
if($_SERVER['HTTPS'] or true) {
	$callback = 'https://'.$callback;
} else {
	$callback = 'http://'.$callback;
}


$fb = new Facebook\Facebook([
    'app_id'  => $app_id,
    'app_secret' => $app_secret,
    'default_graph_version' => 'v2.9',
]);

$helper = $fb->getRedirectLoginHelper();

// $scope = ['publish_actions','manage_pages','publish_pages','email'];
$loginUrl = $helper->getLoginUrl($callback, $scope);

header('Location: '.($loginUrl));
session_write_close();
