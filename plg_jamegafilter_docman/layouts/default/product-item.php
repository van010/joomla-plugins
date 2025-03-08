<?php
/*
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
use Joomla\CMS\Language\Text;

?>
{#data}
<div class="item product product-item col">                
	<div data-container="product-grid" class="product-item-info {?thumbnail}{:else} no-image {/thumbnail}">

		<div class="product-item-details">
			{@info:data}
				<div class="{._class}">
				  {?.key}
				  	<div class="field-label">
				  		{@eq key=._class value="downloads"}
							{?download_fe}
								<?php echo Text::_('COM_JAMEGAFILTER_DOWNLOAD');?>
							{:else}
								<?php echo Text::_('COM_JAMEGAFILTER_DOWNLOADS');?>
							{/download_fe}
				  		{:else}
				  			{.key}
				  		{/eq}
				  	</div>
				  {/.key}
				  <div class="{?.key}field-value{:else}field-value-full{/.key}">
				  {@select key=._class}
				  	{@eq value="name"}
						<h4 class="product-item-name">
							<a href="{url}" class="product-item-link">
								<span class="k-icon-document-{icon|s}"> </span>
								{name|s}
							</a>
						</h4>
					{/eq}
					{@eq value="thumb"}
						{?thumbnail}
						<a tabindex="-1" class="product-item-photo" href="{url}">
							<span class="product-image-container">
								<img alt="{name|s}" src="<?php echo Uri::base(true).'/joomlatools-files/docman-images/'; ?>{thumbnail}" class="product-image-photo">
							</span>
						</a>
						{/thumbnail}
					{/eq}
					{@eq value="downloads"}
						{?download_fe}
							{download_fe}
						{:else}
							{downloads_fe}
						{/download_fe}
					{/eq}
					{@none}
						{.value|s}
					{/none}
				  {/select}
				  </div>
				</div>
			{/info}
		</div>
		<div class="product-item-actions">
			<a class="btn btn-default" href="{url}"><?php echo Text::_('COM_JAMEGAFILTER_VIEW_DETAIL'); ?></a>
			
			{?isVideo}
			<a class="btn btn-primary" href="{url_download}" onclick="window.open(this.href, 'Play Video', 'width=auto, height=auto, left=24, top=24, scrollbars, resizable'); return false;"  style="margin-left: 10px;">
				<span class="fa fa-play" aria-hidden="true" style="padding-right: 5px;"> </span>
				<?php echo Text::_('COM_JAMEGAFILTER_PLAY'); ?>
			</a>
			{:else}
			<a class="btn btn-primary" href="{url_download}" style="margin-left: 10px;">
				<span class="k-icon-data-transfer-download"> </span>
				<?php echo Text::_('COM_JAMEGAFILTER_DOWNLOAD'); ?>
			</a>
			{/isVideo}
		</div>
	</div>
</div>
{/data}