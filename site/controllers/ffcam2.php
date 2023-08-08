<?php 

/**
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.controller');

/**
 
 */
class InscriptionsControllerFfcam2 extends JControllerAdmin
{

	/**
	* Importe l'ensemble des données FFCAM 
	* via l'api Soap et le convertit pour
  * intégration dans la table ffcam1
  *
	*/       
	function importe()
	{
    
    if(InscriptionsHelper::checkCron() === false 
          and InscriptionsHelper::checkVPS() === false) {
      
      $cron = false;
      InscriptionsHelper::checkAdmin();
      
    } else {
      $cron = true;
    }

    
    $model = $this->getModel('ffcam2');
    $model->importFFCAM(); 


    //$model->log = array("Test");

    if($cron === false) {

      $view =  $this->getView('ffcam2', 'html' );
      $view->setLayout('import');
      $view->assignRef('log', $model->log);
      $view->display(); 

    } else {
/*
      require_once(JPATH_ROOT.'/includes/myjmail/myjmail.php');
      $mail = new MyJmail();
      $mail->setSubject("GUMS Paris - Interface FFCAM ");
      $mail->ClearAddresses();
      $mail->addRecipient("dns@gumsparis.asso.fr");

      $msg = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
              <style>
              * {	font-family: "Courier New", Courier, monospace; font-size: 12px;}
              </style>
              </head><body>'.implode("<br>", $model->log).'</body>';

      $mail->MsgHTML($msg);
      $mail->send();

*/  
      echo '<pre>'; print_r($model->log); echo '</pre>'; 
      echo 'Traitement API terminé';
      exit;

    }
      
  }

	function importecsv()
	{
    
    InscriptionsHelper::checkAdmin();
    

    $input = JFactory::getApplication()->input;
    $f = $input->files->get('file');

    if ($f != NULL) {

      $log = array();

      $db = JFactory::getDbo();
      $champs = array(
        "user_id",
        "cb_nocaf",
        "firstname",
        "lastname",
        "cb_dateinscription",
        "cb_adhesion",
        "cb_licenceffcam",
        "cb_assuranceffcam",
        "cb_cheque",
        "cb_procffcam",
        "cb_crampon",
        "cb_stage",
        "cb_abolma"
      );
      
      $fp = fopen($f["tmp_name"], "r");

      while (($d = fgetcsv($fp, 1000, "\t")) !== FALSE) 
      {
        
        if(substr($d[0], 0 ,2) == "75") {

          $ad = new stdClass();
          $ad->no = $d[0];
          $ad->nom = $d[2];
          $ad->ffcam = (float) str_replace(",", ".", $d[5]);
          $ad->cr = (float) str_replace(",", ".", $d[7]);
          $ad->assurance = (float) str_replace(",", ".", $d[8]); 
          $ad->adhesion = (float) str_replace(",", ".", $d[9]); 
          $ad->supplement = (float) str_replace(",", ".", $d[10]); 
          $ad->lma = (float) str_replace(",", ".", $d[11]);
          $ad->crampon = (float) str_replace(",", ".", $d[12]);

          if($ad->supplement==82.30) {
            $ad->stage = 82.30;
          } else {
            $ad->stage = 0;
          }

          if($ad->supplement==13) {
            if($ad->crampon==12) {
              $ad->crampon = 25;
            } else {
              $ad->crampon = 99;            }
          } 
      
          if($ad->no == "750420229023" or $ad->no == "750420239020") {
            continue;
          }



          $query = $db->getQuery(true);    
          $query = "select ".implode(",", $champs)." from #__users as a
            left join #__comprofiler as b 
            on b.user_id = a.id 
            where cb_nocaf  = '".$ad->no."' ";
            //echo '<pre>'; var_dump($query); echo '</pre>'; exit;
          $db->setQuery($query);
          $gums = $db->loadObject();    

          $update = array();

          if($gums === NULL) {
            $log[] = '<br>!!!! '.$ad->no.' - '.$ad->nom.' non présent dans la base GUMS';
            continue;
          }

          if($gums->cb_adhesion == 0) {
            $log[] = '<br>**** '.$ad->no.' - '.$gums->user_id.' - '.$ad->nom.' adhésion pas à jour dans la base GUMS';
            continue;
          }

          $ecart = false;

          if ( (float) $gums->cb_stage <> $ad->stage ) {
            $log[] = '<br>-- '.$ad->no.' - '.$gums->user_id.' - '.$ad->nom.' stage FFCAM = ' .$ad->stage. ' / stage GUMS = ' . $gums->cb_stage;
          }

          if ( (float) $gums->cb_crampon <> $ad->crampon and $gums->cb_crampon == 0) {
            $ecart = true;
            if($ad->crampon==99) {
              $log[] = '<br>-- '.$ad->no.' - '.$gums->user_id.' - '.$ad->nom.' Crampon FFCAM = ' .$ad->crampon. ' / crampon GUMS = ' . $gums->cb_stage;
            } else {
              $update[] = "cb_crampon = ". $ad->crampon;
            }
          }

          if ( (float) $gums->cb_abolma <> $ad->lma) {
            $ecart = true;
            $update[] = "cb_abolma = ". $ad->lma;
          }


          if (count($update) > 0) {

            $query = $db->getQuery(true);    
            $query = "update #__comprofiler 
              set ".implode(",", $update)." 
              where user_id  = ".$gums->user_id;
              //echo '<pre>'; var_dump($query); echo '</pre>'; exit;
            $db->setQuery($query);
            $db->execute();    
            $log[] = '<br>'.$ad->no.' - '.$gums->user_id.' - '.$ad->nom.' Mise à jour = ' . implode(",", $update);

          }

        }

      }

    }

    
    $view =  $this->getView('ffcam2', 'html' );
    $view->setLayout('importcsv');
    $view->assignRef('log', $log);
    $view->display(); 


  }


}

?>
