<?php
/**
 * ------------------------------------------------------------------------
 * JA K2 To Com Content Migration Plugin for J25 & J34
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2011 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - GNU/GPL, http://www.gnu.org/licenses/gpl.html
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites: http://www.joomlart.com - http://www.joomlancers.com
 * ------------------------------------------------------------------------
 */

defined('_JEXEC') or die;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
jimport('joomla.plugin.plugin');

defined('DS') or define('DS', DIRECTORY_SEPARATOR);
if (!class_exists( 'VmConfig' ) && file_exists(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php')) {
	require_once(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php');
if (!class_exists('ShopFunctions') && file_exists(VMPATH_ADMIN . DS . 'helpers' . DS . 'shopfunctions.php'))
    require_once(VMPATH_ADMIN . DS . 'helpers' . DS . 'shopfunctions.php');
    $vmconfig = VmConfig::loadConfig();
}

class PlgSystemJamegafilter extends JPlugin
{
	protected $pathField = '';
	protected $pathForm = '';

	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->pathField 	= dirname(__FILE__) . '/fields/';
		$this->pathForm 	= dirname(__FILE__) . '/forms/';
	}
	

	public function onCheckComponent($component) {
		return $this->checkComponent('com_'.str_replace('com_','',$component));
	}
	
	public function onListType() {
		$path = JPATH_PLUGINS.'/system/jamegafilter/';
		if (JFolder::exists(JPATH_PLUGINS.'/jamegafilter/')) {
			$path = JPATH_PLUGINS.'/jamegafilter/';
		}
		$folders = JFolder::folders($path);
		return $folders;
	}
	
	public function onExportById($obj) {
		$params = json_decode($obj->params);
		JPluginHelper::importPlugin('jamegafilter');
		$dispatcher = JEventDispatcher::getInstance();
		$path = JPATH_SITE.'/media/com_jamegafilter/';
		if(!JFolder::exists($path)) {
			JFolder::create($path, 0755);
		}

		$result = $dispatcher->trigger('onAfterSave'.ucfirst($params->jatype).'Items', array($params));
		$items = $result[0];

		foreach ($items as $key => $item) 
		{
			$json = json_encode($item);
			JFile::write($path.'/'.$key.'/'.$obj->id.'.json', $json);
		}
	}
	
	public function onExportByAll() {
		
	}
	
	public function onExportByType($type) {
		// for event hook in every components;
	}
	
	protected function checkComponent($component)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('extension_id'))
			->from('#__extensions')
			->where($db->quoteName('element') .'='.$db->quote($component))
			->where($db->quoteName('enabled') .'='.$db->quote('1'));
		$db->setQuery($query);
		return $db->loadResult();
	}
}