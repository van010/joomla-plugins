<?php
/**
 * $JA#COPYRIGHT$
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
/**
 *
 * JA LEAFLET MAP PLUGIN SYSTEM CLASS
 * @author JoomlArt
 *
 */
class plgSystemJaosmap extends CMSPlugin
{
    protected $_plgCodeNew = "#{jaosmap(.*?)}\s*{/jaosmap}#i";
    protected $_plgCode = "#{jaosmap(.*?)}#i";
    protected $mapSetting = array();
    protected $mapId = null;
    public $plugin = null;
    public $plgParams = null;


    /**
     *
     * Construct JA Googla Map
     * @param object $subject
     * @param object $config
     */
    function __construct(&$subject, $config)
    {
        $mainframe = Factory::getApplication();
        parent::__construct($subject, $config);

        $this->plugin = PluginHelper::getPlugin('system', 'jaosmap');
        $this->plgParams = new Registry();
        $this->plgParams->loadString($this->plugin->params);
    }

    function onBeforeRender()
    {
        HTMLHelper::_('jquery.framework');
        HTMLHelper::_('behavior.core');
        $lang = Factory::getLanguage();
        $extension = 'plg_system_jaosmap';
        $base_dir = JPATH_ADMINISTRATOR;
        $language_tag = 'en-GB';
        $lang->load($extension, $base_dir, $language_tag, true);
    }

    function onAfterRender()
    {
        $app = Factory::getApplication();
        if ($app->isClient('administrator')) {
            return;
        } 
        $body = $app->getBody();
        
        $plgParams = $this->plgParams;
        $disable_map = $plgParams->get('disable_map', 0);

        if ($disable_map) {
            $body = $this->removeCode($body);
            $app->setBody($body);
            return;
        }

        if (!preg_match($this->_plgCodeNew, $body) && !preg_match($this->_plgCode, $body)) {
            return;
        }

        $pattern = '#value\s*=\s*"[^"]*?{jaosmap.*?}(\s*{/jaosmap})?[^"]*?"#';
        $pattern2 = '#<textarea[^>]*?>[\s\S]*?{jaosmap.*?}(\s*{/jaosmap})?[\s\S]*?</textarea>#';
        $pattern3 = '#<head[^>]*?>[\s\S]*?{jaosmap.*?}(\s*{/jaosmap})?[\s\S]*?</head>#';
        $body = preg_replace_callback($pattern, array($this, 'escapeMap'), $body);
        $body = preg_replace_callback($pattern2, array($this, 'escapeMap'), $body);
        $body = preg_replace_callback($pattern3, array($this, 'escapeMap'), $body);

        //generate map
        $body = preg_replace_callback($this->_plgCodeNew, array($this, 'genMap'), $body);
        $body = preg_replace_callback($this->_plgCode, array($this, 'genMap'), $body);

        //restore short codes
        $body = str_replace(array('{[jaosmap]', '{/[jaosmap]}'), array('{jaosmap', '{/jaosmap}'), $body);

        $headtag = array();
        $headtag[] = '<link href="' . Uri::root() . 'plugins/system/jaosmap/assets/leaflet/leaflet.css" type="text/css" rel="stylesheet" />';
        $headtag[] = '<link href="' . Uri::root() . 'plugins/system/jaosmap/assets/leaflet-routing-machine/leaflet-routing-machine.css" type="text/css" rel="stylesheet" />';
        $headtag[] = '<link href="https://api.tiles.mapbox.com/mapbox-gl-js/v0.35.1/mapbox-gl.css" type="text/css" rel="stylesheet" />';

        $headtag[] = '<script src="' . Uri::root() . 'plugins/system/jaosmap/assets/leaflet/leaflet.js" type="text/javascript" ></script>';
        $headtag[] = '<script src="' . Uri::root() . 'plugins/system/jaosmap/assets/leaflet-routing-machine/leaflet-routing-machine.min.js" ></script>';
        $headtag[] = '<script src="https://api.tiles.mapbox.com/mapbox-gl-js/v0.35.1/mapbox-gl.js" ></script>';
        $headtag[] = '<script src="'. Uri::root() . 'plugins/system/jaosmap/assets/mapbox-gl-leaflet-master/leaflet-mapbox-gl.js" ></script>';
        $headtag[] = '<script src="'. Uri::root() . 'plugins/system/jaosmap/assets/jaosmap.js" ></script>';

        $body = str_replace('</head>', "\t" . implode("\n", $headtag) . "\n</head>", $body);

        $app->setBody($body);
    }

    function escapeMap($matches) {
        return str_replace(array('{jaosmap', '{/jaosmap}'), array('{[jaosmap]', '{/[jaosmap]}'), $matches[0]);
    }
    
    function genMap($matches) {
    	static $mapid = 0;
    	$mapid++;
    	
        $this->mapId = $mapid;
    	$this->mapSetting = $this->parseAttributes($matches[0]);
        $output = $this->loadLayout($this->plugin, 'default');
        return $output;
    }
    
    /**
     * @ref JUtility::parseAttributes
     */
    protected function parseAttributes($string)
    {
        $attr = array();
        $retarray = array();

        $string = preg_replace("/\\\\'/", '__QUOTE__', $string);
        // Let's grab all the key/value pairs using a regular expression
        preg_match_all("/([\w:-]+)[\s]?=[\s]?'([^']*)'/i", $string, $attr);

        if (is_array($attr))
        {
            $numPairs = count($attr[1]);

            for ($i = 0; $i < $numPairs; $i++)
            {
                $retarray[$attr[1][$i]] = str_replace('__QUOTE__', "\'", $attr[2][$i]);
            }
        }

        return $retarray;
    }


    /**
     *
     * Remove map code tag
     * @param string $content
     * @return string
     */
    function removeCode($content)
    {
        return preg_replace($this->_plgCodeNew, '', $content);
        return preg_replace($this->_plgCode, '', $content);
    }

    /**
     *
     * Load content into layout
     * @param object $plugin
     * @param string $layout
     * @return string
     */
    function loadLayout($plugin, $layout = 'default')
    {
        $layout_path = PluginHelper::getLayoutPath('system', 'jaosmap');
        if ($layout_path) {
            ob_start();
            require $layout_path;
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }
        return '';
    }


	/*
	* AJAX Call to transfer location address to lat long.
	* @param array all address without latlong.
	*/
	function onAjaxJaosmap()
	{
		$latlong = [];
		$jinput = Factory::getApplication()->input;
		$address = $jinput->get('address', array(), 'ARRAY');
        $option  = new Registry();
        $option->set('userAgent', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0');

        foreach ($address as $k => $add) {
            $uri = 'https://nominatim.openstreetmap.org/search?q='.urlencode($add).'&format=json&addressdetails=0';
            try {
                $response = HttpFactory::getHttp($option)->get($uri);
            }catch (RuntimeException $e){
                return;
            }
            if ($response->code != 200){
                throw new RuntimeException('Unable to open the feed!');
                return;
            }
            $content = json_decode($response->body);
            if (!empty($content[0]) && !empty($content[0]->lat) && !empty($content[0]->lon)) {
                $latlong[$k] = [$content[0]->lat, $content[0]->lon];
            }
        }
		return $latlong;
	}
}