<?php 
jimport( 'joomla.application.component.view');

class InscriptionsViewFfcam2 extends JViewLegacy
{
	function display($tpl = null)
	{		    

      $input = JFactory::getApplication()->input;		

      $layout = $this->getLayout();      

  
      if($layout != "import" and $layout != "importcsv" ) {
         
         $user   =  JFactory::getUser();
      
         /* Récupération de l'id utilisateur. 
            on reroute sur l'identification si pas logué
         */   
         InscriptionsHelper::checkUser();
   
         $model = $this->getModel();                                   
         
         $input = JFactory::getApplication()->input;		
         $id = (int) $input->get('id');

         if($id > 0 and InscriptionsHelper::checkAdmin()) {
            $this->data = $model->getDataAdherent($id);
         } else {
            $this->data = $model->getDataAdherent($user->id);
         }

      } 


		parent::display();
      //$this->display($tpl);
      
	}
  
}
?>
                                  