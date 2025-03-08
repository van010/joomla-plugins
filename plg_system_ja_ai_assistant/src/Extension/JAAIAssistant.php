<?php

namespace Joomla\Plugin\System\JAAIAssistant\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Plugin\System\JAAIAssistant\Download\ImageDownload;
use Joomla\Plugin\System\JAAIAssistant\OpenAI\ImageHelper;
use Joomla\Plugin\System\JAAIAssistant\OpenAI\ChatHelper;
use Joomla\Registry\Registry;

defined('_JEXEC') or die('Restricted access');

require_once dirname(__DIR__) . '/aiEditorHelper.php';

class JAAIAssistant extends CMSPlugin
{
	public $plugin = null;
	public $plgParams = null;

	public function __construct()
	{
		$this->plugin = PluginHelper::getPlugin('system', 'jaaiassistant');
		$this->plgParams = new Registry();
		$this->plgParams->loadString($this->plugin->params);
	}

    public function onBeforeRender()
    {
        $doc = $this->getApplication()->getDocument();
        $scriptOptions = $doc->getScriptOptions('plg_editor_tinymce');

        if (!$scriptOptions) {
            return;
        }

        $pluginUrl = Uri::root(true) . '/plugins/system/jaaiassistant/media/aieditor/dist/aieditor.js';
        $scriptOptions['tinyMCE']['default']['external_plugins']['jaaiassistant'] = $pluginUrl;
		$scriptOptions['tinyMCE']['default']['toolbar'] = 'aiask | ' . $scriptOptions['tinyMCE']['default']['toolbar'];
		//$scriptOptions['tinyMCE']['default']['quickbars_selection_toolbar'] = 'aiask | ' . $scriptOptions['tinyMCE']['default']['quickbars_selection_toolbar'];
        $scriptOptions['tinyMCE']['default']['quickbars_selection_toolbar'] = 'ai_prompt | ' . $scriptOptions['tinyMCE']['default']['quickbars_selection_toolbar'];

        $doc->addScriptOptions('plg_editor_tinymce', $scriptOptions);

        HTMLHelper::stylesheet('plugins/system/jaaiassistant/media/aieditor/dist/aieditor.css');
        HTMLHelper::stylesheet('plugins/system/jaaiassistant/assets/css/ja-aiassistant.css');
		// Factory::getDocument()->addScript(Uri::root(true) . '/plugins/system/jaaiassistant/assets/js/jaieditor.js');
	    $langs = json_encode($this->parseObj('languages'));
		$tones = json_encode($this->parseObj('tones'));
        $langCommunication = json_encode($this->plgParams->get('communication'));
		Factory::getLanguage()->load('plg_system_jaaiassistant', JPATH_ADMINISTRATOR);
        $alert_api_text = json_encode(Text::_('PLG_SYSTEM_JAAIASSISTANT_API_ALERT'));
		$debug = JDEBUG ? 1 : 0;
	    $script = "
	        var configs = {
	            langs: $langs,
	            tones: $tones,
	            alertApi: $alert_api_text,
	            lang_communicate: $langCommunication,
	            debugMode: $debug
	        };
	    ";
	    $doc->addScriptDeclaration($script);
    }

    public function onAjaxJaaiassistant()
    {
        $app = $this->getApplication();
        $user = Factory::getUser();

        // if (!$app->isClient('administrator') || !$user->id) {
        if (!$user->id) {
            return;
        }

        $input = $this->getApplication()->input;
        $aitask = $input->get('aitask');
		$task = $input->get('task', '');
		if (!empty($task) && $task === 'custom_dev'){
			$chatHelper = new ChatHelper();
			$chatHelper->customDev();
			return ;
		}

        $textTasks = ['askai', 'continue_writing', 'dev_fake_api', 'ai_prompt'];
        $imageTasks = ['create_image'];

        if ($aitask === 'dev_fake_api') {
			return ChatHelper::dev();
        }elseif (in_array($aitask, $textTasks)){
			return ChatHelper::doTask();
		} else if (in_array($aitask, $imageTasks)) {
            return ImageHelper::doTask();
        }

        $task = $input->get('task');

        if ($task === 'downloadImage') {
            $url = trim($app->input->getString('url', ''));
            $url = urldecode($url);

            return ImageDownload::download($url);
        }
    }

	public function parseObj($obj)
	{
		$helper = new \aiEditorHelper();
		$options = $this->plgParams->get($obj);
		$allObjs = new \stdClass();
		if (!$options) return $allObjs;
		foreach ($options as $option) {
			$valueObj = new \stdClass();
			$value = $helper->cleanText($option->value);
			$valueObj->label = ucfirst(strtolower($value));
			$valueObj->class = '';
			$valueObj->cEvent = 'lang_' . strtolower($value);
			$valueObj->customData = '';
			$allObjs->{$obj}[] = $valueObj;
		}
		return $allObjs;
	}
}
