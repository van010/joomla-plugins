<?php
/**
 * ------------------------------------------------------------------------
 * JA Filter Plugin - Content
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2016 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - GNU/GPL, http://www.gnu.org/licenses/gpl.html
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites: http://www.joomlart.com - http://www.joomlancers.com
 * This file may not be redistributed in whole or significant part.
 * ------------------------------------------------------------------------
 */
 
defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormField;

JLoader::register('ContentFilterHelper', JPATH_PLUGINS . '/jamegafilter/content/helper.php');

class JFormFieldContentcategories extends FormField
{
    protected $type = 'Contentcategories';

    protected function getInput()
    {
        $value = 0;
		if (!empty($this->value)) {
			$value = $this->value;
		}

		$helper = new ContentFilterHelper;
		$items = $helper->getChildCategories();
		
		$html = '';
		$html = '<select class="form-select form-select-color-state form-select-success valid form-control-success" name="'
      .$this->name.'">';
		$html .= '<option value="0">'.Text::_('COM_JAMEGAFILTER_ALL_CATEGORIES').'</option>';
		foreach ($items as $item) {
			if ($item->published != '1')
				continue;
			$html .= '<option '.($value == $item->id ? ' selected="selected" ' : '').' value="'.$item->id.'">'.str_repeat('.&nbsp;&nbsp;', ($item->level)).'|_.&nbsp;'.$item->title.'</option>';
		}
		$html.='</select>';
		return $html;
    }
}
