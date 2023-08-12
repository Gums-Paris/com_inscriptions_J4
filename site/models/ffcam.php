<?php
defined('_JEXEC') or die;
use \Joomla\CMS\Factory;

/*
Nouveau process =

1. On recherche les lignes de ffcam2 avec n°ffcam non présents dans ffcam et on les transfèrent dans ffcam3
replace into j3x_ffcam3 select a.* from j3x_ffcam2 as a left join j3x_ffcam as b on a.no_adherent_complet = b.no_adherent_complet where b.no_adherent_complet is null 
delete a from j3x_ffcam2 as a left join j3x_ffcam as b on a.no_adherent_complet = b.no_adherent_complet where b.no_adherent_complet is null 
2. On met en tableau ffcam et ffcam2 avec comme clé le n°CAF

select * from j3x_ffcam as a left join j3x_ffcam2 as b on a.no_adherent_complet = b.no_adherent_complet where b.no_adherent_complet is null 

3. Pour chaque ligne de ffcam
  a. si pas inscription à jour => on passe au suivant
  b. sinon :
    i. si date_inscription <> de ffcam2 => n° dans tableau renouvellement
    ii. si n°caf pas présent dans ffcam2 => n° dans tableau nouvelles
    iii. si un des champs différent => n° dans tableau mise_a_jour
    iiii. sinon => on passe au suivant

4. Pour chaque n° de tableau nouvelles :
  a. recherche assistée d'un éventuel adhérent Gums existant :
    i. n°CAF
    ii. sinon par prenom-nom-date de naissance  
        (voir fonction dans requete mysql pour transformer prenom-nom 
        en minuscules+suppression espaces, tirets, apostrophes)
    iii. sinon prenom-nom
    iiii. sinon date de naissance
  b. si trouvé on change n°Caf dans base adhérent et on transfère de nouvelles vers renouvellement
  c. sinon on cherche adresse mail dans base Gums
    Si adresse existante on crée une redirection ad.nom.prenom@gumsparis.fr vers l'adresse en doublon
  
5. Pour chaque renouvellement 







*/



class InscriptionsModelFfcam extends JModelAdmin
{


  private $tarif = array();    // Prix licence  et assurance en fonction du code 
                               // $tarif["T1"]["licence"] //  $tarif["T1"]["assurance"]
  private $adhesion = array(); // Tarif d'adhésion GUMS  adulte / jeune
  private $date_jeune;         // Date de naissance limite pour un jeune
  private $chemin_ffcam;
  private $fichier;            // Nom et chemin du fichier FFCAM
  private $fichier_date;            // Date du fichier FFCAM



  function audit() {

    $query = "SELECT a.prenom, a.nom, c.firstname, c.lastname, no_adherent_complet, c.cb_nocaf 
        FROM `j3x_ffcam` as a 
        left join j3x_comprofiler as b on no_adherent_complet=cb_nocaf 
        left join j3x_comprofiler as c on a.date_naissance=c.cb_datenaissance 
        WHERE a.`date_inscription` != '0000-00-00' and b.cb_nocaf is null";
    $db->setQuery($query);
    $rows = $db->loadObjectList('no_adherent_complet');
    echo '<pre>'; print_r($rows); echo '</pre>';


  }



  // Données à afficher sur l'écran principal FFCAM
  // lance l'importation des données (si ça n'a pas déjà été fait)

  function statut() {
    
    $this->chemin_ffcam = "/home/gumspari/ffcam/";
          
    $fichier   = $this->chemin_ffcam . "7504.txt";
    
    if (file_exists($fichier)) {
      $this->fichier = $fichier;
      $this->fichier_date = filemtime($fichier);
      $txt = "Date du dernier fichier FFCAM : " . date ("d-m-Y H:i", $this->fichier_date);
      $this->fichier = $fichier;

    } else {
      $txt = "Fichier d'interface FFCAM non trouvé ".$fichier;
    }

    $fichier2   = $this->chemin_ffcam . "activite_7504.txt";
    if (file_exists($fichier2)) {
      $this->fichier2 = $fichier2;
      $this->fichier2_date = filemtime($fichier2);
      $txt .= "<br>Date du dernier fichier FFCAM activites : " . date ("d-m-Y H:i", $this->fichier2_date);
      $this->fichier2 = $fichier2;

    } else {
      $txt .= "<br>Fichier d'interface FFCAM activités non trouvé ".$fichier2;
    }

    $txt .= "<br>".$this->importe();  

    return $txt;
     
  }
  
