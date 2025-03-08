<?php
/**
 * $JA#COPYRIGHT$
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<div class="highslide-caption" id="<?php echo $arrData['captionID'];?>">
	<a href="#" onclick="return hs.previous(this)" class="control" style="float:left; display: block">
	<strong>Previous</strong><br/>
	<small style="font-weight: normal; text-transform: none">Left arrow key</small>
	</a>
	<a href="#" onclick="return hs.next(this)" class="control"
			style="float:left; display: block; text-align: right; margin-left: 50px">
		<strong>Next</strong><br/>
		<small style="font-weight: normal; text-transform: none">Right arrow key</small>
	</a>&nbsp;&nbsp;&nbsp;
	<a href="#" onclick="return hs.close(this)" class="control"><strong>Close</strong></a>
	<!-- <a href="#" onclick="return false" class="highslide-move control"><strong>Move</strong></a> -->
	<div style="clear:both"></div>
</div>