<?php
/**
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

// no direct access
defined('_JEXEC') or die ('Restricted access');

// Initiate class to hold plugin events
class plgJamegafilterK2 extends JPlugin {

	// Some params
	var $pluginName = 'jamegafilterk2';

	function __construct( & $subject, $params) {
		parent::__construct($subject, $params);
	}
	
	function onAfterSaveK2Items($item) {
		require_once (__DIR__.'/helper.php');
		$params = $item->params;
		$helper = new K2FilterHelper($params);
		$objectList = $helper->getFilterItems($params['categoryk2']);
		return $objectList;
	}
	
	function onBeforeDisplayK2Items( $jstemplate, $filter_config, $item )
	{
		$this->jstemplate = $jstemplate;
		$this->config = $filter_config;
		$this->item = $item;
		$input = JFactory::getApplication()->input;
		$jalayout = $input->get('jalayout', 'default');
		$path = JPluginHelper::getLayoutPath('jamegafilter', 'k2', $jalayout);
		
		ob_start();
		include $path;
		$output = ob_get_contents();
		ob_end_clean();
		echo $output;
	}

}