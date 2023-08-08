<?php // no direct access
defined('_JEXEC') or die('Restricted access'); 
$gums = $this->data[0];
$ffcam = $this->data[1];
$ecarts = $this->data[2][0];
//echo '<pre>'; var_dump($ecarts); echo '</pre>'; exit;

$champs = array(
  "cb_datenaissance" => "Date de naissance", 
  "email" => "Adresse mail", 
  "cb_adresse" => "Adresse", 
  "cb_adresse2" => "Adresse2",
  "cb_codepostal" => "Code Postal", 
  "cb_ville" => "Ville", 
  "cb_pays" => "Pays", 
  "cb_mobile" => "Tél. portable", 
  "cb_telfixe" => "Tél. fixe", 
  "cb_contactaccident" => "Contact accident"
);

/*
            [] => 24 rue des Penitentes
            [ => 
            [] => 59800
            [] => Lille
            [] => France
            [] => benoit@dhalluin.com
            [] => 
            [] => 
            [] => M.Mme D halluin 06 85 57 76 22
            [] => 1966-03-11
            [cb_section] => Alpinisme-Ski-Escalade
            [cb_dateinscription] => 2021-09-01
            [cb_adhesion] => 23.00
            [cb_licenceffcam] => 49.30
            [cb_assuranceffcam] => 20.80
            [cb_cheque] => Web
            [cb_procffcam] => 1
            [] => 11.00
            [cb_stage] => 
*/



?>
<h4>Statut / informations personnelles</h4>
<h5><?php echo $gums->firstname.' '.$gums->lastname; ?></h5>
<div>
 <?php
 if($gums->cb_nocaf == 0) {
   echo 'Pas de n° de licence FFCAM trouvé';
 } else {
   if($ffcam === null) {
    echo 'Pas de données trouvées pour licence FFCAM : ' . $gums->cb_nocaf;
   } else {
    echo 'N° de licence FFCAM : ' . $gums->cb_nocaf;
    if ($ffcam->cb_licenceffcam > 0) {
      echo ' => à jour';
    } else {
      echo ' => à renouveller';
    }
   }    
 }
 ?> 
</div>
<form>  
<div>
<table class="table">
<?php
foreach($champs as $k => $c) {

  if((string) $ffcam->$k == "")  {
    if ((string) $gums->$k != "") {
      $ffcam->$k = "<i>Non défini</i>";
    } else {    
      continue;
    }
  }
   
  if(in_array($k, $ecarts)) {
    echo '<tr><td colspan="2"><b>'.$c.'</b></td></tr>';
    echo '<tr><td>Ffcam</td><td>'.$ffcam->$k.'</td></tr>';
    echo '<tr><td>Gums</td><td>'.$gums->$k.'</td></tr>';
  } else {
    echo '<tr><td><b>'.$c.'</b></td><td>'.$ffcam->$k.'</td></tr>';
  }

}

?>  
</table>
</form>
<?php 


echo '<pre>'; print_r($this->data); echo '</pre>'; 


?>
</div>


