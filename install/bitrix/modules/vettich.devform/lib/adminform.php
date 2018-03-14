<?
namespace vettich\devform;

use vettich\devform\types\_type;
use vettich\devform\data\_data;
use vettich\devform\exceptions\TabException;
use CAdminContextMenu;

/**
* @author Oleg Lenshin (Vettich)
*
* 'on beforeSave' callback
* @param array $arValue
* @param array $args
* @param object this
* @return boolean
*
* 'on afterSave' callback
* @param array $arValue
* @param array $args
* @param object this
* @return boolean
*/
class AdminForm extends Module
{
	public $id = 'ID'; // unique
	public $pageTitle = false;
	public $enable = true;
	public $tabs = null;
	public $buttons = null;
	public $headerButtons = null;
	public $datas = null;
	public $get_id = 'ID';
	public $containerTemplate = '<div class="js-vform" style="display:none">
			<form method="post" action="" id="{form-id}" enctype="multipart/form-data">
				{content}
			</form>
		</div>';
	public $js = '';
	public $css = '';

	public $errorMessage = '';
	public $errorTemplate = '<div class="adm-info-message">{errors}</div>';

	function __construct($id, $args = array())
	{
		parent::__construct($args);
		$this->id = $id;
		if(isset($args['enable'])) $this->enable = $args['enable'];
		if(isset($args['js'])) $this->js = $args['js'];
		if(isset($args['css'])) $this->css = $args['css'];
		if(isset($args['pageTitle'])) $this->pageTitle = self::mess($args['pageTitle']);
		if(isset($args['tabs'])) $this->tabs = $this->initTabs($args['tabs']);
		if(isset($args['buttons'])) $this->buttons = _type::createTypes($args['buttons']);
		if(isset($args['headerButtons'])) $this->headerButtons = _type::createTypes($args['headerButtons']);
		if(isset($args['data'])) $this->datas = _data::createDatas($args['data']);

		$this->onHandler('tabsCreate', $this, $this->tabs);
		$this->save($args);
	}

	public static function initTabs($tabs)
	{
		if(empty($tabs)) {
			return array();
		}

		$tabClass = 'vettich\devform\Tab';
		$result = array();
		foreach((array)$tabs as $tab) {
			if($res = $tabClass::createTab($tab)) {
				$result[] = $res;
			}
		}

		return $result;
	}

	public function save($args)
	{
		if(!empty($_POST)) {
			$_POST = self::convertEncodingToCurrent($_POST);
		}
		if($_REQUEST['ajax'] == 'Y' 
			&& (!isset($args['save_ajax']) or $args['save_ajax'] != true)) {
			return;
		}
		if((!isset($args['dont_save']) or $args['dont_save'] != true)
			&& !empty($_POST) && !empty($this->datas)) {
			$arValues = array();
			foreach((array)$this->tabs as $tab) {
				$arValues = array_merge($arValues, _type::getValuesFromPost($tab->params));
			}
			/** 
			* on beforeSave callback
			*/
			$beforeSave = $this->onHandler('beforeSave', $arValues, $args, $this);
			if($beforeSave !== false && !isset($beforeSave['error'])) {
				$this->datas->saveValues($arValues);
				/** 
				* on afterSave callback
				*/
				$this->onHandler('afterSave', $arValues, $args, $this);
				if((isset($_POST['save']) or isset($_POST['_save'])) && !empty($_GET['back_url'])) {
					LocalRedirect($_GET['back_url']);
					exit;
				} elseif(empty($_GET[$this->get_id]) && !empty($arValues[$this->get_id])) {
					$url = $_SERVER['REQUEST_URI'];
					if(strpos($url, '?')) {
						$url .= '&';
					} else {
						$url .= '?';
					}
					$url .= $this->get_id.'='.$arValues[$this->get_id];
					$url .= '&TAB_CONTROL_devform_active_tab='.$_POST['TAB_CONTROL_devform_active_tab'];
					LocalRedirect($url);
					exit;
				} else {
					LocalRedirect($_SERVER['REQUEST_URI']);
					exit;
				}
			} elseif(isset($beforeSave['error'])) {
				$this->errorMessage = $beforeSave['error'];
			}
		}
	}

