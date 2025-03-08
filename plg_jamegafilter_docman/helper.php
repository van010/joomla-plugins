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
defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\LanguageHelper;

JLoader::register('BaseFilterHelper', JPATH_ADMINISTRATOR .'/components/com_jamegafilter/base.php');

class DocmanFilterHelper extends BaseFilterHelper {
	
	public $attr = [];
	public $_db = null;
	public $_params = null;
	
	function __construct($params = array())
	{
		$this->_db = Factory::getDbo();
		$this->_params = new Registry($params);
		return parent::__construct($params);
	}
	
	function getLangSuffix()
	{
    $langs = LanguageHelper::getKnownLanguages();
		$lang_sfx = array();
		foreach ($langs as $lang) {
			$lang_sfx[] = $lang['tag'];
		}
		return $lang_sfx;
	}
	
	function getFilterItems($catid)
	{
		$filterItems = array();
		$lang_sfx = $this->getLangSuffix();

		foreach ($lang_sfx AS $tag) {
			$filterItems[strtolower(str_replace('-','_',$tag))] = $this->getItemList($catid, $tag);
		}
		return $filterItems;
	}
	
	public function getItemList($catid, $lang)
	{
		$itemList = new stdCLass();
		$catList = array();
		$include_root = $this->_params->get('include_root', self::INCLUDE_ROOT);
		$subcat = $this->_params->get('subcat', self::ALL);

		if ($include_root === self::INCLUDE_ROOT && $catid !== '0') {
			$catList[] = $catid;	
		}

		if ($subcat !== self::NONE) {
			$maxLevel = $subcat === self::ALL ? 100 : (int) $subcat;
			if ($catid) {
				$level = $level ?? 0;
				$children = $this->getChildCategories($catid, (int) $level, $maxLevel);
				foreach ($children as $cid) {
					$catList[] = $cid;
				}
			} else {
				$catTree = $this->getDocmanTreeCategories($maxLevel);
				foreach ($catTree as $cat) {
					$catList[] = $cat->id;
				}
			}
		}

		if (!count($catList)) {
			return array();
		}

		$itemIdList = $this->getListId($catList);
		if ($itemIdList) {
			foreach ($itemIdList as $id) {
				$property = 'item_'.$id;
				$item = $this->getItem($id, $catList, $lang);
				if( !empty($item))
					$itemList->{ $property } = $item;
				else
					continue;
			}
		}
		
		return $itemList;
	}

