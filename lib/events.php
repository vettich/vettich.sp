<?
namespace Vettich\SP;
use Bitrix\Main\EventManager;
use vettich\SP\Pending;

/**
 * класс реализующий фукнции хандлеры на системные события,
 * а также создает собственные события - OnGetSocials
 */
class Events extends Module
{
	public function __construct()
	{
		$eventManager = EventManager::getInstance();
		$eventManager->addEventHandler(self::MODULE_ID, "OnGetSocials", array(get_class(), 'OnGetSocials'));
	}

	public static function OnGetSocials()
	{
		$result = array();
		$dir_name = VETTICH_SP_DIR.'/lib/socials/';
		$dir = scandir($dir_name);
		if($dir !== false)
		{
			foreach((array)$dir as $social)
			{
				if($social != '.' && $social != '..' && is_dir($dir_name.$social))
				{
					$cl = '\Vettich\SP\Socials\\'.$social.'\Social';
					if(method_exists($cl, 'OnGetSocial')) {
						$result[] = $cl::OnGetSocial();
					}
				}
			}
		}
		return $result;
	}

	public static function OnAdminListDisplayHandler(&$list)
	{
		$curPage = $GLOBALS['APPLICATION']->GetCurPage();
		if(self::GetOptionString('is_enable', 'Y') == 'Y'
			&& ($curPage == '/bitrix/admin/iblock_element_admin.php'
				or $curPage == '/bitrix/admin/iblock_list_admin.php'
				or $curPage == '/bitrix/admin/iblock_section_admin.php'
				or $curPage == '/bitrix/admin/cat_product_list.php'
				or $curPage == '/bitrix/admin/cat_product_admin.php'
				or $curPage == '/bitrix/admin/cat_section_admin.php'))
		{
			\CJSCore::Init('jquery');
			$list->arActions['VETTICH_SP_IBLOCK_MENU_SEND'] = array(
				'value' => 'VETTICH_SP_IBLOCK_MENU_SEND',
				'name' => GetMessage('VETTICH_SP_IBLOCK_MENU_SEND'),
				'action' => 'Vettich.SP.MenuGroupSend("IBLOCK_ID='.$_GET['IBLOCK_ID'].'");',
				'disable_action_target' => true,
			);
			foreach((array)$list->aRows as $id=>$v)
			{
				$arnewActions = array();
				foreach((array)$v->aActions as $i=>$act)
				{
					if($act['ICON'] == 'delete')
					{
						$qstr = 'IBLOCK_ID='.$v->arRes["IBLOCK_ID"];
						if(substr($v->id, 0, 1) == 'E') {
							$qstr .= '&ELEM_ID='.substr($v->id, 1);
						} elseif(substr($v->id, 0, 1) == 'S') {
							$qstr .= '&SECTION_ID='.substr($v->id, 1);
						} elseif(intval($v->id) > 0) {
							if(strpos($curPage, 'section')) {
								$qstr .= '&SECTION_ID='.$v->id;
							} else {
								$qstr .= '&ELEM_ID='.$v->id;
							}
						}
						if(self::GetOptionString('show_menu_items_one', 'Y') == 'Y') {
							$arnewActions[] = array(
								'GLOBAL_ICON' => 'vap-publish',
								'TEXT' => GetMessage('VETTICH_SP_IBLOCK_MENU_SEND'),
								'MENU' => array(
									array(
										'TEXT' => GetMessage('VETTICH_SP_IBLOCK_MENU_SEND_AUTO'),
										'ACTION' => 'Vettich.SP.MenuSend("'.$qstr.'");',
									),
									array(
										'TEXT' => GetMessage('VETTICH_SP_IBLOCK_MENU_SEND_CUSTOM'),
										'ACTION' => 'Vettich.SP.MenuSendIndividual("'.$qstr.'");',
									),
								),
							);
						} else {
							$arnewActions[] = array(
								'GLOBAL_ICON' => 'vap-publish',
								'TEXT' => GetMessage('VETTICH_SP_IBLOCK_MENU_SEND').': '.GetMessage('VETTICH_SP_IBLOCK_MENU_SEND_AUTO'),
								'ACTION' => 'Vettich.SP.MenuSend("'.$qstr.'");',
							);
							$arnewActions[] = array(
								'GLOBAL_ICON' => 'vap-publish',
								'TEXT' => GetMessage('VETTICH_SP_IBLOCK_MENU_SEND').': '.GetMessage('VETTICH_SP_IBLOCK_MENU_SEND_CUSTOM'),
								'ACTION' => 'Vettich.SP.MenuSendIndividual("'.$qstr.'");',
							);
						}
						$arnewActions[] = array('SEPARATOR'=>true);
					}
					$arnewActions[] = $act;
				}
				$v->aActions = $arnewActions;
			}
		}
	}

	public static function OnBeforePrologHandler()
	{
		if(self::GetOptionString('is_enable', 'Y') != 'Y'
			or self::GetOptionString('method_post', 'hit') != 'hit') {
			return;
		}
		\CJSCore::RegisterExt('vettich_sp_prolog', array(
			'js' => '/bitrix/js/vettich.sp/prolog.js',
			'rel' => array('jquery'),
		));
		\CJSCore::Init(array('vettich_sp_prolog'));
	}

	public static function OnAfterIblockElementAdd($arFields = array())
	{
		Pending::addElem($arFields, array('type' => 'OnAfterIblockElementAdd'));
	}

	public static function OnAfterIBlockElementUpdate($arFields = array())
	{
		Pending::updateElem($arFields, array('type' => 'OnAfterIBlockElementUpdate'));
	}

	public static function OnAfterIBlockElementDelete($arFields = array())
	{
		Pending::deleteElem($arFields, array('type' => 'OnAfterIBlockElementDelete'));
	}
}
