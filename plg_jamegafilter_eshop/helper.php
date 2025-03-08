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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\LanguageHelper;

$route = require_once JPATH_ROOT . '/components/com_eshop/helpers/' . ((version_compare(JVERSION, '3.0', 'ge') && Multilanguage::isEnabled() && count(EshopHelper::getLanguages()) > 1) ? 'routev3.php' : 'route.php');
require_once JPATH_ROOT . '/components/com_eshop/helpers/tax.php';

if (file_exists($route) && !class_exists('EshopRoute')) require_once($route);

JLoader::register('BaseFilterHelper', JPATH_ADMINISTRATOR .'/components/com_jamegafilter/base.php');

class EshopFilterHelper extends BaseFilterHelper
{

	public $_params = null;

	function __construct($params = array())
	{
		if (Multilanguage::isEnabled() && count(EshopHelper::getLanguages())) {
			define('ESHOP_MULTILINGUAL', 1);
		}
        $this->_params = new Registry($params);
		return parent::__construct($params);
	}

	function getFilterItems($catid)
	{
		$config = EshopHelper::getConfig();
		$categories = array();
		$include_root = $this->_params->get('include_root', self::INCLUDE_ROOT);
		$subcat = $this->_params->get('subcat', self::ALL);

		if ($include_root === self::INCLUDE_ROOT && $catid !== '0') {
			$categories[] = $catid;
		}

		if ($subcat !== self::NONE) {
			$maxLevel = $subcat === self::ALL ? 100 : (int) $subcat;
			$children = $this->getChildCategories($catid, $maxLevel);
			foreach ($children as $child) {
				$categories[] = $child->id;
			}
		}

		$lang = Factory::getLanguage();
		$languages = LanguageHelper::getLanguages();
		$filterItems = array();
		foreach ($languages AS $lang) {
			$filterItems[str_replace('-', '_', strtolower($lang->lang_code))] = $this->getItemList($categories, $lang);
		}
		return $filterItems;
	}
	
	function getItemList($categories, $lang) {
		if (!count($categories)) {
			return array();
		}
		
		$item = new stdClass();
		$itemIdList = $this->getProductCategories($categories, $lang->lang_code);
		$itemList = new stdCLass();

		foreach ($itemIdList as $id) {
			$property = 'item_'.$id;
			$item = $this->getItem($id, $lang, $categories);
			if( !empty($item))
				$itemList->{ $property } = $item;
			else
				continue;
		}
		return $itemList;
	}

