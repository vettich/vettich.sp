<?
namespace vettich\devform\types;

use CFile;

/**
* @author Oleg Lenshin (Vettich)
*/
class image extends _type
{
	public $content = '<input name="{name}" value="{value}" {params}>';
	public $params = array('type' => 'file');
	public $module_id = 'vettich.devform';
	public $maxCount = 1;

	public function __construct($id, $args)
	{
		parent::__construct($id, $args);

		if(isset($args['module_id'])) {
			$this->module_id = $args['module_id'];
		}
		if(isset($args['maxCount'])) {
			$this->maxCount = $args['maxCount'];
		}
	}

	public function renderTemplate($template='', $replaces=array())
	{
		if(isset($replaces['{value}'])) {
			$value = $replaces['{value}'];
		} else {
			$value = $this->getValue($this->data);
		}
		if(empty($value)) {
			$value = $this->default_value;
		}
		if (class_exists('\Bitrix\Main\UI\FileInput')) {
			$this->content = \Bitrix\Main\UI\FileInput::createInstance(array(
					"name" => $this->name,
					"description" => false,
					"upload" => true,
					"allowUpload" => "A",
					"medialib" => false,
					"fileDialog" => false,
					"cloud" => false,
					"delete" => true,
					"maxCount" => $this->maxCount,
				))->show($value);
		} else {
			$this->content = CFile::InputFile($this->name, 20, $value);
			if($value > 0) {
				$this->content .= '<br>'.CFile::ShowImage($value, 200, 200, "border=0", "", true);
				$this->content .= '<input type="hidden" name="'.$this->name.'_old" value="'.$value.'">';
			}
		}
		if($value > 0) {
			$this->content .= '<input type="hidden" name="'.$this->name.'_old" value="'.$value.'">';
		}


		return parent::renderTemplate($template, $replaces);
	}

	public function renderView($value=0)
	{
		return CFile::ShowImage($value, 60, 60, "border=0", "", true);
	}

	public function getValueFromPost()
	{
// ddebug($_REQUEST);
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
		    $arIMAGE = self::post($this->name);
		    // if(intval($arIMAGE)) {
		    // 	$arIMAGE = CFile::GetFileArray($arIMAGE);
		    // }
		    if(stripos($arIMAGE['tmp_name'],$_SERVER['DOCUMENT_ROOT']) === false) {
		    	$arIMAGE['tmp_name'] = $_SERVER['DOCUMENT_ROOT'].$arIMAGE['tmp_name'];
		    }
		    $arIMAGE['old_file'] = self::post($this->name.'_old');
		    $arIMAGE['del'] = self::post($this->name.'_del');
		    $arIMAGE['MODULE_ID'] = $this->module_id;
		    if (!empty($arIMAGE['name']) || !empty($arIMAGE['del'])) {
		        $fid = CFile::SaveFile($arIMAGE, $this->module_id);
				$this->value = $fid;
		        return $fid;
		    }
		}
		return 0;
	}
}