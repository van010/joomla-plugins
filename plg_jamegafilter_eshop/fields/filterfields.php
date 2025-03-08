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
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class JFormFieldFilterfields extends JFormFieldJaMegafilter_filterfields
{
	protected $type = 'filterfields';

	function getFieldGroups()
	{
		$class_methods = get_class_methods($this);
		$fl_array = preg_grep('/getJaMegafilterField(.*?)/', $class_methods);
		
		$fieldgroups = array();
		foreach ($fl_array as $value) {
			$array_key = strtolower( substr($value, 20) );
			$fieldgroups[$array_key] = $this->{ $value }();
		}
		return $fieldgroups;
	}
	
	function getJaMegafilterFieldBaseField()
	{
		$basefield = array(
			array(
				"published"=>0,
				"sort" => 0,
				"field"=> "name",
				"title"=>Text::_("COM_JAMEGAFILTER_TITLE"),
				"name"=>Text::_("COM_JAMEGAFILTER_TITLE"),
				"filter_type"=>array("value")
			),

			array(
				"published"=>0,
				"sort" => 0,
				"field"=> "price",
				"title"=>Text::_("COM_JAMEGAFILTER_PRICE"),
				"name"=>Text::_("COM_JAMEGAFILTER_PRICE"),
				"filter_type"=>array("range")
			),

			array(
				"published"=>0,
				"sort" => 0,	
				"field"=> "attr.cat.value",
				"title"=>Text::_("COM_JAMEGAFILTER_CATEGORY"),
				"name"=>Text::_("COM_JAMEGAFILTER_CATEGORY"),
				"filter_type"=>array("single", "dropdown", "select", "multiple")
			),

			array(
				"published"=>0,
				"sort" => 0,	
				"field"=> "attr.manu.value",
				"title"=>Text::_("COM_JAMEGAFILTER_MANUFACTURER"),
				"name"=>Text::_("COM_JAMEGAFILTER_MANUFACTURER"),
				"filter_type"=>array("single", "dropdown", "select", "multiple")
			),	
			array(
					"published" => 0,
					"sort" => 0,
					"field" => "attr.featured.value",
					"title" => Text::_("COM_JAMEGAFILTER_FEATURED"),
					"name" => Text::_("COM_JAMEGAFILTER_FEATURED"),
					"filter_type" => array("single", "dropdown", "select", "multiple")
			),				
			array(
				"published"=>0,
				"sort" => 0,	
				"field"=> "rating",
				"title"=>Text::_("COM_JAMEGAFILTER_RATING"),
				"name"=>Text::_("COM_JAMEGAFILTER_RATING"),
				"filter_type"=>array("range")
			),

			array(
				"published"=>0,
				"sort" => 0,	
				"field"=> "attr.tag.value",
				"title"=>Text::_("COM_JAMEGAFILTER_TAG"),
				"name"=>Text::_("COM_JAMEGAFILTER_TAG"),
				"filter_type"=>array("single", "dropdown", "select", "multiple")
			),

			array(
				"published"=>0,
				"sort" => 0,	
				"field"=> "product_width",
				"title"=>Text::_("COM_JAMEGAFILTER_WIDTH"),
				"name"=>Text::_("COM_JAMEGAFILTER_WIDTH"),
				"filter_type"=>array("range")
			),		
			array(
				"published"=>0,
				"sort" => 0,	
				"field"=> "product_length",
				"title"=>Text::_("COM_JAMEGAFILTER_LENGTH"),
				"name"=>Text::_("COM_JAMEGAFILTER_LENGTH"),
				"filter_type"=>array("range")
			),		
			array(
				"published"=>0,
				"sort" => 0,	
				"field"=> "product_height",
				"title"=>Text::_("COM_JAMEGAFILTER_HEIGHT"),
				"name"=>Text::_("COM_JAMEGAFILTER_HEIGHT"),
				"filter_type"=>array("range")
			),		
			array(
				"published"=>0,
				"sort" => 0,	
				"field"=> "product_weight",
				"title"=>Text::_("COM_JAMEGAFILTER_WEIGHT"),
				"name"=>Text::_("COM_JAMEGAFILTER_WEIGHT"),
				"filter_type"=>array("range")
			),		
			array(
				"published"=>0,
				"sort" => 0,	
				"field"=> "attr.created_date.value",
				"title"=>Text::_("COM_JAMEGAFILTER_CREATED_DATE"),
				"name"=>Text::_("COM_JAMEGAFILTER_CREATED_DATE"),
				"filter_type"=>array("date")
			),		
			array(
				"published"=>0,
				"sort" => 0,	
				"field"=> "attr.modified_date.value",
				"title"=>Text::_("COM_JAMEGAFILTER_MODIFIED_DATE"),
				"name"=>Text::_("COM_JAMEGAFILTER_MODIFIED_DATE"),
				"filter_type"=>array("date")
			),		


		);
		return $basefield;
	}

	function getJaMegafilterFieldCustomField()
	{
		$db = Factory::getDbo();
		$query = 'SELECT ad.id, ad.attribute_name
			FROM `#__eshop_attributes` AS a
			LEFT JOIN `#__eshop_attributedetails` AS ad ON a.id = ad.attribute_id
			WHERE a.published = 1';
		$db->setQuery($query);
		$list = $db->loadObjectList();
		$customfield = array();
		foreach ($list as $field) 
		{
			$customfield[] = array(
				"published" => 0,
				"sort" => 0,	
				"field" => 'attr.ct'.$field->id.'.value',
				"title" => $field->attribute_name,
				"name" => $field->attribute_name,
				"filter_type" => array( "single", "dropdown", "select", "multiple", "color", "size" )
				);
		}

		return $customfield;
	}
	
	function getJaMegafilterFieldOptionsField()
	{
		$currentLanguage = Factory::getLanguage();
		$currentTag = $currentLanguage->getTag();
		$db = Factory::getDbo();
		$sql = 'SELECT * FROM #__eshop_options o
				LEFT JOIN #__eshop_optiondetails od ON (od.option_id = o.id)
				WHERE od.language IN ("*", "'.$currentTag.'") AND o.published = 1';
		$db->setQuery($sql);
		$options = $db->loadObjectList();
		$optionsfield = array();

		foreach ($options as $field) 
		{
			if (!in_array($field->option_type, array('Select','Radio','Checkbox'))) continue;
			$optionsfield[] = array(
				"published" => 0,
				"sort" => 0,
				"field" => 'attr.op'.$field->option_id.'.value',
				"title" => $field->option_name,
				"name" => $field->option_name,
				"filter_type" => array( "single", "dropdown", "select", "multiple", "color", "size" )
				);
		}

		return $optionsfield;
	}
}