  // Importe le fichier FFCAM dans la table #__ffcam
  //  
  //
  
  function importe() {

    
    $a_importer = true;
    $temoin_import = $this->chemin_ffcam."temoin_import.txt";

    if (file_exists($temoin_import)) {
      $dernier_import = (int) file_get_contents($temoin_import);
      if($this->fichier_date <= $dernier_import) {
        $a_importer = false;
      }
    }  
    
    //$a_importer = false;
    //$a_importer = true;
    
    if ($a_importer) {

      $txt = $this->load_data($this->fichier, "#__ffcam")
            . " enregistrements importés dans fichier principal";

       $txt .= "<br>".$this->load_data($this->fichier2, "#__ffcam_activites")
            . " enregistrements importés dans fichier principal";


       file_put_contents($temoin_import, $this->fichier_date);       


      /*
      $query = "LOAD DATA LOCAL INFILE '".$this->fichier."'  
      INTO TABLE `#__ffcam` CHARACTER SET latin1 FIELDS TERMINATED BY ';' 
       ESCAPED BY '\\\\' LINES TERMINATED BY '\\r\\n'"; //ENCLOSED BY '\"'
      $db->setQuery($query);
      $db->execute();
      $txt = "Fichier principal importé";
      
      $db->setQuery("TRUNCATE `#__ffcam_activites`");
      $db->execute();
      $query = "LOAD DATA LOCAL INFILE '".$this->fichier2."'  
      INTO TABLE `#__ffcam_activites` CHARACTER SET latin1 FIELDS TERMINATED BY ';' 
       ESCAPED BY '\\\\' LINES TERMINATED BY '\\r\\n'"; //ENCLOSED BY '\"'
      $db->setQuery($query);
      $db->execute();
      $txt .= "<br>Fichier activités importé";
      */


    } else {
    
      $txt = "Fichier déjà importé";      
    
    }

    return $txt;
  
  }

  // Emulation LOAD DATA INFILE
  //
  //
  function load_data($fichier, $table, $taille_values = 50 ) {

    $lignes = 0;

    $db = $this->getDbo();     
    $query = $db->getQuery(true);    
    $db->setQuery("TRUNCATE `".$table."`");
    $db->execute();

    $data = array();
    if (($handle = fopen($fichier, "r")) !== FALSE) {
      while (($row = fgetcsv($handle, 1000, ";")) !== FALSE) {
        $line = array();
        $num = count($row);                            

        for ($c=0; $c < $num; $c++) {
            if ($c==32 and mb_detect_encoding($row[$c], mb_detect_order(), true)=="UTF-8") {
              $line[] = $db->quote($row[$c]);
            } else {
              $line[] = $db->quote(utf8_encode ($row[$c]));
            }            
        }        
        $data[] = $line;
        //echo '<pre>'; print_r($line);echo '</pre>';exit; 
      }
      fclose($handle);
    }      


    $data2 = array_chunk ( $data, $taille_values);
    //$data2 = array($data);
    $qry = $db->getQuery(true);    

    foreach ($data2 as $d) {
      unset($x);
      $x = array();
      foreach($d as $v) {
        $x[] = "(".implode(",", $v).")";        
      }

      $qry = "INSERT IGNORE INTO  `".$table."` VALUES ". implode(",", $x);              
      $db->setQuery($qry);    
      $db->execute();

      if (count($x) <> $db->getAffectedRows()) {
        echo '<br>Pb = '.count($x).' lignes '.$db->getAffectedRows().' affectées'; 
        echo '<br>'.$qry;
      }

      $lignes +=  $db->getAffectedRows();
  
    }

    return $lignes;

  }



