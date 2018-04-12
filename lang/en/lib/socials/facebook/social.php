<?

$MESS['VETTICH_SP_POST_NAME_facebook'] = 'Facebook';
$MESS['VETTICH_SP_FB_GROUP_ID'] = 'Group / page ID';
$MESS['VETTICH_SP_FB_GROUP_ID_PLACEHOLDER'] = 'First, get access token';
$MESS['VETTICH_SP_FB_APP_ID'] = 'App ID';
$MESS['VETTICH_SP_FB_APP_SECRET'] = 'Secret key';
$MESS['VETTICH_SP_FB_ACCESS_TOKEN'] = 'Access token';
$MESS['VETTICH_SP_FB_ACCESS_TOKEN_PLACEHOLDER'] = 'Click on Get access token';
$MESS['VETTICH_SP_FB_CUSTOM_APP'] = 'Use custom application';
$MESS['VETTICH_SP_FB_GET_ACCESS_TOKEN'] = 'Get àccess token';
$MESS['VETTICH_SP_FB_HELP_CUSTOM_APP'] = 'To use your own application in the FB, you need to configure it. <br>
	<ol>
		<li>Before beginning configuration, make sure that Your website works on <b>https</b>, otherwise nothing will happen)</li>
		<li> Go to the list of apps: <a href="https://developers.facebook.com/apps/" target="_blank" title="In the new tab">https://developers.facebook.com/apps/</a> <br> </li>
		<li> Create a new application. If you already have an application created, go to its settings. </li>
		<li> Go to Settings -> Primary. </li>
		<li> Copy the Identifier and Application Secret to the appropriate fields on this page. </li>
		<li> Enter your email address in the "Email for communication" field and save settings. </li>
		<li> Add the product "Login in via Facebook." </li>
		<li> Go to settings-added "Login via Facebook". </li>
		<li> In the "Valid URLs ..." field, enter the address:
			<b>https://'.$_SERVER['SERVER_NAME'].'/bitrix/admin/vettich.sp.fb_callback.php?app_id=<span id="fb-cb-app_id"></span> & app_secret = <span id = "fb- cb-app_secret "> </span> </b> </li>
		<li> And also check the box "Use strict mode for redirect URI" </li>
		<li> Go to "Application Verification." </li>
		<li> Check the box "Make the application ... accessible to all." </li>
		<li> The last step, click on this page Get access token. </li>
	</ol>';
$MESS['VETTICH_SP_FB_HELP'] = 'To fill in all the fields, just click on the Get access token button, then confirm the rights, then select the group you need.
Or you can use your own (custom) application by checking the appropriate checkbox. Then you will be offered another instruction.';
