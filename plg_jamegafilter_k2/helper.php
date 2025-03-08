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
defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

JLoader::register('BaseFilterHelper', JPATH_ADMINISTRATOR .'/components/com_jamegafilter/base.php');

class K2FilterHelper extends BaseFilterHelper {
	
	private $tree = null;
	private $model = null;
	private $anyK2Link = null;
	private $multipleCategoriesMapping = array();
	private $cache = [
		'item' => array(),
		'category' => array(),
		'tag' => array(),
		'user' => array()
	];

	public function __construct($params = array())
	{
		$this->_params = new JRegistry($params);
		return parent::__construct($params);
	}
	
	public function getFilterItems($catid)
	{

		$lang = JFactory::getLanguage();
		$languages = JLanguageHelper::getLanguages();
		$filterItems = array();
		foreach ($languages AS $lang) {
			$filterItems[str_replace('-', '_', strtolower($lang->lang_code))] = $this->getItemList($catid, $lang);
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
			$categories = $this->getChildCategories($catid, $lang, $maxLevel);
			foreach ($categories as $category) {
				if (in_array($category->language, array('*', $lang->lang_code))) {
					$catList[] = $category->id;
				}
			}
		}

		if (!count($catList)) {
			return array();
		}

		$db = JFactory::getDBO();
		$nullDate = $db->getNullDate();
		$jnow = JFactory::getDate();
		$now = $jnow->toSql();

		$sql = 'SELECT * FROM `#__k2_items` i 
		LEFT JOIN `#__k2_rating` r ON (i.id = r.itemID)
		WHERE i.catid IN (' . implode(',', $catList) . ') AND i.language IN ("*", "' . $lang->lang_code . '")
		AND i.published = 1
			AND i.trash = 0
		AND ( i.publish_up = '.$db->Quote($nullDate).' OR i.publish_up <= '.$db->Quote($now).' )
		AND ( i.publish_down = '.$db->Quote($nullDate).' OR i.publish_down >= '.$db->Quote($now).' )
		GROUP BY i.id order by i.id desc';
		$db->setQuery($sql);
		$items = $db->loadObjectList();
		$itemList = new stdCLass();
		
		$sql = 'SELECT * FROM `#__k2_extra_fields`';
		$db->setQuery($sql);
		$exf = $db->loadObjectList();

		foreach ($items as $id) {
			$property = 'item_' . $id->id;
			$item = $this->getItem($id, $lang, $exf, $catList);
			if (!empty($item))
				$itemList->{ $property } = $item;
			else
				continue;
		}

		return $itemList;
	}

