<?php
/**
 * $JA#COPYRIGHT$
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<a <?php echo $arrData['rel'];?> class="<?php echo $arrData['class']; ?>"  href="<?php echo $arrData['href']; ?>" title="<?php echo $arrData['title'] ?>" ><?php echo $arrData['content'] ?></a>
<script language="javascript" type="text/javascript">
	/* <![CDATA[ */
	jQuery(document).ready(function() {
		if(!(window.japuQuery || window.jQuery)("a.<?php echo $arrData['class']; ?>").fancybox({
			hideOnContentClick: false,
			<?php if($arrData['onopen'] != "") echo "onStart: ".$arrData['onopen'].","; ?>
			<?php if($arrData['onclose'] != "") echo "onClosed: ".$arrData['onclose'].","; ?>
			zoomSpeedIn: <?php echo $arrData['zoomSpeedIn']; ?>,
			zoomSpeedOut: <?php echo $arrData['zoomSpeedOut']; ?>,
			overlayShow: <?php echo $arrData['overlayShow']; ?>,
			overlayOpacity: <?php echo $arrData['overlayOpacity']; ?>,
			centerOnScroll: <?php echo $arrData['centerOnScroll']; ?>,
			width: <?php echo $arrData['frameWidth']; ?>,
			height: <?php echo $arrData['frameHeight']; ?> 
		})){
			(window.japuQuery || window.jQuery)("a.<?php echo $arrData['class']; ?>").fancybox();
		}
	});
/* ]]> */
</script>