<?php
/**
 * $JA#COPYRIGHT$
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<a href="<?php echo $arrData['href'];?>" title="<?php echo $arrData['title'];?>" onclick="return hs.htmlExpand(this, {dimmingOpacity: '<?php echo $arrData['overlayOpacity'];?>', maincontentId: '<?php echo $arrData['href'];?>', headingText: '<?php echo $arrData['title'];?>', restoreDuration: '<?php echo $arrData['restoreDuration'];?>', expandDuration: '<?php echo $arrData['expandDuration'];?>', width:<?php echo $arrData['frameWidth'];?>, height: <?php echo $arrData['frameHeight'];?>, outlineType:<?php echo $arrData['outlineType'];?> <?php echo $arrData['captionId'];?><?php echo $arrData['eventStr'];?>});" ><?php echo $arrData['content'];?></a>