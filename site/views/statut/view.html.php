<?php

jimport( 'joomla.application.component.view');
use \Joomla\CMS\Factory;

class InscriptionsViewStatut extends JViewLegacy
{
	function display($tpl = null)
	{		
    $this->data = $this->get( 'Item' );
    $input = Factory::getApplication()->input;
	  $this->itemid = $input->get('Itemid', 0,'INT');
		parent::display($tpl);
	}
}
?>
