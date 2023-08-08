<?php 
jimport( 'joomla.application.component.view');

class InscriptionsViewFfcam extends JViewLegacy
{
	function display($tpl = null)
	{		    
      	$user   =  JFactory::getUser();
    
    /* Récupération de l'id utilisateur. 
       on reroute sur l'identification si pas logué
    */   
    InscriptionsHelper::checkUser();
    
        
    $model = $this->getModel();                                   

    $app =JFactory::getApplication();
    $no_adherent = $app->input->get("no", "0");

    switch ($app->input->get('layout')) {
  
      // Formulaire de modification
      case "edit":

        if ($no_adherent == 0) {
          $a_modifier = $app->getUserState("ffcam.a_modifier");
          if (count($a_modifier)==0) {
            break;
          }
          $no_adherent = $a_modifier[0];
        }
      
        $this->data = $model->edite($no_adherent);
        $this->certificat = $model->certificat($no_adherent);

        $rap = $model->rapproche($this->data);
        $this->tableau  =  $rap[1];
        $this->data->id = $rap[0];
        
        $this->titrechamps = $model->getTitreChamps(); 

        $this->retour = $app->input->get("retour");


        break;
  
      // Formulaire de création
      case "new":                         

        if ($no_adherent == 0) {
          $a_ajouter = $app->getUserState("ffcam.a_ajouter");
          if (count($a_ajouter)==0) {
            break;
          }
          $no_adherent = $a_ajouter[0];
        }
        $this->data = $model->edite($no_adherent);
        $this->doublonsFfcam = $model->chercheDoublonFfcam($this->data->no_adherent_complet);    
        $this->doublons = $model->chercheDoublon($this->data->firstname, $this->data->lastname);
        $this->doublonsEmail = $model->chercheDoublonEmail($this->data->no_adherent_complet, $this->data->email);        

      break;

      // Controle global
      case "controle":                         

        $this->statut = $model->statut();
        $this->a_modifier = $model->compare_tout(true);
        $this->nb_a_modifier = count($this->a_modifier);
        if ($this->nb_a_modifier>0) { 
          $this->lien_modifier = '<a href="index.php?option=com_inscriptions&view=ffcam&layout=edit">';
        } else {
          $this->lien_modifier = '<a>';
        }


      break;
      
      default:


        $this->statut = $model->statut();
        $this->comparaison = $model->compare();                

        if ($this->comparaison[0] > 0) { 
          $this->lien_modifier = '<a href="index.php?option=com_inscriptions&view=ffcam&layout=edit">';
        } else {
          $this->lien_modifier = '<a>';
        }
        $this->lien_ajouter = '<a href="index.php?option=com_inscriptions&view=ffcam&layout=new">';
        $this->compare_tout = '<a href="index.php?option=com_inscriptions&view=ffcam&layout=controle">';

      break;                        

    }
		parent::display($tpl);
	}
  
}
?>
                                  