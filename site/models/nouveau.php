<?php
defined('_JEXEC') or die;

class InscriptionsModelNouveau extends JModelAdmin
{


  public function nouvelAdherent($prenom, $nom, $email, $dateInscription = NULL, $cb_nocaf = "") {

    $msg = "";
    
		$db = $this->getDbo();
    if (is_null($dateInscription)) {
      $dateInscription = date("Y-m-d");
    }
    
    // Mot de passe
    jimport('joomla.user.helper');
    $password     = JUserHelper::genRandomPassword();
    $salt         = JUserHelper::genRandomPassword(32);
    $crypt        = JUserHelper::getCryptedPassword($password, $salt);
    $hashedpwd    = $crypt.':'.$salt;
    
    // Login
    $username     = $this->genereLogin($prenom);

    $registerDate = date("Y-m-d H:i:s");
    $name         = $prenom." ".$nom;
    $query        = "insert into #__users (name,username,email,password,registerDate) ";
    $query       .= "values ('".$name."','".$username."','".$email."','".$hashedpwd."','".$registerDate."')";
    echo $query.':'.$password.'<br>';
    $db->setQuery($query);
    if (! $db->execute()) {
      $msg .= "<br>Echec insertion dans table users";
      return array(false, $msg);
    } else {
      $id = $db->insertid();
      $msg .= "<br>Insertion dans user => id = ".$id;    
    }
    
    $query = "INSERT INTO #__user_usergroup_map (user_id, group_id) VALUES (".$id.", 2 )";
    $db->setQuery($query);
    if (! $db->execute()) {
      $msg .= "<br>Echec insertion dans table usergroup_map";
      return array(false, $msg);
    } else {
      $msg .= "<br>Insertion dans usergroup_map";    
    }
    
    $query = "INSERT INTO `#__comprofiler` ( `id`, `user_id`, `firstname`, `lastname`, `approved`, `confirmed`,
      `cb_dateinscription`, `cb_anneeentree`, `cb_nocaf`) VALUES
   ('".$id."', '".$id."', '".$prenom."', '".$nom."', '1', '1', '".$dateInscription."', year(now()), '".$cb_nocaf."' )";

    $db->setQuery($query);
    if (! $db->execute()) {
      $msg .= "<br>Echec insertion dans table comprofiler";
      return array(false, $msg);
    }
    $msg .= "<br>Insertion dans comprofiler";            
    return array(true, $msg, $id);
  
  }

  // Avant de créer un nouvel enregistrement dans comprofiler 
  // on vérifie que l'adhérent n'existe pas déjà (sans no ffcam ou 
  // avec un ancien no ffcam)

  public function chercheDoublon($prenom, $nom) {

		$db = $this->getDbo();
    $query1 = "select #__users.id, firstname, lastname, cb_datenaissance FROM #__users 
        LEFT JOIN #__comprofiler ON #__users.id = user_id";
    $query  = $query1." WHERE lastname LIKE '".$db->escape($nom)
     ."' and firstname LIKE '".$db->escape($prenom)."'";
    $db->setQuery($query);
    $rows = $db->loadObjectList();

    return $rows;  
  
  } 


  // Cherche les adhérents avec le même prénom et ajoute un index si besoin
  //
  public function genereLogin($prenom) {
    $prenom  = $this->strToNoAccent(strtolower($prenom));

		$db = $this->getDbo();
    $db->setQuery("select username from #__users where username regexp('^".$db->escape($prenom)."[0-9]{0,2}$')");
    $rows = $db->loadColumn();    
    if (count($rows) > 0) {
      $index = str_replace($prenom, "", $rows);
      $index_new = max($index) + 1;              
      $prenom = $prenom.$index_new;
    }
    
    return $prenom;
  
  }

  public function strToNoAccent($var)
  {
    $var = str_replace(array('à', 'â', 'ä', 'á', 'ã', 'å', 'î', 'ï', 'ì', 'í', 'ô', 'ö', 'ò', 'ó', 'õ', 'ø', 'ù', 'û', 'ü', 'ú', 'é', 'è', 'ê', 'ë', 'ç', 'ÿ', 'ñ', 'À', 'Â', 'Ä', 'Á', 'Ã', 'Å', 'Î', 'Ï', 'Ì', 'Í', 'Ô', 'Ö', 'Ò', 'Ó', 'Õ', 'Ø', 'Ù', 'Û', 'Ü', 'Ú', 'É', 'È', 'Ê', 'Ë', 'Ç', 'Ÿ', 'Ñ',), 
                       array('a', 'a', 'a', 'a', 'a', 'a', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'e', 'e', 'e', 'e', 'c', 'y', 'n', 'A', 'A', 'A', 'A', 'A', 'A', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'e', 'E', 'E', 'E', 'C', 'Y', 'N',), $var);
    return $var;
  }




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


	public function getForm($data = array(), $loadData = true)
	{

		// Get the form.
		$form = $this->loadForm('com_inscriptions.nouveau', 'nouveau', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

 	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_inscriptions.nouveau.nouveau.data', array());
		return $data;
	}
  
  
}
