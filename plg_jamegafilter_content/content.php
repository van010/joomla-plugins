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

// no direct access
defined('_JEXEC') or die ('Restricted access');

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;

// Initiate class to hold plugin events
class plgJamegafilterContent extends CMSPlugin {

	// Some params
	var $pluginName = 'jamegafiltercontent';
	public $item = null;
	public $config = null;
	public $jstemplate = null;

	function __construct( &$subject, $params) {
		parent::__construct($subject, $params);
	}

	function onBeforeSaveContentItems( &$params ) {
		require_once __DIR__ . '/helper.php';
		require_once __DIR__ . '/cacheData.php';
		
		$helper = new ContentFilterHelper($params);
		$cacheHelper = new megafilterCacheData($params);

		// firstly cache some data
		$cacheHelper->main();
		$order = $params['filterfields']['filter_order']['order'];
		foreach ($order as $key => $value) {
			if (!$helper->checkPublished($key)) {
				unset($order[$key]);
			}
		}

		$custom_order = $this->getCustomOrdering($helper, $order);
		$params['filterfields']['filter_order']['custom_order'] = $custom_order;
	}
	
	// save data into json file
	function onAfterSaveContentItems($item) {
		require_once (__DIR__.'/helper.php');
		require_once __DIR__ . '/cacheData.php';
		// firstly cache some data
		$cacheHelper = new megafilterCacheData($item->get('params'));
		$cacheHelper->main();
		$input = Factory::getApplication()->input;
		// echo '<pre>';print_r($input);echo '</pre>'; die;
		$task = $input->get('task'); // cron
		$itemId = $input->get('Itemid');
		$filterId = $input->get('id');
		$view = $input->get('view');
		$token = $input->get('token'); //
		$option = $input->get('option'); // com_jamegafilter


		$params = $item->params;
		$helper = new ContentFilterHelper($params);
		$objectList = $helper->getFilterItems($params['contentcategories']);

		// clear all cache after saving params into db, handling content data
		$cacheFiles = [
			JPATH_ROOT . '/cache/all_users.json', 
			JPATH_ROOT . '/cache/all_cats.json',
			JPATH_ROOT . '/cache/all_articles_in_cats.json',
			JPATH_ROOT . '/cache/all_tags.json',
			JPATH_ROOT . '/cache/all_fields.json',
		];
		foreach($cacheFiles as $file){
			if (is_file($file)){
				unlink($file);
			}
		}

		return $objectList;
	}
	
	function onBeforeDisplayContentItems( $jstemplate, $filter_config, $item )
	{
		$this->jstemplate = $jstemplate;
		$this->config = $filter_config;
		$this->item = $item;
		$input = Factory::getApplication()->input;
		$jalayout = $input->get('jalayout', 'default');
		$path = PluginHelper::getLayoutPath('jamegafilter', 'content', $jalayout);
		
		ob_start();
		include $path;
		$output = ob_get_clean();
		echo $output;
	}

	function getCustomOrdering($helper, $config ) {
		$ordering = array();
		
		foreach ($config as $key => $value) {
			if ($key === 'attr.cat.value') {
				$catid = $helper->_params->get('contentcategories', 0);

				$catid = $catid ? $catid : 1;
				$childOrder = 'rgt ASC';
				switch ($value) {
					case 'name_asc':
						$childOrder = 'title ASC';
						break;
					case 'name_desc':
						$childOrder = 'title DESC';
						break;
					case 'ordering_asc':
						$childOrder = 'rgt ASC';
						break;
					case 'ordering_desc':
						$childOrder = 'rgt DESC';
						break;
				}

				$catList = $helper->getCatList($catid, $childOrder);
				$catList = array_map(function ($id) {
					return (string) $id;
				}, $catList);
				$ordering[$key] = $catList;
				
				continue;
			}

			if (!in_array($value, array('ordering_asc', 'ordering_desc'))) {
				continue;
			}

			preg_match('/ct(\d+)/', $key, $matches);
			if (!count($matches)) {
				continue;
			}

			$id = +$matches[1];

			$db = Factory::getDbo();
			$query = "SELECT `fieldparams` 
					FROM `#__fields` 
					WHERE id = $id
					AND state = 1";
			$row = $db->setQuery($query)->loadResult();
			
			$params = new Registry($row);
			$options = (array) $params->get('options');

			$fieldOrder = array();
			foreach ($options as $opt) {
				$fieldOrder[] = $opt->value;
			}

			if ($value === 'ordering_desc') {
				$fieldOrder = array_reverse($fieldOrder);
			}

			$ordering[$key] = $fieldOrder;
		}

		return $ordering;
	}
}