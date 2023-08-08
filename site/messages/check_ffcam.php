<?php
define('_JEXEC', 1);

$mdp = filter_input(INPUT_GET, "mdp", FILTER_SANITIZE_STRING);
$no  = filter_input(INPUT_GET, "no", FILTER_SANITIZE_STRING);

if ($mdp<>"fixia9") {
  echo 'Non autorisé - '.$path;
  exit;
}


include("/home/gumspari/ffcam/apiffcam.php");

$result = $client->verifierUnAdherent($cnxFfcam, $no);

/*
try {
  $result2 = $client->extractionAdherent($cnxFfcam, $no);     
} catch (Exception $e) {
  echo '<pre>'; var_dump($e); echo '</pre>'; 
}
*/

//$result = $client->extractionClub($connexion, "7504");
//$result = $client->extractionClubs($connexion);

echo '<pre>'; print_r($result); echo '</pre>'; 
echo '**<pre>'; print_r($result2); echo '</pre>'; exit;
  

exit;