  // Compare les tables ffcam et ffcam2 pour détecter les nouveautés
  //
  //  
  function compare() {

    $txt = "";

    $a_modifier = array();
    $a_ajouter  = array();
    $supprimés  = array();

    $a_modifier2 = array();
    $a_ajouter2  = array();

    $erreurs_synchro  = array();

 		$db = $this->getDbo();
    $query = $db->getQuery(true);    

    $query = "select * from `#__ffcam`"; // left join #__comprofiler on no_adherent_complet=cb_nocaf
    $db->setQuery($query);
    $rows1 = $db->loadAssocList('no_adherent_complet');
        
    $query = "select * from `#__ffcam2`";
    $db->setQuery($query);
    $rows2 = $db->loadAssocList('no_adherent_complet');

    foreach($rows1 as $k => $r ) {
    
      // Adhérent existant dans base archivée
      // 
      if(isset($rows2[$k])) {
        $diff = array();
        $diff2 = array();
        
        foreach($r as $j => $v) {
          if ($v<>$rows2[$k][$j]) {
            $diff2[] = $j;
            $c = '';
            if ($j== 'date_inscription') {
              $c = ' - tarif '.$r['categorie']; 
            }
            if ($j== 'assurance_personne') {
              $c = ' - date '.$r['date_assurance_personne'];
            }
            if ($j<> 'date_assurance_personne' and $j<> 'no_adherent_complet') {        
              $diff[] = '<div class="l"><div class="e">'.$j.'</div><div class="v">'
                .str_replace('0000-00-00','', $rows2[$k][$j]).' => '.$v.$c.'</div></div>';          
            }                
          }
        }
        if (count($diff)>0) {        
          $txt .= '<div class="n">Adhérent '.$k. ' - '.$r["prenom"].' '.$r["nom"].'</div>';
          $txt .= implode('',$diff);
          if ($r["inscrit_web"]) {
            $r["modifs"] = $diff2;         
            $a_modifier[] = (string) $k;
            $a_modifier2[] = $rows1[$k]["prenom"].' '.$rows1[$k]["nom"].' - '.$rows1[$k]["no_adherent_complet"];
          }       
        }

        
      // Adhérent absent de la base achivée
      //  
      } else {



      
        $txt .= '<div class="n">Nouvel adhérent '.$k. ' - '.$r["prenom"].' '.$r["nom"].'</div>';
        foreach($r as $j => $v) {
          if ($v<>'' and $v<>'0' and $v<>'0000-00-00') {

            if ($j<> 'no_adherent_complet') {   
              $txt .= '<div class="l"><div class="e">'.$j .'</div><div class="v"> '.$v.'</div></div>';    
            }
                                    
          }
        }
        
        // Contrôle des doublons mail
        //                     
        if ((float) $r["no_adherent_complet"] == 0 ) {
          echo 'Problème numéro adherent à 0 <pre>'; print_r($r);echo '</pre>'; exit;
        }
        
        $doublon_mail = $this->chercheDoublonEmail($r["no_adherent_complet"],$r["email"]);                
        if ($doublon_mail !== NULL) {   
          
            $erreurs_synchro[] = $k. ' - '.$r["prenom"].' '.$r["nom"].
            ' => doublon avec '.$doublon_mail->id.' '.$doublon_mail->name;                    

            $this->maj_ffcam($k);
            $this->maj_ffcam_activites($k);

            //echo '<pre>'; var_dump($k); echo '</pre>'; 
            //echo '<pre>'; print_r($rows2); echo '</pre>'; exit;


        } else {                                          
          $a_ajouter[] = (string) $k;      
          $a_ajouter2[] = $rows1[$k]["prenom"].' '.$rows1[$k]["nom"].' - '.$rows1[$k]["no_adherent_complet"];        
        }
                        
        
      }
    }
    $txt .= '<br><br><br>';
    
    foreach($rows2 as $k => $r ) {
      if(!isset($rows1[$k])) {
        $txt .= '<div class="n">Adhérent supprimé '.$k. ' - '.$r["prenom"].' '.$r["nom"].'</div>';
        $a_retirer[] = (string) $k;      
      }
    }

    $activites = $this->compare_activites();
    if (count($activites)>0) {
      foreach($activites as $a) {
        if ( ! in_array($a, $a_modifier) and ! in_array($a, $a_ajouter)) {
          $a_modifier[] = $a;
        }    
      }    
    }

    $app =Factory::getApplication();
    $app->setUserState( "ffcam.a_modifier", $a_modifier);
    $app->setUserState( "ffcam.a_ajouter", $a_ajouter );
    $app->setUserState( "ffcam.a_retirer", $a_retirer );

    $app->setUserState( "ffcam.a_modifier2", $a_modifier2);
    $app->setUserState( "ffcam.a_ajouter2", $a_ajouter2 );
    
    $app->setUserState( "ffcam.erreurs_synchro", $erreurs_synchro );


    //echo '<pre>';print_r($erreurs_synchro);echo '</pre>';exit;

    //echo $txt;

    return array(count($a_modifier), count($a_ajouter), count($a_retirer), count($erreurs_synchro));
  
  }
  

