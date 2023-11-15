<?php

defined('_JEXEC') or die;

class AdherentFfcam  // extends JModelAdmin 
{
  public $cb_nocaf = "";
  public $firstname = "";
  public $lastname = "";
  public $cb_sexe = "";
  public $cb_adresse = "";
  public $cb_adresse2 = "";
  public $cb_codepostal = "";
  public $cb_ville = "";
  public $cb_pays = "";
  public $email = "";
  public $cb_mobile = "";
  public $cb_telfixe = "";
  public $cb_contactaccident = "";
  public $cb_datenaissance = "";
  public $cb_section = "";

  
  public $cb_dateinscription = "";
  public $cb_adhesion = "";
  public $cb_licenceffcam = "";
  public $cb_assuranceffcam = "";
  public $cb_cheque = "";
  public $cb_procffcam = "";
  public $cb_crampon = "0";
  public $cb_stage = "";

  
  function __construct($r, $tarif, $adhesion, $crampon, $date_jeune)
  {

    $this->cb_nocaf = $r->id;
    $this->firstname = $this->prenom($r->prenom);
    $this->lastname = mb_strtoupper($r->nom, 'UTF-8');
    if(preg_match("`^D [A-Z].`", $this->lastname )) {
      $this->lastname = "D'".substr($this->lastname, 2, 150);
    }
    if ($r->qualite == "M") {
      $this->cb_sexe = 'M';
    } else {
      $this->cb_sexe = 'F';
    }
    $this->cb_adresse = $this->adresse($r->adresse3);
    $this->cb_adresse2 = trim($r->adresse1 . ' ' . $r->adresse2);


    if (trim($r->adresse4) <> "") {

      if (trim($r->adresse4) <> trim($r->ville)) {
        // Complément d'adresse dans localité      
        if ($this->cb_adresse2 <> "") {
          $this->cb_adresse2 .= " - ";
        }
        $this->cb_adresse2 .= $r->adresse4;
      }
    }
    $this->cb_codepostal = $r->codepostal;
    $this->cb_ville = $this->ville($r->ville);
    $this->cb_pays = "France";

    $this->email = strtolower($r->email);
    $this->cb_mobile = $this->telephone($r->portable);
    $this->cb_telfixe = $this->telephone($r->tel);
    $this->cb_contactaccident = $r->accident_qui . ' ' . $this->telephone($r->accident_tel);
    $this->cb_datenaissance =  $r->date_naissance;
    $this->cb_dateinscription = $r->date_inscription;
    $this->cb_section = "Alpinisme-Ski-Escalade";


    if ($r->date_inscription <> "0000-00-00") {

      // Est-ce un jeune ou un adulte
      //
      if ($r->date_naissance >= $date_jeune) {
        $this->cb_adhesion = $adhesion["jeune"];
      } else {
        $this->cb_adhesion = $adhesion["adulte"];
      }

      $this->cb_licenceffcam = $tarif[$r->categorie]["licence"];

      if ($r->assurance_ap) {
        $this->cb_assuranceffcam = $tarif[$r->categorie]["assurance"];
      } else {
        $this->cb_assuranceffcam = 0;
      }

      if ($r->inscrit_par_internet == 1) {
        $this->cb_cheque = "Web";
        $this->cb_procffcam = "1";
      } else {
        $this->cb_cheque = "Non";
        $this->cb_procffcam = "1";
      }


      // Abonnement Crampon
      if($r->revue_club_statut_actuel == "O") {
        $this->cb_crampon = $crampon["normal"];
      } 

      foreach ($r->activites_club as $a)
        if (isset($a->code) and $a->code == "ABO+") {
          //$this->cb_crampon = $a->montant;
          $this->cb_crampon = $crampon["soutien"];
        }
        if (isset($a->code) and $a->code == "STA") {
          $this->cb_stage = $a->montant;
        }
    }
  }



