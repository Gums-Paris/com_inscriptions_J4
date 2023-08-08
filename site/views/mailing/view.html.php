<?php 
jimport( 'joomla.application.component.view');

class InscriptionsViewMailing extends JViewLegacy
{
	function display($tpl = null)
	{		    

		$model = $this->getModel();

		$this->item = $this->get("Item");

		$this->userid	= $model->userid;

		// La liste des adresses mail enregistrées pour l'utilisateur
		$this->adresses = $model->adresses;

		// La liste des listes
		$this->listes   = $model->listes;

		// Les abonnements
		$this->listes1  = $model->listes1;		
				
		$this->msg = $model->msg;
		
 		parent::display($tpl);
	}
}



?>
