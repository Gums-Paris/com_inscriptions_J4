<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
 
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('radio');
 
class JFormFieldAdhesion extends JFormFieldRadio {
 
	protected $type = 'Adhesion';
  
  protected function getOptions()
	{
		$opts = array (array('value'=>'21', 'text'=>'Adulte'), array('value'=>'18', 'text'=>'Jeune'));

		foreach ($opts as $option)
		{

			// Create a new option object based on the <option /> element.
			$tmp = JHtml::_(
				'select.option', (string) $option['value'], trim((string) $option['text'].' : '.$option['value'].' €'), 'value', 'text',
				$disabled
			);

			// Set some option attributes.
			$tmp->class = (string) $option['class'];

			// Set some JavaScript option attributes.
			$tmp->onclick = (string) $option['onclick'];
			$tmp->onchange = (string) $option['onchange'];

			// Add the option object to the result set.
			$options[] = $tmp;
		}

		reset($options);

		return $options;
	}

}
