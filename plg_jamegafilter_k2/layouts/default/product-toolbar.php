<?php
/*
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
?>
<div class="pages pagination-wrap">
	{^autopage}
	<ul aria-labelledby="paging-label" class="items pages-items pagination">
		<li class="item pages-item-first button{@if value=startPage is=curPage} disabled{/if}">			
			<a title="Go to First page" href="#page={startPage}" class="page action start" data-action="page" data-value="{startPage}">
				<span><?php echo JText::_('COM_JAMEGAFILTER_FIRST'); ?></span>
			</a>
		</li>
		
		<li class="item pages-item-prev{@if value=prevPage is=curPage} disabled{/if}">
			<a title="Prev" href="#page={prevPage}" class="page action previous" data-action="page" data-value="{prevPage}">
				<span><?php echo JText::_('JPREV'); ?></span>
			</a>
		</li>
		
		{#pages} 
		<li class='item {@if value=. is=curPage} active  {/if}'>				
			<a class="page" href="#page={.}" title="Go to page {.}" data-action="page" data-value="{.}">
				<span>{.}</span>
			</a>
		</li>
		{/pages}
		
		<li class="item pages-item-next{@if value=nextPage is=curPage} disabled{/if}">
			<a title="Next" href="#page={nextPage}" class="page action next" data-action="page" data-value="{nextPage}">
				<span><?php echo JText::_('JNEXT'); ?></span>
			</a>
		</li>

		<li class="item pages-item-last{@if value=endPage is=curPage} disabled{/if}">
			<a title="Go to Last page" href="#page={endPage}" class="page action last" data-action="page" data-value="{endPage}">
				<span><?php echo JText::_('COM_JAMEGAFILTER_LAST'); ?></span>
			</a>
		</li>
	</ul>
	{/autopage}
</div> 

<div class="orderby-displaynumber">
	<div class="toolbar-sorter sorter">
		<label for="sorter" class="sorter-label"><?php echo JText::_('COM_JAMEGAFILTER_SORT_BY'); ?></label>		
		<select class="sorter-options" data-role="sorter" id="sorter">
			{#sortByOptions}
			<option{@if value=sortField is=field} selected="selected"{/if} value="{field}" data-action="sort" data-value="{field}">{title}</option>
			{/sortByOptions}
		</select>		
		<a data-value="{sortDir}" data-role="direction-switcher" class="action sorter-action sort-{sortDir}" href="#" title="Set Descending Direction" data-action="sortdir">
			<i class="fa fa-long-arrow-up" aria-hidden="true"></i>
		</a>
	</div>

	<div class="field limiter">
	{^autopage}
		<label for="limiter" class="limiter-label">
			<span><?php echo JText::_('JSHOW'); ?></span>
		</label>
		<select class="limiter-options" data-role="limiter" id="limiter">
			{#productsPerPageAllowed}
			<option{@if value=itemPerPage is=.} selected="selected"{/if} value="{.}" data-action="limiter" data-value="{.}">{.}</option>
			{/productsPerPageAllowed}
		</select>
		<span class="limiter-text"><?php echo JText::_('COM_JAMEGAFILTER_PER_PAGE'); ?></span>
	{/autopage}
	</div>

	<div id="toolbar-amount" class="counter toolbar-amount">
		<?php echo JText::_('COM_JAMEGAFILTER_ITEMS'); ?> <span class="toolbar-number" data-lnstate="startIdx">0</span>-<span class="toolbar-number" data-lnstate="endIdx">0</span> <?php echo JText::_('COM_JAMEGAFILTER_OF'); ?> <span class="toolbar-number" data-lnstate="totalItems">0</span>
	</div>

	<div class="field jamg-layout-chooser">
		<span data-layout="grid"><i class="fa fa-th"></i></span>
		<span data-layout="list"><i class="fa fa-list"></i></span>
	</div>

</div>