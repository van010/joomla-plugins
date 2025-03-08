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
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

class JFormFieldFilterfields extends JFormFieldJaMegafilter_filterfields {

	protected $type = 'filterfields';

	function getFieldGroups() {
		$fieldgroups = array();
		$fieldgroups['basefield'] = $this->getJaMegafilterFieldBaseField();
		$extra_field = $this->getJaMegafilterFieldExtraField();

		return array_merge($fieldgroups, $extra_field);
	}

	function getJaMegafilterFieldBaseField() {
		$basefield = array(
				array(
						"published" => 0,
						"sort" => 0,
						"field" => "name",
						"title" => JText::_("COM_JAMEGAFILTER_TITLE"),
						"name" => JText::_("COM_JAMEGAFILTER_TITLE"),
						"filter_type" => array("value")
				),
				array(
						"published" => 0,
						"sort" => 0,
						"field" => "rating",
						"title" => JText::_("COM_JAMEGAFILTER_RATING"),
						"name" => JText::_("COM_JAMEGAFILTER_RATING"),
						"filter_type" => array("range")
				),
				array(
						"published" => 0,
						"sort" => 0,
						"field" => "attr.featured.value",
						"title" => JText::_("COM_JAMEGAFILTER_FEATURED"),
						"name" => JText::_("COM_JAMEGAFILTER_FEATURED"),
						"filter_type" => array("single", "dropdown", "select", "multiple")
				),
				array(
						"published" => 0,
						"sort" => 0,
						"field" => "attr.cat.value",
						"title" => JText::_("COM_JAMEGAFILTER_CATEGORY"),
						"name" => JText::_("COM_JAMEGAFILTER_CATEGORY"),
						"filter_type" => array("single", "dropdown", "select", "multiple")
				),
				array(
						"published" => 0,
						"sort" => 0,
						"field" => "attr.tag.value",
						"title" => JText::_("COM_JAMEGAFILTER_TAG"),
						"name" => JText::_("COM_JAMEGAFILTER_TAG"),
						"filter_type" => array("single", "dropdown", "select", "multiple")
				),
				array(
						"published" => 0,
						"sort" => 0,
						"field" => "attr.author.value",
						"title" => JText::_("COM_JAMEGAFILTER_AUTHOR"),
						"name" => JText::_("COM_JAMEGAFILTER_AUTHOR"),
						"filter_type" => array("single", "dropdown", "select", "multiple")
				),
				array(
					"published"=>0,
					"sort" => 0,	
					"field"=> "published_date",
					"title"=>JText::_("COM_JAMEGAFILTER_PUBLISHED_DATE"),
					"name"=>JText::_("COM_JAMEGAFILTER_PUBLISHED_DATE"),
					"filter_type"=>array("date")
				),			
				array(
					"published"=>0,
					"sort" => 0,	
					"field"=> "created_date",
					"title"=>JText::_("COM_JAMEGAFILTER_CREATED_DATE"),
					"name"=>JText::_("COM_JAMEGAFILTER_CREATED_DATE"),
					"filter_type"=>array("date")
				),		
				array(
					"published"=>0,
					"sort" => 0,	
					"field"=> "modified_date",
					"title"=>JText::_("COM_JAMEGAFILTER_MODIFIED_DATE"),
					"name"=>JText::_("COM_JAMEGAFILTER_MODIFIED_DATE"),
					"filter_type"=>array("date")
				),	

		);
		return $basefield;
	}

	function getSupportedField() {
		return array(
				'multipleSelect' => array("single", "dropdown", "select", "multiple", "color", "size"),
				'textfield' => array("value", "range"),
				'textarea' => array("value"),
				'labels' => array("value"),
				'select' => array("single", "dropdown", "select", "multiple", "color", "size"),
				'radio' => array("single", "dropdown", "select", "multiple", "color", "size"),
				'date' => array("date")
		);
	}

	function getJaMegafilterFieldExtraField() {
		$catList = $this->getCatList();
		$group_q = 'select efg.* from #__k2_extra_fields_groups efg'
						. ' left join #__k2_categories c on (efg.id = c.extraFieldsGroup)'
						. ' where c.id in (' . implode(',', $catList) . ')'
						. ' group by efg.id';
		$group_list = JFactory::getDbo()->setQuery($group_q)->loadObjectList();

		if (empty($group_list))
			return array();

		$text = '';
		$extra_fields = array();
		$supported_field = $this->getSupportedField();
		foreach ($group_list as $group) {
			$q = 'select * from #__k2_extra_fields where published = 1 and `group`=' . $group->id;
			$field_list = JFactory::getDbo()->setQuery($q)->loadObjectList();

			if (empty($field_list))
				continue;
			$text .= "case 'COM_JAMEGAFILTER_GROUP" . $group->id . "': 
									jQuery(this).text('" . $group->name . "');
									if (expand_span == true) {
										var span = jQuery('<span class=\"icon-menu ui-sortable-handle\"></span>');
										jQuery(this).prepend(span);
									}
									break;
								";
			foreach ($field_list as $field) {
				if (in_array($field->type, array_keys($supported_field))) {
					$extra_fields['group' . $group->id][] = array(
							"published" => 0,
							"sort" => 0,
							"field" => "attr.ct" . $field->id . ".value",
							"title" => $field->name,
							"name" => $field->name,
							"filter_type" => $supported_field[$field->type]
					);
				}
			}
		}
		$this->addScriptReplaceLanguage($text);

		return $extra_fields;
	}

	function getCatList() {
		require_once(JPATH_PLUGINS . '/jamegafilter/k2/helper.php');
		$helper = new K2FilterHelper();
		$input = JFactory::getApplication()->input;
		$catid = $input->get('catid', 0);
		$childCat = $helper->_fetchElement($catid, '', array());

		$catList = array($catid);

		foreach ($childCat as $cat) {
			$catList[] = $cat->id;
		}

		return $catList;
	}

	function addScriptReplaceLanguage($text) {
		$script = "var wait = function(selector, callback) {
		  if (jQuery(selector).length) {
			callback();
		  } else {
			setTimeout(function() {
			  wait(selector, callback);
			}, 1);
		  }
		};

		wait('.ui-tabs-tab', function() {
			var expand_span = true;
				jQuery('.ui-tabs-anchor').each(function() {
					switch(jQuery(this).text()) {
						" . $text . "
					}
				});
		});

		wait('.field-title span', function() {
			var expand_span = false;
				jQuery('.field-title span').each(function() {
					switch(jQuery(this).text()) {
						" . $text . "
					}
				});
		});	";

		JFactory::getDocument()->addScriptDeclaration($script);
	}

}
