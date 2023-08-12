<?php 
jimport( 'joomla.application.component.view');

use \Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

class InscriptionsViewReadhesion extends JViewLegacy
{
	function display($tpl = null)
	{		    
    /* Récupération de l'id utilisateur. 
       on reroute sur l'identification si pas logué
    */  
    $app = Factory::getApplication();
  	$user   =  Factory::getUser();
		if (!$user->id) {
 //     $uri = JFactory::getURI(); 
	  $uri = Uri::getInstance();
      $return = $uri->toString(); 
			$app->redirect('index.php?option=com_comprofiler&view=login&return='. urlencode(base64_encode($return)), 
        JText::_('Connexion nécessaire pour gérer son adhésion') ); 
		}      

    /* Récupération des données (community builder) de l'utilisateur. 
       on reroute sur l'accueil en cas d'échec
    */    
    $this->data = $this->get( 'Item' );
    
    if ($this->data==null) {              
			$app->redirect(JRoute::_('index.php'), 
        JText::_('Erreur de traitement, contacter l\'administrateur') ); 
    }      
    
    /* Récupération du titre indiquant la saison en cours        
    */  
    $this->saison	= $this->get('Saison');


    switch ($app->input->get('layout')) {
    
      // Formulaire de ré-adhésion
      case "edit":
        if ($this->data->cb_adhesion>0) {
          $app->redirect(JRoute::_('index.php?option=com_inscriptions'));
        }

        
        if ($app->input->get('clubext',false , "BOOL")) {
          $this->clubext = 1;
          $this->titre ='Adhésion simple au GUMS <br />avec licence FFCAM <b>déjà prise dans un autre club</b> ';
        } else {
          $this->clubext = 0;
          $this->titre ='Adhésion section Randonnée Pédestre (<b>sans</b> licence FFCAM)';
        }
        
        
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
        
        // Calcul du montant    
        $this->montant = $this->data->cb_adhesion + $this->data->cb_assurancegums + $this->data->cb_crampon;    

        if ($this->montant==0) {
            $app->redirect(JRoute::_('index.php'), 
            JText::_('Erreur de traitement, contacter l\'administrateur'), 'error' ); 
        }      
      
      break;
      
      //  Affichage initial
      //
      default:

        // récup infos année précédente
        //
        $this->AnPrec = $this->get( 'AnPrec' );

        if (empty($this->AnPrec) ) {

          // Nouvel adhérent (pas présent dans la base archive année précédente)

          // Est-il enregistré avec un n° de licence autre club ?
          //
          if ($this->data->cb_nocaf<>'' and substr(trim($this->data->cb_nocaf), 0, 4)<>'7504') {
            $this->licence = 'FFCAM_ext';      
            $saison = "";
          } else {

            // Sinon pas de renouvellement - cas non prévu !
            //
            $this->licence = 'Impossible';      
          }


        } else {

          // Adhérent trouvé dans la base archive année précédente 

          if ($this->AnPrec->periode==1) {
            $saison = 'l\'avant-dernière saison';
          } else {
            $saison = 'la saison dernière';
          }
                      
          // Licencié FFCAM l'année précédente
          
          if ($this->AnPrec->cb_licenceffcam>0) {
            $this->licence = 'FFCAM';
          }
              
          // Licenciés FFCAM dans autre club l'année précédente
          if ($this->AnPrec->cb_nocaf<>'' 
              and substr(trim($this->AnPrec->cb_nocaf), 0, 4)<>'7504' 
              and $this->AnPrec->cb_licenceffcam==0 ) {
            
            $this->licence = 'FFCAM_ext';
          }    
      

        }


        
        if ($this->data->cb_adhesion > 0) {
          if (  in_array($this->data->cb_cheque, array("Ok", "Web", "WebGums")) ) {
            $this->msg = 'Ton adhésion est déjà à jour - Merci';
          } elseif ($this->data->cb_assurancegums>0) {
            $this->msg = 'Ton type d`\'adhésion a été choisi mais non payé
              <br><br>=> veux-tu procéder au paiement ?
              <br>Si oui : <a href="'.JRoute::_('index.php?option=com_inscriptions&view=readhesion&layout=paiement&Itemid=556').'">Clique ici</a>';
          } elseif ($this->data->cb_licenceffcam>0) {
            $this->msg = 'Tu as choisi l\'adhésion avec licence FFCAM, ton dossier est en cours de traitement';
          } else {
            $this->msg = 'Problème de traitement. Contacte l\'administrateur ouebe@gumsparis.asso.fr';
          }
                  
        } else {


          if ($this->licence=='') {
            $this->msg = 'Tu avais choisi '.$saison.' la section Randonnée Pédestre.  
              <br><br>=> Si tu pratiques toujours uniquement la randonnée pédestre (en plaine) tu peux renouveller ton adhésion en ligne, <a href="'.JRoute::_('index.php?option=com_inscriptions&view=readhesion&layout=edit&Itemid=556').'">
              <b>c\'est ici</b></a>
              <br><br>=> Si tu pratiques toute autre activité de montagne au GUMS (alpinisme, escalade, ski, randonnée sportive) il faut que tu change de formule en prenant une licence FFCAM, merci de le faire en ligne  
                directement <a href="https://extranet-clubalpin.com/app/webeff/we_crv2_step01.php?IDCLUB=7504&Hchk=vB8dNIGppHQONBPgW3m7h8EcPrqS14" target="_blank">sur le site FFCAM</a>.
              <br>C\'est rapide et pratique (paiement par carte bancaire).   
              <br><br>Sauf si tu ne le souhaites pas,<b>veille à bien cocher la case "Le Crampon"</b> pour l\'abonnement annuel de 12 € ( tu peux compléter par un don de 13 € case à cocher plus bas)
              <br>Il reste possible d\'utiliser le <a href="index.php/documents-docman/gestion/pratique/588-bulletin-adhesion-2016">formulaire papier</a> (mais c\'est plus de travail pour les permanenciers...)  
            ';

          } elseif ($this->licence=='FFCAM')  {
          
            if ($this->AnPrec->periode==1) {
              // Cas particulier des licenciés FFCAM en n-2, non adhérents en n-1
              //
              $this->msg = 'Tu étais licencié FFCAM '.$saison.' (Club Alpin) mais pas la saison dernière  
                <br><br>=> si tu étais licencié la saison dernière dans un autre club et que tu y prend ta licence cette année 
                  <br>* commence par renouveller ton adhésion sur le site FFCAM <a href="https://extranet-clubalpin.com/renouveler/" target="_blank"> ici...</a>
                  <br>* renouvelle ensuite ton adhésion GUMS <a href="'.JRoute::_('index.php?option=com_inscriptions&view=readhesion&layout=edit&clubext=1&Itemid=556').'"> ici...</a>             
                  <br>(pour reprendre ta licence au GUMS contacte la perma)        
                <br><br>=> sinon il faut refaire une inscription 
                <a href="https://extranet-clubalpin.com/app/webeff/we_crv2_step01.php?IDCLUB=7504&Hchk=vB8dNIGppHQONBPgW3m7h8EcPrqS14" target="_blank">ici...</a>. 
                <br><br>Sauf si tu ne le souhaites pas,<b>veille à bien cocher la case "Le Crampon"</b> pour l\'abonnement annuel de 12 € ( tu peux compléter par un don de 13 € case à cocher plus bas)
                <br><br>=> si tu ne souhaites plus prendre de licence FFCAM et repasser sur une adhésion simple au GUMS  
                <a href="'.JRoute::_('index.php?option=com_inscriptions&view=readhesion&layout=edit&Itemid=556').'">Clique ici</a>   
                <br>(<b>attention</b> la licence FFCAM est impérative pour participer aux activités alpinisme, ski de rando, <b>escalade</b> et randonnée sportive)  
              ';
            } else {
              $this->msg = 'Tu étais licencié FFCAM '.$saison.' (Club Alpin) 
                <br><br>=> pour renouveller ton adhésion, <a href="https://extranet-clubalpin.com/renouveler/" target="_blank">
                <b>fais le en ligne sur le site FFCAM</b></a> (le code t\'a été ou doit t\'être envoyé par courrier - tu peux aussi le demander par mail sur la <a href="https://extranet-clubalpin.com/renouveler/" target="_blank">page FFCAM</a>)
                <br><br>Sauf si tu ne le souhaites pas,<b>veille à bien cocher la case "Le Crampon"</b> pour l\'abonnement annuel de 12 € ( tu peux compléter par un don de 13 € case à cocher plus bas)
                <br><br>=> si tu ne souhaites plus prendre de licence FFCAM et repasser sur une adhésion simple au GUMS 
                <a href="'.JRoute::_('index.php?option=com_inscriptions&view=readhesion&layout=edit&Itemid=556').'">Clique ici</a>   
                <br>(<b>attention</b> la licence FFCAM est impérative pour participer aux activités alpinisme, ski de rando, <b>escalade</b> et randonnée sportive)  
              ';                
            }

          } elseif ($this->licence=='FFCAM_ext')  {
            $this->msg = 'Tu étais licencié FFCAM la saison dernière mais tu avais pris ta licence <b>dans un autre club</b> 
              <br><br>=> pour renouveller ton adhésion de la même façon cette année :
              <br>* si ça n\'a pas déjà été fait, commence par renouveller ton adhésion <a href="https://extranet-clubalpin.com/renouveler/" target="_blank">sur le site FFCAM</a> 
              <br>* renouvelle ensuite ton adhésion GUMS <a href="'.JRoute::_('index.php?option=com_inscriptions&view=readhesion&layout=edit&clubext=1&Itemid=556').'">ici...</a>   
            ';
          }           
        }
        
      break;
    }    
		parent::display($tpl);
	}
}
