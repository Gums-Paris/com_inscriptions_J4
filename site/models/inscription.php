<?php
defined('_JEXEC') or die;

class InscriptionsModelInscription extends JModelItem
{
	public function getItem($userid = 0)
	{
    
    $app = JFactory::getApplication();
 		$id = $app->input->getInt('id', 0);
		$user	= JFactory::getUser();
    if ($userid==0) {
      $this->userid = $user->id;
    } elseif ( in_array($user->groups[0], array(6, 7, 8)) ) {
        $this->userid = $userid;
    } else {
      $this->item = null;
    }
    
 		if ($this->userid>0)
		{
			try
			{
				$db = $this->getDbo();
				$query = $db->getQuery(true)
					->select('*');
				$query->from('#__comprofiler AS a');

				// Join on category table.
				$query->select('b.email')
					->join('LEFT', '#__users AS b on b.id = a.user_id');

				$query->where('a.user_id = ' . $this->userid);
				$db->setQuery($query);

				$data = $db->loadObject();				

				if (empty($data))
				{
          JFactory::getApplication()->enqueueMessage('Utilisateur non trouvé', 'error');  
				}
				$this->item = $data;
                        
			}
			catch (Exception $e)
			{
//        echo '<pre>';print_r($db);echo '</pre>';exit;

        JFactory::getApplication()->enqueueMessage( $e->getMessage(), 'error');  
				$this->item = false;
			}
		}

		return $this->item;
	}
  
  public function getAnPrec($userid = 0)
	{
    
    $app = JFactory::getApplication();
 		$id = $app->input->getInt('id', 0);
		$user	= JFactory::getUser();
    if ($userid==0) {
      $this->userid = $user->id;
    } elseif ( in_array($user->groups[0], array(6, 7, 8)) ) {
        $this->userid = $userid;
    } else {
     JFactory::getApplication()->enqueueMessage('Droits insuffisants', 'error');  
    }
    
 		if ($this->userid>0)
		{
			try
			{
      
        // Année archive
        $an = date("Y");
        if (time() < mktime(0, 0, 0, 9, 1, $an)) {
        // Si on est avant le 1er septembre
          $archive = $an-1;
        } else {
          $archive = $an;
        }
      
				$db = $this->getDbo();
				$query = $db->getQuery(true);
				$query->select('*')
				      ->from('#__comprofiler'.$archive.' AS a')
              ->where('a.user_id = ' . $this->userid);
				$db->setQuery($query);

				$data = $db->loadObject();

				if (empty($data))
				{
          JFactory::getApplication()->enqueueMessage('Utilisateur non trouvé', 'error');  
				}
				$this->AnPrec = $data;
                        
			}
			catch (Exception $e)
			{
        JFactory::getApplication()->enqueueMessage( $e->getMessage(), 'error');  
				$this->item = false;
			}
		}

		return $this->AnPrec;
	}
  
  
}
