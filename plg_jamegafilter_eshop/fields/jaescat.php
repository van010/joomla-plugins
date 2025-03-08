<?php
/**
 * ------------------------------------------------------------------------
 * JA Filter Plugin - Eshop
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2016 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - GNU/GPL, http://www.gnu.org/licenses/gpl.html
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites: http://www.joomlart.com - http://www.joomlancers.com
 * This file may not be redistributed in whole or significant part.
 * ------------------------------------------------------------------------
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\LanguageHelper;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

class JFormFieldJaescat extends FormField
{
	protected $type = 'Jaescat';

	protected function getInput()
	{
		$value = 0;
		if (!empty($this->value)) {
			$value = $this->value;
		}
		$catids = EshopHelper::getAllChildCategories(0);
		$langConfig = ComponentHelper::getParams('com_languages');
		$siteLang = $langConfig->get('site');
		$currentLang = Factory::getLanguage()->getTag();

		$html = '';
    $html = '<select class="form-select" name="' . $this->name . '">';
		$html .= '<option value="0">'.Text::_('COM_JAMEGAFILTER_ALL_CATEGORIES').'</option>';
		foreach ($catids AS $id) {
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('a.id', 'IF (b.category_name IS NOT NULL, b.category_name, c.category_name) AS category_name'))
				->from($db->qn('#__eshop_categories', 'a'))
				->leftJoin($db->qn('#__eshop_categorydetails', 'b') . " ON b.category_id = a.id AND b.`language` = '$currentLang'")
				->leftJoin($db->qn('#__eshop_categorydetails', 'c') . " ON c.category_id = a.id AND c.`language` = '$siteLang'")
				->where($db->qn('a.id') . '=' . $db->q($id));

			$category = $db->setQuery($query)->loadObject();
			$parent = EshopHelper::getParentCategories($id);
			$lv = count($parent)-1;
			$html .= '<option '.($value == $category->id ? ' selected="selected" ' : '').' value="'.$category->id.'">'.str_repeat('&nbsp;&nbsp;', ($lv)).'|_.&nbsp;'.$category->category_name.'</option>';
		}
		$html.='</select>';
		return $html;
	}
}