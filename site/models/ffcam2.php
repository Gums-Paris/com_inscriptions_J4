<?php
defined('_JEXEC') or die;
use \Joomla\CMS\Factory;

include(__DIR__ ."/adherentffcam.php");

//

class InscriptionsModelFfcam2 extends JModelAdmin
{


  private $tarif = array();    // Prix licence  et assurance en fonction du code 
                               // $tarif["T1"]["licence"] //  $tarif["T1"]["assurance"]
  private $adhesion = array(); // Tarif d'adhésion GUMS  adulte / jeune
  private $crampon = array(); // Tarif d'abonnement Crampon  normal / soutien
  private $date_jeune;         // Date de naissance limite pour un jeune
  public $date_debut_saison;

  private $compteur        = 0;         //
  private $compteur_maj    = 0;         //
  private $compteur_ajouts = 0;

  private $apiFFCAM = array();

  public $adherents_gums = array();
  public $adherents_ffcam = array();

  public $log = array();

  

  public $champs_infos = array(
    "cb_adresse",
    "cb_adresse2",
    "cb_codepostal",
    "cb_ville",
    "cb_pays",
    "email",
    "cb_mobile",
    "cb_telfixe",
    "cb_contactaccident",
    "cb_section",
  );

  public $champs_base = array(
    "firstname",
    "lastname",
    "cb_sexe",
    "cb_datenaissance"
  );

  public $champs_adhesion = array(
    "cb_dateinscription",
    "cb_adhesion",
    "cb_licenceffcam",
    "cb_assuranceffcam",
    "cb_cheque",
    "cb_procffcam",
    "cb_crampon",
    "cb_stage"    
  );

  public $champs_autres = array(
    "user_id",
    "cb_nocaf"
  );


  //-----------------------------------------------------------------------------
  // Fonction principale de l'interface 
  //
  //
  function importFFCAM() {

    $app = Factory::getApplication();
		$cache = $app->input->getInt('cache', 0);
    if ($cache == 0) {
      unset($_SESSION["apiFFCAM"]);
    }
    
    $this->log[] = "Début de traitement " . date("Y-h-d H:i:s");


    if(count($this->apiFFCAM)==0) {

      if(isset($_SESSION["apiFFCAM"]) and count($_SESSION["apiFFCAM"])>0) {
        
        $this->apiFFCAM = $_SESSION["apiFFCAM"];
        $this->log[] = 'Recup données session apiFFCAM '.count($this->apiFFCAM).' adhérents';

      } else {

        $this->log[] =  'Données session apiFFCAM vide - lancement API';
        if ($this->getApiFFCAM()===false) {
          return false;
          $this->log[] =  'Echec API';
        }
      }
    }


    // Récupération des infos nécessaires au rapprochement
    //
    $this->getDataGums();
    $this->get_date_debut_saison();
    $this->getTarifs();


    foreach($this->apiFFCAM as $r) {


      // Exclusion du traitement pour Brigitte Lesourd et Céline Koehler
      if ( in_array($r->id, array("750420169027","750420220005"))) { continue; }
      

      if($r->date_inscription != '0000-00-00') {
        $date_inscription = new DateTime($r->date_inscription);
      } else {
        $date_inscription = new DateTime("1970-01-01");          
      }

      // Licencié avec date_inscription renseignée = a pris sa licence
      //

      if($date_inscription >= $this->date_debut_saison) {          

        $this->compteur++;

        $ad = new AdherentFfcam($r, $this->tarif, $this->adhesion, $this->crampon, $this->date_jeune);       


        // Est qu'on trouve le n° de licence dans la base Gums ?
        //
        $test = array_key_exists($ad->cb_nocaf, $this->adherents_gums);

        // si non => on lance une recherche avec nom et date de naissance
        if($test === false)  {

          $rech = $this->recherche($ad);          

          if ($rech === false) {

            // Adhérent non trouvé => on crée un nouveau


            if (trim($ad->email) == "") {

              $this->log[] = "Attention : nouvel adhérent  ".$ad->firstname." ".$ad->lastname. " => Pas d'adresse mail" ;
              $ad->email = "ad." . strtolower(preg_replace("`'/[^a-zA-Z\-]/m'`", "", $ad->firstname)) 
                    . "." . strtolower(preg_replace("`'/[^a-zA-Z\-]/m'`", "", $ad->lastname))
                    . "@gumsparis.asso.fr";

              $this->alerte(1, $ad, NULL, NULL);

            } 

            $this->log[] = "Création adherent ".$ad->firstname." ".$ad->lastname;
            $this->nouvelAdherent($ad);                        

            
          } elseif ($rech != "proche") {

            // Adhérent trouvé => on valide test pour lancer le rapprochement

            $test = true;

          }
    
        } 

        if($test) {
          $this->rapproche($ad);
        }        

      }


    }

    $this->log[] = count($this->apiFFCAM) . " enregistrements dans base FFCAM dont " . $this->compteur . " à jour de licence";
    $this->log[] = $this->compteur_ajouts . " nouvelles adhésions / ".$this->compteur_maj." adhérents mis à jour / "
      . ($this->compteur - $this->compteur_ajouts - $this->compteur_maj) . " non traités ";

    $this->logRegister();

  }