	public function getItem($baseItem, $lang, $exf, $catList)
	{
		$item = new stdClass();
		$item->id = $baseItem->id;
		$item->name = strip_tags(preg_replace('/\s+/', ' ', $baseItem->title));
		if ($this->checkDisplayOnFO('desc')) {
			$text = trim($baseItem->introtext . $baseItem->fulltext);
			$item->desc = $text ? $this->getDesc($text) : '';
		}
		$item->published = $baseItem->published;
		$item->introtext = strip_tags($baseItem->introtext);
		$item->publish_up = strtotime($baseItem->publish_up);
		$item->publish_down = strtotime($baseItem->publish_down);
		if (!empty($baseItem->rating_sum) && !empty($baseItem->rating_count))
			$item->rating = ($baseItem->rating_sum / $baseItem->rating_count);
		else
			$item->rating = 0;
		$item->width_rating = $item->rating * 20;

		$item->alias = $baseItem->alias;
		$item->catid = $baseItem->catid;

		require_once JPATH_ROOT . '/components/com_k2/helpers/route.php';
		$route = $this->getItemRoute($item->id . ':' . $item->alias, $item->catid, $lang->lang_code);
		
		if (JLanguageMultilang::isEnabled()) {
			$route .= '&lang=' . $lang->lang_code;
		}
		
		$uriLeng = mb_strlen(JUri::root(true));
		$item->url = mb_substr(JRoute::_($route), $uriLeng);

		if (JFile::exists(JPATH_SITE . '/media/k2/items/cache/' . md5("Image" . $baseItem->id) . '_L.jpg'))
			$item->thumbnail = 'media/k2/items/cache/' . md5("Image" . $baseItem->id) . '_L.jpg';
		
		$item->attr = array();

		if ($this->checkDisplayOnFO('name')) {
			$item->attr['name']['frontend_value'] = $item->name;
			$fieldconfig = $this->getFieldConfig('name');
			$item->attr['name']['title'] = array($fieldconfig['title']);
			$item->attr['name']['type'] = $fieldconfig['type'];
		}

		if ($this->checkDisplayOnFO('rating')) {
			$item->attr['rating']['frontend_value'] = $item->width_rating;
			$fieldconfig = $this->getFieldConfig('rating');
			$item->attr['rating']['title'] = array($fieldconfig['title']);
			$item->attr['rating']['type'] = $fieldconfig['type'];
		}
		
		if ($baseItem->publish_up != '0000-00-00 00:00:00') {
			if ($this->checkPublished('published_date')) {
				$item->published_date = array( strtotime($baseItem->publish_up) );
			}

			if ($this->checkDisplayOnFO('published_date')) {
				$item->attr['published_date']['frontend_value'] = array( strtotime($baseItem->publish_up) );
				$fieldconfig = $this->getFieldConfig('published_date');
				$item->attr['published_date']['title'] = array($fieldconfig['title']);
				$item->attr['published_date']['type'] = $fieldconfig['type'];
			}
		}
		
		if ($baseItem->created != '0000-00-00 00:00:00') {
			if ($this->checkPublished('created_date')) {
				$item->created_date = array( strtotime($baseItem->created) );
			}

			if ($this->checkDisplayOnFO('created_date')) {
				$item->attr['created_date']['frontend_value'] = array( strtotime($baseItem->created) );
				$fieldconfig = $this->getFieldConfig('created_date');
				$item->attr['created_date']['title'] = array($fieldconfig['title']);
				$item->attr['created_date']['type'] = $fieldconfig['type'];
			}
		}
		
		if ($baseItem->modified != '0000-00-00 00:00:00') {
			if ($this->checkPublished('modified_date')) {
				$item->modified_date = array(strtotime($baseItem->modified));
			}

			if ($this->checkDisplayOnFO('modified_date')) {
				$item->attr['modified_date']['frontend_value'] = array( strtotime($baseItem->modified) );
				$fieldconfig = $this->getFieldConfig('modified_date');
				$item->attr['modified_date']['title'] = array($fieldconfig['title']);
				$item->attr['modified_date']['type'] = $fieldconfig['type'];
			}
		}

		$parents = $this->getParentCategories($baseItem->catid, $catList);
		$cats = array();
		foreach ($parents as $key => $cat) {
			$tmp = new stdClass;
			$tmp->id = $cat->id;
			$tmp->name = $cat->name;

			$rest = array_slice($parents, $key + 1);
			foreach ($rest as $c) {
				$tmp->name =  $c->name . ' &raquo; ' . $tmp->name;
			}

			$cats[] = $tmp;
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
			$item->attr['cat'] = $cdata;
		}

		if ($this->checkDisplayOnFO('attr.cat.value') && !empty($item->attr['cat']['value'])) {
			$item->attr['cat']['frontend_value'] = $cdata['frontend_value'];
			$fieldconfig = $this->getFieldConfig('attr.cat.value');
			$item->attr['cat']['title'] = array($fieldconfig['title']);
			$item->attr['cat']['type'] = $fieldconfig['type'];
		}
		
		$tdata = $this->getItemTags($baseItem);
		
		if ($tdata && $this->checkPublished('attr.tag.value')) {
			$item->attr['tag'] = $tdata;
		}
		
		if ($tdata && $this->checkDisplayOnFO('attr.tag.value')) {
			$item->attr['tag']['frontend_value'] = $tdata['frontend_value'];
			$fieldconfig = $this->getFieldConfig('attr.tag.value');
			$item->attr['tag']['title'] = array($fieldconfig['title']);
			$item->attr['tag']['type'] = $fieldconfig['type'];
		}

		if ($this->checkPublished('attr.author.value')) 
			$item->attr['author'] = $this->getItemAuthors($baseItem);

		$featured = $baseItem->featured;
		if ($this->checkPublished('attr.featured.value')) {
			$item->attr['featured']['value'] = array($featured);
			$item->attr['featured']['frontend_value'] = $featured ? array(JText::_('COM_JAMEGAFILTER_ONLY_FEATURED')) : array(JText::_('COM_JAMEGAFILTER_NOT_FEATURED'));
		}
		
		if ($this->checkDisplayOnFO('attr.featured.value')) {
			$item->attr['featured']['frontend_value'] = $featured ? array(JText::_('COM_JAMEGAFILTER_ONLY_FEATURED')) : array(JText::_('COM_JAMEGAFILTER_NOT_FEATURED'));
			$fieldconfig = $this->getFieldConfig('attr.featured.value');
			$item->attr['featured']['title'] = array($fieldconfig['title']);
			$item->attr['featured']['type'] = $fieldconfig['type'];
		}

		$extrafield = json_decode($baseItem->extra_fields);
		$edata = array();
		foreach ($extrafield AS $ex) {
			foreach ($exf AS $ef) {
				if ($ex->id == $ef->id) {
					// need simple way to check published field
					$key = 'ct' . $ef->id;
					if ($ef->type == 'multipleSelect' || $ef->type == 'radio' || $ef->type == 'select') {
						$edata[$key]['value'] = array();
						$edata[$key]['frontend_value'] = array();
						$realval = json_decode($ef->value);
						$evalue = is_array($ex->value) ? $ex->value : array($ex->value);
						foreach ($realval AS $r) {
							if (in_array($r->value, $evalue)) {
								$edata[$key]['value'][] = (string) $r->value;
								$edata[$key]['frontend_value'][] = $r->name;
							}
						}
					} else if ($ef->type == 'date') {
						$edata[$key]['value'][] = (string) strtotime($ex->value);
						$edata[$key]['frontend_value'][] = (string) strtotime($ex->value);
					} else {
						$edata[$key]['value'] = $ex->value;
						$edata[$key]['frontend_value'] = $this->compactText($ex->value);
					}
					
					if ($this->checkDisplayOnFO('attr.'.$key.'.value')) {
						$fieldconfig = $this->getFieldConfig('attr.'.$key.'.value');
						$item->attr[$key]['title'] = array($fieldconfig['title']);
						$item->attr[$key]['type'] = $fieldconfig['type'];
					}
				}
			}
		}
		foreach ($edata as $k => $e) {
			if ($this->checkPublished('attr.'.$k.'.value')) {
				$item->attr[$k] = $e;
			}

			if ($this->checkDisplayOnFO('attr.'.$k.'.value')) {
				$item->attr[$k]['frontend_value'] = $e['frontend_value'];
				$fieldconfig = $this->getFieldConfig('attr.'.$k.'.value');
				$item->attr[$k]['title'] = array($fieldconfig['title']);
				$item->attr[$k]['type'] = $fieldconfig['type'];
			}
		}

		$item->access = $this->getPermission($baseItem);

		return $item;
	}

