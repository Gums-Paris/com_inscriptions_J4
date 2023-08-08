<?php
/**
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

/**
 
 */
class InscriptionsControllerMailing extends JControllerAdmin
{


 	function ajouter() {



		$model = $this->getModel("mailing"); 		

		$app = JFactory::getApplication();

		$userid = $app->input->getInt('userid', 0);
		$email  = $app->input->get('email', '', 'string');

		$model->ajouter($userid, $email, 0);
		
		echo implode("<br>",$model->msg);
		exit;

	}

	
	function supprimer() {

		$app = JFactory::getApplication();
		$userid = $app->input->getInt('userid', 0);

		$model = $this->getModel("mailing"); 		
		$id_adresse  = $app->input->get('id_adresse');

		$model->supprimer($id_adresse);
		
		echo implode("<br>",$model->msg);
		exit;

	}


	function abonner() {

		$app = JFactory::getApplication();

		$id_liste   = $app->input->getInt('id_liste', 0);
		$id_adresse = $app->input->getInt('id_adresse', 0);

		$model = $this->getModel("mailing");
		$model->abonner($id_adresse, $id_liste);
				
		echo implode("<br>", $model->msg);
		exit;

	}

	function desabonner() {

		$app = JFactory::getApplication();

		$id_liste   = $app->input->getInt('id_liste', 0);
		$id_adresse = $app->input->getInt('id_adresse', 0);

		$model = $this->getModel("mailing");
		$model->desabonner($id_adresse, $id_liste);
				
		echo implode("<br>", $model->msg);
		exit;

	}

	function synchro() {

		//InscriptionsHelper::checkManager();


		$model = $this->getModel("mailing"); 		

		$model->synchroAdherents();
		
		$model->synchroPerma();
		$model->synchroEscalade();		
		$model->synchroCd();
		$model->synchroBureau();
		$model->synchroResCores();
		$model->synchroRes();
		$model->synchroEncadrantsAlpi();		
		$model->synchroEncadrantsCascade();		

			
		//echo implode($model->msg, "\n\r");
		echo implode("<br>", $model->msg);
		echo " - fin ".date("Y-m-d H:i:s");
		

		file_put_contents ("/home/gumspari/www/components/com_inscriptions/messages/test.log", implode("\n\r", $model->msg)."\n\r".date("Y-m-d H:i:s") );
		// wget -O - -q 'https://www.gumsparis.asso.fr/index.php/synchro-mailing-list'
		// /usr/bin/wget -q -O /dev/null https://www.gumsparis.asso.fr/index.php?option=com_inscriptions&task=mailing.synchro > /dev/null 2>&1

		//print_r($_SERVER);

		// [REMOTE_ADDR] => 135.125.205.29



		exit;

	}




	function save()
	{
  	$user	= JFactory::getUser();
    $aid = max ($user->getAuthorisedViewLevels());
    
	  if ($aid < 1) {			
       $LoginUrl = 'index.php?option=com_comprofiler&view=login'
       . '&return='. base64_encode(JURI::getInstance()->toString());
       $this->setRedirect($LoginUrl);
       $this->redirect();
		 return;
		}  
		// Check for request forgeries
		//JSession::checkToken() or jexit( 'Invalid Token' );

		//get data from the request
    $input = JFactory::getApplication()->input;		
		//$post = $input->post->get('jform', '', 'ARRAY');
		
		$model = $this->getModel('mailing');
    

      
  }
    
	function cancel()
	{		
    $this->setRedirect(JRoute::_('index.php'));         
	}

    
	function annuler()
	{		
  	$user   =  JFactory::getUser();
		if (!$user->id) {
      $uri = JFactory::getURI(); 
      $return = $uri->toString(); 
			$app->redirect('index.php?option=com_comprofiler&view=login&return='. urlencode(base64_encode($return)), 
        JText::_('Connexion nécessaire pour gérer son adhésion') ); 
		}      
    
      $model = $this->getModel('readhesion');
      $model->annuler();
			  $msg = 'Opération annulée';
        $type = 'message';
        $this->setRedirect(JRoute::_('index.php?option=com_inscriptions&view=readhesion', false), $msg, $type);
	}


}

?>
