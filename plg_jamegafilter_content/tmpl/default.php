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

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

$input = Factory::getApplication()->input;
$direction = !empty($this->config->Moduledirection) ? $this->config->Moduledirection : $this->config->direction;
if ($direction == 'vertical')
	$direction='';

if((!empty($this->config->isComponent) && empty($this->config->isModule)) ||
				(empty($this->config->isComponent) && !empty($this->config->isModule)) ) {

	$user = Factory::getUser();
	$userID = $user->id;
	$groups = $user->getAuthorisedGroups();
}

$megfailterData = file_get_contents(JPATH_ROOT . $this->config->json);
$radioOpts = [];
if (!empty($megfailterData)){
	$field_val_obj = json_decode($megfailterData)->custom_field_values;
	if (!empty($field_val_obj)){
		foreach($field_val_obj as $fieldName => $fieldVal){
			$radioOpts[$fieldName] = json_decode($fieldVal)->options;
		}
	}
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
		<div data-mgfilter="content" class="<?php echo $direction ?> ja-mg-sidebar sidebar-main">
			<a href="javascript:void(0)" class="sidebar-toggle">
				<span class="filter-open">
					<i class="fa fa-filter"></i><?php echo Text::_('COM_JAMEGAFILTER_OPEN_FILTER'); ?>
				</span>
				<span class="filter-close">
					<i class="fa fa-close"></i><?php echo Text::_('COM_JAMEGAFILTER_CLOSE_FILTER'); ?>
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
	<div data-mgfilter="content" class="<?php echo $direction ?> ja-mg-sidebar sidebar-main">
		<div class="block ub-layered-navigation-sidebar sidebar-content"></div>
		<?php if(empty($this->config->isComponent)): ?>
			<a id="jamegafilter-search-btn" class="btn btn-default " href="javascript:void(0)"><?php echo Text::_('COM_JAMEGAFILTER_SEARCH') ?></a>
		<?php endif;?>
	</div>
<?php endif; ?>

<?php if((!empty($this->config->isComponent) && empty($this->config->isModule)) || (empty($this->config->isComponent) && !empty($this->config->isModule)) ): ?>

<script type="text/javascript">

<?php if(!empty($this->config->url)): ?>
var filter_url = '<?php echo $this->config->url?>';
<?php endif; ?>

var JABaseUrl = '<?php echo Uri::base(true); ?>';
var ja_default_sort="<?php echo $this->config->default_sort; ?>";
var ja_sort_by="<?php echo $this->config->sort_by; ?>";
var ja_layout_addition="<?php echo $this->config->layout_addition; ?>";
var ja_layout_columns=<?php echo json_encode($this->config->jacolumn); ?>;
var ja_userGroup = <?php echo json_encode($groups); ?>;
var p = <?php echo json_encode($this->jstemplate); ?>;
for (var key in p) {
  if (p.hasOwnProperty(key)) {
    var compiled = dust.compile(p[key], key);
    dust.loadSource(compiled);
  }
}

function bindCallback() {
	setTimeout(function(){
		if (jQuery('.jamegafilter-wrapper').find('.pagination-wrap').length) {
			jQuery('.jamegafilter-wrapper').removeClass('no-pagination');
		} else {
			jQuery('.jamegafilter-wrapper').addClass('no-pagination');
		}

		if (isMobile.apple.tablet && jQuery('#t3-off-canvas-sidebar').length) {
			jQuery('select').unbind().off().on('touchstart', function() {
    			formTouch=true;
    			fixedElement.css('position', 'absolute');
    			fixedElement.css('top', jQuery(document).scrollTop());
			});
			jQuery('html').unbind().off().on('touchmove', function() {
				if (formTouch==true) {
					fixedElement.css('position', 'fixed');
					fixedElement.css('top', '0');
					formTouch=false;
				}
			});
		}
		initScript();
	  }, 100);
	if (jQuery('.items.product-items').find('.item').length == 0) {
		jQuery('.toolbar-amount').each(function(){
			jQuery(this).find('.toolbar-number').first().text(0);
		});
	}
}

function scrolltop() {
	if (!isMobile.phone) jQuery("html, body").stop().animate({ scrollTop: jQuery('div.ja-megafilter-wrap').offset().top }, 400);
}

function MegaFilterCallback() {
	bindCallback();
	<?php echo $input->getCmd('scrolltop') ? 'scrolltop();':'' ?>
}


function afterGetData(item) {
	if (typeof(item.thumbnail) != 'undefined' && item.thumbnail != '') {
		thumbnail = item.thumbnail;
		if (!thumbnail.match(/^http|https:/)) {
			item.thumbnail = '<?php echo Uri::root(true). '/'?>'+item.thumbnail;
		}
	}

	// owner
  if (item.created_by == '<?php echo $userID ?>')
    return false;
  if (typeof item['access'] !== 'undefined' && item['access'] !== undefined && item['access'] !== null) {
    let itemAccess = item['access'].split(',');
    for (let i = 0; i < itemAccess.length; i++) {
      for (let x in ja_userGroup) {
        // super admin could see it all, public or guest.
        if (itemAccess[i] == ja_userGroup[x] || ja_userGroup[x] == 8) {
          return false;
        }
      }
    }
  }
	return true;

}

jQuery(document).ready(function() {
  var UBLNConfig = {};
  
  UBLNConfig.dataUrl = "<?php echo Uri::base(true).$this->config->json;  ?>";
  UBLNConfig.customFieldVal = <?php echo json_encode($radioOpts); ?>;
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