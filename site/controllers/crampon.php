<?php
/**
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.controller');

use \Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/**
 
 */
class InscriptionsControllerCrampon extends JControllerAdmin
{

  function paiement() {
  
    $view =  $this->getView('crampon', 'html' );
    $view->setModel($this->getModel('crampon'), true );
    $view->setLayout('paiement');
    $view->display(); 
  }



	function cancel()
	{		
		$type = 'message';
		$msg = 'Opération annulée';
		$this->setRedirect(JRoute::_('index.php?option=com_content&view=category&layout=blog&id=133&Itemid=571'), $msg, $type);      

	}

    
	function annuler()
	{		
  	$user   =  Factory::getUser();
		if (!$user->id) {
 //     $uri = JFactory::getURI(); 
      $uri = Uri::getInstance();
      $return = $uri->toString(); 
			$app->redirect('index.php?option=com_comprofiler&view=login&return='. urlencode(base64_encode($return)), 
        JText::_('Connexion nécessaire pour gérer son adhésion') ); 
		}      
    
      $model = $this->getModel('crampon');
      $model->annuler();
			  $msg = 'Opération annulée';
        $type = 'message';
        $this->setRedirect(JRoute::_('index.php?option=com_inscriptions&view=crampon', false), $msg, $type);
	}


}

?>