	public function getItem($id, $catList, $lang)
	{
		$baseItem = $this->getBaseItem($id);
		$itemParams = json_decode($baseItem->params);
		$desc = strip_tags($baseItem->description??'');
		
		$item = new stdCLass();
		$item->id = $id;
		$item->name = $baseItem->title;
		$item->thumbnail =  !empty($baseItem->image) ? $baseItem->image : '';
		$item->icon = $itemParams->icon;
		$item->isVideo = ($item->icon == 'video') ? true : false;
		$item->desc = !empty($desc) ? utf8_encode(substr($desc,0,100)).'...' : utf8_encode($desc);
		$item->created_by = $baseItem->created_by;
// 		$item->access = $baseItem->access;
		
		$item->attr = array();
		if ($this->checkDisplayOnFO('name')) {
			$item->attr['name']['frontend_value'] = $item->name;
			$fieldconfig = $this->getFieldConfig('name');
			$item->attr['name']['title'] = array($fieldconfig['title']);
			$item->attr['name']['type'] = $fieldconfig['type'];
		}
		
		//Url and Url download.
		$alias = $id.'-'.$baseItem->slug;
		$category_slug = $this->getCategorySlug($id);
		$Itemid = $this->getItemid($lang);

		$route = "index.php?option=com_docman&view=document&alias=$alias&category_slug=$category_slug&Itemid=$Itemid";
		$route_download = "index.php?option=com_docman&view=download&alias=$alias&category_slug=$category_slug&Itemid=$Itemid";
		if (Multilanguage::isEnabled()) {
			$route .= '&lang=' . $lang;
			$route_download .= '&lang=' . $lang;
		}
		$uriLeng = mb_strlen(Uri::root(true));
		$item->url = mb_substr(Route::_($route), $uriLeng);
		$item->url_download = mb_substr(Route::_($route_download), $uriLeng);
		$item->published_date = array (strtotime($baseItem->publish_on??''));
		
		// Downloads
		$item->downloads = (int)$baseItem->hits; // for order.
		// for show up frontend.
		if ((int)$baseItem->hits >= 2) {
			$item->downloads_fe = (int)$baseItem->hits;
		} else {
			$item->download_fe = (int)$baseItem->hits;
		}
		
		if ($this->checkDisplayOnFO('downloads')) {
			$item->attr['downloads']['frontend_value'] = $item->downloads;
			$fieldconfig = $this->getFieldConfig('downloads');
			$item->attr['downloads']['title'] = array($fieldconfig['title']);
			$item->attr['downloads']['type'] = $fieldconfig['type'];
		}
		
		if ($baseItem->publish_on != '0000-00-00 00:00:00') {
			if ($this->checkPublished('published_date')) {
				$item->published_date = array (strtotime($baseItem->publish_on??''));
			}

			if ($this->checkDisplayOnFO('published_date')) {
				$item->attr['published_date']['frontend_value'] = array (strtotime($baseItem->publish_on??''));
				$fieldconfig = $this->getFieldConfig('published_date');
				$item->attr['published_date']['title'] = array($fieldconfig['title']);
				$item->attr['published_date']['type'] = $fieldconfig['type'];
			}
		}
		
		if ($baseItem->created_on != '0000-00-00 00:00:00') {
			if ($this->checkPublished('created_date')) {
				$item->created_date = array( strtotime($baseItem->created_on??''));
			}

			if ($this->checkDisplayOnFO('created_date')) {
				$item->attr['created_date']['frontend_value'] = array( strtotime($baseItem->created_on??'') );
				$fieldconfig = $this->getFieldConfig('created_date');
				$item->attr['created_date']['title'] = array($fieldconfig['title']);
				$item->attr['created_date']['type'] = $fieldconfig['type'];
			}
		}

		if ($baseItem->modified_on != '0000-00-00 00:00:00') {
			if ($this->checkPublished('modified_date')) {
				$item->modified_date = array( strtotime($baseItem->modified_on??'') );
			}

			if ($this->checkDisplayOnFO('modified_date')) {
				$item->attr['modified_date']['frontend_value'] = array( strtotime($baseItem->modified_on??'') );
				$fieldconfig = $this->getFieldConfig('modified_date');
				$item->attr['modified_date']['title'] = array($fieldconfig['title']);
				$item->attr['modified_date']['type'] = $fieldconfig['type'];
			}
		}
		
		//Attributes
		$this->attr = array();
		//Category Info
		$category = $this->getCategoryInfo($id, $catList);
		//Tag Info
		$this->getTagInfo($id);
		//File Type
		$this->getFileType($item->icon);
		
		// get Permission
		$item->access = $this->getPermission($baseItem, $category);
		$item->attr = array_merge($item->attr, $this->attr);
		
		return $item;
	}
	
	public function getPermission($item, $category)
	{
		if ($item->access == 0) {
			// inherit
			if ($category->access < 0) {
				// groups // get form docman Level
				$sql = 'SELECT `groups` FROM #__docman_levels WHERE entity = '.$this->_db->quote($category->uuid);
				$this->_db->setQuery($sql);
				$access = $this->_db->loadResult();
				if (!empty($access))
					return $access;
			}
			if ($category->access > 0) {
				// preset // get form joomla view access
				$sql = 'SELECT `rules` FROM #__viewlevels WHERE id = '.$this->_db->quote($item->access);
				$this->_db->setQuery($sql);
				$access = $this->_db->loadResult();
				if (!empty($access))
					return str_replace(['[', ']'], ['', ''],$access);
			}
		}
		
		if ($item->access < 0) {
			// groups // get form docman Level
			$sql = 'SELECT `groups` FROM #__docman_levels WHERE entity = '.$this->_db->quote($item->uuid);
			$this->_db->setQuery($sql);
			$access = $this->_db->loadResult();
			if (!empty($access))
				return $access;
		}

		if ($item->access > 0) {
			// preset // get form joomla view access
			$sql = 'SELECT `rules` FROM #__viewlevels WHERE id = '.$this->_db->quote($item->access);
			$this->_db->setQuery($sql);
			$access = $this->_db->loadResult();
			if (!empty($access))
				return str_replace(['[', ']'], ['', ''],$access);
		}

		return $item->access;
	}
	
