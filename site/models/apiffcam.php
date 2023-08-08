<?php
defined('_JEXEC') or die;

$client = new SoapClient('https://extranet-clubalpin.com/app/soap/extranet_pro.wsdl');

$connexion = $client->auth();
$connexion->utilisateur = '7504_0006';
$connexion->motdepasse = '5byMayjf';

