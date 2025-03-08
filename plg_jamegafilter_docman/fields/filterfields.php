<?php
/*
 * ------------------------------------------------------------------------
 * JA Filter Plugin - Docman
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2016 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - GNU/GPL, http://www.gnu.org/licenses/gpl.html
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites: http://www.joomlart.com - http://www.joomlancers.com
 * This file may not be redistributed in whole or significant part.
 * ------------------------------------------------------------------------
 */
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;

class JFormFieldFilterfields extends JFormFieldJaMegafilter_filterfields {

	protected $type = 'filterfields';

	function getFieldGroups()
	{
		$class_methods = get_class_methods($this);
		$fl_array	  = preg_grep('/getJaMegafilterField(.*?)/', $class_methods);
		
		$fieldgroups = array();
		foreach ($fl_array as $value) {
			$array_key			   = strtolower(substr($value, 20));
			$fieldgroups[$array_key] = $this->{$value}();
		}
		return $fieldgroups;
	}
	
	function getJaMegafilterFieldBaseField()
	{
		$basefield = array(
			array(
				"published" => 0,
				"sort" => 0,
				"field" => "name",
				"title" => Text::_("COM_JAMEGAFILTER_TITLE"),
				"name" => Text::_("COM_JAMEGAFILTER_TITLE"),
				"filter_type" => array(
					"value"
				)
			),
			
			array(
				"published" => 0,
				"sort" => 0,
				"field" => "attr.cat.value",
				"title" => Text::_("COM_JAMEGAFILTER_CATEGORY"),
				"name" => Text::_("COM_JAMEGAFILTER_CATEGORY"),
				"filter_type" => array(
					"single",
					"dropdown", "select",
					"multiple"
				)
			),
			
			array(
				"published" => 0,
				"sort" => 0,
				"field" => "downloads",
				"title" => Text::_("COM_JAMEGAFILTER_DOWNLOADS"),
				"name" => Text::_("COM_JAMEGAFILTER_DOWNLOADS"),
				"filter_type" => array(
					"range"
				)
			),
			
			array(
				"published" => 0,
				"sort" => 0,
				"field" => "attr.tag.value",
				"title" => Text::_("COM_JAMEGAFILTER_TAG"),
				"name" => Text::_("COM_JAMEGAFILTER_TAG"),
				"filter_type" => array(
					"single",
					"dropdown", "select",
					"multiple"
				)
			),
			
			array(
				"published" => 0,
				"sort" => 0,
				"field" => "attr.type.value",
				"title" => Text::_("COM_JAMEGAFILTER_FILE_TYPE"),
				"name" => Text::_("COM_JAMEGAFILTER_FILE_TYPE"),
				"filter_type" => array(
					"single",
					"dropdown", "select",
					"multiple"
				)
			),
			
			array(
				"published" => 0,
				"sort" => 0,
				"field" => "published_date",
				"title" => Text::_("COM_JAMEGAFILTER_PUBLISHED_DATE"),
				"name" => Text::_("COM_JAMEGAFILTER_PUBLISHED_DATE"),
				"filter_type" => array(
					"date"
				)
			),
			
			array(
				"published" => 0,
				"sort" => 0,
				"field" => "created_date",
				"title" => Text::_("COM_JAMEGAFILTER_CREATED_DATE"),
				"name" => Text::_("COM_JAMEGAFILTER_CREATED_DATE"),
				"filter_type" => array(
					"date"
				)
			),
			
			array(
				"published" => 0,
				"sort" => 0,
				"field" => "modified_date",
				"title" => Text::_("COM_JAMEGAFILTER_MODIFIED_DATE"),
				"name" => Text::_("COM_JAMEGAFILTER_MODIFIED_DATE"),
				"filter_type" => array(
					"date"
				)
			),
		);
		return $basefield;
	}
}