	function getItem($id, $lang, $categories) {
		$db = Factory::getDbo();
		$item = new stdClass();
		$item->attr = array();
		$baseItem = EshopHelper::getProduct($id, $lang->lang_code);
		if(!$baseItem) return; // quick fix for unknow language
		$item->id = $id;
		$item->name = $baseItem->product_name;
		$currency = new EshopCurrency();
		$item->is_salable = (int)$baseItem->product_quantity > 0 ? true : false;
		
		// prices display
		$productPriceArray = EshopHelper::getProductPriceArray($id, ($baseItem->product_price));
        $tax = new EshopTax(EshopHelper::getConfig());        
        if (!empty($productPriceArray['salePrice']))
        {
            $item->base_price = ($productPriceArray['basePrice']);
            $item->frontend_base_price = $currency->format($tax->calculate($productPriceArray['basePrice'], $baseItem->product_taxclass_id, EshopHelper::getConfigValue('tax')));
            $item->price = ($productPriceArray['salePrice']);
            $item->frontend_price = $currency->format($tax->calculate($productPriceArray['salePrice'], $baseItem->product_taxclass_id, EshopHelper::getConfigValue('tax')));
        }
        else
        {
            $item->price = ($productPriceArray['basePrice']);
            $item->frontend_price = $currency->format($tax->calculate($productPriceArray['basePrice'], $baseItem->product_taxclass_id, EshopHelper::getConfigValue('tax')));
        }
        if (EshopHelper::getConfigValue('tax') && EshopHelper::getConfigValue('display_ex_tax'))
        {
            if (!empty($productPriceArray['salePrice']))
            {
                $item->exprice = $currency->format($productPriceArray['salePrice']);
            }
            else
            {
                $item->exprice = $currency->format($productPriceArray['basePrice']);
            }
        }

		$item->rating = ceil(EshopHelper::getProductRating($id) ?? 0);
		$item->width_rating = $item->rating*20;

		$item->attr = array();
		if ($this->checkDisplayOnFO('name')) {
			$item->attr['name']['frontend_value'] = $item->name;
			$fieldconfig = $this->getFieldConfig('name');
			$item->attr['name']['title'] = array($fieldconfig['title']);
			$item->attr['name']['type'] = $fieldconfig['type'];
		}

		if ($this->checkDisplayOnFO('price')) {
			$item->attr['price']['frontend_value'] = $item->frontend_price;
			$fieldconfig = $this->getFieldConfig('price');
			$item->attr['price']['title'] = array($fieldconfig['title']);
			$item->attr['price']['type'] = $fieldconfig['type'];
		}

		if ( $this->checkDisplayOnFO('rating')) {
			$item->attr['rating']['frontend_value'] = $item->width_rating;
			$fieldconfig = $this->getFieldConfig('rating');
			$item->attr['rating']['title'] = array($fieldconfig['title']);
			$item->attr['rating']['type'] = $fieldconfig['type'];
		}

		if ($baseItem->created_date != '0000-00-00 00:00:00') {
			if ($this->checkPublished('attr.created_date.value')) {
				$item->attr['created_date']['value'] = array( strtotime($baseItem->created_date??'') );
				$item->attr['created_date']['frontend_value'] = array( strtotime($baseItem->created_date??'') );
			}

			if ($this->checkDisplayOnFO('attr.created_date.value')) {
				$item->attr['created_date']['frontend_value'] = array( strtotime($baseItem->created_date??'') );
				$fieldconfig = $this->getFieldConfig('attr.created_date.value');
				$item->attr['created_date']['title'] = array($fieldconfig['title']);
				$item->attr['created_date']['type'] = $fieldconfig['type'];
			}
		}
		
		if ($baseItem->modified_date != '0000-00-00 00:00:00') {
			if ($this->checkPublished('attr.modified_date.value')) {
				$item->attr['modified_date']['value'] = array( strtotime($baseItem->modified_date??'') );
				$item->attr['modified_date']['frontend_value'] = array( strtotime($baseItem->modified_date??'') );
			}

			if ($this->checkDisplayOnFO('attr.modified_date.value')) {
				$item->attr['modified_date']['frontend_value'] = array( strtotime($baseItem->modified_date??'') );
				$fieldconfig = $this->getFieldConfig('attr.modified_date.value');
				$item->attr['modified_date']['title'] = array($fieldconfig['title']);
				$item->attr['modified_date']['type'] = $fieldconfig['type'];
			}
		}

		$item->shopper_groups = !empty($baseItem->product_customergroups) ? explode(',',$baseItem->product_customergroups) : array();

		$maincat = EshopHelper::getProductCategory($id);
		$item->maincat = $maincat; // need catid for override.

		$url = Route::_(EshopRoute::getProductRoute($item->id, $item->maincat, $lang->lang_code));
		$uriLeng = mb_strlen(Uri::root(true));
		$item->url = mb_substr($url, $uriLeng);
		// get image from eshop
		$thumbImageSizeFunction = EshopHelper::getConfigValue('thumb_image_size_function', 'resizeImage');
		// Main image resize
		if ($baseItem->product_image && File::exists(JPATH_ROOT.'/media/com_eshop/products/' . $baseItem->product_image))
		{
			if (EshopHelper::getConfigValue('product_use_image_watermarks'))
			{
				$watermarkImage = EshopHelper::generateWatermarkImage(JPATH_ROOT . '/media/com_eshop/products/' . $baseItem->product_image);
				$productImage = $watermarkImage;
			}
			else
			{
				$productImage = $baseItem->product_image;
			}
			$thumbImage = call_user_func_array(array('EshopHelper', $thumbImageSizeFunction), array($productImage, JPATH_ROOT . '/media/com_eshop/products/', EshopHelper::getConfigValue('image_thumb_width'), EshopHelper::getConfigValue('image_thumb_height')));
		}
		else
		{
			$thumbImage = call_user_func_array(array('EshopHelper', $thumbImageSizeFunction), array('no-image.png', JPATH_ROOT . '/media/com_eshop/products/', EshopHelper::getConfigValue('image_thumb_width'), EshopHelper::getConfigValue('image_thumb_height')));
		}
		$item->thumbnail = 'media/com_eshop/products/resized/' . $thumbImage;

		$_categories = EshopHelper::getProductCategories($id, $lang->lang_code);
		$cdata = array();
		foreach ($_categories AS $cat) {
			if (!in_array($cat->id, $categories)) {
				continue;
			}

			$parents = EshopHelper::getParentCategories($cat->id, $lang->lang_code);
			$catTree = array();
			foreach ($parents as $p) {
			 	if (in_array($p->id, $categories)) {
			 		$catTree[] = $p;
			 	}
			}

			$cats = array();
			foreach ($catTree as $key => $_cat) {
				$tmp = new stdClass;
				$tmp->id = $_cat->id;
				$tmp->category_name = $_cat->category_name;

				$rest = array_slice($catTree, $key + 1);
				foreach ($rest as $c) {
					$tmp->category_name =  $c->category_name . ' &raquo; ' . $tmp->category_name;
				}

				$cats[] = $tmp;
			}

			$cdata['value'] = array();
			$cdata['frontend_value'] = array();
			foreach ($cats as $c) {
				if (!in_array($c->id, $cdata['value'])) {
					$cdata['value'][] = $c->id;
					$cdata['frontend_value'][] = $c->category_name;
				}
			}
		}

		if ($this->checkPublished('attr.cat.value')) {
			$item->attr['cat'] = $cdata;
		}

		if ($this->checkDisplayOnFO('attr.cat.value')) {
			$item->attr['cat']['frontend_value'] = $cdata['frontend_value'];
			$fieldconfig = $this->getFieldConfig('attr.cat.value');
			$item->attr['cat']['title'] = array($fieldconfig['title']);
			$item->attr['cat']['type'] = $fieldconfig['type'];
		}

		$manufacturers = EshopHelper::getProductManufacturer($id, $lang->lang_code);
		$mdata = array();
		if ($manufacturers) {
			$mdata['value'] = array( $manufacturers->id );
			$mdata['frontend_value']= array( $manufacturers->manufacturer_name );
			
			if ($this->checkPublished('attr.manu.value')) {
				$item->attr['manu'] = $mdata;
			}
			
			if ($this->checkDisplayOnFO('attr.manu.value')) {
				$item->attr['manu']['frontend_value'] = $mdata['frontend_value'];
				$fieldconfig = $this->getFieldConfig('attr.manu.value');
				$item->attr['manu']['title'] = array($fieldconfig['title']);
				$item->attr['manu']['type'] = $fieldconfig['type'];
			}
		}
		
		$tags = EshopHelper::getProductTags($id);
		$tdata = array();
		foreach ($tags AS $tag) {
			$tdata['value'][] = $tag->id;
			$tdata['frontend_value'][] = $tag->tag_name;
		}
		if($tags) {		
			if ($this->checkPublished('attr.tag.value')) {
				$item->attr['tag'] = $tdata;
			}

			if ($this->checkDisplayOnFO('attr.tag.value')) {
				$item->attr['tag']['frontend_value'] = $tdata['frontend_value'];
				$fieldconfig = $this->getFieldConfig('attr.tag.value');
				$item->attr['tag']['title'] = array($fieldconfig['title']);
				$item->attr['tag']['type'] = $fieldconfig['type'];
			}
		}

		$featured = $baseItem->product_featured;
		if ($this->checkPublished('attr.featured.value')) {
			$item->attr['featured']['value'] = array($featured);
			$item->attr['featured']['frontend_value'] = $featured ? array(Text::_('COM_JAMEGAFILTER_ONLY_FEATURED')) : array(Text::_('COM_JAMEGAFILTER_NOT_FEATURED'));
		}
		
		if ($this->checkDisplayOnFO('attr.featured.value')) {
			$item->attr['featured']['frontend_value'] = $featured ? array(Text::_('COM_JAMEGAFILTER_ONLY_FEATURED')) : array(Text::_('COM_JAMEGAFILTER_NOT_FEATURED'));
			$fieldconfig = $this->getFieldConfig('attr.featured.value');
			$item->attr['featured']['title'] = array($fieldconfig['title']);
			$item->attr['featured']['type'] = $fieldconfig['type'];
		}

		$dimension_unit = $this->getLengthUnit($baseItem->product_length_id);
		$weight_unit = $this->getWeightUnit($baseItem->product_weight_id);

		$product_weight = $baseItem->product_weight ? +$baseItem->product_weight : 0;
		if ($this->checkPublished('product_weight')) {
			$item->product_weight = $product_weight;
		}

		if ($this->checkDisplayOnFO('product_weight')) {
			$item->attr['product_weight']['frontend_value'] =  $product_weight . $weight_unit;
			$fieldconfig = $this->getFieldConfig('product_weight');
			$item->attr['product_weight']['title'] = array($fieldconfig['title']);
			$item->attr['product_weight']['type'] = $fieldconfig['type'];
		}

		$product_width = $baseItem->product_width ? +$baseItem->product_width : 0 ;
		if ($this->checkPublished('product_width')) {
			$item->product_width = $product_width;
		}

		if ($this->checkDisplayOnFO('product_width')) {
			$item->attr['product_width']['frontend_value'] = $product_width . $dimension_unit;
			$fieldconfig = $this->getFieldConfig('product_width');
			$item->attr['product_width']['title'] = array($fieldconfig['title']);
			$item->attr['product_width']['type'] = $fieldconfig['type'];
		}

		$product_length = $baseItem->product_length ? +$baseItem->product_length : 0;
		if ($this->checkPublished('product_length')) {
			$item->product_length = $product_length;
		}

		if ($this->checkDisplayOnFO('product_length')) {
			$item->attr['product_length']['frontend_value'] = $product_length . $dimension_unit;
			$fieldconfig = $this->getFieldConfig('product_length');
			$item->attr['product_length']['title'] = array($fieldconfig['title']);
			$item->attr['product_length']['type'] = $fieldconfig['type'];
		}

		$product_height = $baseItem->product_height ? +$baseItem->product_height : 0;
		if ($this->checkPublished('product_height')) {
			$item->product_height = $product_height;
		}

		if ($this->checkDisplayOnFO('product_height')) {
			$item->attr['product_height']['frontend_value'] = $product_height . $dimension_unit;
			$fieldconfig = $this->getFieldConfig('product_height');
			$item->attr['product_height']['title'] = array($fieldconfig['title']);
			$item->attr['product_height']['type'] = $fieldconfig['type'];
		}

		$attributes = EshopHelper::getProductAttributes($id, $lang->lang_code);
		
		foreach ($attributes AS $att) {
			if (defined('ESHOP_MULTILINGUAL')) {
				$att->value = $att->{'value_' . $lang->lang_code};
			}
			if (!$att->value) {
				continue;
			}
			$key = 'ct'.$att->id;
			$published = false;
			if ($this->checkPublished('attr.'.$key.'.value')) {
				$published = true;
				$item->attr[$key]['value'][] = $att->value;
				$item->attr[$key]['frontend_value'][] = $att->value;
			}
			if ($this->checkDisplayOnFO('attr.'.$key.'.value')) {
				if (!$published) {
					$item->attr[$key]['frontend_value'][] = $att->value;
				}
				$fieldconfig = $this->getFieldConfig('attr.'.$key.'.value');
				$item->attr[$key]['title'] = array($fieldconfig['title']);
				$item->attr[$key]['type'] = $fieldconfig['type'];
			}
		}
		
		// options fields.
		$options = EshopHelper::getProductOptions($item->id, $lang->lang_code);
		$odata = array();
		foreach ($options AS $option) {
			if (!in_array($option->option_type, array('Select','Radio','Checkbox'))) continue;
			$values = EshopHelper::getProductOptionValues($item->id, $option->id);
			$odata = array_merge($odata, $values);
		}
		
		foreach ($odata as $o) {
			$query = 'SELECT value FROM `#__eshop_optionvaluedetails` WHERE optionvalue_id = '. $o->option_value_id . ' AND language = ' . $db->quote($lang->lang_code);
			$val = $db->setQuery($query)->loadResult();
			$key = 'op'.$o->option_id;
			$published = false;
			if ($this->checkPublished('attr.'.$key.'.value')) {
				$published = true;
				$item->attr[$key]['value'][] = $val;
				$item->attr[$key]['frontend_value'][] = $val;
			}
			if ($this->checkDisplayOnFO('attr.'.$key.'.value')) {
				if (!$published) {
					$item->attr[$key]['frontend_value'][] = $val;
				}
				$fieldconfig = $this->getFieldConfig('attr.'.$key.'.value');
				$item->attr[$key]['title'] = array($fieldconfig['title']);
				$item->attr[$key]['type'] = $fieldconfig['type'];
			}
		}
		
		$labels = EshopHelper::getProductLabels($item->id, $lang->lang_code);
		$strlabel = '';
		for ($i = 0; $n = count($labels), $i < $n; $i++)
		{
			$label = $labels[$i];
			if ($label->label_style == 'rotated' && !($label->enable_image && $label->label_image))
			{
				$strlabel .= '<div class="cut-rotated">';
			}
			if ($label->enable_image && $label->label_image)
			{
				$imageWidth = $label->label_image_width > 0 ? $label->label_image_width : EshopHelper::getConfigValue('label_image_width');
				if (!$imageWidth)
					$imageWidth = 50;
				$imageHeight = $label->label_image_height > 0 ? $label->label_image_height : EshopHelper::getConfigValue('label_image_height');
				if (!$imageHeight)
					$imageHeight = 50;
				$strlabel .= '<span class="horizontal '.$label->label_position.' small-db" style="opacity: '.$label->label_opacity.';background-image: url(' . str_replace('administrator/','',$label->label_image) . '); background-repeat: no-repeat; width: '.$imageWidth.'px; height: '.$imageHeight.'px; box-shadow: none;"></span>';
			}
			else 
			{
				$strlabel .= '<span class="'.$label->label_style.' '.$label->label_position.' small-db" style="float:left;background-color: #'.$label->label_background_color.'; color: #'.$label->label_foreground_color.'; opacity: '.$label->label_opacity.';'.($label->label_bold ? 'font-weight: bold;' : '').'">'.$label->label_name.'</span>';
			}
			if ($label->label_style == 'rotated' && !($label->enable_image && $label->label_image))
			{
				$strlabel .= '</div>';
			}
		}
		$item->labels = $strlabel;

		return $item;
	}
	
