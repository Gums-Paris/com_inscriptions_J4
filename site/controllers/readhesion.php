<?php
/**
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.controller');

/**
 
 */
class InscriptionsControllerReadhesion extends JControllerAdmin
{

	/**
	* Saves the record on an edit form submit
	*
	* @acces public
	* @since 1.5
	*/
	function save()
	{
		InscriptionsHelper::checkUser();

		// Check for request forgeries
		JSession::checkToken() or jexit( 'Invalid Token' );

		//get data from the request
    $input = JFactory::getApplication()->input;		
		$post = $input->post->get('jform', '', 'ARRAY');
		
		$model = $this->getModel('readhesion');
    
 	  if ($model->store($post)) {
			  $msg = 'Choix enregistrés - procéder au paiement';
        $type = 'message';
        $this->setRedirect(JRoute::_('index.php?option=com_inscriptions&view=readhesion&layout=paiement', false), $msg, $type);
		} else {
			  $msg = 'Erreur dans l\'enregistrement (contacter l\'administrateur)';
        $type = 'error';
        $this->setRedirect(JRoute::_('index.php', false), $msg, $type);
		}
    
        

      
  }
    
	function cancel()
	{		
		$type = 'message';
		$msg = 'Opération annulée';
		$this->setRedirect(JRoute::_('index.php', false), $msg, $type);    
	}

    
	function annuler()
	{		
  	InscriptionsHelper::checkUser();
		
		$model = $this->getModel('readhesion');
    $model->annuler();
		$msg = 'Opération annulée';
    $type = 'message';
    $this->setRedirect(JRoute::_('index.php?option=com_inscriptions&view=readhesion', false), $msg, $type);
	}


}

?>