  // Compare les tables ffcam et ffcam2 pour détecter les nouveautés
  //
  //  
  function compare_activites() {

 		$db = $this->getDbo();
	$query = $db->getQuery(true);
    $activites = array();

    $query = "select *, concat(no_adherent_complet, code_activite) as cle from `#__ffcam_activites`"; 
    $db->setQuery($query);
    $rows1 = $db->loadObjectList('cle');
        
    $query = "select *, concat(no_adherent_complet, code_activite) as cle from `#__ffcam_activites2`"; 
    $db->setQuery($query);
    $rows2 = $db->loadObjectList('cle');

    foreach($rows1 as $k => $r) {
    
      if (isset($rows2[$k])) {
        if ($rows2[$k]->date_inscription<>$rows1[$k]->date_inscription or 
         $rows2[$k]->montant<>$rows1[$k]->montant ) {         
          $activites[] = $r->no_adherent_complet;         
        }
      } else {
        $activites[] = $r->no_adherent_complet; 
      }  
    }
        
    return $activites;
    
  }


  function compare_tout($ctrl_adhesion = false) {
  
 		$db = $this->getDbo();
    $app =Factory::getApplication();
	$query = $db->getQuery(true);

    $query = "select a.* from `#__ffcam` as a 
      left join #__comprofiler on no_adherent_complet=cb_nocaf 
      where cb_nocaf is not null and date_inscription<>'0000-00-00'"; 
    $db->setQuery($query);
    $a_modifier = $db->loadColumn();
    $liste = $db->loadObjectList();

    if ($ctrl_adhesion) {
      $a_modifier2 = array();
      $liste2 = array();
      foreach($a_modifier as $k => $a) {
        $r = $this->edite($a);
        $ctrl = $this->rapproche($r, true);
        if (count($ctrl[1])>0) {
          $a_modifier2[] = $a;
          $liste2[] = $liste[$k];
        }        
      }
      $a_modifier = $a_modifier2;
    }

    $app->setUserState( "ffcam.a_modifier", $a_modifier);
    
    return $liste2;
      
  }


  // Extrait un enregistrement de la table FFCAM
  // et le converti 
  //
   function edite($no_adherent) {

 		$db = $this->getDbo();
	$query = $db->getQuery(true);
                                             
    $query = "select * from `#__ffcam` where no_adherent_complet=". (float) $no_adherent;
    $db->setQuery($query);
    $r = $db->loadObject();   
    if ((float) $r->no_adherent_complet <> (float) $no_adherent ) {
      return false;
    } else {
      $r = $this->convert($r);            
      return $r;          
    }

   }

  // Conversion du fichier FFCAM
  // A partir d'une ligne de j3x_ffcam (objet $r) => ajoute des propriétés 
  // correspondant aux champs de j3x_comprofiler 
  // le tout formatté aux "normes GUMS" 
  //
  function convert($r) {
  
 		$db = $this->getDbo();
	$query = $db->getQuery(true);

    $this->getTarifs();
 
    $r->firstname = $this->prenom($r->prenom);   
    $r->lastname = mb_strtoupper($r->nom, 'UTF-8');
    if ($r->qualite=="M") {
      $r->cb_sexe = 'M';       
    } else {
      $r->cb_sexe = 'F'; 
    }
    $r->cb_adresse = $this->adresse($r->adresse_rue);
    $r->cb_adresse2 = trim($r->adresse_bat.' '.$r->adresse_complement);



    // Adhérents à l'étranger      
    $etranger = false;


    if (trim($r->adresse_localite)<>"") {

      // On récupère la liste des pays
      $pays = $this->pays();

      // Si ville correspond à un pays, c'est un résident étranger                 
      if (in_array(strtoupper($r->ville), $pays)) {
        $etranger = true;
      } elseif (trim($r->adresse_localite)<>trim($r->ville)) {
        // Complément d'adresse dans localité      
        if ($r->cb_adresse2<>"") {
          $r->cb_adresse2 .= " - ";
        } 
        $r->cb_adresse2 .= $r->adresse_localite;  
      }  

    }    
    
    if ($etranger) {
      $r->cb_ville = $this->ville($r->adresse_localite);
      $r->cb_pays  = $this->ville($r->ville); 
    } else {
      $r->cb_ville = $this->ville($r->ville);
      $r->cb_pays  = "France";
    }
    $r->cb_codepostal = $r->code_postal;            
    $r->cb_mobile = $this->telephone($r->tel_portable);
    $r->cb_telfixe = $this->telephone($r->tel_domicile);
    $r->cb_contactaccident = $r->contact_accident . ' ' .$this->telephone($r->tel_accident);
    $r->cb_datenaissance =  $r->date_naissance;
    $r->cb_dateinscription = $r->date_inscription;
    $r->cb_section = "Alpinisme-Ski-Escalade";


    if ($r->date_inscription<>"0000-00-00") {
      
      // Est-ce un jeune ou un adulte
      //
      if ($r->date_naissance>=$this->date_jeune) {
        $r->cb_adhesion = $this->adhesion["jeune"];        
      } else {
        $r->cb_adhesion = $this->adhesion["adulte"];
      } 

      $r->cb_licenceffcam = $this->tarif[$r->categorie]["licence"];

      if($r->assurance_personne) {
        $r->cb_assuranceffcam = $this->tarif[$r->categorie]["assurance"];
      } else {
        $r->cb_assuranceffcam = 0;
      }

      if ($r->inscrit_web == 1) {
        $r->cb_cheque ="Web";
        $r->cb_procffcam ="1";
      }

    }
    
    // Synchro avec ffcam_activites
    // pour l'abonnement au crampon
    //

    $r->cb_crampon = "";

    $query = "select * from `#__ffcam_activites` where no_adherent_complet=". $r->no_adherent_complet;
    $db->setQuery($query);
    $activites = $db->loadObjectList();
        
    if (count($activites)>0) { 
      foreach ($activites as $a) {
        if ($a->code_activite=="ABO+" or $a->code_activite=="ABO") {
          $r->cb_crampon = $a->montant;
        }
      }
    }        

    // Date de dernière modif de la fiche adhérent GUMS 
    //

    $query = "select lastupdatedate from #__comprofiler
            where cb_nocaf='".(float) $r->no_adherent_complet."'";   
    $db->setQuery($query);
    $r->lastupdatedate = $db->loadResult();  

    // Divers
    //

    $r->cb_cheque    = "";
    $r->cb_procffcam = "";

    return $r;

  }

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




