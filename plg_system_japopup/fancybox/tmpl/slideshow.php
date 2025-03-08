<?php
/**
 * $JA#COPYRIGHT$
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

$show = false;
foreach ($arrData['content'] as $v){
$image_url = trim($v);
?>
<a class="<?php echo $arrData['class']; ?>" rel="<?php echo $arrData['rel']; ?>"  href="<?php echo $image_url; ?>" title="<?php echo $arrData['title'] ?>" >
<?php if($arrData['imageNumber'] == "all"){ ?>
	<img src="<?php echo $image_url; ?>" width="<?php echo $arrData['frameWidth']; ?>"/>
<?php }elseif($show === false){ ?>
	<?php $show = true; echo $arrData['imageNumber']; ?>
<?php } ?>
</a>
<?php
	}
?>
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