<?php 

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

class megafilterCacheData extends ContentFilterHelper{

	public $params;
	public $cache_tag_path = JPATH_ROOT . '/cache/all_tags.json';
    public $cache_user_path = JPATH_ROOT . '/cache/all_users.json';
    public $cache_categories_path = JPATH_ROOT . '/cache/all_cats.json';
    public $cache_articles_category_path = JPATH_ROOT . '/cache/all_articles_in_cats.json';
	public $cache_field_path = JPATH_ROOT . '/cache/all_fields.json';

    public function __construct($params=null)
    {
        $this->params = new Registry($params);
    }

	/**
	 * main
	 * 
	 * @return void
	 */
    public function main(){
		if (empty($this->params)) return ;
		$cats = $this->params->get('contentcategories');

		$this->cacheTag();
		$this->cacheAllCats();
		$this->cacheUsersInfo();
		$this->cacheAritlcesWithCats($this->getCatList($cats));
		$this->cacheCustomFields();
    }

	/**
	 * cache all articles in categories into json file
	 * 
	 * @param array $catList
	 * 
	 * @return void
	 */
    public function cacheAritlcesWithCats($catList){
		if (is_file($this->cache_articles_category_path))return ;
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('con.id as a_id, cat.*')
			->from('#__content as con')
			->join('INNER', '#__categories as cat ON cat.id = con.catid')
			->where('con.catid in (' . implode(',', $catList) . ')')
			->where('con.state = 1')
			->where('cat.published = 1');
		$db->setQuery($query);
		$results = $db->loadObjectList('a_id');
		$this->writeToFile($results, $this->cache_articles_category_path);
	}

	/**
	 * cache all categories have level > 0 into json file
	 * 
	 * @return void
	 */
	public function cacheAllCats(){
        if (is_file($this->cache_categories_path)) return ;
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('`#__categories`')
			->where('`level` > 0');
		$db->setQuery($query);
		$cats = $db->loadObjectList('id');
		$this->writeToFile($cats, $this->cache_categories_path);
	}

	/**
	 * cache all user information into json file
	 * 
	 * @return void
	 */
    public function cacheUsersInfo(){
        if (is_file($this->cache_user_path)) return ;
        $db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('`id`, `name`')
			->from('`#__users`')
			->where('`block` = 0');
		$db->setQuery($query);
		$users = $db->loadObjectList('id');
		$this->writeToFile($users, $this->cache_user_path);
    }

	/**
	 * cache all tag data with all languages into json file
	 * 
	 * @param null|string $lang
	 * 
	 * @return void
	 */
	public function cacheTag($lang=null){
		if (is_file($this->cache_tag_path)) return ;
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('`id`, `title`, `parent_id`, `language`')
			->from('`#__tags`')
			->where('`published` = 1');
		if (isset($lang)){
			$query->where('`language` IN ("*", "'.$lang.'")');
		}
		$db->setQuery($query);
		$tags = $db->loadObjectList('id');
		$this->writeToFile($tags, $this->cache_tag_path);
	}

	/**
	 * cache all joomla custom fields data in to json file
	 * 
	 * @return void
	 */
	public function cacheCustomFields(){
		if (is_file($this->cache_field_path)) return ;
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('f.id, f.title , fv.value, f.type, f.fieldparams, f.params, fv.item_id')
			->from('#__fields as f')
			->join('INNER', '#__fields_values as fv ON fv.field_id = f.id')
			->where('f.state = 1')
			->where('f.context = "com_content.article"');
		$db->setQuery($query);
		$fields = $db->loadObjectList();
		$customData = [];
		foreach($fields as $key => $field){
			$customData[$field->item_id][] = $field;
		}
		$this->writeToFile($customData, $this->cache_field_path);
	}

	/**
	 * write data into json file | write only if file not exsit
	 * 
	 * @return void
	 */
    public function writeToFile($data, $path_to_file){
        if(!is_file($path_to_file)){
            file_put_contents($path_to_file, json_encode($data).PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

	/**
	 * from a root category gets all sub-categories (included a root category)
	 * 
	 * @param int|string $catId
	 * @param string $ordering optional
	 * 
	 * @return array $catList
	 */
	public function getCatList($catid, $ordering = 'rgt ASC')
	{
		$catid = $catid ? $catid : '1';

		$catList = array();
		$include_root = $this->params->get('include_root', self::INCLUDE_ROOT);
		$subcat = $this->params->get('subcat', self::ALL);

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
}

?>