<?php

jimport( 'joomla.application.component.view');

class InscriptionsViewInscription extends JViewLegacy
{
	function display($tpl = null)
	{		
  
  
  
    $this->data = $this->get( 'Item' );
    if ($this->data==null) {
      exit;
    }  
    
    $this->AnPrec = $this->get( 'AnPrec' );
    		
    
		parent::display($tpl);
	}
}
?>
