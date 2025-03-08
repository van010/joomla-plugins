<?php

/**
 * ------------------------------------------------------------------------
 * JA Filter Plugin - Content
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
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Component\Content\Site\Helper\RouteHelper;

JLoader::register('BaseFilterHelper', JPATH_ADMINISTRATOR . '/components/com_jamegafilter/base.php');
JLoader::register('ContentHelperRoute', JPATH_ROOT . '/components/com_content/helpers/route.php');

class ContentFilterHelper extends BaseFilterHelper
{
	public $attr = [];
	public $_db = null;
	public $plugin = null;
	public $_params = null;
	public $plgParams = null;

	public $userInfo = null;
	public $cache_user_path = JPATH_ROOT . '/cache/all_users.json';
	
	public $cache_all_cats = null;
	public $cache_categories_path = JPATH_ROOT . '/cache/all_cats.json';

	public $cache_content_with_cat = null;
	public $cache_articles_category_path = JPATH_ROOT . '/cache/all_articles_in_cats.json';

	public $tagInfo = null;
	public $cache_tag_path = JPATH_ROOT . '/cache/all_tags.json';

	public $fieldInfo = null;
	public $cache_field_path = JPATH_ROOT . '/cache/all_fields.json';


	public function __construct($params = array())
	{
		$this->_db = Factory::getDbo();
		$this->_params = new Registry($params);
		$this->plugin = PluginHelper::getPlugin('jamegafilter', 'content');
		$this->plgParams = new Registry($this->plugin);
		$this->plgParams->loadString($this->plugin->params);

		if (is_file($this->cache_articles_category_path)){
			$this->cache_content_with_cat = json_decode(file_get_contents($this->cache_articles_category_path));
		}
		if (is_file($this->cache_categories_path)){
			$this->cache_all_cats = json_decode(file_get_contents($this->cache_categories_path));
		}
		if (is_file($this->cache_user_path)){
			$this->userInfo  = json_decode(file_get_contents($this->cache_user_path));
		}
		if (is_file($this->cache_tag_path)){
			$this->tagInfo = json_decode(file_get_contents($this->cache_tag_path));
		}
		if (is_file($this->cache_field_path)){
			$this->fieldInfo = json_decode(file_get_contents($this->cache_field_path));
		}
		return parent::__construct($params);
	}

	public function getFilterItems($catid)
	{
		$filterItems = array();
		$lang_sfx = $this->getLangSuffix();

		foreach ($lang_sfx as $lang) {
			$simpleLang = strtolower(str_replace('-', '_', $lang));
			$filterItems[$simpleLang] = $this->getItemList($catid, $lang);
		}

		return $filterItems;
	}

	public function getLangSuffix()
	{
		# $langs = Factory::getLanguage()->getKnownLanguages();
		$langs = LanguageHelper::getKnownLanguages();
		$lang_sfx = array();
		foreach ($langs as $lang) {
			$lang_sfx[] = $lang['tag'];
		}

		return $lang_sfx;
	}

	public function getCatList($catid, $ordering = 'rgt ASC')
	{
		$catid = $catid ? $catid : '1';

		$catList = array();
		$include_root = $this->_params->get('include_root', self::INCLUDE_ROOT);
		$subcat = $this->_params->get('subcat', self::ALL);

		if ($include_root === self::INCLUDE_ROOT && $catid !== '1') {
			$catList[] = $catid;
		}

		if ($subcat !== self::NONE) {
			$maxLevel = $subcat === self::ALL ? 100 : (int) $subcat;
			$categories = $this->getChildCategories($catid, $maxLevel, 0, $ordering);
			foreach ($categories as $category) {
				$catList[] = $category->id;
			}
		}

		return $catList;
	}

	public function getItemList($catid, $lang)
	{
		$catList = $this->getCatList($catid);
		if (!count($catList)) {
			return array();
		}

		$itemList = new stdCLass();
		# wrong data here
		$itemIdList = $this->getListId($catList, $lang);
		if ($itemIdList) {
			foreach ($itemIdList as $id) {
				$property = 'item_' . $id;
				$item = $this->getItem($id, $catList, $lang);
				if (!empty($item))
					$itemList->{$property} = $item;
				else
					continue;
			}
		}
		$itemList->custom_field_values = $this->loadFieldAjax();
		return $itemList;
	}

	public function loadFieldAjax(){
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('`name`, `fieldparams`')
			->from('`#__fields`')
			->where("`state` = 1 AND `context` = 'com_content.article'")
			->where("`type` = 'radio'");
		$db->setQuery($query);
		$fieldParams = $db->loadAssocList('name', 'fieldparams');

		return $fieldParams;
	}

	public function getListId($catids, $lang, $removeExpire=true)
	{
		$db = $this->_db;
		$nullDate = $db->quote($db->getNullDate());
		$nowDate  = $db->quote(Factory::getDate()->toSql());
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__content')
			->where('state = 1 AND catid IN (' . implode(',', $catids) . ') AND language IN ("*", "' . $lang . '")')
			->where('(publish_up = ' . $nullDate . ' OR publish_up <= ' . $nowDate . ')');
		if ($removeExpire){
			$query->where('publish_down IS NULL OR `publish_down` >= ' . $nowDate);
		}
		$query->order('id desc');
		$db->setQuery($query);
		$listId = $db->loadColumn();
		return $listId;
	}

	public function getCategoryListInfo($catList)
	{
		if (version_compare(JVERSION, '3.7', '<'))
			return;

		$cdata = array();
		$cdata['value'] = array();
		$cdata['frontend_value'] = array();

		$query = $this->_db->getQuery(true);
		$query->select('c.*')
			->from('#__categories as c')
			->where('c.id in (' . implode(',', $catList) . ')')
			->where('c.published = 1');
		$this->_db->setQuery($query);
		$categories = $this->_db->loadObjectList();

		foreach ($categories as $cat) {
			$cdata['value'][] = $cat->id;
			$cdata['frontend_value'][] = $this->getCatNameAsTree($cat);
		}

		return $cdata;
	}

	public function getItem($id, $catList, $lang)
	{
		$app = Factory::getApplication();
		$baseItem = $this->getBaseItem($id);
		$baseItem->text = $baseItem->introtext . $baseItem->fulltext;

		// Process the content plugins.
		PluginHelper::importPlugin('content');
		$dispatcher = Factory::getApplication();

		if (isset($baseItem->params)) {
			$dispatcher->triggerEvent('onContentPrepare', array('com_content.article', &$baseItem, &$baseItem->params, 0));
		}
		$images = new Registry($baseItem->images);

		$item = new stdCLass();
		if (in_array($baseItem->language, array('*', $lang))) {
			$item->id = $id;
			$item->slug = $id . '-' . $baseItem->alias;
			$item->lang = $lang;
			$item->hits = (int) $baseItem->hits;
			$item->name = $baseItem->title;
			$item->catid = $baseItem->catid;

			$img = $images->get('image_intro', $images->get('image_fulltext', ''));
			if (strpos($img, '#joomlaImage:')) {
				$img = explode('#joomlaImage:', $img)[0];
			}
			$item->thumbnail = $this->generateThumb($id, $img, 'content');
			$juri = Uri::getInstance();
			if (preg_match('/^\/\//', $item->thumbnail)) {
				$item->thumbnail = $juri->getScheme() . ':' . $item->thumbnail;
			}

			if ($this->checkDisplayOnFO('desc')) {
				$text = trim($baseItem->text);
				$item->desc = $text ? $this->getDesc($text) : '';
				if (preg_match('/<img src="[^http|\/]/', $item->desc)) {
					// change to right link with custom field media. basic use. will be update change later.
					$item->desc = preg_replace('/<img src="([^http|\/].*?)"/', '<img src="' . Uri::root(true) . '/$1"', $item->desc);
				}
			}

			//Item link
			$slug = $baseItem->alias ? ($baseItem->id . ':' . $baseItem->alias) : $baseItem->id;
			$catslug = isset($baseItem->category_alias) ? ($baseItem->catid . ':' . $baseItem->category_alias) : $baseItem->catid;
			if (version_compare(JVERSION, '4.0', '>=')){
				$route = RouteHelper::getArticleRoute($slug, $catslug, $item->lang);
			}else{
				$route = ContentHelperRoute::getArticleRoute($slug, $catslug, $item->lang);
			}

			$uriLeng = mb_strlen(Uri::root(true));
			$item->url = mb_substr(Route::_($route), $uriLeng);
			$item->attr = array();

			if ($this->checkDisplayOnFO('name')) {
				$item->attr['name']['frontend_value'] = $item->name ?? '';
				$fieldconfig = $this->getFieldConfig('name');
				$item->attr['name']['title'] = array($fieldconfig['title']);
				$item->attr['name']['type'] = $fieldconfig['type'];
			}

			//Ratings
			if ($this->checkPublished('rating') || $this->checkDisplayOnFO('rating')) {
				$item->rating = $this->getRating($id) ? $this->getRating($id) : 0;
				$item->width_rating = $item->rating * 20;
				$item->attr['rating']['frontend_value'] = $item->width_rating;
				$item->attr['rating']['rating'] = floatval($item->rating);
				$fieldconfig = $this->getFieldConfig('rating');
				$item->attr['rating']['title'] = array($fieldconfig['title']);
				$item->attr['rating']['type'] = $fieldconfig['type'];
			}

			if ($this->checkDisplayOnFO('hits')) {
				$item->attr['hits']['frontend_value'] = $item->hits ?? '';
				$fieldconfig = $this->getFieldConfig('hits');
				$item->attr['hits']['title'] = array($fieldconfig['title']);
				$item->attr['hits']['type'] = $fieldconfig['type'];
			}

			if ($this->checkPublished('attr.fulltext.value') || $this->checkDisplayOnFO('attr.fulltext.value')) {
				$item->attr['fulltext']['frontend_value'] = $item->desc ?? '';
				$item->attr['fulltext']['value'] = strip_tags($baseItem->text);
				$fieldconfig = $this->getFieldConfig('attr.fulltext.value');
				$item->attr['fulltext']['title'] = array($fieldconfig['title']);
				$item->attr['fulltext']['type'] = $fieldconfig['type'];
			}

			$featured = $baseItem->featured;
			$item->featured = $featured; // this value for custom use on FO like icon or something
			if ($this->checkDisplayOnFO('attr.featured.value') || $this->checkPublished('attr.featured.value')) {
				$item->attr['featured']['value'] = array($featured);
				$item->attr['featured']['frontend_value'] = $featured ? array(Text::_('COM_JAMEGAFILTER_ONLY_FEATURED')) : array(Text::_('COM_JAMEGAFILTER_NOT_FEATURED'));
				$fieldconfig = $this->getFieldConfig('attr.featured.value');
				$item->attr['featured']['title'] = array($fieldconfig['title']);
				$item->attr['featured']['type'] = $fieldconfig['type'];
			}

			if ($baseItem->created != '0000-00-00 00:00:00') {
				if ($this->checkPublished('created_date')) {
					$item->created_date = array(strtotime($baseItem->created));
				}

				if ($this->checkDisplayOnFO('created_date')) {
					$item->attr['created_date']['frontend_value'] = array(strtotime($baseItem->created));
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
					$item->attr['modified_date']['frontend_value'] = array(strtotime($baseItem->modified));
					$fieldconfig = $this->getFieldConfig('modified_date');
					$item->attr['modified_date']['title'] = array($fieldconfig['title']);
					$item->attr['modified_date']['type'] = $fieldconfig['type'];
				}
			}

			if ($baseItem->publish_up != '0000-00-00 00:00:00') {
				if ($this->checkPublished('published_date')) {
					$item->published_date = array(strtotime($baseItem->publish_up));
				}

				if ($this->checkDisplayOnFO('published_date')) {
					$item->attr['published_date']['frontend_value'] = array(strtotime($baseItem->publish_up));
					$fieldconfig = $this->getFieldConfig('published_date');
					$item->attr['published_date']['title'] = array($fieldconfig['title']);
					$item->attr['published_date']['type'] = $fieldconfig['type'];
				}
			}

			//Attributes
			$this->attr = array();
			// start optimize
			$this->getAuthorInfo($baseItem);

			// need to optimize
			//Category Info
			$this->getCategoryInfo($id, $catList);
			//Tag Info
			if (!empty($baseItem->tags->tags)){
				$this->getTagInfo($baseItem->tags->tags, $lang);
			}
			//Custom fields
			$this->getCustomFieldsInfo($id, $lang);
			// end optimize

			$item->access = $this->getPermission($baseItem);

			$item->attr = array_merge($item->attr, $this->attr);

			// support user custom field, only parse to json if template required.
			if (File::exists(JPATH_SITE . '/templates/' . $app->getTemplate() . '/etc/jamegafilter-ucf.log'))
				$item->ucf = $this->getCustomJFields($baseItem->created_by, "user");

			// support ja content type
			if (isset($baseItem->attribs['ctm_content_type']) && !empty($baseItem->attribs['ctm_' . $baseItem->attribs['ctm_content_type']])) {
				$item->{"cmt_" . $baseItem->attribs['ctm_content_type']} = $baseItem->attribs['ctm_' . $baseItem->attribs['ctm_content_type']];
			}
		}
		return $item;
	}

	public function getPermission($item)
	{
		$app = Factory::getApplication();
		$params = $app->getParams('com_content');
		if ($params->get('show_noauth')){
			return '1';
		}
		$sql = 'SELECT rules FROM #__viewlevels WHERE id = ' . $this->_db->quote($item->access);
		$this->_db->setQuery($sql);
		$access = $this->_db->loadResult();
		if (!empty($access)){
			return str_replace(['[', ']'], ['', ''], $access);
		}
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

	public function getRating($itemId)
	{
		$rateOption = $this->plgParams->get('rating-option', 'com_content');
		if ($rateOption === 'com_content') {
			$query = $this->_db->getQuery(true);
			$query->select('rating_sum, rating_count')->from('#__content_rating')->where('content_id = ' . (int) $itemId);
			$this->_db->setQuery($query);
			$rating = $this->_db->loadObject();

			if (!$rating) {
				return false;
			}
			return round((int) $rating->rating_sum / (int) $rating->rating_count, 0);
		}

		if (!$this->getComponentStatus('com_komento')) {
			return false;
		}

		$query1[] = 'SELECT ax.`component`, ax.`cid`, count(1) AS `count`, sum(ax.`ratings`) AS `totalRating`, ROUND(AVG(ax.`ratings`)/2,2) AS `avgRating`';
		$query1[] = 'FROM `#__komento_comments` AS `ax`';
		$query1[] = 'WHERE ax.`published` = ' . $this->_db->Quote(1);
		// display the posts that have ratings given
		$query1[] = 'AND ax.`ratings` > 0';
		$query1[] = 'AND ax.`cid` = ' . $this->_db->Quote($itemId);
		$query1[] = 'AND ax.`created` = ';
		$query1[] = '(SELECT MAX(bx.`created`) FROM `#__komento_comments` AS `bx`';
		$query1[] = 'WHERE bx.`email` = ax.`email`';
		$query1[] = 'AND bx.`component` = ax.`component`';
		$query1[] = 'AND bx.`cid` = ax.`cid`';
		$query1[] = ')';
		$query1[] = "AND ax.`component` = " . $this->_db->quote('com_content');
		$query1[] = "GROUP BY ax.`cid`";
		$query1   = implode(' ', $query1);
		$this->_db->setQuery($query1);
		$data = $this->_db->loadObject();
		if (!$data) {
			return false;
		}
		return isset($data) && !is_null($data) ? $data->avgRating : 0;
	}

	public function getComponentStatus($component)
	{
		$db = Factory::getDbo();
		$q = 'select enabled from #__extensions where type="component" and element = "' . $component . '"';
		$db->setQuery($q);
		$status = $db->loadResult();
		if ($status) {
			return true;
		} else {
			return false;
		}
	}

	public function getBaseItem($id)
	{
		BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/administrator/components/com_content/models', 'ContentModel');
		if (version_compare(JVERSION, '4.0', 'ge'))
			$model = new Joomla\Component\Content\Administrator\Model\ArticleModel();
		else
			$model = BaseDatabaseModel::getInstance('Article', 'ContentModel');
		$baseItem = $model->getItem($id);

		return $baseItem;
	}

	public function getAuthorInfo($baseItem)
	{
		$data = array();
		if ($baseItem->created_by_alias) {
			$data['value'][] = urlencode($baseItem->created_by_alias);
			$data['frontend_value'][] = $baseItem->created_by_alias;
		} else if ($baseItem->created_by) {
			$user = null;
			if (!empty($this->userInfo->{$baseItem->created_by})){
				$user = $this->userInfo->{$baseItem->created_by};
			}

			if ($user) {
				$data['value'][] = $baseItem->created_by;
				$data['frontend_value'][] = $user->name;
			}
		}

		if (!$data) return;

		if ($this->checkPublished('attr.author.value') || $this->checkDisplayOnFO('attr.author.value')) {
			$this->attr['author'] = $data;
			$this->attr['author']['frontend_value'] = $data['frontend_value'];
			$fieldconfig = $this->getFieldConfig('attr.author.value');
			$this->attr['author']['title'] = array($fieldconfig['title']);
			$this->attr['author']['type'] = $fieldconfig['type'];
		}
	}

	public function getCategoryInfo($article_id, $catList)
	{
		if (version_compare(JVERSION, '3.7', '<')) return;
		
		if (!empty($this->cache_content_with_cat)){
			// load cache data to handle item and write to json file
			$category = isset($this->cache_content_with_cat->{$article_id}) 
							? $this->cache_content_with_cat->{$article_id} : null;
		}else{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('c.*, a.id as a_id')
				->from('#__categories as c')
				->join('LEFT', '#__content as a ON a.catid = c.id')
				->where('a.id = ' . (int) $article_id)
				->where('c.id in (' . implode(',', $catList) . ')')
				->where('c.published = 1');
			$db->setQuery($query);
			$category = $db->loadObject();
		}

		if ($category) {
			$categories = $this->getParentCategories($category->id, $catList);
			$cats = array();
			foreach ($categories as $key => $cat) {
				$tmp = new stdClass;
				$tmp->id = $cat->id;
				$tmp->title = $cat->title;

				$rest = array_slice($categories, $key + 1);
				foreach ($rest as $c) {
					$tmp->title =  $c->title . ' &raquo; ' . $tmp->title;
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
				$cdata['frontend_value'][] = $cat->title;
			}

			if ($this->checkPublished('attr.cat.value') || $this->checkDisplayOnFO('attr.cat.value')) {
				$this->attr['cat'] = $cdata;
				$this->attr['cat']['frontend_value'] = $cdata['frontend_value'];
				$fieldconfig = $this->getFieldConfig('attr.cat.value');
				$this->attr['cat']['title'] = array($fieldconfig['title']);
				$this->attr['cat']['type'] = $fieldconfig['type'];
			}
		}

		return $this->attr;
	}

	public function getParentCategories($catid, $catList)
	{
		$db = Factory::getDbo();
		$parents = array();
		while (true) {
			if (!empty($this->cache_all_cats)){
				$result = isset($this->cache_all_cats->{$catid}) 
							? $this->cache_all_cats->{$catid} : null;
			}else{
				$query = "SELECT * 
					FROM `#__categories` 
					WHERE id = $catid 
					AND level > 0";
				$result = $db->setQuery($query)->loadObject();
			}
			if ($result && in_array($result->id, $catList)) {
				$parents[] = $result;
				$catid = $result->parent_id;
			} else {
				break;
			}
		}

		return $parents;
	}

	public function getCatNameAsTree($cat, $catList)
	{
		if (!in_array($cat->parent_id, $catList)) {
			return $cat->title;
		}
		$query = $this->_db->getQuery(true);
		$query->select('*')
			->from('#__categories')
			->where('id = ' . $cat->parent_id . ' and level > 0');
		$this->_db->setQuery($query);
		$result = $this->_db->loadObject();

		if ($result) {
			$result->title = $result->title . ' &raquo; ' . $cat->title;
			return $this->getCatNameAsTree($result, $catList);
		} else {
			return $cat->title;
		}
	}

	public function getCustomJFields($id, $context)
	{
		if ($context == 'article')
			$context = 'com_content.article';
		else if ($context == 'contact')
			$context = 'com_contact.contact';
		else if ($context == 'user')
			$context = 'com_users.user';
		$currentLanguage = Factory::getLanguage();
		$currentTag = $currentLanguage->getTag();

		$db = Factory::getDbo();
		$sql = 'SELECT fv.value, fg.title AS gtitle, f.title AS ftitle, f.name
				FROM `#__fields_values` fv
				INNER JOIN `#__fields` f ON fv.field_id = f.id
				INNER JOIN `#__fields_groups` fg ON fg.id = f.group_id
				WHERE fv.item_id = ' . $db->quote($id) . '
				AND f.context = "' . $context . '"
				AND f.language IN ("*", "' . $currentTag . '")
				AND f.access = 1
				';
		
		$db->setQuery($sql);
		$result = $db->loadObjectList();
		$arr = array();
		foreach ($result as $r) {
			$arr[$r->name] = $r->value;
		}

		return $arr;
	}

	public function getCustomFieldsInfo($itemId, $lang)
	{
		if (version_compare(JVERSION, '3.7', '<')) return;

		if (empty($this->fieldInfo)){
			// directly fetch data from database
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('f.id, f.title , fv.value, f.type, f.fieldparams, f.params')
				->from('#__fields as f')
				->join('INNER', '#__fields_values as fv ON fv.field_id = f.id')
				->where('f.context = ' . $db->quote('com_content.article'))
				->where('fv.item_id = ' . $db->quote($itemId));
			$db->setQuery($query);
			$fields = $db->loadObjectList();
		}else {
			// load fields data from cache file
			$fields = isset($this->fieldInfo->{$itemId}) ?  $this->fieldInfo->{$itemId} : null;
		}

		if ($fields) {
			$fdata = array();
			foreach ($fields as $field) {
				if (empty($field->value)) {
					continue;
				}
				
				$params = json_decode($field->params);
				$render_class = !empty($params->render_class) ? $params->render_class : ''; // class of a html tag wraps field title and field value
				$label_render_class = !empty($params->label_render_class) ? $params->label_render_class : ''; // class of a field label
				$value_render_class = !empty($params->value_render_class) ? $params->value_render_class : ''; // class of a field value
				if (!empty($render_class) || !empty($label_render_class) || !empty($value_render_class)){
					$fdata['custom_fields']['ct' . $field->id]['params']['render_class'] = $render_class;
					$fdata['custom_fields']['ct' . $field->id]['params']['label_render_class'] = $label_render_class;
					$fdata['custom_fields']['ct' . $field->id]['params']['value_render_class'] = $value_render_class;
				}

				switch ($field->type) {
					case 'text':
					case 'editor':
					case 'textarea':
						if (empty($fdata['ct' . $field->id]['value']))
							$fdata['ct' . $field->id]['value'] = "";
						$fdata['ct' . $field->id]['value'] .= $field->value;
						$fdata['ct' . $field->id]['frontend_value'] = $field->value;
						break;
					case 'url':
						if (empty($fdata['ct' . $field->id]['value']))
							$fdata['ct' . $field->id]['value'] = "";
						$fdata['ct' . $field->id]['value'] .= $field->value;
						$fdata['ct' . $field->id]['frontend_value'] = '<a target="_blank" href="' . $field->value . '">' . $field->value . '</a>';
						break;
					case 'calendar':
						$fdata['ct' . $field->id]['value'][] = strtotime($field->value);
						$fdata['ct' . $field->id]['frontend_value'][] = strtotime($field->value);
						break;
					case 'integer':
						$fdata['ct' . $field->id]['value'][] = $field->value;
						$fdata['ct' . $field->id]['frontend_value'][] = $field->value;
						break;
					case 'checkboxes':
					case 'radio':
					case 'list':
					case 'sql':
						$fdata['ct' . $field->id]['value'][] = str_replace('+', '%20', urlencode($field->value));
						$name = $this->getCustomName($field->id, $field->type, $field->value);
						$fdata['ct' . $field->id]['frontend_value'][] = empty($name) ? '' : $name;
						break;
					case 'usergrouplist':
						$gname = $this->getUserGroupName($field->value);
						if ($gname) {
							$fdata['ct' . $field->id]['value'][] = str_replace('+', '%20', urlencode($field->value));
							$fdata['ct' . $field->id]['frontend_value'][] = $gname;
						}
						break;
					case 'user':
						if (Factory::getUser($field->value)->id) {
							$fdata['ct' . $field->id]['value'][] = str_replace('+', '%20', urlencode($field->value));
							$fdata['ct' . $field->id]['frontend_value'][] = Factory::getUser($field->value)->get('name');
						}
						break;
					case 'imagelist':
						if ($field->value == '-1')
							break;
						$fieldparams = json_decode($field->fieldparams);
						$path = 'images/' . $fieldparams->directory . '/';
						$fdata['ct' . $field->id]['value'][] = str_replace('+', '%20', urlencode($path . $field->value));
						$fdata['ct' . $field->id]['frontend_value'][] = $path . $field->value;
						break;
					case 'media':
						$fieldparams = json_decode($field->fieldparams);
						if (version_compare(JVERSION, 4, 'ge')) {
							$cleaned_field = preg_replace('/"alt_text":"([^"]*?)"([^"]*?)"([^"]*?)"/', '"alt_text":"$1$2$3"', $field->value);
							if (is_string($cleaned_field)){
								$cleaned_field = json_decode($cleaned_field);
							}
							$mediaData = new Registry($cleaned_field);
							$urlData = HTMLHelper::cleanImageURL($mediaData->get('imagefile'));
							$url = $urlData->url;
							// fix in case: value != {'imagefile': '', 'alt_text': ''} && only have an url image string format
							// field value is url image string & do not have value type: {'imagefile': '', 'alt_text': ''}
							if (!empty($field->value) && empty(array_keys((array) $mediaData->getIterator()))) {
								$url = $field->value;
							}
							// field value is {'imagefile': '', 'alt_text': ''} && empty(imagefile)
							if (
								!empty($field->value) && !empty(array_keys((array) $mediaData->getIterator()))
								&& empty($mediaData->get('imagefile'))
							) {
								$url = [];
							}
						} else {
							$url = $field->value;
						}

						if (!is_array($url) && !empty($url)) {
							$fdata['ct' . $field->id]['value'][] = str_replace('+', '%20', urlencode($url));
							$fdata['ct' . $field->id]['frontend_value'][] = $url;
						}
						break;
					case 'repeatable':
					case 'acfphp':
					case 'acfupload':
					case 'acfgallery':
					case 'acfaddress':
					case 'acfarticles':
					case 'acfchainedfields':
					case 'acfconvertforms':
					case 'acfcountdown':
					case 'acfcountry':
					case 'acfcurrency':
					case 'acfdownloadbutton':
					case 'acfemail':
					case 'acffacebook':
					case 'acffaq':
					case 'acfgravatar':
					case 'acfhtml5audio':
					case 'acfiframe':
					case 'acfmap':
					case 'acfmodule':
					case 'acfpaypal':
					case 'acfprogressbar':
					case 'acfqrcode':
					case 'acfsoundcloud':
					case 'acftelephone':
					case 'acftimepicker':
					case 'acftruefalse':
					case 'acftwitter':
					case 'acfurl':
					case 'acfvideo':
					case 'acfwhatsappctc':
						break;
					default:
						$fdata['ct' . $field->id]['value'][] = str_replace('+', '%20', urlencode($field->value));
						$fdata['ct' . $field->id]['frontend_value'][] = $field->value;
						break;
				}
			}

			foreach ($fdata as $k => $f) {
				if ($this->checkPublished('attr.' . $k . '.value') || $this->checkDisplayOnFO('attr.' . $k . '.value')) {
					$this->attr[$k] = $f;
					$this->attr[$k]['frontend_value'] = $f['frontend_value'];
					$fieldconfig = $this->getFieldConfig('attr.' . $k . '.value');
					$this->attr[$k]['title'] = array($fieldconfig['title']);
					$this->attr[$k]['type'] = $fieldconfig['type'];
				}
				if ($k === 'custom_fields'){
					$this->attr[$k] = $f;
				}
			}
		}

		return $this->attr;
	}

	public function getUserGroupName($groupId)
	{
		$query = $this->_db->getQuery(true);
		$query->select('title')->from('#__usergroups')->where('id = ' . (int)$groupId);
		$this->_db->setQuery($query);

		return $this->_db->loadResult();
	}

	public function getTagInfo_($tagId, $lang){
		$query = $this->_db->getQuery(true);
		$query->select('id, title, parent_id')
			->from('#__tags')
			->where('id IN (' . $tagId . ') AND `language` IN ("*", "' . $lang . '") AND published = 1');
		$this->_db->setQuery($query);
		$tags = $this->_db->loadObjectList();

		$tdata = array();
		if ($tags) {
			foreach ($tags as $tag) {
				$tdata['value'][] = $tag->id;
				$tdata['frontend_value'][] = $this->getTagTreeName($tag);
			}
		}
		return $tdata;
	}

	public function getTagInfo($tagId, $lang)
	{
		$tdata = [];
		$tagData = $this->tagInfo;
		if (empty($tagData)){
			$tdata = $this->getTagInfo_($tagId, $lang);
		}else{
			foreach(explode(',', $tagId) as $id){
				if (isset($tagData->{$id})){
					$tdata['value'][] = $id;
					$tdata['frontend_value'][] = $this->getTagTreeName($tagData->{$id});
				}
			}
		}

		if (empty($tagData)) return ;

		if ($this->checkPublished('attr.tag.value') || $this->checkDisplayOnFO('attr.tag.value')) {
			$this->attr['tag'] = $tdata;
			$this->attr['tag']['frontend_value'] = $tdata['frontend_value'];
			$fieldconfig = $this->getFieldConfig('attr.tag.value');
			$this->attr['tag']['title'] = array($fieldconfig['title']);
			$this->attr['tag']['type'] = $fieldconfig['type'];
		}
		return $this->attr;
	}

	public function getTagTreeName($tag)
	{
		$q = 'SELECT * FROM `#__tags` WHERE id = ' . $tag->parent_id . ' AND id > 1';
		$db = Factory::getDbo()->setQuery($q);
		$result = $db->loadObject();
		if ($result) {
			$result->title = $result->title . ' &raquo; ' . $tag->title;
			return $this->getTagTreeName($result);
		} else {
			return $tag->title;
		}
	}

	public function getCustomFields()
	{
		if (version_compare(JVERSION, '3.7', '<'))
			return;

		$query = $this->_db->getQuery(true);
		$query->select('*')->from('#__fields')->where('context = "com_content.article" AND state = 1');
		$this->_db->setQuery($query);
		$fields = $this->_db->loadObjectList();

		return $fields;
	}

	/**
	 * get a name of this field value base on params in `fieldparams` in table `#__fields`
	 * 
	 * @param int $field_id
	 * @param string $field_type
	 * @param string|int $field_value
	 * 
	 * @return null|string field name of this equivalent field value
	 */
	public function getCustomName($field_id, $field_type, $field_value)
	{
		$query = $this->_db->getQuery(true);
		$query->select('fieldparams')
			->from('#__fields')
			->where('id = ' . (int) $field_id);
		$this->_db->setQuery($query);
        $field_value = trim($field_value);
		$fparams = $this->_db->loadResult();

		if (empty($fparams)) return ;

		$registry = new Registry;
		$registry->loadString($fparams);
		$fparams = $registry->toArray();
		switch ($field_type) {
			case 'sql':
				$q = $fparams['query'];
				if (!empty($q)) {
					$this->_db->setQuery($q);
					$results = $this->_db->loadObjectList();
					if ($results) {
						foreach ($results as $r) {
							if (trim($r->value) != $field_value) continue;
							return trim($r->text);
						}
					}
				}
				break;
			default:
				if (!empty($fparams['options'])) {
					foreach ($fparams['options'] as $option) {
						if (trim($option['value']) != $field_value) continue;
						return trim($option['name']);
					}
				}
				break;
		}
	}

	// copy from helper.php. if change this need to change there too.
	public function getChildCategories($catid = 1, $maxLevel = 100, $level = 0, $ordering = 'rgt ASC')
	{
		$level++;
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__categories')
			->where('parent_id = ' . (int)$catid)
			->where('extension IN ("com_content", "system")')
			->where('published = 1')
			->order($ordering);

		$db->setQuery($query);

		$children = $db->loadObjectList();
		$cats = array();
		foreach ($children as $child) {
			$cats[] = $child;
			if ($level < $maxLevel) {
				foreach ($this->getChildCategories($child->id, $maxLevel, $level, $ordering) as $c) {
					$cats[] = $c;
				}
			}
		}

		return $cats;
	}
}
