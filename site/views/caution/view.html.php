<?php 
jimport( 'joomla.application.component.view');

class InscriptionsViewCaution extends JViewLegacy
{
	function display($tpl = null)
	{
      		    
    /* Récupération de l'id utilisateur. 
       on reroute sur l'identification si pas logué
    */  
    $app = JFactory::getApplication();
  	$user   =  JFactory::getUser();
		if (!$user->id) {
      $uri = JFactory::getURI(); 
      $return = $uri->toString(); 
			$app->redirect('index.php?option=com_comprofiler&view=login&return='. urlencode(base64_encode($return)), 
        JText::_('Connexion nécessaire pour saisir sa caution') ); 
		}      

    /* Récupération des données (community builder) de l'utilisateur. 
       on reroute sur l'accueil en cas d'échec
    */    
    $this->data = $this->get( 'Item' );

    //echo '<pre>'; print_r($this->data); echo '</pre>'; exit;

    if ($this->data==null) {              
			$app->redirect(JRoute::_('index.php'), 
        JText::_('Erreur de traitement, contacter l\'administrateur') ); 
    }      

    // Caution existante

    if ( (int) $this->data->cb_caution>0) {
      
      $layout = 'default';
      $this->setLayout('default');   
    
    } else {
    
      $layout = $this->getLayout();
      if ($layout=="default") {        
        $layout = "paiement";        
      }
      
    }
         
    switch ($layout) {
    
    // Formulaire de souscription
    case "edit":
    	
    break;
    
    // Page d'après paiement
    case "aprespaiement":     
      $retour = $app->input->get('vads_result');
      if ($retour=='00') {
        $this->msg = 'Paiement enregistré - Merci';
      } else {
        $app->redirect(JRoute::_('index.php?option=com_inscriptions'),'Transaction annulée','error');
      
      }
    break;
    
    // Page de paiement CB
    case "paiement":
           
      // Calcul du montant    
      $this->montant = (float) 350.00;           
      $date = new DateTime();      
      $date->add(new DateInterval('P365D'));
      $this->date = $date->format('d-m-Y');      
      $this->setLayout("paiement");

    
    break;
    
    //  Affichage initial
    default:
  
      if ($this->data->cb_crampon > 0) {
          $this->msg = 'Ton abonnement est déjà à jour - Merci';
        } else {
          $this->msg = '<br>S\'abonner au crampon ?
             <br>Si oui : <a href="'.JRoute::_('index.php?option=com_inscriptions&view=crampon&layout=paiement').'">Clique ici</a>';
        }              
    }

	  parent::display($tpl);
    
	}
}
?>