	function getContextMenu()
	{
		$arResult = array();

		if(isset($_GET['back_url'])) {
			$arResult['back'] = array(
				'TEXT' => GetMessage('VDF_BACK_LIST'),
				'TITLE' => GetMessage('VDF_BACK_LIST_TITLE'),
				'LINK' => $_GET['back_url'],
				'ICON' => 'btn_list',
			);
		}
		if(isset($_GET[$this->get_id]) && $_GET[$this->get_id] > 0) {
			$get = $_GET;
			unset($get[$this->get_id]);
			$arResult['add'] = array(
				'TEXT' => GetMessage('VDF_ADD'),
				'TITLE' => GetMessage('VDF_ADD_TITLE'),
				'LINK' => $_SERVER['SCRIPT_NAME'].'?'.http_build_query($get),
				'ICON' => 'btn_new',
			);
			if(isset($_GET['back_url'])) {
				$get = array(
					'ID' => $_GET[$this->get_id],
					'action' => 'delete',
					'sessid' => bitrix_sessid(),
				);
				$url = $_GET['back_url'];
				$url .= (strpos($url, '?') ? '&' : '?').http_build_query($get);
				$arResult['delete'] = array(
					'TEXT' => GetMessage('VDF_LIST_DELETE'),
					'TITLE' => GetMessage('VDF_LIST_DELETE_TITLE'),
					'LINK' => 'javascript:if(confirm("'
						.GetMessage('VDF_LIST_DELETE_CONFIRM2')
						.'")) window.location="'.$url.'";',
					'ICON' => 'btn_delete',
				);
			}
		}
		if(is_array($this->headerButtons)) {
			foreach((array)$this->headerButtons as $id=>$button) {
				$arResult[$id] = array(
					'HTML' => $button->render(),
				);
			}
		}
		return $arResult;
	}

	public function renderErrors($errors)
	{
		if(!empty($errors)) {
			if(!is_array($errors)) {
				$errors = array($errors);
			}
			$errors = '<ul style="margin:0"><li class="errortext">'
				.implode('</li><li class="errortext">', $errors)
				.'</li></ul>';
			echo(str_replace(
				array('{errors}'),
				array($errors),
				$this->errorTemplate));
		}
	}

	public function render()
	{
		\CJSCore::Init(array('ajax'));
		\CJSCore::Init(array('jquery'));
		$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/vettich.devform/script.js');
		$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/css/vettich.devform/style.css');

		$arTabs = array();
		foreach((array)$this->tabs as $k => $tab) {
			$arTabs[] = array(
				'DIV' => 'DIV_'.$k,
				'TAB' => $tab->name,
				'TITLE' => $tab->title,
			);
		}

		$context = new CAdminContextMenu($this->getContextMenu());
		$context->Show();
		if($_REQUEST['ajax'] == 'Y' && $_REQUEST['ajax_formid'] == $this->id) {
			$GLOBALS['APPLICATION']->RestartBuffer();
		}
		$this->renderErrors($this->errorMessage);

		if(!!$this->pageTitle) {
			$GLOBALS['APPLICATION']->SetTitle($this->pageTitle);
		}

		$tabControl = new \CAdminTabControl('TAB_CONTROL_'.$this->id, $arTabs, true, true);
		$tabControl->Begin();

		ob_start();

		foreach((array)$this->tabs as $tab) {
			$tabControl->BeginNextTab();
			$tab->render($this->datas);
		}
		if(!empty($this->buttons)) {
			$tabControl->Buttons();
			echo _type::renderTypes($this->buttons);
		}

		echo bitrix_sessid_post();
		$tabControl->End();

		if(!empty($this->js)) {
			echo '<script>'.$this->js.'</script>';
		}
		if(!empty($this->css)) {
			echo '<style>'.$this->css.'</style>';
		}

		$ob_content = ob_get_contents();
		ob_end_clean();

		echo str_replace(
			array('{form-id}',        '{content}'),
			array('FORM_'.$this->id,  $ob_content),
			$this->containerTemplate
		);
	}
}
