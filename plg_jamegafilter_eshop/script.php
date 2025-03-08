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
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class plgJamegafilterEshopInstallerScript
{
	function postflight($type, $parent) 
	{
    	$db    = Factory::getDBO();
        $query = $db->getQuery(true);
        $array = array (
            $db->quoteName('enabled').'= 1',
            $db->quoteName('params').'='.$db->quote('{}')
        );
        $query
            ->update('#__extensions')
            ->set($array)
            ->where("type='plugin'")
            ->where("folder='jamegafilter'")
            ->where("element='". "eshop" ."'");
        $db->setQuery($query);
        $db->execute();
	}
}