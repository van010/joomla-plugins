<?php
/**
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

// no direct access
defined('_JEXEC') or die ('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;

// Initiate class to hold plugin events
class plgJamegafilterDocman extends CMSPlugin {

	// Some params
	var $pluginName = 'jamegafilterdocman';
	public $item = null;
	public $config = null;
	public $jstemplate = null;

	function __construct( & $subject, $params) {
		parent::__construct($subject, $params);
	}
	
	function onAfterSaveDocmanItems($item) {
		require_once (__DIR__.'/helper.php');
		$params = $item->params;
		$helper = new DocmanFilterHelper($params);
		$objectList = $helper->getFilterItems($params['docmancategories']);
		return $objectList;
	}
	
	function onBeforeDisplayDocmanItems( $jstemplate, $filter_config, $item )
	{
		$this->jstemplate = $jstemplate;
		$this->config = $filter_config;
		$this->item = $item;
		$input = Factory::getApplication()->input;
		$jalayout = $input->get('jalayout', 'default');
		$path = PluginHelper::getLayoutPath('jamegafilter', 'docman', $jalayout);
		
		ob_start();
		include $path;
		$output = ob_get_contents();
		ob_end_clean();
		echo $output;
	}

}