<?php
defined('_JEXEC') or die;

class InscriptionsModelCertificats extends JModelAdmin
{

	public function getData()
	{

    $app = JFactory::getApplication();
 		$id = $app->input->getInt('id', 0);
    
    $liste = $app->input->getInt('liste', 0);

        
		try
		{
			$db = $this->getDbo();
			$query = $db->getQuery(true)
				->select(array('a.id','name', 'email',  
          'cb_adhesion', 'cb_licenceffcam', 'cb_dateinscription',
          'cb_certificat_file', 'cb_certificat_date', 'cb_certifmedical'));
			$query->from('#__comprofiler AS a');
      
			$query->join('LEFT', '#__users AS b on b.id = a.user_id');
      
      if ($id<>0) {
      
        if ($id==-1) {

  			  $query->where("a.cb_certificat_file <> ''"); // and cb_adhesion>0
    			$db->setQuery($query);      
      		$data = $db->loadObjectList();      
          if (count($data)>0) {
            $a_valider = array();
            foreach($data as $r) {          
              $a_valider[] = $r->id;        
            }
            $app->setUserState("certificats.a_valider", $a_valider);      
          }            

        
        } else {
      
  			  $query->where('a.user_id = ' . $id);
    			$db->setQuery($query);      
      		$data = $db->loadObject();      
    			if (empty($data))
    			{
            JFactory::getApplication()->enqueueMessage('Utilisateur non trouvé', 'error');  
  		  	}

        }

      } else {
      
        
        if ($liste==0) {
          $query->where("a.cb_certificat_file <> '' and (a.cb_certifmedical<>'1' or a.cb_certifmedical is NULL)"); //  and cb_adhesion>0 
        } else {
         $query->where("a.cb_certificat_file <> '' and cb_adhesion>0 "); //
         $app->setUserState("certificats.liste", 1);                 
        }         
  			$db->setQuery($query);      
    		$data = $db->loadObjectList();      

        if (count($data)>0) {
          $a_valider = array();
          foreach($data as $r) {          
            $a_valider[] = $r->id;        
          }
          $app->setUserState("certificats.a_valider", $a_valider);      
        }            

    }            
	      
			$this->list = $data;
                      
		}
		catch (Exception $e)
		{
      JFactory::getApplication()->enqueueMessage( $e->getMessage(), 'error');  
			$this->list = false;
		}

		return $this->list;
	}
  
  
  
  public function save($post) {
  
    $app = JFactory::getApplication();
		$db = $this->getDbo();

    $post->cb_certificat_date = JFactory::getDate($post->cb_certificat_date)->toSql(); 
  
    $query = "UPDATE #__comprofiler 
      set cb_certificat_date = '".$post->cb_certificat_date."',
          cb_certifmedical = '1' 
      where id = ". (int) $post->id;
		$db->setQuery($query);      
    
    return $db->execute();
  
  }
  
	public function getForm($data = array(), $loadData = true){  
   return true;  
  }
  

	public function getSansCertif()
	{

    $app = JFactory::getApplication();
        
		try
		{
			$db = $this->getDbo();
			$query = $db->getQuery(true)
				->select(array('a.id','name', 'email',  
          'cb_adhesion', 'cb_licenceffcam', 'cb_dateinscription',
          'cb_certificat_file', 'cb_certificat_date', 'cb_certifmedical'));
			$query->from('#__comprofiler AS a');
      
			$query->join('LEFT', '#__users AS b on b.id = a.user_id');
      $query->order('cb_dateinscription ASC');
      
        $query->where("cb_licenceffcam>0 and (a.cb_certifmedical<>'1' or a.cb_certifmedical is NULL)"); //          
  			$db->setQuery($query);      
    		$data = $db->loadObjectList();      
                            
	      
			$this->list = $data;
                      
		}
		catch (Exception $e)
		{
      JFactory::getApplication()->enqueueMessage( $e->getMessage(), 'error');  
			$this->list = false;
		}

		return $this->list;
	}
  
 







}
