<?php
/**
 * $JA#COPYRIGHT$
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<?php if(version_compare(JVERSION, '3.2.0', 'gt')){ ?>
<a <?php echo $arrData['rel'];?> class="<?php echo $arrData['class']; ?>"  href="<?php echo $arrData['href']; ?>" title="<?php echo $arrData['title'] ?>" ><?php echo $arrData['content'] ?></a>
<?php } else { ?>
<a <?php echo $arrData['rel'];?> class="<?php echo $arrData['class']; ?>"  href="<?php echo $arrData['href']; ?>" title="<?php echo $arrData['title'] ?>" >
	<span>	
		<?php echo $arrData['content'] ?>
	</span>
</a>
<?php } ?>

<script language="javascript" type="text/javascript">
/* <![CDATA[ */
	jQuery(document).ready(function() {
		(window.japuQuery || window.jQuery)("a.<?php echo $arrData['class']; ?>").fancybox({
			imageScale:<?php echo $arrData['imageScale']?>,
			overlayShow: <?php echo $arrData['overlayShow']; ?>,
			overlayOpacity: <?php echo $arrData['overlayOpacity']; ?>,
			<?php if($arrData['onopen'] != "") echo "onStart: ".$arrData['onopen'].","; ?>
			<?php if($arrData['onclose'] != "") echo "onClosed: ".$arrData['onclose'].","; ?>
			centerOnScroll: <?php echo $arrData['centerOnScroll']; ?>
		});
	});
/* ]]> */
</script>