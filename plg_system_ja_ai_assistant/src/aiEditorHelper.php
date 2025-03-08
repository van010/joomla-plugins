<?php


defined('_JEXEC') or die('Restricted access');


class aiEditorHelper
{
	public function __construct()
	{
		// todo
	}

	public function cleanText($text)
	{
		$cleanText = preg_replace('/[^\w\s]/', '', $text);
		return trim($cleanText);
	}

}

?>