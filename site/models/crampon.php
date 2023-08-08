<?php
defined('_JEXEC') or die;

class InscriptionsModelCrampon extends JModelAdmin
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
  
  public function getAnPrec($periode = 0)
	{
    
    $app = JFactory::getApplication();
 		$id = $app->input->getInt('id', 0);
		$user	= JFactory::getUser();
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
          JFactory::getApplication()->enqueueMessage('Utilisateur non trouvé', 'error');  
				}
                
        $data->periode = $periode;                
				$this->AnPrec = $data;                        
                        
			}
			catch (Exception $e)
			{
        JFactory::getApplication()->enqueueMessage( $e->getMessage(), 'error');  
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
      $limite_age = (date("Y")-22).'-01-01';
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
      //$options[] = array ( 'Pas d\'abonnement', 0, '', '');

    $html[] = $this->radio('Abonnement à la revue Crampon','cb_crampon', $options);   
               
		return $html;
	}

  protected function radio($titre, $name, $options, $hidden = false) {
    $i = 0;
    $html = '<fieldset id="jform_'.$name.'" class=""
               style="padding-left: 30px;padding-right: 30px; width: 350px;">';
    $html .= '<label for="jform_' . $name .'"><b>'.$titre.'</b></label>';
    $onchange = 'onChange="cale_montant() "';
                   
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
		$form = $this->loadForm('com_inscriptions.crampon', 'crampon', 
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

  
}
