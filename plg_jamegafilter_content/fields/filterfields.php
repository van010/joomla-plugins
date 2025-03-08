<?php
/*
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
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class JFormFieldFilterfields extends JFormFieldJaMegafilter_filterfields {

	protected $type = 'filterfields';
	protected $catOrdering = true;

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
				"field" => "hits",
				"title" => Text::_("COM_JAMEGAFILTER_HITS"),
				"name" => Text::_("COM_JAMEGAFILTER_HITS"),
				"filter_type" => array(
					"range"
				)
			),

			array(
				"published" => 0,
				"sort" => 0,
				"field" => "rating",
				"title" => Text::_("COM_JAMEGAFILTER_RATING"),
				"name" => Text::_("COM_JAMEGAFILTER_RATING"),
				"filter_type" => array(
					'rating', 'range'
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
				"field" => "published_date",
				"title" => Text::_("COM_JAMEGAFILTER_PUBLISHED_DATE"),
				"name" => Text::_("COM_JAMEGAFILTER_PUBLISHED_DATE"),
				"filter_type" => array(
					"date"
				)
			),

			array(
				"published"=>0,
				"sort" => 0,
				"field"=> "created_date",
				"title"=>Text::_("COM_JAMEGAFILTER_CREATED_DATE"),
				"name"=>Text::_("COM_JAMEGAFILTER_CREATED_DATE"),
				"filter_type"=>array("date")
			),
			array(
				"published" => 0,
				"sort" => 0,
				"field" => "attr.author.value",
				"title" => Text::_("COM_JAMEGAFILTER_AUTHOR"),
				"name" => Text::_("COM_JAMEGAFILTER_AUTHOR"),
				"filter_type" => array(
					"single",
					"dropdown", "select",
					"multiple"
				)
			),
			array(
				"published"=>0,
				"sort" => 0,
				"field"=> "modified_date",
				"title"=>Text::_("COM_JAMEGAFILTER_MODIFIED_DATE"),
				"name"=>Text::_("COM_JAMEGAFILTER_MODIFIED_DATE"),
				"filter_type"=>array("date")
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
				"published" => 0,
				"sort" => 0,
				"field" => "attr.fulltext.value",
				"title" => Text::_('COM_JAMEGAFILTER_FULLTEXT'),
				"name" => Text::_('COM_JAMEGAFILTER_FULLTEXT'),
				"filter_type" => array("value")
			),
		);

		return $basefield;
	}

	function getJaMegafilterFieldCustomFields() {
		if (version_compare(JVERSION, '3.7', '<'))
			return;
		$customFields = array();
		require_once(JPATH_PLUGINS . '/jamegafilter/content/helper.php');
		$helper = new ContentFilterHelper();
		$fields = $helper->getCustomFields();
		if ($fields) {
			foreach ($fields as $field) {
				if ($field->type === 'repeatable') {

				} else {
					$customField = array(
						"published" => 0,
						"sort" => 0,
						"field" => 'attr.ct'.$field->id.'.value',
						"title" => $field->title,
						"name" => $field->title,
					);

					switch ($field->type) {
						case 'text':
							$customField['filter_type'] = array('value', 'range', 'latlong', 'numberrange');
							break;
						case 'editor' :
						case 'textarea' :
						case 'url' :
							$customField['filter_type'] = array('value', 'range');
							break;
						case 'color' :
							$customField['filter_type'] = array('color');
							break;
						case 'calendar' :
							$customField['filter_type'] = array('date');
							break;
						case 'integer' :
							$customField['filter_type'] = array(
								'range',
								'single',
								'dropdown',
								'multiple',
								"size"
							);
							break;
						case 'media':
						case 'imagelist':
							$customField['filter_type'] = array('media');
							break;
						default :
							$customField['filter_type'] = array(
								"single",
								"dropdown",
								"select",
								"multiple",
								"size"
							);
							break;
					}
					$customFields[] = $customField;
				}
			}
		}

		return $customFields;
	}

	function hasCustomOrdering($field) {
		if ($field['field'] === 'attr.cat.value') {
			return true;
		}

		preg_match('/\d+/', $field['field'], $matches);
		if (!count($matches)) {
			return false;
		}

		$id = $matches[0];
		$db = Factory::getDbo();
		$query = "SELECT `id` 
				FROM `#__fields` 
				WHERE `id` = $id
				AND `type` IN ('list', 'checkboxes')
				AND `state` = 1";
		$result = $db->setQuery($query)->loadResult();
		return !!$result;
	}
}
