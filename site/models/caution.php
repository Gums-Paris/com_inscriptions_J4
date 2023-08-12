<?php
defined('_JEXEC') or die;
use \Joomla\CMS\Factory;

class InscriptionsModelCaution extends JModelAdmin
{

	public function getItem($userid = 0)
	{

		$app = Factory::getApplication();
		$id = $app->input->getInt('id', 0);
		$user	= Factory::getUser();
		
		if ($userid==0) {
			$this->userid = $user->id;
		} elseif ( in_array($user->groups[0], array(6, 7, 8)) ) {
			$this->userid = $userid;
		} else {
		$this->item = null;
		}
    
 		if ($this->userid>0)
		{
			try	{
				$db = $this->getDbo();
				$query = $db->getQuery(true);
				$champs = array( 
					'b.id', 'b.name', 'b.email', 
					'a.cb_caution', 'a.cb_caution_echeance',
					'c.date', 'vads_amount', 'vads_card_number', 'vads_presentation_date',
					'vads_expiry_month', 'vads_expiry_year'
				  );
				  
				$query->select($db->quoteName($champs));
				$query->from('#__comprofiler AS a');        
				$query->join('LEFT', '#__users AS b on b.id = a.user_id');
				$query->join('LEFT', 'payzen_log AS c on a.cb_caution = c.id');
				$query->where('a.user_id = ' . $this->userid);
				$db->setQuery($query);
					
				$data = $db->loadObject();				

				if (empty($data))
				{
          			$app->enqueueMessage('Utilisateur non trouvé', 'error');  
				}
				$this->item = $data;
                        
			}
			catch (Exception $e)
			{
				echo '<pre>'; print_r($db->getErrorMsg()); echo '</pre>'; exit;		  				
//		        echo '<pre>';print_r($db);echo '</pre>';exit;
        		$app->enqueueMessage( $e->getMessage(), 'error');  
				$this->item = false;
			}
		}

		return $this->item;
	}
  
	protected function loadFormData(){
  		$data = $this->getItem();            
		return $data;
	}

  
	public function getForm($data = array(), $loadData = true){  
   		return true;  
  	}


/*
-----------------------------------------------------------------
*/

  
}
