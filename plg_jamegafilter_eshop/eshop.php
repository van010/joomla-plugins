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

// no direct access
defined('_JEXEC') or die ('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;

if (file_exists(JPATH_ROOT.'/components/com_eshop/helpers/helper.php'))
	require_once(JPATH_ROOT.'/components/com_eshop/helpers/helper.php');
if (file_exists(JPATH_ROOT.'/components/com_eshop/helpers/customer.php'))
	require_once(JPATH_ROOT.'/components/com_eshop/helpers/customer.php');
if (file_exists(JPATH_ROOT.'/components/com_eshop/helpers/image.php'))
	require_once(JPATH_ROOT.'/components/com_eshop/helpers/image.php');
if (file_exists(JPATH_ROOT.'/components/com_eshop/helpers/currency.php'))
	require_once(JPATH_ROOT.'/components/com_eshop/helpers/currency.php');

// Initiate class to hold plugin events
class plgJamegafilterEshop extends CMSPlugin {

	// Some params
	var $pluginName = 'jamegafiltereshop';
	var $pluginNameHumanReadable = 'JA Megafilter Eshop Plugin';
	public $item = null;
	public $config = null;
	public $jstemplate = null;

	function __construct( & $subject, $params) {
		parent::__construct($subject, $params);
	}
	
	function onAfterSaveEshopItems($item) {
		require_once (__DIR__.'/helper.php');
		$params = $item->params;
		$helper = new EshopFilterHelper($params);
		$objectList = $helper->getFilterItems($params['jaescat']);
		return $objectList;
	}
	
	function onBeforeDisplayEshopItems( $jstemplate, $filter_config, $item )
	{
		$this->item = $item;
		$this->jstemplate = $jstemplate;
		$this->config = $filter_config;
		$input = Factory::getApplication()->input;
		$jalayout = $input->get('jalayout', 'default');
		$path = PluginHelper::getLayoutPath('jamegafilter', 'eshop', $jalayout);
		
		ob_start();
		include $path;
		$output = ob_get_contents();
		ob_end_clean();
		echo $output;
	}
	
	function getProductQuantity($pIds) {
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id, product_quantity')
			->from('#__eshop_products AS a')
			->where('a.id IN (' . implode(',', $pIds) . ')');
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		$array = array();
		foreach ($rows AS $row) {
			$array[$row->id] = $row->product_quantity;
		}
		return $array;
	}
}