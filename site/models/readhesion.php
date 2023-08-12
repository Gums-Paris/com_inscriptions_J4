<?php
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;


class InscriptionsModelReadhesion extends JModelAdmin
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
			try
			{
				$db = $this->getDbo();
				$query = $db->getQuery(true)
					->select('*');
				$query->from('#__comprofiler AS a');
        
				$query->select('b.email')
					->join('LEFT', '#__users AS b on b.id = a.user_id');

				$query->where('a.user_id = ' . $this->userid);
				$db->setQuery($query);

				$data = $db->loadObject();

				if (empty($data))
				{
          Factory::getApplication()->enqueueMessage('Utilisateur non trouvé', 'error');  
				}
				$this->item = $data;
                        
			}
			catch (Exception $e)
			{
//        echo '<pre>';print_r($db);echo '</pre>';exit;

        Factory::getApplication()->enqueueMessage( $e->getMessage(), 'error');  
				$this->item = false;
			}
		}

		return $this->item;
	}
  
  public function getAnPrec($periode = 0)
	{
    
    $app = Factory::getApplication();
 		$id = $app->input->getInt('id', 0);
		$user	= Factory::getUser();
    $this->userid = $user->id;    
    
 		if ($this->userid>0)
		{
			try
  	{
      
        // Année archive
        $an = date("Y");
        if (time() < mktime(0, 0, 0, 9, 1, $an)) {
        // Si on est avant le 1er septembre
          $archive = $an-1-$periode;
        } else {
          $archive = $an-$periode;
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
          return $data;
          // Factory::getApplication()->enqueueMessage('Utilisateur non trouvé', 'error');  
				}
                
        $data->periode = $periode;                
				$this->AnPrec = $data;                        
                        
			}
			catch (Exception $e)
			{
        Factory::getApplication()->enqueueMessage( $e->getMessage(), 'error');  
				$this->item = false;
			}
		}


    if ($data->cb_adhesion==0 and $periode==0) {
      return $this->getAnPrec(1); 
    } else {
      return $this->AnPrec;
    }


	}

	public function getFormulaire(){    
    
    if (! isset($this->item)) {
      $this->getItem();
    }
  	$db = $this->getDbo();

    // Détermine si jeune ou adulte pour la cotisation Gums                
    if (time() < mktime(0, 0, 0, 9, 1, date("Y"))) {
    // Si on est avant le 1er septembre
      $limite_age = (date("Y")-24).'-01-01';
      $this->saison = (date("Y")-1).'-'.date("Y");
    } else {
      $limite_age = (date("Y")-23).'-01-01';
      $this->saison = (date("Y")).'-'.(date("Y")+1);
    }        
    if ($this->item->cb_datenaissance >= $limite_age ) {
      $this->jeune = 1;  
    } else {
      $this->jeune = 0;
    }


    // Récupère la valeur des cotisations dans les champs community builder
    // (données à mettre à jour avec la fonction ad-hoc)
    //
		$query = $db->getQuery(true);
		$query->select('fieldtitle, fieldlabel')
		      ->from('#__comprofiler_field_values')
            ->where('fieldid = 66');
		$db->setQuery($query);        
		$cotisations = $db->loadObjectList();

    $options = array();
    
      foreach ($cotisations as $c) {
        if (strpos($c->fieldlabel, "Adulte")!==false) {
          $cotisation_adulte = $c->fieldtitle;
          if ($this->jeune) { $disabled = 'disabled '; $checked='';} else { $disabled = ''; $checked='checked ';}
        } elseif (strpos($c->fieldlabel, "Jeune")!==false) {
          $cotisation_jeune = $c->fieldtitle;
          if ($this->jeune) { $disabled = ''; $checked='checked ';} else { $disabled = 'disabled ';  $checked='';}
        } else {
          echo 'Erreur cotisation - contactez l\'administrateur';
          exit; 
        }
        $options[] = array ( $c->fieldlabel. ' €', $c->fieldtitle, $disabled, $checked);
      }

    $html[] = $this->radio('Cotisation GUMS','cb_adhesion', $options);

    // Récupère la valeur de l'assurance dans les champs community builder
    // (données à mettre à jour avec la fonction ad-hoc)
    //
		$query = $db->getQuery(true);
		$query->select('fieldtitle')
		      ->from('#__comprofiler_field_values')
            ->where('fieldid = 72');
		$db->setQuery($query);        
		$assurance = $db->loadResult();
    $options = array();
    $options[] = array ( $assurance.' €', $assurance, '', 'checked ');
    $html[] = $this->radio('Assurance de personne (obligatoire)','cb_assurancegums', $options);


    // Récupère la valeur de l'abonnement Crampon dans les champs community builder
    // (données à mettre à jour avec la fonction ad-hoc)
    //
		$query = $db->getQuery(true);
		$query->select('fieldtitle, fieldlabel')
		      ->from('#__comprofiler_field_values')
            ->where('fieldid = 67');
		$db->setQuery($query);        
		$cotisations = $db->loadObjectList();

    $options = array();
    
      foreach ($cotisations as $c) {
        if (strpos($c->fieldlabel, "Normal")!==false) {
          $cotisation_adulte = $c->fieldtitle;
          $checked='checked ';
        } else {
          $checked='';
        }
        $options[] = array ( $c->fieldlabel. ' €', $c->fieldtitle, '', $checked);
      }
      $options[] = array ( 'Pas d\'abonnement', 0, '', '');

    $html[] = $this->radio('Abonnement à la revue Crampon (recommandé)','cb_crampon', $options);


    // Licenciés FFCAM dans autre club
    //		
    $nocaf = $this->item->cb_nocaf;
    $html[] = '<fieldset style="padding-left: 30px;padding-right: 30px; width: 350px;">' 
         .'<label for="jform_cb_nocaf">N° de licence FFCAM <b>valide et à jour</b></label>'
         .'<input type="text" id="jform_cb_nocaf" name="jform[cb_nocaf]" value="'.$nocaf.'">' 
         .'</fieldset>';
              

    $options = array();
    $options[] = array ( ' 0 € - Non souscrite', 0, '', 'checked ');
    $html[] = $this->radio('Assurance de personne','cb_assurancegums', $options);
  
 
               
		return $html;
	}

  protected function radio($titre, $name, $options, $hidden = false) {
    $i = 0;
    $html = '<fieldset id="jform_'.$name.'" class=""
               style="padding-left: 30px;padding-right: 30px; width: 350px;">';
    $html .= '<label for="jform_' . $name .'"><b>'.$titre.'</b></label>';
    $onchange = 'onChange="cale_montant() "';
    $onclick = '';
                   
    foreach ($options as $option) {  
     $html .= '<label for="jform_' . $name . $i . '" >'.'<input type="radio" id="jform_' . $name . $i . '" name="jform[' . $name . ']" value="'
				. htmlspecialchars($option[1], ENT_COMPAT, 'UTF-8') . '" ' . $onclick
				. $onchange . $option[2] . $option[3] . ' style="margin-top: 5px;" />'
        . $option[0].'</label>';
    }
    $html .= '</fieldset>';    
     
    return $html;
  }

  protected function radio1($titre, $name, $options, $hidden = false) {
    $i = 0;
    $html = '<fieldset id="jform_'.$name.'" class="radio btn-group btn-group-yesno"
               style="padding-left: 30px;padding-right: 30px; width: 350px;">';
    $html .= '<label for="jform_' . $name .'">'.$titre.'</label>';
    $onchange = 'onChange="cale_montant() "';
    $onclick = '';
                   
    foreach ($options as $option) {  
     $html .= 	'<input type="radio" id="jform_' . $name . $i . '" name="jform[' . $name . ']" value="'
				. htmlspecialchars($option[1], ENT_COMPAT, 'UTF-8') . '" ' . $onclick
				. $onchange . $option[2] . $option[3] . ' style="margin-top: 5px;" />'
        . '<label for="jform_' . $name . $i . '">'.$option[0].'</label>';
    }
    $html .= '</fieldset>';    
    
    return $html;
  }

	public function getForm3($data = array(), $loadData = true){
		// Get the form.       
		$form = $this->loadForm('com_inscriptions.readhesion', 'readhesion', 
            array('control' => 'jform', 'load_data' => $loadData));    
            
		if(empty($form)){
			return false;
		}

		return $form;

	}

	protected function loadFormData(){
  	$data = $this->getItem();            
		return $data;
	}

  
	public function getForm($data = array(), $loadData = true){  
   return true;  
  }