  //-----------------------------------------------------------------------------
  //
  function get_date_debut_saison() {

    // Détermine la date à laquelle démarre la saison
    // = 1/9 de l'année en cours si on est après le 31/8
    //   sinon 1/9 de l'année précédente

    $date = new DateTime();
    if ( (int) $date->format("m") > 8) {
      $this->date_debut_saison = new DateTime($date->format("Y")."-09-01");
    } else {
      $this->date_debut_saison = new DateTime( ($date->format("Y")-1)."-09-01");
    }    

  }


  //-----------------------------------------------------------------------------
  // A finaliser
  // Nettoyage base adhérent Gums en vue du rapprochement
  //
  function checkDataGums() {


    // Check doublon nocaf

    $query = "SELECT a.id, a.firstname, a.lastname, b.id, b.firstname, b.lastname 
     FROM `j3x_comprofiler` as a 
     left join `j3x_comprofiler` as b on a.cb_nocaf = b.cb_nocaf and a.id <> b.id 
     WHERE a.cb_nocaf <> '' and b.cb_nocaf is not null ";

    // Check date naissance non vide

    // Check prénom

    $query = "update `j3x_comprofiler` 
      set firstname = replace(trim(firstname), ' ', '-') 
      WHERE trim(`firstname`) REGEXP '.* .*' and (cb_remarques<>'xxx' or cb_remarques is null)";

  }

  //-----------------------------------------------------------------------------
  // On crée un tableau adherents_gums avec n° de licence en clé 
  // pour rapprochement rapide
  // 
  function getDataGums() {

    $db = $this->getDbo();

    $this->champs = array_merge($this->champs_autres, $this->champs_base, $this->champs_adhesion, $this->champs_infos );    

    $query = $db->getQuery(true);    
    $query = "select ".implode(",", $this->champs)." from #__users as a
      left join #__comprofiler as b 
      on b.user_id = a.id 
      where cb_nocaf like '7504%' 
      order by cb_nocaf";
    $db->setQuery($query);
    $this->adherents_gums = $db->loadObjectList("cb_nocaf");    

  }

  //-----------------------------------------------------------------------------
  // Récupère les infos d'un adhérent communes avec les infos ffcam 
  // 
  // 
  function getDataAdherent($id) {

    $db = $this->getDbo();

    $this->champs = array_merge($this->champs_autres, $this->champs_base, $this->champs_adhesion, $this->champs_infos );    

    $query = $db->getQuery(true);    
    $query = "select ".implode(",", $this->champs).",  lastupdatedate  from #__users as a
      left join #__comprofiler as b 
      on b.user_id = a.id 
      where a.id = " . (int) $id;
    $db->setQuery($query);
    $mb = $db->loadObject();
    
    if($mb === null) {
      return "Adherent " .$id." non trouvé";
    }

    // Récupère données API

    if((int) $mb->cb_nocaf > 0) {

      try {
        include("/home/gumspari/ffcam/apiffcam.php");
        $r = $client->extractionAdherent($cnxFfcam, $mb->cb_nocaf);     
      } catch (Exception $e) {
        return false;
      }

      if((int) $r->id  > 0 ) {

        //echo '<pre>'; print_r($r); echo '</pre>'; exit;

        $this->get_date_debut_saison();
        $this->getTarifs();

        $ad = new AdherentFfcam($r, $this->tarif, $this->adhesion, $this->crampon, $this->date_jeune);

        $this->adherents_gums[$ad->cb_nocaf] = $mb;

        $result = $this->rapproche($ad);

      } else {

        $ad     = null;
        $result = null;
  
      }


    } else {

      $ad     = null;
      $result = null;

    }
    
    return array($mb, $ad, $result, $r);    

  }



