<?php
/**
 * $JA#COPYRIGHT$
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$plgParams = array(
	'api_version' => '3',
	'context_menu' => 1,
	'mode' => 'normal',
	'locations' => '{}',
	'to_location' => 'New York',
	'target_lat' => 0.000000,
	'target_lon' => 0.000000,
	'to_location_info' => '',
	'to_location_changeable' => 0,
	'from_location' => '',
	'map_width' => 500,
	'map_height' => 300,
	'maptype' => 'standard',
	'maptype_control_display' => 1,
	'maptype_control_style' => 'drop_down',
	'maptype_control_position' => 'RT',
	'toolbar_control_display' => 1,
	'toolbar_control_style' => 'small',
	'toolbar_control_position' => 'LT',
	'display_layer' => 'none',
	'display_scale' => 1,
	'display_overview' => 1,
	'zoom' => 10,
	'api_key' => '',
// 	'sensor' => 0,
	'display_popup' => 0,
	'popup_width' => 640,
	'popup_height' => 480,
	'popup_type' => 'click',
	'map_styles'=>'',
	'disable_scrollwheelzoom'=>0,
	'clustering'=>0,
	'center'=>'all',
	'custom_tile'=> '',
	'custom_style'=> '',
	'custom_style_token'=> '',
	'route' => 'mapbox',
	'mapbox_access_token' => '',
	'routing_language' => 'auto',
	'osrm_service' => 'https://router.project-osrm.org/route/v1'
);
$aUserSetting = $this->mapSetting;

//
$map = new stdClass();

$map->id = $this->mapId;
$aOptions = array();

foreach ($plgParams as $var => $value) {
    $map->$var = (isset($aUserSetting[$var])) ? $aUserSetting[$var] : $this->plgParams->get($var, $value);
	
    if (is_int($value)) {
        $map->$var = intval($map->$var);
    } elseif (is_float($value)) {
        $map->$var = floatval($map->$var);
    }

    if (is_int($map->$var) || is_float($map->$var)) {
        $aOptions[$var] = $map->$var;
    } else if($var=='map_styles'){
        $str = $map->$var;
        $str = preg_replace('/(\n|\r\n|\/)/', '', $str);
		if($this->plgParams->get('mapstyles_control_display') == 0) $str='';
        $aOptions[$var] = $str;
    }else{
        $str = $map->$var;
        //$str = preg_replace('/(\n|\r\n|\'|\"|\/)/', '', $str);
        $aOptions[$var] = $str;
    }

}

$aOptions['scrollwheel'] = ($this->plgParams->get('disable_scrollwheelzoom','0') == '1') ? 'false' : 'true';
$langs = array(
	'ar-aa' => 'ar',
	'da-dk' => 'da',
	'de-de' => 'de',
	'en-gb' => 'en',
	'eo-xx' => 'eo',
	'es-es' => 'es-ES',
	'es-co' => 'es',
	'fi-fi' => 'fi',
	'fr-fr' => 'fr',
	'he-il' => 'he',
	'id-id' => 'id',
	'it-it' => 'it',
	'ko-kr' => 'ko',
	'my-my' => 'my',
	'nl-nl' => 'no',
	'no-nb' => 'no',
	'pl-pl' => 'pl',
	'pt-br' => 'pt-BR',
	'pt-pt' => 'pt-PT',
	'ro-ro' => 'ro',
	'ru-ru' => 'ru',
	'sl-si' => 'sl',
	'sv-se' => 'sv',
	'tr-tr' => 'tr',
	'uk-ua' => 'uk',
	'vi-vn' => 'vi',
	'zh-cn' => 'zh-Hans'
);

$current = strtolower(Factory::getLanguage()->getTag());
if ($aOptions['routing_language'] === 'auto' && isset($langs[$current])) {
	$aOptions['routing_language'] = $current;
}

//exception: don't use default value of from_location
//because: google map can not calculate direction for every case

$map_id = 'ja-leaflet-map' . $map->id;

$popup_type = ($map->popup_type != 'global') ? 'modal="'.$map->popup_type.'"' : '';

//support unit in width and height
$mapwidth  = (isset($aUserSetting['map_width'])) ? $aUserSetting['map_width'] : $this->plgParams->get('map_width', $value);
$mapheight = (isset($aUserSetting['map_height'])) ? $aUserSetting['map_height'] : $this->plgParams->get('map_height', $value);
preg_match('/^(-?\d*\.?\d+)(px|%|em|rem|pc|ex|in|deg|s|ms|pt|cm|mm|rad|grad|turn)?/', $mapwidth . '', $map_width);
preg_match('/^(-?\d*\.?\d+)(px|%|em|rem|pc|ex|in|deg|s|ms|pt|cm|mm|rad|grad|turn)?/', $mapheight . '', $map_height);
if($map_width && isset($map_width[1])){
	$mapwidth = $map_width[1] . (isset($map_width[2]) ? $map_width[2] : 'px');
}
if($map_height && isset($map_height[1])){
	$mapheight = $map_height[1] . (isset($map_height[2]) ? $map_height[2] : 'px');
}
?>

<div class="map-container" style="height:<?php echo $mapheight; ?>;width:<?php echo $mapwidth; ?>;max-width:100%;">
	<div id="<?php echo $map_id; ?>" style="width:100%;height:100%"></div>
</div>


<script type="text/javascript">
(function(root, $) {
        var mapid = '<?php echo $map_id ?>';
        var settings = <?php echo json_encode($aOptions); ?>;
        
        var map = new root.JAOSMAP;
        map.render(mapid, settings);
})(window, jQuery);
</script>

<style>
#<?php echo $map_id ?>.swiping::after {
	content: '<?php echo Text::_('JAOSMAP_USE_TWO_FINGERS_TO_MOVE_THE_MAP'); ?>';
	color: #fff;
	font-size: 24px;
	font-weight: 300;
	justify-content:center;
	display: flex;
	align-items: center;
	padding: 15px;
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	z-index: 500;
	pointer-events:none;
	text-align: center;
}
</style>