	public function getProductCategories($categories, $langCode = 'en-GB')
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('p.id')
			->from('#__eshop_products p')
			->leftJoin('#__eshop_productcategories pc ON (p.id = pc.product_id)')
			->where('pc.category_id IN ('.implode(',', $categories).')')
			->where('p.published = 1')
			->group('p.id');
		$db->setQuery($query);
		$rows = $db->loadColumn();
		return $rows;
	}
	
	public function getAllChildCategories($id)
	{
		$data = array();
		
		if ($results = EshopHelper::getCategories($id, '', true))
		{
			foreach ($results as $result)
			{
				$data[] = $result;
				$subCategories = EshopHelper::getAllChildCategories($result->id);
				if ($subCategories)
				{
					$data = array_merge($data, $subCategories);
				}
			}
		}
		return $data;
	}

	public function getLengthUnit($id) {
		$db = Factory::getDbo();
		$query = 'SELECT length_unit FROM `#__eshop_lengthdetails` WHERE length_id = ' . $id;
		$db->setQuery($query);
		return $db->loadResult();
	}

	public function getWeightUnit($id) {
		$db = Factory::getDbo();
		$query = 'SELECT weight_unit FROM `#__eshop_weightdetails` WHERE weight_id = ' . $id;
		$db->setQuery($query);
		return $db->loadResult();
	}

	public function getChildCategories($catid, $maxLevel = 100) {
		if ($maxLevel <= 0) {
			return array();
		}

		$cats = EshopHelper::getCategories($catid);
		$items = array();
		foreach ($cats as $cat) {
			$items[] = $cat;
			$children = $this->getChildCategories($cat->id, $maxLevel - 1);
			foreach ($children as $child) {
				$items[] = $child;
			}
		}

		return $items;
	}

}