	public function getBaseItem($id)
	{
		$query = $this->_db->getQuery(true);
		$query->select('dd.*')
			->from('#__docman_documents as dd')
			->where('dd.docman_document_id = ' . (int) $id);
		$this->_db->setQuery($query);
		$baseItem = $this->_db->loadObject();
		
		return $baseItem;
	}
	
	public function getListId($catids)
	{
		$query = $this->_db->getQuery(true);
		$query ->select('dd.docman_document_id as id')->from('#__docman_documents as dd')
				->where('dd.docman_category_id IN ('.implode(',', $catids).') AND dd.`enabled` = "1"')
				->order('dd.title ASC');
		$this->_db->setQuery($query);
		$listId = $this->_db->loadColumn();
		
		return $listId;
	}
	
	public function getCategorySlug($document_id)
	{
		$query = $this->_db->getQuery(true);
		$query->select('dc.slug')
				->from('#__docman_categories as dc')
				->join('LEFT', '#__docman_documents as dd ON dd.docman_category_id = dc.docman_category_id')
				->where('dd.docman_document_id = '. (int) $document_id);
		$this->_db->setQuery($query);
		$slug = $this->_db->loadResult();
		
		return $slug;
	}
	
	public function getItemid($lang)
	{
		$query = $this->_db->getQuery(true);
		$query->select('id')
				->from('#__menu')
				->where('`link` REGEXP "^(index.php.*com_docman)(&view=[list|tree|flat|document])"')
				->where('language IN (' . $this->_db->quote($lang) . ',"*")')
				->where('menutype != "main"')
      ->where('published=1');
		$this->_db->setQuery($query);
		
    $data = $this->_db->loadResult();
    
		return $data;
	}
	
	public function getFileType($icon)
	{
		if ($this->checkPublished('attr.type.value')) {
			$this->attr['type']['value'] = array($icon);
			$this->attr['type']['frontend_value'] = array('<span class="k-icon-document-'.$icon.'"></span>'.' '.strtoupper($icon));
		}

		if ( $this->checkDisplayOnFO('attr.type.value')) {
			$this->attr['type']['frontend_value'] = array('<span class="k-icon-document-'.$icon.'"></span>'.' '.strtoupper($icon));
			$fieldconfig = $this->getFieldConfig('attr.type.value');
			$this->attr['type']['title'] = array($fieldconfig['title']);
			$this->attr['type']['type'] = $fieldconfig['type'];
		}
		
		return $this->attr;
	}

	public function getCategory($catId) {
		$query = "SELECT docman_category_id as id, title as name, access, uuid
				FROM `#__docman_categories`
				WHERE docman_category_id = $catId";

		$category = $this->_db->setQuery($query)->loadObject();
		return $category;
	}
	
	public function getCategoryInfo($id, $catList)
	{
		$query = $this->_db->getQuery(true);
		$query->select ('dc.docman_category_id as id, dc.title as name, dc.access, dc.uuid')
				->from('#__docman_categories as dc')
				->join('LEFT', '#__docman_documents as dd ON dd.docman_category_id = dc.docman_category_id')
				->where('dd.docman_document_id = '. (int) $id);

		$this->_db->setQuery($query);
		$category = $this->_db->loadObject();

		if ($category) {
			$catTree = $this->getParentList($category->id, $catList);
			$catTree[] = $category;
			$catTree = array_reverse($catTree);
			$cats = array();
			foreach ($catTree as $key => $cat) {
				$item = new stdClass;
				$item->id = $cat->id;
				$item->name = $cat->name;

				$rest = array_slice($catTree, $key + 1);
				foreach ($rest as $c) {
					$item->name =  $c->name . ' &raquo; ' . $item->name;
				}

				$cats[] = $item;
			}

			$cdata = array();
			$cdata['value'] = array();
			$cdata['frontend_value'] = array();
			
			foreach ($cats as $cat) {
				if (in_array($cat->id, $cdata['value'])) {
					continue;
				}
				
				$cdata['value'][] = $cat->id;
				$cdata['frontend_value'][] = $cat->name;
			}

			if ($this->checkPublished('attr.cat.value')) {
				$this->attr['cat'] = $cdata;
			}

			if ($this->checkDisplayOnFO('attr.cat.value')) {
				$this->attr['cat']['frontend_value'] = $cdata['frontend_value'];
				$fieldconfig = $this->getFieldConfig('attr.cat.value');
				$this->attr['cat']['title'] = array($fieldconfig['title']);
				$this->attr['cat']['type'] = $fieldconfig['type'];
			}
		}
		
		return $category;
	}
	
