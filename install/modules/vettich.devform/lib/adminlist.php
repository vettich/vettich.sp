<?
namespace vettich\devform;

use CAdminSorting;
use CAdminList;
use CAdminResult;
use CAdminFilter;
use vettich\devform\types\_type;
use vettich\devform\data\_data;
use vettich\devform\data\orm;

/**
* show elements list on admin page
*
* @author Oleg Lenshin (Vettich)
* @var string $pageTitle
* @var string $sTableID
* @var string $navLabel
* @var CAdminSorting $sort
* @var CAdminList $list
* @var _data $datas
* @var array $params types
* @var array $hiddenParams
* @var array $dontEdit
* @var array $onHandlers
*/
class AdminList extends Module
{
	protected $pageTitle = '';
	protected $sTableID = '';
	protected $navLabel = '';
	protected $sort = null;
	protected $list = null;
	protected $datas = null;
	protected $params = array();
	protected $hiddenParams = array();
	protected $dontEdit = array('ID');
	protected $linkEditInsert = array();
	protected $editLinkParams = array();

	/**
	 * @param string $pageTitle
	 * @param string $sTableID
	 * @param boolean|array[] $arSort
	 * @param string $navLabel
	 */
	function __construct($pageTitle, $sTableID, $args)
	{
		parent::__construct($args);
		$this->pageTitle = self::mess($pageTitle);
		$this->sTableID = $sTableID;
		$this->params = _type::createTypes($args['params']);

		$this->setSort($args);

		if(isset($args['data'])) {
			$this->datas = _data::createDatas($args['data']);
		} elseif(isset($args['dbClass'])) {
			$this->datas = _data::createDatas(new orm(array('dbClass' => $args['dbClass'], 'filter' => array())));
		}

		if(isset($args['navLabel'])) $this->navLabel = $args['navLabel'];
		if(isset($args['linkEditInsert'])) $this->linkEditInsert = $args['linkEditInsert'];
		if(isset($args['hiddenParams'])) $this->hiddenParams = $args['hiddenParams'];
		if(isset($args['dontEdit'])) $this->dontEdit = $args['dontEdit'];
		if(isset($args['editLinkParams'])) $this->editLinkParams = $args['editLinkParams'];

		$this->list = new CAdminList($this->sTableID, $this->sort);

		if(!isset($args['buttons']['add'])) {
			$args['buttons'] = array_merge(array(
				'add' => 'buttons\newLink:#VDF_ADD#:'.str_replace(array('=', '[', ']'), array('\=', '\[', '\]'), $this->getLinkEdit()),
			), (array)$args['buttons']);
		}
		$this->buttons = _type::createTypes($args['buttons']);

		if(!isset($args['isFilter']) or $args['isFilter']) {
			$filters = array(
				'find',
				'find_type',
			);
			$this->list->InitFilter($filters);
		}

		$this->doGroupActions();
		$this->doEditAction();
	}

	public function getLinkEdit($params=array())
	{
		$params = array_merge($this->editLinkParams, $params);
		$p = $_GET;
		unset($p['mode']);
		$p = http_build_query($p);
		$params['back_url'] = $_SERVER['SCRIPT_NAME'].(empty($p) ? '' : '?'.$p);
		return str_replace('.php', '_edit.php', $_SERVER['SCRIPT_NAME'])
			.'?'.http_build_query($params);
	}

	function isHiddenParam($id)
	{
		return in_array($id, $this->hiddenParams);
	}

	function doGroupActions()
	{
		if(($arID = $this->list->GroupAction())) {
			if($_REQUEST['action_target']=='selected') {
				$arID = array();
				$rs = $this->getDataSource(array(), array(), array('ID'));
				while($ar = $rs->fetch()) {
					$arID[] = $ar['ID'];
				}
			}
			foreach((array)$arID as $ID) {
				$ID = IntVal($ID);
				if($ID <= 0) {
					continue;
				}
				switch($_REQUEST['action']) {
					case 'delete':
						if(false !== $this->onHandler('beforeGroupDelete', $this, $ID)) {
							$this->datas->delete('ID', $ID);
							$this->onHandler('afterGroupDelete', $this, $ID);
						}
						break;
				}
			}
			$this->onHandler('doGroupActions', $arID, $_REQUEST['action'], $this);
		}
	}

