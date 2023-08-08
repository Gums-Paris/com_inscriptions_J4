<?php

jimport( 'joomla.application.component.view');

class InscriptionsViewNouveau extends JViewLegacy
{
	function display($tpl = null)
	{		
    $this->form		= $this->get('Form');
		parent::display($tpl);
	}
}
?>
