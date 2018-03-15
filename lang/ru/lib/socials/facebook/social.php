<?

$MESS['VETTICH_SP_POST_NAME_facebook'] = 'Facebook';
$MESS['VETTICH_SP_FB_GROUP_ID'] = 'ID группы/страницы';
$MESS['VETTICH_SP_FB_GROUP_ID_PLACEHOLDER'] = 'Сначала получите аccess token';
$MESS['VETTICH_SP_FB_APP_ID'] = 'ID приложения';
$MESS['VETTICH_SP_FB_APP_SECRET'] = 'Секретный код';
$MESS['VETTICH_SP_FB_ACCESS_TOKEN'] = 'Access token';
$MESS['VETTICH_SP_FB_ACCESS_TOKEN_PLACEHOLDER'] = 'Нажмите на Получить аccess token';
$MESS['VETTICH_SP_FB_CUSTOM_APP'] = 'Использовать кастомное приложение';
$MESS['VETTICH_SP_FB_GET_ACCESS_TOKEN'] = 'Получить аccess token';
$MESS['VETTICH_SP_FB_HELP_CUSTOM_APP'] = 'Чтобы использовать собственное приложение в ФБ, его необходимо настроить.<br>
	<ol>
		<li>Перейдите в список приложений: <a href="https://developers.facebook.com/apps/" target="_blank" title="В новой вкладке">https://developers.facebook.com/apps/</a><br></li>
		<li>Создайте новое приложение. Если у вас уже есть созданное приложение, то перейдите в его настройки.</li>
		<li>Добавьте продукт "Вход через Facebook".</li>
		<li>В поле "Действительные URL-адреса..." введите адрес:
			<b>'.vettich\SP\Module::siteUrl().'/bitrix/admin/vettich.sp.fb_callback.php?app_id=<span id="fb-cb-app_id"></span>&app_secret=<span id="fb-cb-app_secret"></span></b></li>
		<li>А так же установите галочку в поле "Использовать строгий режим для URI перенаправления"</li>
		<li>Перейдите в пункт Настройки -> Основное.</li>
		<li>Введите вашу эл. почту в поле "Эл. адрес для связи".</li>
		<li>Скопируйте Идентификатор и Секрет приложения в соответствующие поля на этой странице.</li>
		<li>Перейдите в пункт "Проверка приложения".</li>
		<li>Выставьте галочку в параметре "Сделать приложение ... доступным для всех".</li>
		<li>Последний шаг, нажимаете на этой странице Получить access token.</li>
	</ol>';
$MESS['VETTICH_SP_FB_HELP'] = 'Чтобы заполнить все поля, достаточно нажать на кнопку Получить access token, после чего подтвердить права, затем выбрать нужную вам группу.
	Либо Вы можете использовать собственное (кастомное) приложение, отметив соответствующую галочку. Тогда Вам будет предложена другая инструкция.';
