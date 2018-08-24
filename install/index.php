<?
IncludeModuleLangFile(__FILE__);
class vettich_sp extends CModule
{
	const MODULE_ID = 'vettich.sp';
	var $MODULE_ID = 'vettich.sp';
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = 'Y';
	var $MODULE_ROOT_DIR = '';

	function vettich_sp()
	{
		$arModuleVersion = array();
		include(__DIR__.'/version.php');
		if(is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)){
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}
		$this->MODULE_ROOT_DIR = dirname(__DIR__);
		$this->MODULE_NAME = GetMessage('VETTICH_SP_MODULE_NAME');
		$this->MODULE_DESCRIPTION = GetMessage('VETTICH_SP_MODULE_DESCRIPTION');
		$this->PARTNER_NAME = GetMessage('VETTICH_SP_PARTNER_NAME'); 
		$this->PARTNER_URI = GetMessage('VETTICH_SP_PARTNER_URI');
	}

	function DoInstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $errors, $ver, $GLOBALS;
		$GLOBALS['CACHE_MANAGER']->CleanAll();
		$this->InstallDevform();
		if($this->InstallDB()
			&& $this->InstallFiles()
			&& $this->InstallEvents()) {
			RegisterModule(self::MODULE_ID);
			$APPLICATION->IncludeAdminFile(GetMessage('VETTICH_SP_INSTALL_TITLE'), $this->MODULE_ROOT_DIR.'/install/step1.php');
			return true;
		}
		return false;
	}

	function InstallDevform()
	{
		if(CModule::IncludeModule('vettich.devform')) {
			return;
		}
		if(!file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/vettich.devform/install/index.php')) {
			CopyDirFiles($this->MODULE_ROOT_DIR.'/install/bitrix/modules',$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules', true, true);
		}
		include $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/vettich.devform/install/index.php';
		if(class_exists('vettich_devform')) {
			$cl = new vettich_devform();
			if(!$cl->IsInstalled()) {
				$cl->DoInstall();
			}
		}
	}

	function DoUninstall(){
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = IntVal($step);
		if($step<2) {
			$APPLICATION->IncludeAdminFile(GetMessage('VETTICH_SP_UNINSTALL_TITLE'), $this->MODULE_ROOT_DIR.'/install/unstep1.php');
		} elseif($step==2) {
			if($this->UnInstallDB(array(
					'savedata' => $_REQUEST['savedata'],
				))
				&& $this->UnInstallFiles()
				&& $this->UnInstallEvents()) {
				UnRegisterModule(self::MODULE_ID);
				return true;
			}
			return false;
		}
	}

	function InstallDB($arModuleParams = array())
	{
		$lib = $this->MODULE_ROOT_DIR.'/lib';
		include $lib.'/db/dbase.php';
		include $lib.'/db/post.php';
		if(!Vettich\SP\db\postTable::createTable()) {
			return false;
		}

		include $lib.'/db/postaccount.php';
		if(!Vettich\SP\db\postAccountTable::createTable()) {
			return false;
		}
		include $lib.'/db/pending.php';
		if(!Vettich\SP\db\pendingTable::createTable()) {
			return false;
		}

		$def_options = array(
			// posts
			'is_enable' => 'Y',
			// facebook
			'is_fb_enable' => 'Y',
			// twitter
			'is_twitter_enable' => 'Y',
			// vk
			'is_vk_enable' => 'Y',
		);
		foreach($def_options as $k => $v) {
			COption::SetOptionString(self::MODULE_ID, $k, $v);
		}
		return true;
	}

	function UnInstallDB($arParams = array())
	{
		COption::RemoveOption(self::MODULE_ID);
		if (!$arParams['savedata'] && \CModule::IncludeModule(self::MODULE_ID)) {
			if(!vettich\sp\db\postTable::dropTable()) {
				return false;
			}
			if(!vettich\sp\db\postAccountTable::dropTable()) {
				return false;
			}
			if(!vettich\sp\db\pendingTable::dropTable()) {
				return false;
			}
		}

		return true;
	}

	function InstallEvents()
	{
		RegisterModuleDependences('main', 'OnAdminListDisplay', 'vettich.sp', '\Vettich\SP\Events', 'OnAdminListDisplayHandler');
		RegisterModuleDependences('main', 'OnBeforeProlog', 'vettich.sp', '\Vettich\SP\Events', 'OnBeforePrologHandler');
		RegisterModuleDependences('iblock', 'OnAfterIblockElementAdd', 'vettich.sp', '\Vettich\SP\Events', 'OnAfterIblockElementAdd');
		RegisterModuleDependences('iblock', 'OnAfterIBlockElementUpdate', 'vettich.sp', '\Vettich\SP\Events', 'OnAfterIBlockElementUpdate');
		RegisterModuleDependences('iblock', 'OnAfterIBlockElementDelete', 'vettich.sp', '\Vettich\SP\Events', 'OnAfterIBlockElementDelete');
		// RegisterModuleDependences('catalog', 'OnProductAdd', 'vettich.sp', '\Vettich\SP\Posting', 'OnProductAdd');

		return true;
	}

	function UnInstallEvents()
	{
		UnRegisterModuleDependences('main', 'OnAdminListDisplay', 'vettich.sp', '\Vettich\SP\Events', 'OnAdminListDisplayHandler');
		UnRegisterModuleDependences('main', 'OnBeforeProlog', 'vettich.sp', '\Vettich\SP\Events', 'OnBeforePrologHandler');
		UnRegisterModuleDependences('iblock', 'OnAfterIblockElementAdd', 'vettich.sp', '\Vettich\SP\Events', 'OnAfterIblockElementAdd');
		UnRegisterModuleDependences('iblock', 'OnAfterIBlockElementUpdate', 'vettich.sp', '\Vettich\SP\Events', 'OnAfterIBlockElementUpdate');
		UnRegisterModuleDependences('iblock', 'OnAfterIBlockElementDelete', 'vettich.sp', '\Vettich\SP\Events', 'OnAfterIBlockElementDelete');
		// UnRegisterModuleDependences('catalog', 'OnProductAdd', 'vettich.sp', '\Vettich\SP\Posting', 'OnProductAdd');

		return true;
	}

	function InstallFiles()
	{
		CopyDirFiles($this->MODULE_ROOT_DIR.'/install/bitrix',$_SERVER['DOCUMENT_ROOT'].'/bitrix', true, true);
		return true;
	}

	function UnInstallFiles()
	{
		DeleteDirFiles($this->MODULE_ROOT_DIR.'/install/bitrix/admin', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin');
		DeleteDirFiles($this->MODULE_ROOT_DIR.'/install/bitrix/js/vettich.sp', $_SERVER['DOCUMENT_ROOT'].'/bitrix/js/vettich.sp');
		DeleteDirFiles($this->MODULE_ROOT_DIR.'/install/bitrix/css/vettich.sp', $_SERVER['DOCUMENT_ROOT'].'/bitrix/css/vettich.sp');
		DeleteDirFiles($this->MODULE_ROOT_DIR.'/install/bitrix/images/vettich.sp', $_SERVER['DOCUMENT_ROOT'].'/bitrix/images/vettich.sp');
		return true;
	}
}
?>