	function doEditAction()
	{
		if($this->list->EditAction()) {
			foreach((array)$_REQUEST['FIELDS'] as $id => $arField) {
				$arField['ID'] = $id;
				$this->datas->saveValues($arField);
			}
		}
	}

	function setSort($args)
	{
		if(isset($args['isSort']) && !$args['isSort']) {
			$this->sort = false;
		} else {
			if(isset($args['sortDefault'])) {
				$sBy = key($args['sortDefault']);
				$sOrder = current($args['sortDefault']);
				if(!$sBy) {
					$sBy = $sOrder;
					$sOrder = 'ASC';
				}
			} else {
				$sBy = 'ID';
				$sOrder = 'ASC';
			}
			$this->sortBy = $sBy;
			$this->sortOrder = $sOrder;
			$this->sort = new CAdminSorting($this->sTableID, $sBy, $sOrder);
		}
	}

	function getHeaders()
	{
		$arHeaders = array();
		foreach((array)$this->params as $id => $param) {
			$arHeaders[] = array(
				'id' => $param->id,
				'content' => $param->title,
				'sort' => (strpos($id, '[') === false ? $param->id : false),
				// 'align' => $param->info['align'],
				'default' => !$this->isHiddenParam($param->id),
			);
		}
		return $arHeaders;
	}

	function getSelectedFields()
	{
		$arSelectedFields = $this->list->GetVisibleHeaderColumns();
		if (!is_array($arSelectedFields) || empty($arSelectedFields)) {
			$arSelectedFields = array();
			foreach((array)$this->params as $id => $param) {
				if ($this->isHiddenParam($id)) {
					$arSelectedFields[] = $id;
				}
			}
		}
		return $arSelectedFields;
	}

	function getDataSource($arOrder=array(), $arFilter=array(), $arSelect=array())
	{
		$params = array();
		if(!empty($arOrder)) $params['order'] = $arOrder;
		if(!empty($arFilter)) $params['filter'] = $arFilter;
		if(!empty($arSelect)) $params['select'] = $arSelect;
		if(!in_array('ID', $params['select'])) {
			$params['select'][] = 'ID';
		}
		if(!empty($this->datas->datas)) {
			foreach((array)$this->datas->datas as $data) {
				if(method_exists($data, 'getList')) {
					return $data->getList($params);
				}
			}
		}
		return null;
	}

	function getOrder()
	{
		global $by, $order;
		return array($by => $order);
	}

	function getFilter()
	{
		global $find, $find_type;

		$arFilter = array();
		foreach((array)$this->params as $param) {
			$find_name = 'find_'.$param->id;
			if (!empty($find) && $find_type == $find_name) {
				$arFilter[$param->getFilterId()] = $find;
			} elseif (isset($GLOBALS[$find_name])) {
				$arFilter[$param->getFilterId()] = $GLOBALS[$find_name];
			}
		}

		foreach((array)$arFilter as $key => $value) {
			if ($value == "")
				unset($arFilter[$key]);
		}
		return $arFilter;
	}

	function getActions($row)
	{
		$arActions = array(
			'edit' => array(
				'ICON' => 'edit',
				'DEFAULT' => true,
				'TEXT' => GetMessage('VDF_LIST_EDIT'),
				'ACTION' => $this->list->ActionRedirect($this->getLinkEdit(array('ID' => $row->arRes['ID']))),
			),
			'delete' => array(
				'ICON' => 'delete',
				'TEXT' => GetMessage('VDF_LIST_DELETE'),
				'ACTION' => 'if(confirm("'
					.GetMessage('VDF_LIST_DELETE_CONFIRM', array('#NAME#' => $row->arRes['NAME'])).'")) '
					.$this->list->ActionDoGroup($row->arRes['ID'], 'delete', http_build_query($_GET)),
			),
		);
		$arActions = array_merge($arActions, (array)$this->onHandler('actionsBuild', $this, $row, $arActions));
		return $arActions;
	}

	function getFooter()
	{
		return array();
	}

	function getContextMenu()
	{
		$arResult = array();
		foreach((array)$this->buttons as $button) {
			$arResult[] = array(
				'HTML' => $button->render(),
			);
		}
		return $arResult;
	}

