<?php 
jimport( 'joomla.application.component.view');

class InscriptionsViewCertificats extends JViewLegacy
{
	function display($tpl = null)
	{    
      		    
    /* Récupération de l'id utilisateur. 
       on reroute sur l'identification si pas logué
    */  
 
    InscriptionsHelper::checkUser();
 
    $app =JFactory::getApplication();
    $id = $app->input->get("id", "0");

    $this->liste = (int) $app->getUserState("certificats.liste");
    if ($this->liste==0) {
      $this->liste = $app->input->getInt('liste', 0);
    }

    switch ($app->input->get('layout')) {
  
      // Formulaire de modification
      case "edit":

        if ($no_adherent == 0) {
          $a_valider = $app->getUserState("certificats.a_valider");
          if (count($a_valider)==0) {
            break;
          }
          $id = $a_valider[0];
        }
      break;
  
    }

    /* Récupération des données (community builder) de l'utilisateur. 
       on reroute sur l'accueil en cas d'échec
    */    
    $this->data = $this->get('Data');    
    $this->sans = $this->get('SansCertif');
    
    if ($this->data==null) {              
			$msg = "Pas d'enregistrement à traiter"; 
    }      
    
    // $path = "C:\wamp64\www\gums\images\comprofiler\plug_cbfilefield\62"
    $this->path_images = JPATH_BASE."/images/comprofiler/plug_cbfilefield/";

	  parent::display($tpl);
    
	}
}
?>
