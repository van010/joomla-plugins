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

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

?>
{#data}
<div class="item product product-item col">                
	<div data-container="product-grid" class="product-item-info {?thumbnail}{:else} no-image {/thumbnail}">

		<div class="product-item-details">
			{@info:data}
				<div class="{._class}">
				  {?.key}<div class="field-label">{.key}</div>{/.key}
				  <div class="{?.key}field-value{:else}field-value-full{/.key}">
				  {@select key=._class}
				  	{@eq value="price"}
						<div data-product-id="{id}" data-role="priceBox" class="price-box price-final_price">
							<span class="price-container price-final_price tax weee">
								<span class="price-wrapper" data-price-type="finalPrice" data-price-amount="{price}" id="product-price-{id}">
								    {?frontend_base_price}
								        <span style="text-decoration:line-through;" class="eshop-base-price">{frontend_base_price|s}</span> 
								    {/frontend_base_price}
									<span class="price eshop-sale-price">{frontend_price|s}</span>
								</span>
							</span>
						</div>
<!-- 
                        {?exprice}
                        <div>
                            <span class="eshop-ex-price">Ex: {exprice|s}</span> 
                        </div>
                        {/exprice}
 -->
					{/eq}
				  	{@eq value="name"}
						<h4 class="product-item-name">
							<a href="{url}" class="product-item-link">
								{name|s}
							</a>
						</h4>
					{/eq}
					{@eq value="thumb"}
						{?thumbnail}
						<a tabindex="-1" class="product-item-photo" href="{url}">
							<span class="product-image-container">
								<img alt="{name|s}" src="<?php echo Uri::base(true).'/'; ?>{thumbnail}" class="product-image-photo">
								{labels|s}
							</span>
						</a>
						{/thumbnail}
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
			{?is_salable}
			<div class="addtocart-area">
					<div class="addtocart-bar">
						<span class="quantity-box">
							<input class="quantity-input js-recalculate" name="quantity" id="quantity_{id}" data-errstr="<?php echo Text::_('COM_JAMEGAFILTER_ERROR_CAN_ONLY_BUY'); ?> %s <?php echo Text::_('COM_JAMEGAFILTER_ERROR_CAN_ONLY_BUY_PIECES'); ?>!" value="1" init="1" step="1" type="text">
						</span>
						<span class="quantity-controls js-recalculate">
							<input class="quantity-controls quantity-plus" type="button">
							<input class="quantity-controls quantity-minus" type="button">
						</span>
						<span class="addtocart-button">
							<input id="add-to-cart-{id}" onclick="addToCart({id}, 1, BaseUrl, '');" name="addtocart" class="btn btn-default" value="<?php echo Text::_('COM_JAMEGAFILTER_ADD_TO_CART'); ?>" title="<?php echo Text::_('COM_JAMEGAFILTER_ADD_TO_CART'); ?>" type="button">             
						</span>
					</div>
			</div>
			{:else}
			<div class="stock unavailable"><span><?php echo Text::_('COM_JAMEGAFILTER_OUT_STOCK'); ?></span></div>
			<a class="btn btn-default" href="{url}"><?php echo Text::_('COM_JAMEGAFILTER_VIEW_DETAIL'); ?></a>
			{/is_salable}
		</div>
	</div>
</div>
{/data}