  //  Compare la table comprofiler avec les données
  //  de la table ffcam
  //
  function rapproche($r, $ctrl_adhesion = false) {
  
 		$db = $this->getDbo();
 	$qry = $db->getQuery(true);
   
    $rapport = "";
    $ecarts = array();
    $id = null;
        
    $qry = "select  * from #__comprofiler as c  
            left join #__users as u on c.user_id=u.id
            where cb_nocaf='".(float) $r->no_adherent_complet."'";   
    $db->setQuery($qry);        
    $r2 = $db->loadObject();

    //echo '<pre>';print_r($r);echo '</pre>'; 
    //echo '<pre>';print_r($r2);echo '</pre>'; exit;

    if ($r2->cb_nocaf==$r->no_adherent_complet) {      

      // Si champ cb_sexe est à null on suppose que c'est un nouvel adhérent
      // et on coche tous les champs pour mise à jour
      // sinon on ne coche que pour les champs du tableau $champs1
      if ($r2->cb_sexe === NULL) {
        $nouveau = true;
      } else {
        $nouveau = false;
      }      

      $id = $r2->id;
    
      // Corrections pour rapprochement
      if ($r2->cb_assuranceffcam=="") {$r2->cb_assuranceffcam=0;} 
      if ($r->cb_assuranceffcam=="")  {$r->cb_assuranceffcam=0;}

      // Champs liés à l'adhésion
      //
      $champs1 = array("cb_adhesion", "cb_licenceffcam", 
          "cb_datenaissance", "cb_assuranceffcam", "cb_dateinscription");
      if ($r->cb_crampon<>"") {
        $champs1[] = "cb_crampon";
      }
      if ($r->cb_cheque<>"") {
        $champs1[] = "cb_cheque";
      }
      if ($r->cb_procffcam == 1) {
        $champs1[] = "cb_procffcam";
      }      

      // Autres champs 
      //
      $champs2 = array("cb_adresse", "cb_adresse2" ,"cb_codepostal" ,"cb_ville", "cb_pays"  ,"cb_mobile", "cb_telfixe", 
            "cb_contactaccident", "email", "cb_sexe", "cb_section");                                          
            
      if ($ctrl_adhesion) {
        $champs = $champs1;
      } else {
        $champs = array_merge($champs1, $champs2);        
      }
            
      foreach($champs as $c) {
      
        $new = strtolower($this->remove_accents($r->$c));
        $old = strtolower($this->remove_accents($r2->$c));            

        if ( $new <> $old ) {
                        
          $ecart = new StdClass();
          $ecart->champ = $c;
          $ecart->old = $r2->$c;
          $ecart->new = $r->$c;
          $ecart->doublon = false;                    
          if (in_array($c, $champs1) or $nouveau) {
            $ecart->inscription = true;
          }  else {
            $ecart->inscription = false;          
          }
          
          // Cas particulier de l'adresse mail : si nouvelle adresse mail on s'assure qu'elle
          // n'est pas déjà attribuée à un autre adhérent (pour respecter l'unicité des adresses mails
          // dans la base users)
          if ($c == "email") {          
            $doublon = $this->chercheDoublonEmail($r->no_adherent_complet, $new);
            if ((int) $doublon->id > 0) {
              $ecart->doublon = $doublon->id . ' - '. $doublon->name;
            }
          }                                                                               
          
          $ecarts[] = $ecart;               
        }              
      }
    }
        
    return array($id, $ecarts);
         
  }
  
