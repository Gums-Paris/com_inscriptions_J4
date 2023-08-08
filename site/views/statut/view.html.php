<?php

jimport( 'joomla.application.component.view');

class InscriptionsViewStatut extends JViewLegacy
{
	function display($tpl = null)
	{		
    $this->data = $this->get( 'Item' );
    $input = JFactory::getApplication()->input;
	  $this->itemid = $input->get('Itemid', 0,'INT');
		parent::display($tpl);
	}
}
?>
