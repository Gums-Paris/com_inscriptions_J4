<?php
/**
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.controller');

use \Joomla\CMS\Factory;


/**
 
 */
class InscriptionsControllerFfcam extends JControllerAdmin
{

	/**
	* Saves the record on an edit form submit
	*
	* @acces public
	* @since 1.5
	*/       
	function save()
	{
    InscriptionsHelper::checkManager();
    // -------
		// Check for request forgeries
		JSession::checkToken() or jexit( 'Invalid Token' );

		//get data from the request
    $input = Factory::getApplication()->input;		
		$post = (object) $input->post->get('jform', '', 'ARRAY');
		
		$model = $this->getModel('ffcam');
    $msg = $model->save($post);
    $msg .= $model->maj_ffcam($post->no);
    $msg .= $model->maj_ffcam_activites($post->no);
    
 	  if ($msg<>"") {

      $type = 'message';

      $app =Factory::getApplication();
      $a_modifier = $app->getUserState( "ffcam.a_modifier");  
      $item = array_search($post->no, $a_modifier);

      // On retire l'item modifié de la liste a_modifier
      unset($a_modifier[$item]);
      $app->setUserState( "ffcam.a_modifier", $a_modifier);  

      if ($post->retour<>"") {
        $type = 'message'; 
        $ret = JRoute::_('index.php?option=com_comprofiler&view=userdetails&uid='.$post->retour.'&Itemid=405#cbtabpane25', false);          
        $app->redirect($ret);
        exit;   
      }

      if (isset($a_modifier[$item+1])) {
        $next = $a_modifier[$item+1];
        $this->setRedirect(JRoute::_(
        'index.php?option=com_inscriptions&view=ffcam&layout=edit&no='.$next,
         false), $msg, $type);
      } else {
        $msg .= 'Dernier enregistrement';
        $this->setRedirect(JRoute::_(
        'index.php?option=com_inscriptions&view=ffcam', false), $msg, $type);          
      }

		} else {
		  $msg = 'Erreur dans l\'enregistrement (contacter l\'administrateur)';
      $type = 'error';
        $this->setRedirect(JRoute::_(
        'index.php?option=com_inscriptions&view=ffcam', false), $msg, $type);          
		}
      
  }
    
	function cancel()
	{		
    $this->setRedirect(JRoute::_('index.php'));         
	}


  function suivant() {

    InscriptionsHelper::checkManager();
    $input = Factory::getApplication()->input;		
		$post = $input->post->get('jform', '', 'ARRAY');
 
    $app =Factory::getApplication();
    $a_modifier = $app->getUserState( "ffcam.a_modifier");  
    $item = array_search($post["no"], $a_modifier);

    // On synchronise     
		$model = $this->getModel('ffcam');
    $msg = $model->maj_ffcam($post["no"]);
    $msg .= $model->maj_ffcam_activites($post["no"]);
    // On retire l'item modifié de la liste a_modifier
    unset($a_modifier[$item]);


    if (isset($a_modifier[$item+1])) {
    
      $next = $a_modifier[$item+1];
      $this->setRedirect(JRoute::_(
      'index.php?option=com_inscriptions&view=ffcam&layout=edit&no='.$next,
       false), $msg, $type);

    } else {

      $msg .= '<br>Dernier enregistrement';
      $this->setRedirect(JRoute::_(
      'index.php?option=com_inscriptions&view=ffcam', false), $msg, $type);          

    }
  
  }

  function later() {

    InscriptionsHelper::checkManager();
    $input = Factory::getApplication()->input;		
		$post = $input->post->get('jform', '', 'ARRAY');
 
    $app =Factory::getApplication();
    $a_modifier = $app->getUserState( "ffcam.a_modifier");  
    $item = array_search($post["no"], $a_modifier);

    if (isset($a_modifier[$item+1])) {
    
      $next = $a_modifier[$item+1];
      $this->setRedirect(JRoute::_(
      'index.php?option=com_inscriptions&view=ffcam&layout=edit&no='.$next,
       false), $msg, $type);

    } else {

      $msg .= '<br>Dernier enregistrement';
      $this->setRedirect(JRoute::_(
      'index.php?option=com_inscriptions&view=ffcam', false), $msg, $type);          

    }
  
  }



  function replace() {
    InscriptionsHelper::checkManager();
 		$model = $this->getModel('ffcam');

    $app =Factory::getApplication();
    $input = Factory::getApplication()->input;		
		$post = (object) $input->post->get('jform', '', 'ARRAY');
    if ($model->change_no_ffcam($post->no, $post->id)) {
      
      $msg = "Adhérent recalé";
      $type = "message";
      $this->setRedirect(JRoute::_(
        'index.php?option=com_inscriptions&view=ffcam&layout=edit&no='.$post->no, false),
         $msg, $type);        
    } else {
  	  $msg = 'Problème recalage adhérent';
      $type = 'error';
      $this->setRedirect(JRoute::_('index.php?option=com_inscriptions&view=ffcam', false), $msg, $type);
    }           
    
  }

  function nouveau() {
    InscriptionsHelper::checkManager();

 		$model = $this->getModel('ffcam');
    $model2 = $this->getModel('Nouveau', 'InscriptionsModel');

    $input = Factory::getApplication()->input;		
		$post = (object) $input->post->get('jform', '', 'ARRAY');
    $r = $model->edite($post->no);

    $i = $model2->nouvelAdherent($r->firstname, $r->lastname, $r->email,
       $r->cb_dateinscription, $r->no_adherent_complet);
    
    if ($i[0]) {
      $this->setRedirect(JRoute::_('index.php?option=com_inscriptions&view=ffcam&layout=edit&no='.$r->no_adherent_complet
        .'&retour='.$i[2], false), $i[1], $type);    
    } else {
  	  $msg = $i[1];
      $type = 'error';
      $this->setRedirect(JRoute::_('index.php?option=com_inscriptions&view=ffcam', false), $msg, $type);    
    }
  
  }

    
	function annuler()
	{		
    InscriptionsHelper::checkManager();
      
	  $msg = 'Opération annulée';
    $type = 'message';
    $this->setRedirect(JRoute::_('index.php?option=com_inscriptions&view=ffcam', false), $msg, $type);
	}

}

?>
