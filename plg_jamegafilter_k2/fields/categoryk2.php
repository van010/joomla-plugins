<?php

/*
 * ------------------------------------------------------------------------
 * JA Filter Plugin - K2
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2016 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - GNU/GPL, http://www.gnu.org/licenses/gpl.html
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites: http://www.joomlart.com - http://www.joomlancers.com
 * This file may not be redistributed in whole or significant part.
 * ------------------------------------------------------------------------
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * JA Param K2 Helper
 *
 * @since      Class available since Release 1.2.0
 */
class JFormFieldCategoryk2 extends JFormField {
	/*
	 * Category K2 name
	 *
	 * @access	protected
	 * @var		string
	 */

	var $type = 'categoryk2';

	/**
	 * Fetch Ja Element K2 Catetgory Param method
	 *
	 * @return	object  param
	 */
	function getInput() {
		require_once(JPATH_PLUGINS . '/jamegafilter/k2/helper.php');
		$helper = new K2FilterHelper();

		$input = JFactory::getApplication()->input;
		$catid = $input->get('catid');
		$id = $input->get('id');

		if (!empty($this->value) && $catid === null)
			$this->changeUri($this->value);

		$this->addScriptChangeCat($id);

		$flag = false;

		$attr = '';
		// Initialize some field attributes.
		$attr .= $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';

		// To avoid user's confusion, readonly="true" should imply disabled="true".
		if ((string) $this->element['readonly'] == 'true' || (string) $this->element['disabled'] == 'true') {
			$attr .= ' disabled="disabled"';
		}

		$attr .= $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';
		$attr .= $this->multiple ? ' multiple="multiple"' : '';

		// Initialize JavaScript field attributes.
		$attr .= $this->element['onchange'] ? ' onchange="' . (string) $this->element['onchange'] . '"' : '';

		$categories = $helper->_fetchElement(0, '', array());
		$HTMLSelect = '<select onchange="return changeCat(this);" name="' . $this->name . '" id="' . $this->id . '" ' . $attr . '>';

		$HTMLCats = '';
		$value = $this->value;
		foreach ($categories as $item) {
			if (isset($item->id) && $item->id > 0) {
				$check = '';
				if ($item->id == $catid) {
					$check = "selected";
				}

				$class = ' percat="' . $item->parent . '"';

				if ($item->parent != 0)
					$class = ' class="subcat" percat="' . $item->parent . '" ';

				$HTMLCats .= '<option value="' . $item->id . '" ' . $check . ' ' . $class . '>' . $item->treename . '</option>';
			}
		}
		if ($flag == true) {
			$HTMLSelect .= '<option value="0">' . JText::_("COM_JAMEGAFILTER_ALL_CATEGORY") . '</option>';
		} else {
			$HTMLSelect .= '<option value="0" selected="selected">' . JText::_("COM_JAMEGAFILTER_ALL_CATEGORY") . '</option>';
		}
		$HTMLSelect .= $HTMLCats;
		$HTMLSelect .= '</select>';
		return $HTMLSelect;
	}

	function addScriptChangeCat($id) {
		$uri = JUri::getInstance();
		$uri->delVar('catid');
		$uri->delVar('title');
		$uri->delVar('published');
		$script = '
			function changeCat(e){
				catid = jQuery(e).val();
				title = jQuery("#jform_title").val();
				published = jQuery("#jform_params_menu_text input:checked").val();
				window.location =  "' . $uri . '&catid="+catid+"&title="+title+"&published="+published;
			}
			';
		JFactory::getDocument()->addScriptDeclaration($script);
	}

	function changeUri($value) {
		$uri = JUri::getInstance();
		$uri->setVar('catid', $value);
		$app = JFactory::getApplication();
		$app->redirect($uri);
	}

}