  //-----------------------------------------------------------------------------
  // Traite le rapprochement d'un adhérent trouvé dans la base Gums
  // 
  function rapproche($ad) { 

    $db = $this->getDbo();
    $user	= Factory::getUser();

    $erreur = false;
    $erreurs = array();
    $champs_ok = array();

    // Données de l'adhérent dans la base Gums
    $mb = $this->adherents_gums[$ad->cb_nocaf];

    // Contrôle des données de base 
    //
  
    foreach($this->champs_base as $c) {

      if($c=="firstname" or $c=="lastname" ) { 
        $mb->$c = InscriptionsHelper::retire_accents($mb->$c);
        $ad->$c = InscriptionsHelper::retire_accents($ad->$c);
      }

      if($c=="firstname" and strlen(trim($mb->$c))>14) {    
        $mb->$c = substr(trim($mb->$c), 0, 14);                 
      }


      if(trim($ad->$c) <> trim($mb->$c)) {
        $erreur = true;
        $erreurs[] = $c;
      } else {
        $champs_ok[] = $c;
      } 

    }

    if($erreur) {

      $this->log[] = "Attention erreur sur données de base pour " 
      .$mb->user_id. " " .$ad->firstname. " " . $ad->lastname;
      $this->alerte(1, $ad, $mb, $c);

    }

    // Contrôle des données adhésion
    //
    //    

    // Ajout abonnement crampon suite modif API 10.2022
    if($ad->cb_crampon > 0 and (int) $mb->cb_crampon == 0) {
      $query = $db->getQuery(true);    
      $query = "update #__comprofiler set cb_crampon = " . $ad->cb_crampon 
        . " where user_id = " . $mb->user_id;
      $db->setQuery($query);
 
      $db->execute();
    }

    // Renouvellement adhésion
    //
    if ( (float) $ad->cb_licenceffcam > 0 and (float) $mb->cb_licenceffcam == 0) {

      $data = array();

      foreach($this->champs_adhesion as $c) {
        $data[] = $db->quoteName($c) . ' = ' . $db->quote($ad->$c);
      }

      $query = $db->getQuery(true);    
      $query = "update #__comprofiler set " . implode(",", $data) 
        . " where user_id = " . $mb->user_id;
      $db->setQuery($query);
      $db->execute();
  
      $this->log[] = "Mise à jour adhésion pour " . $mb->user_id ." - ".$mb->firstname ." ". $mb->lastname;

      $this->compteur_maj++;

    // Enregistré comme licencié dans base Gums mais pas dans base FFCAM
    //
    } elseif ( (float) $ad->cb_licenceffcam == 0 and (float) $mb->cb_licenceffcam > 0) {

      $this->alerte(2, $ad, $mb, array("cb_licenceffcam"));

    }
     
    // Ecarts dans les informations facultatives 
    //

    // On regarde chaque champ pour vérifier les écarts
    // écarts => tableau $ecarts
    // pas d'écarts => tableau $champs_ok

    foreach($this->champs_infos as $c) {

      if($c == "cb_adresse" or $c == "cb_adresse2" or $c == "cb_ville") {
        $mb->$c = InscriptionsHelper::retire_accents($mb->$c);
      }

      if($ad->$c != $mb->$c) {
        $erreur = true;
        $erreurs[] = $c;
      } else {
        $champs_ok[] = $c;
      }
    }
/*
    if($mb->user_id==1529) {

      echo '<pre>';
      print_r($ad);
      print_r($mb);
      print_r($erreurs);
      print_r($champs_ok);
      exit;

    }
*/


    // On regarde dans la table ffcam_ecarts si l'écart existe déjà
    // si non, on crée l'enregistrement
    // si oui, on le met à jour le cas échéant

    $query = $db->getQuery(true);    
    $query = "select * from #__ffcam_ecarts where user_id = " . $mb->user_id;
    $db->setQuery($query);
    $ecarts = $db->loadObjectList("champ");

    if($erreur) {      

      foreach($erreurs as $c) {
        $data = array();

        if(isset($ecarts[$c])) {

          if($ad->$c != $ecarts[$c]->ffcam or 
              $mb->$c != $ecarts[$c]->gums) {

            // si changement dans les données FFCAM ou dans la base Gums, on met à jour la ligne écart

            $query = $db->getQuery(true);    
            $query = "update #__ffcam_ecarts set ffcam = ".$db->quote($ad->$c).", 
              gums = " . $db->quote($mb->$c) . ", informe = 0, valide = 0, maj = now(), 
              user_modif = " . $user->id . "
              where user_id = ".$mb->user_id." and champ = " . $db->quote($c);
            $db->setQuery($query);
            $db->execute();

            $this->log[] = "Modification d'écarts de données ".$c." pour " . $mb->user_id ." - ".$mb->firstname ." ". $mb->lastname;
                      
          }


        } else {
          
          // La ligne n'existe pas encore, on la crée

          $query = $db->getQuery(true);    
          $query = "insert into #__ffcam_ecarts 
            values (null, ".$mb->user_id.", ".$db->quote($c).", ".$db->quote($ad->$c)
            .", ".$db->quote($mb->$c).",  0,  0, now(), " . $user->id . ")";
          $db->setQuery($query);
          $db->execute();

          $this->log[] = "Création d'écarts de données ".$c." pour " . $mb->user_id ." - ".$mb->firstname ." ". $mb->lastname;
    
        }

      }

    }

    if(count($champs_ok)>0) {

    // Si le champ ne comporte pas d'écart on regarde dans la table ffcam_ecarts 
    // si un écart avait été enregistré 
    // si oui, on le supprime

      foreach($champs_ok as $c) {

        if(isset($ecarts[$c])) {
          $query = $db->getQuery(true);    
          $query = "delete from #__ffcam_ecarts 
          where user_id = ".$mb->user_id." and champ = " . $db->quote($c);
          $db->setQuery($query);
          $db->execute();

          $this->log[] = "Données ".$c." rectifiée pour " . $mb->user_id ." - ".$mb->firstname ." ". $mb->lastname;

        }

      }

    }     

    return array($erreurs, $champs_ok);

  }

  //-----------------------------------------------------------------------------
  // Alerte erreur fichier
  //   
  function alerte($type, $ad, $mb, $champs) {




  }


  // Recherche adhérent par nom / prenom / date naissance
  //   
  function recherche($ad) {

    $db = $this->getDbo();
    
    $query = $db->getQuery(true);    
    $query = "select a.id, firstname, lastname, cb_datenaissance, cb_nocaf, cb_licenceffcam  
      from #__users as a
      left join #__comprofiler as b on b.user_id = a.id 
      where cb_datenaissance = " . $db->quote($ad->cb_datenaissance)
      . " and lastname = ". $db->quote($ad->lastname)
      . " and firstname = ". $db->quote($ad->firstname);
    $db->setQuery($query);
    $recherche = $db->loadObjectList();

    if(count($recherche)==0) {

      $query = "select a.id, firstname, lastname, cb_datenaissance, cb_nocaf, cb_licenceffcam  
      from #__users as a
      left join #__comprofiler as b on b.user_id = a.id 
      where cb_datenaissance = " . $db->quote($ad->cb_datenaissance)
      . " and ( lastname = ". $db->quote($ad->lastname)
      . " or firstname = ". $db->quote($ad->firstname) . ")";
      $db->setQuery($query);
      $recherche2 = $db->loadObjectList();

      if(count($recherche2)==0) {

        $this->log[] = "Licencié " . $ad->cb_nocaf . " - ".$ad->firstname ." ". $ad->lastname 
        . " => pas trouvé d'adhérent correspondant exactemement";  
        return false;

      } else {

        $this->log[] = "Licencié " . $ad->cb_nocaf . " - ".$ad->firstname ." ". $ad->lastname 
        . " => trouvé adhérent proche : " . $recherche2[0]->id . " " . $recherche2[0]->firstname 
        . " " . $recherche2[0]->lastname;
        return "proche";

      } 
    

    } else {
 
      $mb = $recherche[0];

      $this->log[] = "Licencié " . $ad->cb_nocaf . " - ".$ad->firstname ." ". $ad->lastname 
      . " => trouvé adhérent correspondant exactemement " . $mb->user_id . " - ancien n° licence ". $mb->cb_nocaf ;

      // Check que l'adhérent trouvé n'est pas déjà licencié pour la saison en cours
      if((float) $mb->cb_licenceffcam >0 and $mb->cb_cheque == "Web") {

        echo 'Erreur = adhérent trouvé déjà licencié sous le numéro '. $mb->cb_nocaf; exit;
        return false;

      }

      // On met à jour le champ cb_nocaf de l'adhérent trouvé 

      $query = $db->getQuery(true);    
      $query = "update #__comprofiler set cb_nocaf = " . $db->quote($ad->cb_nocaf) 
        . "where id = " . $mb->id;
      $db->setQuery($query);
      $x = $db->execute();

      $this->log[] = "Adhérent " . $mb->user_id ." - ".$mb->firstname ." ". $mb->lastname 
      . " => mise à jour n° de licence " . $ad->cb_nocaf ;

      // Si l'adhérent trouvé avait déjà une licence Gums, on corrige simplement le 
      // tableau $this->adherents_gums en intervertissant la clé avec le nouveau 
      // numéro de licence

      if ( substr($mb->cb_nocaf, 0, 4) == "7504" 
            and isset($this->adherents_gums[$mb->cb_nocaf]) ) {        

        $this->adherents_gums[$ad->cb_nocaf] = $this->adherents_gums[$mb->cb_nocaf];
        unset($this->adherents_gums[$mb->cb_nocaf]);

      } else {

        // Autres cas => on reconstitue le tableau $this->adherents_gums avec les données à jour
        $this->getDataGums();

      }

    
    }

  }

  //-----------------------------------------------------------------------------
  // Création d'un nouvel adhérent
  //
  //
  public function nouvelAdherent($ad) {
   
		$db = $this->getDbo();
      $query = $db->getQuery(true);    

    // Check doublon mail
    
    $query  = "select id, name from #__users 
      where ".$db->quoteName("email") . " = " .$db->quote($ad->email);
    $db->setQuery($query);
    $emails = $db->loadObject();
    if($emails !== null) {
      $this->log[] = "Adresse mail " . $ad->email . " existe déjà 
          pour ". $emails->id . " " . $emails->name . " - Adhérent non créé ";
      return false; 
    }


    // Check cas douteux 

    $check_datenaissance  = $db->quoteName("cb_datenaissance") . " = " . $db->quote("cb_datenaissance");
    $check_firstname      = $db->quoteName("firstname") . " = " . $db->quote("firstname");
    $check_lastname       = $db->quoteName("lastname") . " = " . $db->quote("lastname");
    
    $query  = "select id, firstname, lastname, cb_datenaissance from #__comprofiler 
      where ( 
         (" . $check_firstname . " and " . $check_lastname . " ) 
      or ( " . $check_firstname . " and " . $check_datenaissance . " ) 
      or ( " . $check_lastname  . " and " . $check_datenaissance . " ) 
       ) ";

    $db->setQuery($query);
    $douteux = $db->loadObjectList();

    if(count($douteux) > 0) {
      
      $this->log[] = "Licencié " . $ad->cb_nocaf . " - " . $ad->firstname . " " . $ad->lastname 
      . " - Adhérents avec données proches trouvés : ";

      foreach($douteux as $d ) {

        $this->log[] = $d->id . " - ". $d->firstname . " " . $d->lasstname;

      }

      return false; 
    
    }

    // OK pour création nouvel adhérent GUMS
    //
    
    // Mot de passe
    jimport('joomla.user.helper');
    $password     = JUserHelper::genRandomPassword();
    $salt         = JUserHelper::genRandomPassword(32);
    $crypt        = JUserHelper::getCryptedPassword($password, $salt);
    $hashedpwd    = $crypt.':'.$salt;
    
    // Login
    $username     = $this->genereLogin($ad->firstname);

    $registerDate = date("Y-m-d H:i:s");
    $name         = $ad->firstname." ".$ad->lastname;
    $query        = "insert into #__users (name,username,email,password,registerDate) ";
    $query       .= "values ('".$name."','".$username."','".$ad->email."','".$hashedpwd."','".$registerDate."')";

    $db->setQuery($query);
    if (! $db->execute()) {
      $this->log[] = "Echec insertion dans table users de " . $name;
      return false;
    } else {
      $id = $db->insertid();
      $this->log[] = "Insertion de ". $name ." dans user => id = ".$id;    
    }
    
    $query = "INSERT INTO #__user_usergroup_map (user_id, group_id) VALUES (".$id.", 2 )";
    $db->setQuery($query);
    if (! $db->execute()) {
      $this->log[] = "<br>Echec insertion dans table usergroup_map de " . $name;
      return false;
    } else {
      $this->log[] = "Insertion de ". $name ."  dans usergroup_map";    
    }

    $ad->id = $id;
    $ad->user_id = $id;
    $ad->approved = 1;
    $ad->confirmed = 1;
    $ad->cb_anneeentree = date("Y");

    foreach ($ad as $field => $value) {

      if(! in_array($field, array("email"))) {

        $fields[] = $db->quoteName($field);
        $values[] = $db->quote($value);

      }

    }

    $fields2 = "( ".implode(", ", $fields) . " )";
    $values2 = "( ".implode(", ", $values). " )";
    
    $query = "INSERT INTO `#__comprofiler` " . $fields2 . " VALUES " . $values2;
    $db->setQuery($query);
    if (! $db->execute()) {
      $this->log[] = "Echec insertion dans table comprofiler de " . $name;
      return false;
    }

    $this->log[] =  "Insertion dans comprofiler de " . $name;
    return true;
  
  }


  public function genereLogin($prenom) {
       
    $prenom = strtolower(InscriptionsHelper::retire_accents($prenom));

		$db = $this->getDbo();
      $query = $db->getQuery(true);    		
    $db->setQuery("select username from #__users where username regexp('^".$db->escape($prenom)."[0-9]{0,2}$')");
    $rows = $db->loadColumn();  
    
    if (count($rows) > 0) {
      $index = str_replace($prenom, "", $rows);
      $index_new = max($index) + 1;              
      $prenom = $prenom.$index_new;
    }
    
    return $prenom;
  
  }






  // Lance requête Soap sur API FFCAM 
  // pour récupérer la liste des adhérents avec les infos
  //
  function getApiFFCAM() {

    $user	= Factory::getUser();  
    if($user->guest == 0) {
      $par = $user->name . ' (' . $user->id . ')';
    } else {
      $par = "Cron";
    }
    $this->log[] = "Import du ".date("d-m-Y H:i:s")." ".$par;
    
    try {
    
      // Récupère identifiants de connexion
      //
      include("/home/gumspari/ffcam/apiffcam.php");

      $result = $client->extractionAdherents($cnxFfcam, "7504"); 
      $this->apiFFCAM = $result->collection;    
      //$rows = array($client->extractionAdherent($connexion, "750420189029") );       
      //echo '<pre>'; print_r($rows); echo '</pre>'; exit;

      $this->log[] = "Extraction SOAP : " . count($this->apiFFCAM) . " adhérents extraits";

      //echo '<pre>'; print_r($rows); echo '</pre>'; exit;      
      
      $_SESSION["apiFFCAM"] = $this->apiFFCAM;
    
      return true;
    
    } catch (Exception $e) {

      $this->log[] =  "L'erreur ".$e->getMessage()." s'est produite pendant 
          l'interrogation du webservice, la vérification n'a pas aboutit";  

      $this->apiFFCAM = array();      
      $_SESSION["apiFFCAM"] = $this->apiFFCAM;

      return false;

    }

    
      
  }
    


  function logRegister() {

    $this->log[] = "Fin de traitement " . date("Y-m-d H:i:s");
    $this->log[] = " ";

    $logfile = JPATH_COMPONENT . "/log/log".date("Ymd").".log";

    file_put_contents($logfile, implode("\n", $this->log), FILE_APPEND);

  }



/*

[firstname] => Yves
    [lastname] => DELARUE
    [email] => y.delarue@wanadoo.fr
    [cb_datenaissance] => 1940-08-30

stdClass Object
(
    [collection] => Array
        (
            [0] => stdClass Object
                (
                    [id] => 750420050002
                    [idclub] => 7504
                    [adherent] => 20050002
                    [cle_publique] => 08
                    [categorie] => T1
                    [chef_famille] => 
                    [date_naissance] => 1970-11-06
                    [date_inscription] => 2020-09-10
                    [qualite] => M
                    [nom] => AIGOUY
                    [prenom] => LIONEL
                    [adresse1] => 
                    [adresse2] => 
                    [adresse3] => 43 RUE HOUDAN
                    [adresse4] => SCEAUX
                    [codepostal] => 92330
                    [ville] => SCEAUX
                    [inscrit_par_internet] => 1
                    [assurance_ap] => 1
                    [date_assurance_ap] => 2020-09-10
                    [assurance_monde] => 0
                    [date_assurance_monde] => 
                    [assurance_paralpinisme] => 0
                    [date_assurance_paralpinisme] => 
                    [assurance_acr] => 0
                    [date_assurance_acr] => 
                    [accident_qui] => Bertrand Aigouy
                    [accident_tel] => 01 60 10 22 20
                    [tel] => 
                    [portable] => 06 32 66 03 17
                    [email] => lionel.aigouy@espci.fr
                    [date_radiation] => 
                    [motif_radiation] => 
                    [diplomes] => Array
                        (
                            [0] => stdClass Object
                                (
                                    [diplome] => BRV-BFSM40
                                    [libelle] => INITIATEUR SKI DE RANDONNEE
                                    [obtention] => 2017-04-02
                                )

                            [1] => stdClass Object
                                (
                                    [diplome] => BRV-BFEA10
                                    [libelle] => INITIATEUR Escalade SAE FFCAM
                                    [obtention] => 2017-03-12
                                )

                            [2] => stdClass Object
                                (
                                    [diplome] => BRV-QFSN10
                                    [libelle] => Qualification ski alpinisme
                                    [obtention] => 2018-03-26
                                )

                        )

                    [fonctions] => Array
                        (
                            [0] => stdClass Object
                                (
                                    [code] => ASKIMONT
                                    [style] => activite
                                    [libelle] => Responsable Ski de montagne
                                    [libelle_libre] => 
                                )

                            [1] => stdClass Object
                                (
                                    [code] => CESCALADE
                                    [style] => activite
                                    [libelle] => Cadre Escalade
                                    [libelle_libre] => 
                                )

                            [2] => stdClass Object
                                (
                                    [code] => MCD
                                    [style] => election
                                    [libelle] => Membres CD
                                    [libelle_libre] => 
                                )

                            [3] => stdClass Object
                                (
                                    [code] => GEPI
                                    [style] => gestion
                                    [libelle] => Responsable EPI
                                    [libelle_libre] => 
                                )

                            [4] => stdClass Object
                                (
                                    [code] => CSKIMONT
                                    [style] => activite
                                    [libelle] => Cadre Ski de montagne
                                    [libelle_libre] => 
                                )

                        )

                    [activites_club] => Array
                        (
                            [0] => stdClass Object
                                (
                                    [code] => ABO
                                    [date_inscription] => 2020-09-10
                                    [montant] => 11.00
                                )

                        )

                    [activites_pratiquees] => Array
                        (
                            [0] => stdClass Object
                                (
                                    [activite] => 10
                                    [description] => SKI DE RANDONNEE
                                )

                            [1] => stdClass Object
                                (
                                    [activite] => 6
                                    [description] => RANDONNEE
                                )

                            [2] => stdClass Object
                                (
                                    [activite] => 4
                                    [description] => ESCALADE
                                )

                            [3] => stdClass Object
                                (
                                    [activite] => 1
                                    [description] => ALPINISME
                                )

                        )

                )


*/

  




  //  Pour contrôle certificat médical  
  //
  function certificat($cb_nocaf) {
    $db = $this->getDbo();
    $qry = $db->getQuery(true);    
   
    $qry = "select cb_certifmedical, cb_certificat_file, cb_certificat_date from #__comprofiler               
            where cb_nocaf='".(float) $cb_nocaf."'";   
    $db->setQuery($qry);
    $r = $db->loadObject();        
    return $r;
  }


  
  // Récupère les libellés des champs comprofiler
  //  
  //  
  function getTitreChamps() {
 		$db = $this->getDbo();
      $query = $db->getQuery(true);        
    // 
    //
    $query = "SELECT * from j3x_comprofiler_fields";
    $db->setQuery($query);
    $rows = $db->loadObjectList();
    
    // Recherche des titres dans le fichier de langue française
    $x = str_replace("\\", "/", JPATH_BASE)."/components/com_comprofiler/plugin/language/fr-fr/language.php";
    define("CBLIB", true);
    $libs = include($x);               
    $titres = array();
    foreach($rows as $r) {
      if (isset($libs[$r->title])) {
        $titres[$r->name] = $libs[$r->title];
      } else {
        $titres[$r->name] = $r->title;
      }
    }
    
    return $titres;    
  }

  
  // Récupère les tarifs applicables dans les champs de community builder
  //  
  //  
  function getTarifs() {

    $db = $this->getDbo();
    $query = $db->getQuery(true);       
    // Recherche du tarif de licence
    //
    $query = "SELECT fieldtitle, left(fieldlabel,2) as cat  FROM `j3x_comprofiler_field_values` as v 
              left join j3x_comprofiler_fields as f on f.fieldid=v.fieldid 
              WHERE f.name = 'cb_licenceffcam'";
    $db->setQuery($query);
    $rows = $db->loadObjectList();
    foreach($rows as $r) {
      $this->tarif[$r->cat]["licence"] = $r->fieldtitle;    
    }
    
    // Recherche du tarif d'assurance
    //
    $query = "SELECT fieldtitle, fieldlabel  FROM `j3x_comprofiler_field_values` as v 
              left join j3x_comprofiler_fields as f on f.fieldid=v.fieldid 
              WHERE f.name = 'cb_assuranceffcam'";
    $db->setQuery($query);
    $rows = $db->loadObjectList();
    foreach($rows as $r) {
      foreach($this->tarif as $k => $e) {
        if(strpos($r->fieldlabel, $k) !== false) {
          $this->tarif[$k]["assurance"] = $r->fieldtitle; 
        }      
      }        
    }
    
    // Recherche du tarif d'adhésion GUMS
    //    
    $query = "SELECT fieldtitle, fieldlabel  FROM `j3x_comprofiler_field_values` as v 
              left join j3x_comprofiler_fields as f on f.fieldid=v.fieldid 
              WHERE f.name = 'cb_adhesion'";
    $db->setQuery($query);
    $rows = $db->loadObjectList();
    foreach($rows as $r) {
      if(stripos($r->fieldlabel, "Jeune") !== false) {
          $this->adhesion["jeune"] =  $r->fieldtitle; 
      } else {
          $this->adhesion["adulte"] =  $r->fieldtitle;
      }      
    }

    // Recherche du tarif Crampon
    //    
    $query = "SELECT fieldtitle, fieldlabel  FROM `j3x_comprofiler_field_values` as v 
              left join j3x_comprofiler_fields as f on f.fieldid=v.fieldid 
              WHERE f.name = 'cb_crampon'";
    $db->setQuery($query);
    $rows = $db->loadObjectList();
    foreach($rows as $r) {
      if(stripos($r->fieldlabel, "Normal") !== false) {
          $this->crampon["normal"] =  $r->fieldtitle; 
      } else {
          $this->crampon["soutien"] =  $r->fieldtitle;
      }      
    }

    if(date("m")>8) {
      $this->date_jeune = (date("Y")-23) ."-01-01";
    } else {
      $this->date_jeune = (date("Y")-24) ."-01-01";
    }     
  
  }
  



	public function getForm($data = array(), $loadData = true){  
   return true;  
  }




}