	public function getParentList($catid, $catList)
	{
		$query = $this->_db->getQuery(true);
		$query->select('dc.docman_category_id as id, dc.title as name')
				->from('#__docman_category_relations as dcr')
				->join('LEFT', '#__docman_categories as dc ON dc.docman_category_id = dcr.ancestor_id')
				->where('dcr.descendant_id = ' . (int) $catid )
				->where('dcr.`level` > 0')
				->where('dc.docman_category_id IN (' . implode(',', $catList) . ')')
				->order('dcr.`level` desc');

		$this->_db->setQuery($query);
		$parentCats = $this->_db->loadObjectList();
		return $parentCats;
	}
	
	public function getTagInfo($id) 
	{
		$query = $this->_db->getQuery(true);
		$query->select('dt.tag_id as id, dt.title as tag_name')
				->from('#__docman_tags_relations as dtr')
				->join('LEFT', '#__docman_tags as dt ON dt.tag_id = dtr.tag_id')
				->join('LEFT', '#__docman_documents as dd ON dd.uuid = dtr.row')
				->where('dd.docman_document_id = ' . (int) $id);
		$this->_db->setQuery($query);
		$tags = $this->_db->loadObjectList();
		
		if ($tags) {
			$tdata = array();
			foreach ($tags as $tag) {
				$tdata['value'][] = $tag->id;
				$tdata['frontend_value'][] = $tag->tag_name;
			}
			
			if ($this->checkPublished('attr.tag.value')) {
				$this->attr['tag'] = $tdata;
			}

			if ( $this->checkDisplayOnFO('attr.tag.value')) {
				$this->attr['tag']['frontend_value'] = $tdata['frontend_value'];
				$fieldconfig = $this->getFieldConfig('attr.tag.value');
				$this->attr['tag']['title'] = array($fieldconfig['title']);
				$this->attr['tag']['type'] = $fieldconfig['type'];
			}
		}
		
		return $this->attr;
	}
	
	public function getCategoryLevel($catid)
	{
		$query = $this->_db->getQuery(true);
		$query->select('(count(drc.level) - 1) as level')
			->from('#__docman_category_relations as drc')
			->where('descendant_id = ' . (int) $catid);
		$this->_db->setQuery($query);
		$level = $this->_db->loadResult();
		
		return $level;
	}
	
	public function getDocmanTreeCategories($maxLevel = 100) 
	{
		$query = $this->_db->getQuery(true);
		$query->select('dc.docman_category_id as id, dc.title as title, count(dcr.descendant_id) - 1 as level')
			->from('#__docman_categories as dc')
			->join('INNER', '#__docman_category_relations as dcr ON dc.docman_category_id = dcr.descendant_id')
			->GROUP('dc.docman_category_id')
			->ORDER('dc.title');
		$this->_db->setQuery($query);
		$categories = $this->_db->loadObjectList();
		
		$items = array();
		if (count($categories)) {
			foreach ($categories as $cat) {
				if ($cat->level == '0') {
					$items[] = $cat;
					$children = $this->getChildCategories($cat->id, (int) $cat->level, $maxLevel - 1);
					foreach ($children as $child) {
						foreach ($categories as $cat) {
							if ($child == $cat->id) {
								$items[] = $cat;
							}
						}
					}
				}
			}
		}

		return $items;
	}
	
	public function getChildCategories($catid, $level, $maxLevel = 100) {
		if ($maxLevel <= 0) {
			return array();
		}

		if ($level == 0) {
			$level = 1;
		}

		$query = $this->_db->getQuery(true);
		$query ->select('dc.docman_category_id as id, drc.`level` as `level`')
			->from('#__docman_categories as dc')
			->join('INNER', '#__docman_category_relations as drc ON dc.docman_category_id = drc.descendant_id')
			->where('drc.ancestor_id = '.(int) $catid . ' AND '.$this->_db->quoteName('drc.level').' = '. $level)
			->order('dc.title ASC');

		$this->_db->setQuery($query);
		$cats = $this->_db->loadObjectList();
		$items = array();
		
		if ($cats) {
			foreach ($cats as $cat) {
				$items[] = $cat->id;
				$children = $this->getChildCategories($cat->id, (int)$cat->level, $maxLevel - 1);
				if ($children) {
					foreach ($children as $child) {
						$items[] = $child;
					}
				}
			}
		}
		
		return $items;
	}
}
