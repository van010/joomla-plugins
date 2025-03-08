<?php
/**
 * $JA#COPYRIGHT$
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormField;

class JFormFieldOpts extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'Opts';

	/**
	 * Flag to tell the field to always be in multiple values mode.
	 *
	 * @var    boolean
	 * @since  11.1
	 */
	protected $forceMultiple = true;

	protected function getLabel()
	{
		return '';
	}
	/**
	 * Method to get the field input markup for check boxes.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
		$k = 0;
		$html = array();
		// Initialize some field attributes.
		$class = $this->element['class'] ? ' class="checkboxes ' . (string) $this->element['class'] . '"' : ' class="checkboxes"';
		// Start the checkbox field output.
		$html[] = '<fieldset id="' . $this->id . '"' . $class . '>';
		
		$cols = intval($this->element['cols']);
		if($cols == 0){
			$cols = 1;
		}
		$width = floor(100/$cols);
		$style = ' style="float:left; width:'.$width.'%;"';
		if($this->element->children()){
			foreach ($this->element->children() as $option)
			{
				$group = isset($option['group'])?intval($option['group']):0;
				$odesc	= isset($option['description'])?Text::_($option['description']):'';
				$otext	= Text::_(trim((string) $option));

				$tooltip	= addslashes(htmlspecialchars($odesc, ENT_QUOTES, 'UTF-8'));
				$titletip		= addslashes(htmlspecialchars($otext, ENT_QUOTES, 'UTF-8'));

				if($titletip) {
					$titletip = $titletip.'::';
				}
				
				if($group) {
					$html[] =  "<div class=\"group_title\" style=\"clear:both; margin:5px 0 0 0; color:#AAA;\"><span class=\"hasTip\" title=\"{$titletip}{$tooltip}\">$otext</span></div>";
				} else {

					
					$oval	= $option['value'];
					$children	= $option['children'];
					$alt = ($children) ? ' alt="'.$children.'"' : '';
					$extra	 = '';

					if (is_array( $this->value ))
					{
						foreach ($this->value as $val)
						{
							$val2 = is_object( $val ) ? $val->$key : $val;
							if ($oval == $val2)
							{
								$extra .= ' checked="checked"';
								break;
							}
						}
					} else {
						$extra .= ( (string)$oval == (string)$this->value  ? ' checked="checked"' : '' );
					}
					
					$html[] = "<div class=\"group_item\" $style>";	
					$html[] = "<input type=\"checkbox\" class=\"jacheckbox\" name=\"{$this->name}\" id=\"{$this->id}{$k}\" value=\"$oval\"$extra $alt /> ";
					$html[] = "<label id=\"{$this->id}{$k}-label\" class=\"hasTip\" title=\"{$titletip}{$tooltip}\" for=\"{$this->id}{$k}\">$otext</label>";
					$html[] = "</div>";
					
					$k++;
				}
			}
		}

		return implode("\r\n", $html);
	}
}