	function displayFilter()
	{
		global $APPLICATION, $find, $find_type;

		$findFilter = array(
			'reference' => array(),
			'reference_id' => array(),
		);
		$listFilter = array();
		$filterRows = array();
		foreach((array)$this->params as $param) {
			$listFilter[$param->id] = $param->title;
			$findFilter['reference'][] = $param->title;
			$findFilter['reference_id'][] = 'find_'.$param->id;
		}

		if (!empty($listFilter)) {
			$filter = new CAdminFilter($this->sTableID.'_filter', $listFilter);
			?>
			<form name="find_form" method="get" action="<? echo $APPLICATION->GetCurPage(); ?>">
				<? $filter->Begin(); ?>
				<? if (!empty($findFilter['reference'])): ?>
					<tr>
						<td><b><?=GetMessage('PERFMON_HIT_FIND')?>:</b></td>
						<td><input
							type="text" size="25" name="find"
							value="<? echo htmlspecialcharsbx($find) ?>"><? echo SelectBoxFromArray('find_type', $findFilter, $find_type, '', ''); ?>
						</td>
					</tr>
				<? endif; ?>
				<?
				foreach((array)$this->params as $param) {
					?><tr>
						<td><? echo $param->title ?></td>
						<td><? echo $param->renderTemplate('{content}', array('{name}' => 'find_'.$param->id)) ?></td>
					</tr><?
				}
				$filter->Buttons(array(
					'table_id' => $this->sTableID,
					'url' => $APPLICATION->GetCurPage(),
					'form' => 'find_form',
				));
				$filter->End();
				?>
			</form>
		<?
		}
	}

	/**
	* show page on display
	* @global $APPLICATION
	*/
	public function render()
	{
		$this->renderBegin();
		$select = $this->getSelectedFields();
		$dataSource = $this->getDataSource($this->getOrder(), $this->getFilter(), $select);
		$data = new CAdminResult($dataSource, $this->sTableID);
		$data->NavStart();
		$this->list->NavText($data->GetNavPrint($this->navLabel));
		while ($arRes = $data->NavNext(false)) {
			$row = $this->list->AddRow($arRes['ID'], $arRes);
			$this->onHandler('renderRow', $this, $row);
			foreach((array)$select as $fieldId) {
				$param = $this->params[$fieldId];
				if ($param) {
					if(in_array($param->id, $this->linkEditInsert)) {
						$param->href = $this->getLinkEdit(array('ID' => $arRes['ID']));
					}
					$view = $param->renderView(self::valueFrom($arRes, $param->id));
					$row->AddViewField($param->id, $view);

					if(!in_array($param->id, $this->dontEdit)) {
						if(($pos = strpos($param->id, '[')) !== false) {
							$prekey = substr($param->id, 0, $pos);
							$postkey = substr($param->id, $pos);
							$name = "FIELDS[{$arRes['ID']}][$prekey]$postkey";
						} else {
							$name = "FIELDS[{$arRes['ID']}][{$param->id}]";
						}
						$edit = $param->renderTemplate('{content}', array(
							'{id}' => 'FIELDS-'.$arRes['ID'].'-'.str_replace(array('][', ']', '['), array('-', '', '-'), $param->id),
							'{value}' => self::valueFrom($arRes, $param->id),
							'{name}' => $name,
						));
						$row->AddEditField($param->id, $edit);
					}
				}
			}
			$arActions = $this->getActions($row);
			$row->AddActions($arActions);
			$this->onHandler('afterRow', $this, $row);
		}
		$this->renderEnd();
	}

	private function renderBegin()
	{
		\CJSCore::Init(array('ajax'));
		\CJSCore::Init(array('jquery'));
		$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/vettich.devform/script.js');
		$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/css/vettich.devform/style.css');
		$this->list->addHeaders($this->getHeaders());
	}

	private function renderEnd()
	{
		$this->list->AddFooter($this->getFooter());
		$this->list->AddAdminContextMenu($this->getContextMenu());
		$this->list->AddGroupActionTable(array('delete'=>true));
		$this->list->CheckListMode();
		if(!!$this->pageTitle) {
			$GLOBALS['APPLICATION']->SetTitle($this->pageTitle);
		}
		global $adminPage, $adminMenu, $adminChain, $USER, $APPLICATION;
		require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');
		$this->displayFilter();
		$this->list->DisplayList();
	}
}