/*
------------------------------------------------------------------

*/
  function store($data)
	{

    $data["lastupdatedate"] = date("Y-m-d H:i:s");

    $row = $this->getTable();    

//    $datalog = $data;
//    $rowlog = $this->_loadData();

		// Bind the form fields to the table
		if (!$row->bind($data)) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		// Store to the database
		if (!$row->store()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

   $this->id = $row->id;
//   $this->log($datalog, $rowlog, $row->id);

		return true;
	}

/*
------------------------------------------------------------------

*/
  function getSaison()
	{
    if (time() < mktime(0, 0, 0, 8, 1, date("Y"))) {
    // Si on est avant le 1er septembre
      $saison = (date("Y")-1).'-'.date("Y");
    } else {
      $saison = (date("Y")).'-'.(date("Y")+1);
    }
    return $saison;
   }   

/*
------------------------------------------------------------------

*/
  function annuler()
	{

  	$user   =  Factory::getUser();
		if ($user->id>0) {

				$db = $this->getDbo();
				$query = $db->getQuery(true);
        $query->select('TIMESTAMPDIFF(MINUTE, lastupdatedate,now())')
				->from('#__comprofiler')
        ->where('user_id = ' . $user->id);
        $db->setQuery($query);
        $maj = $db->loadResult();
        if ($maj<600) {
          $q = "update #__comprofiler set cb_adhesion=0, cb_assurancegums=0, cb_crampon=0, lastupdatedate=now() where user_id=". $user->id;
	  			$db->setQuery($q);
          $x = $db->execute();          
        } else {
          echo 'temps dépassé '.$maj.'<br>'.date("Y-m-d H:i:s"); exit;
        }                

     }
   } 

  
}
