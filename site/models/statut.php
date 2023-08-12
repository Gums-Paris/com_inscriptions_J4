<?php
defined('_JEXEC') or die;
use \Joomla\CMS\Factory;

class InscriptionsModelStatut extends JModelItem
{
	public function getItem($userid = 0)
	{
	    $app = Factory::getApplication();
 		$id  = $app->input->getInt('id', 0);
 		$api = $app->input->getInt('api', 0);
		$user	= Factory::getUser();
    


		
		include("/home/gumspari/ffcam/apiffcam.php");
		$result = $client->extractionAdherents($cnxFfcam, "7504"); 




		return $this->item;
	}
}