	public function getPermission($item)
	{
		$db = JFactory::getDBO();
		$sql = 'SELECT rules FROM `#__viewlevels` WHERE id = '.$db->quote($item->access);
		$db->setQuery($sql);
		$access = $db->loadResult();
		if (!empty($access))
			return str_replace(['[', ']'], ['', ''],$access);
	}

	public function getDesc($desc)
	{
		$length = 20;
		$desc = strip_tags($desc);
		$exp = explode(' ', $desc);
		$result = '';
		foreach ($exp as $key => $value) {
			if ($key > $length) {
				break;
			}
			$result .= $value . ' ';
		}
		return $result;
	}
	
	public function compactText($text)
	{
		$exp = explode(' ', $text);
		$result = '';
		foreach ($exp as $key => $value) {
			if ($key > 19) {
				break;
			}
			$result .= $value . ' ';
			if ($key === 19) {
				$result .= '...';
			}
		}
		return $result;
	}

	public function getTreeName($cats)
	{
		$catTree = array();
		foreach ( $cats as $cat )
		{
			$nametree = '';
			$tcat = clone $cat;
			foreach( $catTree as $singleCat ) 
			{
				if ($cat->parent == $singleCat->id) {
					$nametree = $singleCat->name.' &raquo; '.$nametree;
					break;
				} 
			}

			$tcat->name = $nametree . $cat->name;
			array_push($catTree, $tcat);
		}

		return $catTree;
	}