  // Récupère les tarifs applicables dans les champs de community builder
  //  
  //  
  function getTitreChamps() {
 		$db = $this->getDbo();
	$query = $db->getQuery(true);
    
    // Recherche du tarif de licence
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

    if(date("m")>8) {
      $this->date_jeune = (date("Y")-23) ."-01-01";
    } else {
      $this->date_jeune = (date("Y")-24) ."-01-01";
    }     
  
  }
  
  //  Nettoie les accents 
  //
  //  
  function remove_accents($str)   {
    setlocale(LC_ALL, "en_GB.UTF-8");
    $str = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $str);
	  return preg_replace("/['^]([a-z])/ui", '\1', $str);
  }

  // Remet en forme le prénom
  //
  // 
  function prenom($prenom) {
  
    $prenom = mb_convert_case($prenom, MB_CASE_TITLE, 'utf-8');
    $prenom = preg_replace_callback('`-(\w)`', function ($matches) {
           return '-'.strtoupper($matches[1]); }, $prenom);   
  
    return $prenom;  
  
  }


  // Remet en forme un champ téléphone
  //
  // 
  function telephone($numero) {
   if (strpos($numero,"+")===false) {
     $n = str_replace(array("/","-"," ","."), "", $numero);
     $n = implode(" ", str_split($n,2));
     $n = preg_replace("`^00 `", "+", $n);
     return $n;
   } else {
     return $numero;
   }
  }
  
  // Extrait un ou plusieurs no de téléphone d'un champ (pour contact accident)
  //
  //
  function telephones($x) {
    $x = str_replace(array(".", "-", "/"), " ", $x);    
        
    $x = preg_replace_callback("`[0-9]([\.\-\s]){1}[0-9]`", 
        function ($match) { return str_replace($match[1], "", $match[0]);	}
        , $x);                
    $x = preg_replace_callback("`[0-9]([\.\-\s]){1}[0-9]`", 
        function ($match) { return str_replace($match[1], "", $match[0]);	}
        , $x);

    $x = trim(str_replace(" 00", " +", " ".$x));
    $x = str_replace("+33", "0", $x);

    preg_match_all("`\s0[1-9]{1}[0-9]{8}`", " ".$x, $nos);
    preg_match_all("`\s\+[0-9]{8,12}`", " ".$x, $nos2);    
    $nos = array_merge($nos[0], $nos2[0]);
        
    $numeros = array();    
    foreach($nos as $n)  {
      $n = trim($n);
     if (substr($n, 0, 1)=="+") {
      $n = "+" . implode(" ", str_split(substr($n, 1, 100), 2)); 
     } else {
      $n = implode(" ", str_split($n, 2));
     }
	   $numeros[] = $n;	
    }
    return $numeros;
  }
  

  // Remet en forme le champ cb_contactaccident
  //
  //
  function contact_accident($x) {

    $nos = $this->telephones($x);
    if (count($nos)==1) {
      $x = str_replace("M.", "Mr", $x);
      preg_match_all("`[a-zA-ZÀ-ÿ'\s\-\(\)]{4,35}`", $x, $noms);
      $noms = $noms[0]; 
      foreach($noms as $k=>$n) {
        if (strtolower(trim($n))=="ou") {
          unset($noms[$k]);
        }        
      }

      if (count($noms)==1) {
        $nom = trim($noms[0]);
        $nom = ucwords($nom);                
        $nom = str_replace(array("De ", "D'"), array("de ", "d'"), $nom);         
        if (trim($nom)<>"") {
          if(substr($nom, 0, 1) == "(") {            
            $nom = str_replace(array("(",")"), "", $nom);
          }
          $nom = $nom." : ";     
        } else {
          $nom = "";
        }   
      }          
      return $nom . $nos[0];
    } else {
      return $x;
    }
  
    
  
  }
  
  function adresse($adresse) {
      
    $voies = array("Rue ", "Boulevard ", "Avenue ", "Place ", "Allée ", "Chemin ", 
        "Square ", "Bd ", "Bis ","Ter ","Le ","Les ","La ","Au ","De ",
          "Des ","Du ","D'","L'");   
    $voies2 = array_map("strtolower", $voies);
      
    // Met tout en minuscule sauf la première lettre de chaque mot
    $adresse = mb_convert_case($adresse, MB_CASE_TITLE, 'utf-8');
    
    // Retire les virgules
    $adresse = str_replace(",", " ", $adresse);
    // Met les voies et les articles en minuscule
    $adresse = str_replace($voies, $voies2, $adresse);
    // Corrige la casse derrière une apostrophe
    $adresse = preg_replace_callback('`\'(\w)`', function ($matches) {
           return '\''.strtoupper($matches[1]); }, $adresse);   
    
    return $adresse;  
       
  }
  
  function ville($ville) {
  
    $villes = array("Sur ", "Les ", "Le ","Du ","La ","Au ","Aux ", "De ", "Des ", "Et ", "L'", "D'");   
    $villes2 = array_map("strtolower", $villes);   
  
    $ville = mb_convert_case($ville, MB_CASE_TITLE, 'utf-8');
    // Retire les tirets
    $ville = str_replace("-", " ", $ville);
    $ville = str_replace($villes, $villes2, $ville);
    $ville = preg_replace_callback('`\'(\w)`', function ($matches) {
           return '\''.strtoupper($matches[1]); }, $ville);   
    $ville = ucfirst($ville);

    return $ville;  
  }


  /* Met à jour la table comprofiler à partir du formulaire edit  
  */

  function save($post) {

 		$db = $this->getDbo();
	$query = $db->getQuery(true);
    $fields = array();  // Champs de comprofiler
    $fields2 = array(); // Champs de user (email)
    $msg = "";
    if (count($post->modif)>0) {
    foreach ($post->modif as $k => $v) {
      if ($k=="email") {
        $fields2[] = $db->quoteName($k) . " = " . $db->quote($post->value[$k]);          
      }  else {
        $fields[] = $db->quoteName($k) . " = " . $db->quote($post->value[$k]);
      }  
    }
    }
    if (count($fields)>0) {      
      $query = "UPDATE `#__comprofiler` set " .implode("," ,$fields). " WHERE id = " . (int) $post->id;
      $db->setQuery($query);
			if ($db->execute()) {

        $msg .= 'Enregistrement comprofiler mis à jour'; 
        $msg .= '<br>'.$query; 
            
      } else {
        echo var_dump($db); exit;      
      }
    } else {
      $msg .= "Pas de données mises à jour dans comprofiler";
    }     

    if (count($fields2)>0) {      
      $query = "UPDATE `#__users` set " .implode("," ,$fields2). " WHERE id = " . (int) $post->id;
      $db->setQuery($query);
			if ($db->execute()) {
        $msg .= '<br>Enregistrement user mis à jour';     
      } else {
        echo var_dump($db); exit;
      }      
    } else {
      $msg .= "<br>Pas de données mises à jour dans user";
    }     
    return $msg;   
  } 


  // Remplace un enregistrement de la base miroir ffcam2 par celui à jour
  //
  function maj_ffcam($no) {

		$db = $this->getDbo();
	$query = $db->getQuery(true);

    $query = "REPLACE INTO `#__ffcam2` 
        select * from  `#__ffcam` WHERE no_adherent_complet = '" . (float) $no ."'";
    $db->setQuery($query);
		if ($db->execute()) {
      $msg = '<br>Fichier FFCAM mirroir mis à jour';     
    } else {
      $msg = '<br>Problème mise à jour FFCAM mirroir';
      $msg .= var_dump($db->query);             
    }

    return $msg; 
  
  } 

  // Remplace un enregistrement de la base miroir ffcam_activites2 par celui à jour
  //
  function maj_ffcam_activites($no) {

		$db = $this->getDbo();
	$query = $db->getQuery(true);

    $query = "REPLACE INTO `#__ffcam_activites2` 
        select * from  `#__ffcam_activites` WHERE no_adherent_complet = '" . (float) $no ."'";
    $db->setQuery($query);
		if ($db->execute()) {
      $msg = '<br>Fichier FFCAM activités mirroir mis à jour';     
    } else {
      $msg = '<br>Problème mise à jour FFCAM  activités mirroir';
      $msg .= var_dump($db->query);             
    }

    return $msg; 
  
  } 
  
  // Recale le no d'adherent FFCAM dans la base comprofiler 
  //
  function change_no_ffcam($no_adherent, $id) {

		$db = $this->getDbo();
	$query = $db->getQuery(true);
    $query = "UPDATE `#__comprofiler` set cb_nocaf = " .$db->quote($no_adherent)
      . " WHERE id = " . (int) $id;
    $db->setQuery($query);
    $r = $db->execute();
    if ($r) {
      $app =Factory::getApplication();    
      $a_modifier = $app->getUserState( "ffcam.a_modifier");  
      $a_ajouter = $app->getUserState( "ffcam.a_ajouter");
      $a_modifier[] = $no_adherent;
      $app->setUserState( "ffcam.a_modifier", $a_modifier);

      $item = array_search($no_adherent, $a_ajouter);
      unset($a_ajouter[$item]);
      sort($a_ajouter);
      $app->setUserState( "ffcam.a_modifier", $a_modifier);              
    } 

    return $r;
  
  } 


	public function getForm($data = array(), $loadData = true){  
   return true;  
  }


    // Avant de créer un nouvel enregistrement dans comprofiler 
  // on vérifie que l'adhérent n'existe pas déjà (sans no ffcam ou 
  // avec un ancien no ffcam)

  public function chercheDoublon($prenom, $nom) {

		$db = $this->getDbo();
 	$query = $db->getQuery(true);
    $query1 = "select #__users.id, firstname, lastname, cb_datenaissance, cb_nocaf FROM #__users 
        LEFT JOIN #__comprofiler ON #__users.id = user_id";
    $query  = $query1." WHERE lastname LIKE '".$db->escape($nom)
     ."' and firstname LIKE '".$db->escape($prenom)."'";
    $db->setQuery($query);
    $rows = $db->loadObjectList();

    return $rows;  
  
  } 
   
  // On vérifie que l'adhérent n'existe pas déjà (sans no ffcam ou 
  // avec un ancien no ffcam)

  public function chercheDoublonEmail($no_adherent, $email) {

		$db = $this->getDbo();            
	$query = $db->getQuery(true);
    
    $query = "select #__users.id, #__users.name  FROM #__users 
        LEFT JOIN #__comprofiler ON #__users.id = user_id
        WHERE email = '".strtolower($db->escape($email))."' 
        and cb_nocaf <>'".(float) $no_adherent."'";;        
    $db->setQuery($query);
    $row = $db->loadObject();

    if ($row !== NULL) {    
      $row->test_email = strtolower($db->escape($email));
      $row->test_nocaf = (float) $no_adherent;
      $row->qry = str_replace("#__", "j3x_", $query);

    }
    
    return $row;  
  
  } 


  // Avant de créer un nouvel enregistrement dans comprofiler 
  // on vérifie qu'il existe pas déjà un numéro ffcam présent dans la base GUMS 
  // (cas d'une création parallèle GUMS+FFCAM)

  public function chercheDoublonFfcam($no_adherent) {

    if ($no_adherent>0) {
		$db = $this->getDbo();
	$query = $db->getQuery(true);
    $query1 = "select #__users.id, firstname, lastname, cb_datenaissance, cb_nocaf FROM #__users 
        LEFT JOIN #__comprofiler ON #__users.id = user_id";
    $query  = $query1." WHERE cb_nocaf = '".$db->escape($no_adherent)."'";
    $db->setQuery($query);
    $rows = $db->loadObjectList();

      return $rows;  
    } else {
      return array();
    }
  
  }
  
  
  private function pays() {
    
    if (! isset($this->pays)) {
      $pays = file(__DIR__ ."/pays_ffcam.csv");
      $this->pays = array_map(function ($a) { return trim($a); },$pays);                  
    }
  
    return $this->pays;

    /*      
          $pays2 = array();
          foreach($pays as $p) {
            $pays2[] = trim($p)."\n";
          }
          file_put_contents(__DIR__ ."/pays_ffcam.csv", $pays2);
    */

  
  } 



/*
----------------------------------------------------------------------------
  A transférer dans modèle nouveau
  
*/


}
