<?php
/**
 * $JA#COPYRIGHT$
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<a class="<?php echo $arrData['class'];?>" id="<?php echo $arrData['id'];?>" title="<?php echo $arrData['title'];?>" href="<?php echo $arrData['href'];?>" rel="<?php echo $arrData['rel'];?>" ><?php echo $arrData['content'];?></a>
<?php if (trim($arrData['desc'])!=''):?><span class="multiBoxDesc <?php echo $arrData['id'];?>"><?php echo $arrData['desc'];?></span><?php endif;?>