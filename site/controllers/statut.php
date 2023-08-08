<?php
/**
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.controller');

/**
 
 */
class InscriptionsControllerStatut extends JControllerAdmin
{

	/**
	* Importe l'ensemble des données FFCAM 
	* via l'api Soap et le convertit pour
  * intégration dans la table ffcam1
  *
	*/       
	function importe()
	{
    InscriptionsHelper::checkManager();
      
  }

}

?>
