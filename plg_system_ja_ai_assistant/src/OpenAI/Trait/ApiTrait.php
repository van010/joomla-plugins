<?php

namespace Joomla\Plugin\System\JAAIAssistant\OpenAI\Trait;

use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

defined('_JEXEC') or die('Restricted access');

trait ApiTrait
{
    protected static function getApiKey()
    {
		$plugin = PluginHelper::getPlugin('system', 'jaaiassistant');
		$pluginParams = new Registry();
		$pluginParams->loadString($plugin->params);
		$apiKey = $pluginParams->get('api_key');
        return $apiKey;
    }
}
