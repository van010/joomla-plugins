<?php
/**
 * $JA#COPYRIGHT$
 */

// Ensure this file is being included by a parent file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;

jimport('joomla.form.formfield');
/**
 *
 * JA Fetch for Map
 * @author JoomlArt
 *
 */
class JFormFieldJamap extends FormField
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_type = 'Jamap';


	/**
	 *
	 * Construction Fetch
	 */
	function getInput()
	{
		$func = (string) $this->element['function'];
		if(!$func) {
			$func = 'mapkey';
		}
		
		if(method_exists($this, $func)) {
			return call_user_func_array(array($this, $func), array());
		}
		return null;
	}


	/**
	 * return - map_key, function="@map_key"
	 */
	function mapkey()
	{
		//popup
		JaLoadAssets::jquery();
        if(version_compare(JVERSION, '4', 'ge')){
            HTMLHelper::_('bootstrap.modal');
            Factory::getApplication()
                ->getDocument()
                ->getWebAssetManager()
                ->useScript('bootstrap.modal');
        }else{
            HTMLHelper::_('behavior.modal');
        }

        $doc = Factory::getDocument();
        
        $doc->addScriptOptions('jaosmap', array('juri_root' => Uri::root()));
        $doc->addStyleSheet(Uri::root() . 'plugins/system/jaosmap/assets/leaflet/leaflet.css');
        $doc->addScript(Uri::root() . 'plugins/system/jaosmap/assets/leaflet/leaflet.js');

		$doc->addStyleSheet(Uri::root() . 'plugins/system/jaosmap/assets/leaflet-routing-machine/leaflet-routing-machine.css');
        $doc->addScript(Uri::root() . 'plugins/system/jaosmap/assets/leaflet-routing-machine/leaflet-routing-machine.min.js');

        $doc->addStyleSheet(Uri::root() . 'plugins/system/jaosmap/assets/tingle/tingle.min.css');
        $doc->addScript(Uri::root() . 'plugins/system/jaosmap/assets/tingle/tingle.min.js');

        $doc->addScript(Uri::root() . 'plugins/system/jaosmap/assets/jaosmap.js');

		$path = Uri::root(true).'/plugins/system/jaosmap/assets/';
		$doc->addStyleSheet($path . 'style.css?v=1');
		$doc->addScript($path . 'jagencode.js?v=1');

		return '';
	}


	/**
	 * return - map_code, function="map_code"
	 */
	function mapcode()
	{
		$paramname = $this->name;
        $id = $this->name;
		$cols = (isset($this->element['cols']) && $this->element['cols'] != '') ? 'cols="' . intval($this->element['cols']) . '"' : '';
		$rows = (isset($this->element['rows']) && $this->element['rows'] != '') ? 'rows="' . intval($this->element['rows']) . '"' : '';
		$value = $this->value ? $this->value : (string) $this->element['default'];

		$html = "";
		$html .= "\n\t<div>";
		$html .= "\n\t<a name=\"mapPreview\"></a>";
		$html .= "\n\t<textarea name=\"{$paramname}\" class='form-control' id=\"{$id}\" style=\"width:100%; max-width:650px; height: 100px;\" >{$value}</textarea><br />";
        $html .= "\n\t" . '<a href="javascript: CopyToClipboard(\'' . $id . '\');">' . Text::_('SELECT_ALL') . '</a>';
		$html .= "\n\t" . '&nbsp;|&nbsp;';
		$html .= "\n\t" . '<a id="jaMapPreview" href="#mapPreview" >' . Text::_('PREVIEW_MAP') . '</a>';
		$html .= '<div id="map-preview-container"></div>';
		$html .= '</div>';
		if (version_compare(JVERSION, '4.0', 'ge')) {
			$html .= '
				<!-- Modal -->
				<div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
				  <div class="modal-dialog" role="document">
					<div class="modal-content">
					  <div class="modal-header">
						<h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						  <span aria-hidden="true">&times;</span>
						</button>
					  </div>
					  <div class="modal-body" id="previewBody">
	
					  </div>
					  <div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					  </div>
					</div>
				  </div>
				</div>';
		}
		return $html;
	}
}
