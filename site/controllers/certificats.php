<?php
/**
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.controller');

use \Joomla\CMS\Factory;

/**
 
 */
class InscriptionsControllerCertificats extends JControllerAdmin
{

	function save()
	{
    InscriptionsHelper::checkUser();
    // -------
		// Check for request forgeries
		JSession::checkToken() or jexit( 'Invalid Token' );

		//get data from the request
    $input = Factory::getApplication()->input;		
		$post = (object) $input->post->get('jform', '', 'ARRAY');
    		
		$model = $this->getModel('certificats');    
    $save = $model->save($post);
    
 	  if ($save) {

      $type = 'message';
      $msg = "Certificat validé";
      
      $app =Factory::getApplication();
      $a_valider = $app->getUserState( "certificats.a_valider");  
      $item = array_search($post->id, $a_valider);

      // On retire l'item modifié de la liste a_modifier
      unset($a_valider[$item]);
      $app->setUserState( "certificats.a_valider", $a_valider);  

      if (isset($a_valider[$item+1])) {
        $next = $a_valider[$item+1];
        $this->setRedirect(JRoute::_(
        'index.php?option=com_inscriptions&view=certificats&layout=edit&id='.$next,
         false), $msg, $type);
      } else {
        $msg .= 'Dernier enregistrement';
        $this->setRedirect(JRoute::_(
        'index.php?option=com_inscriptions&view=certificats', false), $msg, $type);          
      }

		} else {
		  $msg = 'Erreur dans l\'enregistrement (contacter l\'administrateur)';
      $type = 'error';
        $this->setRedirect(JRoute::_(
        'index.php?option=com_inscriptions&view=certificats', false), $msg, $type);          
		}
      
  }
    
  function suivant() {

    InscriptionsHelper::checkUser();
    $input = Factory::getApplication()->input;		
		$post = $input->post->get('jform', '', 'ARRAY');
 
    $app =Factory::getApplication();
    $a_valider = $app->getUserState( "certificats.a_valider");  
    $item = array_search($post["id"], $a_valider);

    if (isset($a_valider[$item+1])) {
    
      $next = $a_valider[$item+1];
      $this->setRedirect(JRoute::_(
      'index.php?option=com_inscriptions&view=certificats&layout=edit&id='.$next,
       false), $msg, $type);

    } else {

      $msg .= '<br>Dernier enregistrement';
      $this->setRedirect(JRoute::_(
      'index.php?option=com_inscriptions&view=certificats', false), $msg, $type);          

    }
  
  }

    
	function cancel()
	{		
    InscriptionsHelper::checkUser();
      
	  $msg = 'Opération annulée';
    $type = 'message';
    $this->setRedirect(JRoute::_('index.php?option=com_inscriptions&view=certificats', false), $msg, $type);
	}

}

?>
