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

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
$input = JFactory::getApplication()->input;
$direction = !empty($this->config->Moduledirection) ? $this->config->Moduledirection : $this->config->direction;
if ($direction == 'vertical')
	$direction='';

if((!empty($this->config->isComponent) && empty($this->config->isModule)) ||
				(empty($this->config->isComponent) && !empty($this->config->isModule)) ) {

	$jnow = JFactory::getDate();
	$now = $jnow->toSql();
	$now = strtotime($now);

	$user = JFactory::getUser();
	$userID = $user->id;
// 	$groups = $user->groups;
// 	$groups = $user->getAuthorisedViewLevels();
	$groups = $user->getAuthorisedGroups();
}	
?>

<?php if(!empty($this->config->isComponent) && empty($this->config->isModule)): ?>
<?php	
	$hasModule = JaMegafilterHelper::hasMegafilterModule(); 
	if($hasModule) {
		$this->config->sticky = 0; 
	} 
?>
<?php if (isset($this->item['mparams']) && $this->item['mparams']->get('show_page_heading')) : ?>
<div class="page-header">
	<h1> <?php echo $this->item['mparams']->get('page_heading'); ?> </h1>
</div>
<?php endif; ?>
<div class="jarow <?php echo $this->item['type'] ?> <?php echo $direction; ?> ja-megafilter-wrap clearfix">
	<?php if(!empty($this->config->fullpage) && !$hasModule): ?>
		<div data-mgfilter="k2" class="<?php echo $direction ?> ja-mg-sidebar sidebar-main">
			<a href="javascript:void(0)" class="sidebar-toggle">
				<span class="filter-open">
					<i class="fa fa-filter"></i><?php echo JText::_('COM_JAMEGAFILTER_OPEN_FILTER'); ?>
				</span>
				<span class="filter-close">
					<i class="fa fa-close"></i><?php echo JText::_('COM_JAMEGAFILTER_CLOSE_FILTER'); ?>
				</span>
			</a>
			<div class="block ub-layered-navigation-sidebar sidebar-content"></div>
		</div>
	<?php endif; ?>
	<?php 
		if ($hasModule || (empty($this->config->fullpage) && !$hasModule)) {
			$full_width = 'full-width';
		} else {
			$full_width = '';
		}
	?>
	<div class="main-content <?php echo $full_width ?>"></div>
</div>
<?php else: ?>
	<div data-mgfilter="k2" class="<?php echo $direction ?> ja-mg-sidebar sidebar-main">
		<div class="block ub-layered-navigation-sidebar sidebar-content"></div>
		<?php if(empty($this->config->isComponent)): ?>
			<a id="jamegafilter-search-btn" class="btn btn-default " href="javascript:void(0)"><?php echo JText::_('COM_JAMEGAFILTER_SEARCH') ?></a>
		<?php endif;?>
	</div>	
<?php endif; ?>

<?php if((!empty($this->config->isComponent) && empty($this->config->isModule)) ||
				(empty($this->config->isComponent) && !empty($this->config->isModule)) ): ?>

<script type="text/javascript">

<?php if(!empty($this->config->url)): ?>
var filter_url = '<?php echo $this->config->url?>';
<?php endif; ?>

var JABaseUrl = '<?php echo JUri::base(true); ?>';
var jnow = '<?php echo $now; ?>';
var ja_default_sort="<?php echo $this->config->default_sort; ?>";
var ja_sort_by="<?php echo $this->config->sort_by; ?>";
var ja_layout_addition="<?php echo $this->config->layout_addition; ?>";
var ja_userGroup = <?php echo json_encode($groups); ?>;
var ja_layout_columns=<?php echo json_encode($this->config->jacolumn); ?>;

var p = <?php echo json_encode($this->jstemplate); ?>;
for (var key in p) {
	if (p.hasOwnProperty(key)) {
		var compiled = dust.compile(p[key], key);
		dust.loadSource(compiled);
	}
}

function bindCallback() {
	setTimeout(function(){
		initScript();
	}, 100);
}

function scrolltop() {
	if (!isMobile.phone) jQuery("html, body").stop().animate({ scrollTop: jQuery('div.ja-megafilter-wrap').offset().top }, 400);
}

function MegaFilterCallback() {
	bindCallback()
	<?php echo $input->getCmd('scrolltop') ? 'scrolltop()':'' ?>
}


function afterGetData(item) {
	if (jnow > item.publish_up && jnow < item.publish_down)
		return false;

	// owner
	if (item.created_by == '<?php echo $userID ?>') 
		return false;
    if (typeof item['access'] !== 'undefined' && item['access'] !== undefined && item['access'] !== null) {
        let itemAccess = item['access'].split(',');
        for (let i=0;i<itemAccess.length;i++) {
            for (let x in ja_userGroup) {
                // super admin could see it all, public or guest.
                if (itemAccess[i] == ja_userGroup[x] || ja_userGroup[x]==8) {
                    return false;
                }
            }
        }
    }
	return true;
}

jQuery(document).ready(function() {
	var UBLNConfig = {};
	UBLNConfig.dataUrl = "<?php echo JUri::base(true).$this->config->json;  ?>";
	UBLNConfig.fields = <?php echo json_encode($this->config->fields); ?>;
	UBLNConfig.sortByOptions = <?php echo str_replace('.value','.frontend_value',json_encode($this->config->sorts)); ?>;
	UBLNConfig.defaultSortBy = "<?php echo $this->config->default_sort; ?>";
	UBLNConfig.productsPerPageAllowed = [<?php echo implode(',', $this->config->paginate); ?>];
	UBLNConfig.autopage = <?php echo $this->config->autopage ? 'true':'false' ?>;
	UBLNConfig.sticky = <?php echo $this->config->sticky ? 'true':'false' ?>;
	UBLN.main(UBLNConfig);
});
</script>
<?php 
endif;