	public function getItemTags($item)
	{
		$tags = array();
		$sql = 'SELECT t.id, t.name FROM `#__k2_tags` t
				LEFT JOIN `#__k2_tags_xref` tx ON (tx.tagID = t.id)
				WHERE t.published = 1 
				AND tx.itemID = ' . $item->id;
		$db = JFactory::getDBO();
		$db->setQuery($sql);
		$_tags = $db->loadObjectList();
		foreach ($_tags AS $v) {
			$tags['value'][] = $v->id;
			$tags['frontend_value'][] = $v->name;
		}
		return $tags;
	}

	public function getItemAuthors($item)
	{
		$sql = 'SELECT u.name FROM #__k2_items i
			LEFT JOIN #__users u ON (i.created_by = u.id)
			WHERE i.created_by = ' . $item->created_by;
		$db = JFactory::getDBO();
		$db->setQuery($sql);
		$user = $db->loadAssoc();
		$obj = array();
		$obj['value'] = array($item->created_by);
		$obj['frontend_value'] = array($user['name']);

		if ($this->checkDisplayOnFO('attr.author.value')) {
			$fieldconfig = $this->getFieldConfig('attr.author.value');
			$obj['title'] = array($fieldconfig['title']);
			$obj['type'] = $fieldconfig['type'];
		}
		return $obj;
	}

	public function fetchChild($parent)
	{
		$mainframe = JFactory::getApplication();
		$user = JFactory::getUser();
		$aid = (int) $user->get('aid');
		$db = JFactory::getDBO();
		$query = "SELECT * FROM `#__k2_categories` WHERE parent = '{$parent}' ";
		$query .= " AND published=1 AND trash=0";
		$query .= " ORDER BY ordering ASC";
		$db->setQuery($query);
		$cats = $db->loadObjectList();

		return $cats;
	}

	public function _fetchElement($id, $indent, $list, $maxlevel = 9999, $level = 0, $type = 1)
	{
		$children = $this->fetchChild($id);

		if (@$children && $level <= $maxlevel) {
			foreach ($children as $v) {
				$id = $v->id;

				if ($type) {
					$pre = '|_&nbsp;.';
					$spacer = '.&nbsp;&nbsp;';
				} else {
					$pre = '- ';
					$spacer = '&nbsp;&nbsp;';
				}

				$txt = $pre . $v->name;
				$pt = $v->parent;
				$list[$id] = $v;
				$list[$id]->treename = "{$indent}{$txt}";
				$list[$id]->children = count(@$children);
				$list[$id]->haschild = true;
				$list[$id]->level = $level;
				$list = $this->_fetchElement($id, $indent . $spacer, $list, $maxlevel, $level + 1, $type);
			}
		} else {
			if (isset($list[$id])) {
				$list[$id]->haschild = false;
			}
		}
		return $list;
	}

	public function getChildCategories($catid, $lang, $maxLevel = 100, $level = 0) 
	{
		$level++;
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select(array('id', 'name', 'language'))
				->from('#__k2_categories')
				->where('parent = '. (int)$catid)
				->where('language IN ("*", "'.$lang->lang_code.'")')
				->where('published = 1');

		$db->setQuery($query);
		
		$children = $db->loadObjectList();
		$cats = array();
		foreach ($children as $child) {
			$cats[] = $child;
			if ($level < $maxLevel) {
				foreach ($this->getChildCategories($child->id, $lang, $maxLevel, $level) as $c) {
					$cats[] = $c;
				}
			}
		}
		
		return $cats;
	}

	public function getParentCategories($catid, $catList) {
		$db = JFactory::getDbo();
		$parents = array();
		while (true) {
			$query = "SELECT * 
				FROM `#__k2_categories` 
				WHERE id = $catid
				AND published = 1";

			$row = $db->setQuery($query)->loadObject();
			if ($row && in_array($row->id, $catList)) {
				$parents[] = $row;
				$catid = $row->parent;
			} else {
				break;
			}
		}

		return $parents;
	}
	
