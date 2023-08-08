<?php
defined('_JEXEC') or die;

class InscriptionsModelStatut extends JModelItem
{
	public function getItem($userid = 0)
	{
	    $app = JFactory::getApplication();
 		$id  = $app->input->getInt('id', 0);
 		$api = $app->input->getInt('api', 0);
		$user	= JFactory::getUser();
    


		
		include("/home/gumspari/ffcam/apiffcam.php");
		$result = $client->extractionAdherents($cnxFfcam, "7504"); 




		return $this->item;
	}
}
