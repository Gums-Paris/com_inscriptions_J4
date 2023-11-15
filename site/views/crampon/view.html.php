<?php 
jimport( 'joomla.application.component.view');
use \Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class InscriptionsViewCrampon extends JViewLegacy
{
	function display($tpl = null)
	{
      		    
    /* Récupération de l'id utilisateur. 
       on reroute sur l'identification si pas logué
    */  
    $app = Factory::getApplication();
  	$user   =  Factory::getUser();
		if (!$user->id) {
//      $uri = JFactory::getURI(); 
      $uri = Uri::getInstance();
      $return = $uri->toString(); 
			$app->redirect('index.php?option=com_comprofiler&view=login&return='. urlencode(base64_encode($return)), 
        JText::_('Connexion nécessaire pour gérer son abonnement') ); 
		}      
	  $menu_actif     = $app->getMenu()->getActive();
 	  	if (isset($menu_actif)) {
		  $this->itemid = $menu_actif->id;
		}

    /* Récupération des données (community builder) de l'utilisateur. 
       on reroute sur l'accueil en cas d'échec
    */    
    $this->data = $this->get( 'Item' );

    
    if ($this->data==null) {              
			$app->redirect(JRoute::_('index.php'), 
        JText::_('Erreur de traitement, contacter l\'administrateur') ); 
    }      

    // Déja abonné => on diffuse juste le message

    if ((int) $this->data->cb_crampon>0) {
      
      $layout = 'default';
      $this->setLayout('default');   
    
    } else {
    
      $layout = $this->getLayout();
      if ($layout=="default") {        
        $layout = "edit";
        $this->setLayout('edit');            
      }
      
    }

    /* Récupération du titre indiquant la saison en cours        
    */  
    $this->saison	= $this->get('Saison');

         
    switch ($layout) {
    
    // Formulaire de souscription
    case "edit":
    	$this->form		= $this->get('Formulaire');      
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
      
      $post = $app->input->post->get('jform', '', 'ARRAY');
      
      // Calcul du montant    
      $this->montant = (float) $post['cb_crampon'];      
      
      if ($this->montant==0) {
     			$app->redirect(JRoute::_('index.php'), 
          JText::_('Erreur de traitement, contacter l\'administrateur'), 'error' ); 
      }      
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