	public function getItemRoute($id, $catid = 0, $langTag)
	{
		$key = (string)(int)$id.'|'.(int)$catid;
		if (isset($this->cache['item'][$key])) {
			return $this->cache['item'][$key];
		}
		$needles = array(
			'item' => (int)$id,
			'category' => (int)$catid,
		);
		$link = 'index.php?option=com_k2&view=item&id='.$id;
		if ($item = $this->_findItem($needles, $langTag)) {
			$link .= '&Itemid='.$item->id;
		}
		
		$this->cache['item'][$key] = $link;
		return $link;
	}
	
	public function _findItem($needles, $langTag)
	{
		$component = JComponentHelper::getComponent('com_k2');
		$app = JFactory::getApplication();
		$menu = $app->getMenu('site', array());
		
		if (K2_JVERSION == '15') {
			$items = $menu->getItems('componentid', $component->id);
		} else {
			$items = $menu->getItems(array('component_id', 'language'), array($component->id, $langTag));
		}
		
		$match = null;
		foreach ($needles as $needle => $id) {
			if (count($items)) {
				// First pass
				foreach ($items as $item) {
					// Detect multiple K2 categories link and set the generic K2 link ( if any )
					if (@$item->query['view'] == 'itemlist' && @$item->query['task'] == '') {
						if (!isset($this->multipleCategoriesMapping[$item->id])) {
							if (K2_JVERSION === '15') {
								$menuparams = explode("\n", $item->params);
								foreach ($menuparams as $param) {
									if (strpos($param, 'categories=') === 0) {
										$array = explode('categories=', $param);
										$item->K2Categories = explode('|', $array[1]);
									}
								}
								if (!isset($item->K2Categories)) {
									$item->K2Categories = array();
								}
							} else {
								$menuparams = json_decode($item->params);
								$item->K2Categories = isset($menuparams->categories) ? $menuparams->categories : array();
							}
							
							$this->multipleCategoriesMapping[$item->id] = $item->K2Categories;
							
							if (count($item->K2Categories) === 0) {
								$this->anyK2Link = $item;
							}
						}
					}
					if ($needle === 'user' || $needle === 'category') {
						if ((@$item->query['task'] == $needle) && (@$item->query['id'] == $id)) {
							$match = $item;
							break;
						}
					} elseif ($needle == 'tag') {
						if ((@$item->query['task'] == $needle) && (@$item->query['tag'] == $id)) {
							$match = $item;
							break;
						}
					} else {
						if ((@$item->query['view'] == $needle) && (@$item->query['id'] == $id)) {
							$match = $item;
							break;
						}
					}
					
					if (!is_null($match)) {
						break;
					}
				}
				
				// Second pass (for menu items pointing to multiple K2 categories).
				// Triggered only if we do not have find any match above (menu item to direct category).
				if (is_null($match) && $needle == 'category') {
					foreach ($items as $item) {
						if (@$item->query['view'] == 'itemlist' && @$item->query['task'] == '') {
							if (isset($this->multipleCategoriesMapping[$item->id]) && is_array($this->multipleCategoriesMapping[$item->id])) {
								foreach ($this->multipleCategoriesMapping[$item->id] as $catid) {
									if ((int)$catid == $id) {
										$match = $item;
										break;
									}
								}
							}
							if (!is_null($match)) {
								break;
							}
						}
					}
				}
			}
			if (!is_null($match)) {
				break;
			}
		}
		
		if (is_null($match)) {
			// Try to detect any parent category menu item
			if ($needle == 'category') {
				if (is_null($this->tree)) {
					K2Model::addIncludePath(JPATH_SITE.'/components/com_k2/models');
					$model = K2Model::getInstance('Itemlist', 'K2Model');
					$this->model = $model;
					$this->tree = $model->getCategoriesTree();
				}
				$parents = $this->model->getTreePath($this->tree, $id);
				if (is_array($parents)) {
					foreach ($parents as $categoryID) {
						if ($categoryID != $id) {
							$match = $this->_findItem(array('category' => $categoryID), $langTag);
							if (!is_null($match)) {
								break;
							}
						}
					}
				}
			}
			if (is_null($match) && !is_null($this->anyK2Link)) {
				$match = $this->anyK2Link;
			}
		}
		
		return $match;
	}
}