  //  Nettoie les accents 
  //
  //  
  function remove_accents($str)
  {
    setlocale(LC_ALL, "en_GB.UTF-8");
    $str = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $str);
    return preg_replace("/['^]([a-z])/ui", '\1', $str);
  }

  // Remet en forme le prénom
  //
  // 
  function prenom($prenom)
  {

    $prenom = mb_convert_case($prenom, MB_CASE_TITLE, 'utf-8');
    $prenom = preg_replace_callback('`(?:-|\s)(\w)`', function ($matches) {
      return '-' . strtoupper($matches[1]);
    }, $prenom);
    return $prenom;
  }


  // Remet en forme un champ téléphone
  //
  // 
  function telephone($numero)
  {
    $n = str_replace(array("/", "-", " ", "."), "", $numero);

    $prefix = "";
    if (substr($n, 0, 2) == "00") {
      $prefix = "+";
      $n = "+" . substr($n, 2);
    }   
    if (substr($n, 0, 1) == "+") {
      if(substr($n, 1, 2) == "33") {
        $prefix = "";  
        $n = "0" . substr($n, 3);
      } else {
        $prefix = "+";
        $n = substr($n, 1);
      }
    }
    $x = substr($n, 0, 2);
    if($prefix=="+" and $x=="32" ) {
      $prefix = $prefix . $x . " " . substr($n, 2, 1) . " ";
      $n = substr($n, 3);
    }

    $n = $prefix . implode(" ", str_split($n, 2));
    return $n;

  }

  // Extrait un ou plusieurs no de téléphone d'un champ (pour contact accident)
  //
  //
  function telephones($x)
  {
    $x = str_replace(array(".", "-", "/"), " ", $x);

    $x = preg_replace_callback(
      "`[0-9]([\.\-\s]){1}[0-9]`",
      function ($match) {
        return str_replace($match[1], "", $match[0]);
      },
      $x
    );
    $x = preg_replace_callback(
      "`[0-9]([\.\-\s]){1}[0-9]`",
      function ($match) {
        return str_replace($match[1], "", $match[0]);
      },
      $x
    );

    $x = trim(str_replace(" 00", " +", " " . $x));
    $x = str_replace("+33", "0", $x);

    preg_match_all("`\s0[1-9]{1}[0-9]{8}`", " " . $x, $nos);
    preg_match_all("`\s\+[0-9]{8,12}`", " " . $x, $nos2);
    $nos = array_merge($nos[0], $nos2[0]);

    $numeros = array();
    foreach ($nos as $n) {
      $n = trim($n);
      if (substr($n, 0, 1) == "+") {
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
  function contact_accident($x)
  {

    $nos = $this->telephones($x);
    if (count($nos) == 1) {
      $x = str_replace("M.", "Mr", $x);
      preg_match_all("`[a-zA-ZÀ-ÿ'\s\-\(\)]{4,35}`", $x, $noms);
      $noms = $noms[0];
      foreach ($noms as $k => $n) {
        if (strtolower(trim($n)) == "ou") {
          unset($noms[$k]);
        }
      }

      if (count($noms) == 1) {
        $nom = trim($noms[0]);
        $nom = ucwords($nom);
        $nom = str_replace(array("De ", "D'"), array("de ", "d'"), $nom);
        if (trim($nom) <> "") {
          if (substr($nom, 0, 1) == "(") {
            $nom = str_replace(array("(", ")"), "", $nom);
          }
          $nom = $nom . " : ";
        } else {
          $nom = "";
        }
      }
      return $nom . $nos[0];
    } else {
      return $x;
    }
  }


  function adresse($adresse)
  {

    $voies = array(
      "Rue ", "Boulevard ", "Avenue ", "Place ", "Allée ", "Chemin ",
      "Square ", "Bd ", "Bis ", "Ter ", "Le ", "Les ", "La ", "Au ", "De ",
      "Des ", "Du ", "D'", "L'"
    );
    $voies2 = array_map("strtolower", $voies);

    // Met tout en minuscule sauf la première lettre de chaque mot
    $adresse = mb_convert_case($adresse, MB_CASE_TITLE, 'utf-8');

    // Retire les virgules
    $adresse = str_replace(",", " ", $adresse);
    // Met les voies et les articles en minuscule
    $adresse = str_replace($voies, $voies2, $adresse);
    // Corrige la casse derrière une apostrophe
    $adresse = preg_replace_callback('`\'(\w)`', function ($matches) {
      return '\'' . strtoupper($matches[1]);
    }, $adresse);
    // Retire les doubles espaces 
    $adresse = str_replace(array("  ", "   "), " ", $adresse);


    return $adresse;
  }

  function ville($ville)
  {

    $villes = array("Sur ", "Les ", "Le ", "Du ", "La ", "Au ", "Aux ", "De ", "Des ", "Et ", "L'", "D'");
    $villes2 = array_map("strtolower", $villes);

    $ville = mb_convert_case($ville, MB_CASE_TITLE, 'utf-8');
    // Retire les tirets
    $ville = str_replace("-", " ", $ville);
    $ville = str_replace($villes, $villes2, $ville);
    $ville = preg_replace_callback('`\'(\w)`', function ($matches) {
      return '\'' . strtoupper($matches[1]);
    }, $ville);
    $ville = ucfirst($ville);

    return $ville;
  }


  function getForm($data = array(), $loadData = true)
  {
    return true;
  }
}
