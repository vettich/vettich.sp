<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

CModule::IncludeModule('vettich.sp');
CModule::IncludeModule('iblock');
IncludeModuleLangFile(__FILE__);

$rsPosts = vettich\SP\db\postTable::getList();
$arPosts = array();
while($ar = $rsPosts->fetch()) {
	$arPosts[$ar['ID']] = "[$ar[ID]] $ar[NAME]";
}

(new \vettich\devform\AdminList('#VCH_QUEUE_PAGE_TITLE#', 'sTableID', array(
	'dbClass' => 'vettich\SP\db\pending',
	'sortDefault' => array('ID' => 'DESC'),
	'params' => array(
		'ID' => 'number',
		'NAME' => 'textlink:#VDF_NAME#',
		'IS_ENABLE' => 'checkbox:#VDF_IS_ENABLE#',
		'STATUS' => array(
			'type' => 'select',
			'title' => '#VETTICH_SP_STATUS#',
			'options' => array(
				'PUBLISH' => '#VETTICH_SP_STATUS_PUBLISH#',
				'UPDATE' => '#VETTICH_SP_STATUS_UPDATE#',
				'DELETE' => '#VETTICH_SP_STATUS_DELETE#',
			),
		),
		'RESULT' => array(
			'type' => 'select',
			'title' => '#VETTICH_SP_RESULT#',
			'options' => array(
				'READY' => '<span class="vettich_sp_result ready"></span><span class="vettich_sp_result text">#VETTICH_SP_RESULT_READY#</span>',
				'RUNNING' => '<span class="vettich_sp_result running"></span><span class="vettich_sp_result text">#VETTICH_SP_RESULT_RUNNING#</span>',
				'SUCCESS' => '<span class="vettich_sp_result success"></span><span class="vettich_sp_result text">#VETTICH_SP_RESULT_SUCCESS#</span>',
				'ERROR' => '<span class="vettich_sp_result error"></span><span class="vettich_sp_result text">#VETTICH_SP_RESULT_ERROR#</span>',
				'WARNING' => '<span class="vettich_sp_result warning"></span><span class="vettich_sp_result text">#VETTICH_SP_RESULT_WARNING#</span>',
				'ERROR_POST' => '<span class="vettich_sp_result error_post"></span><span class="vettich_sp_result text">#VETTICH_SP_RESULT_ERROR_POST#</span>',
			),
		),
		'POST_ID' => array(
			'type' => 'select',
			'title' => '#VETTICH_SP_POST#',
			'options' => $arPosts,
		),
		'IBLOCK_TYPE' => array(
			'type' => 'text',
			'title' => '#POST_IBLOCK_TYPE#',
			'on renderView' => array('Vettich\SP\Module', 'onRenderViewIblockType'),
		),
		'IBLOCK_ID' => array(
			'type' => 'text',
			'title' => '#POST_IBLOCK#',
			'on renderView' => array('Vettich\SP\Module', 'onRenderViewIblockId'),
		),
		'ELEMENT_ID' => array(
			'type' => 'text',
			'title' => '#POST_IBLOCK_ELEMENT_ID#',
			'on renderView' => function($obj, &$value=null) {

			},
		),
		'UPDATED_AT' => 'text:#VETTICH_SP_UPDATED_AT#',
	),
	'on actionsBuild' => function($obj, $row, $arActions) {
		$result = array();
		if($row->arRes['STATUS'] != 'RUNNING') {
			$result['publish'] = array(
				'TEXT' => GetMessage('VETTICH_SP_PUBLISH'),
				'ACTION' => 'Vettich.SP.PublishFromQueue('.$row->arRes['ID'].');',
			);
		}
		return $result;
	},
	'on renderRow' => function($obj, &$row) {

	},
	'dontEdit' => array('ID', 'IBLOCK_TYPE' , 'IBLOCK_ID', 'STATUS', 'RESULT', 'UPDATED_AT', 'POST_ID', 'ELEMENT_ID'),
	'hiddenParams' => array('UPDATED_AT', 'IBLOCK_TYPE'),
	'buttons' => array(
		'add' => null,
		// 'unload' => 'buttons\simple:Unload goods',
	),
	'linkEditInsert' => array('NAME'),
)))->render();

echo (new vettich\devform\types\note('footer', array(
	'title' => '#VETTICH_SP_RESULT_VALUE#:<br>
		<span class="vettich_sp_result ready"></span> - #VETTICH_SP_RESULT_READY#<br>
		<span class="vettich_sp_result running"></span> - #VETTICH_SP_RESULT_RUNNING#<br>
		<span class="vettich_sp_result success"></span> - #VETTICH_SP_RESULT_SUCCESS#<br>
		<span class="vettich_sp_result error"></span> - #VETTICH_SP_RESULT_ERROR#<br>
		<span class="vettich_sp_result warning"></span> - #VETTICH_SP_RESULT_WARNING#<br>',
)))->renderView();

if($_GET['ajax'] != 'Y') {
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
}
