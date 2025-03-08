<?php
/**
 * $JA#COPYRIGHT$
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<a class="highslide" href="<?php echo $arrData['href'];?>" title="<?php echo $arrData['title'];?>" onclick="return hs.htmlExpand(this, {dimmingOpacity: '<?php echo $arrData['overlayOpacity'];?>', outlineType:<?php echo $arrData['outlineType'];?> <?php echo $arrData['captionId'];?>});" ><?php echo $arrData['content'];?></a>