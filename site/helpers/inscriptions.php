<?php
/**
 * @package     Joomla.Site
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

abstract class InscriptionsHelper
{
	public static function checkUser($redirect = true)
	{ 
    $user   =  Factory::getUser();
    $check = false;
    if($user->guest != 1) {
      $check = true;
    }
    if ($check == false and $redirect) {
//        $uri = JFactory::getURI(); 
	    $uri = Uri::getInstance();
        $return = $uri->toString(); 
        $app = Factory::getApplication(); 
        $app->redirect('index.php?option=com_comprofiler&view=login&return='. urlencode(base64_encode($return)), 
          JText::_('Connexion nécessaire pour gérer son adhésion') ); 
    }      

    return $check;

	}
  
	public static function checkAdmin($redirect = true)
	{ 
    $user	= Factory::getUser();     
    $aid = $user->groups;
    $groupes_autorises = array(8, 7);
    $check = ( count(array_intersect($aid, $groupes_autorises)) > 0);

    if($check === false and $redirect) {
      $app = Factory::getApplication();
      $app->redirect('index.php', 
        JText::_('Reservé aux administrateurs'), 'warning' ); 
    }          

    return $check;

	}

  public static function checkCron()
	{ 

    $input = Factory::getApplication()->input;		
		$path = $input->server->get('PATH');
    $server_user =  $input->server->get('USER');

    if ($path == "/usr/local/bin:/usr/bin:/bin" 
          and $server_user == "gumspari") {
      return true;      

    } else {

      return false;

    }    

	}

  public static function checkVPS()
	{ 

    $input = Factory::getApplication()->input;		

    if(
      $input->server->get('HTTP_REMOTE_IP') == "135.125.205.29" and 
      //$input->server->get('HTTP_USER_AGENT') == "Wget1.20.1linux-gnu" and 
      $input->get('mdp') == "qo2sj750Rq" 
    ) {

      return true;

    } else {
      /*
      echo "Check VPS = false ";       
      echo '<pre>'; var_dump($input->server->get('HTTP_REMOTE_IP')); echo '</pre>';
      echo '<pre>'; var_dump($input->server->get('HTTP_USER_AGENT')); echo '</pre>';
      echo '<pre>'; var_dump($input->get('mdp')); echo '</pre>'; 
      */

      return false;

    }    

	}


	public static function checkManager($redirect = true)
	{ 
    $user	= Factory::getUser();     
    $aid = $user->groups;
    $groupes_autorises = array(8, 7, 6, 13);
    $check = ( count(array_intersect($aid, $groupes_autorises)) > 0);

    if($check === false and $redirect) {
        $app = Factory::getApplication();
  		  $app->redirect('index.php', 
        JText::_('Reservé aux gestionnaires'), 'warning' ); 
    }          

    return $check;

	}
  

  public static function dateMyHt($date) {
  
    $date = new JDate($date);
    return $date->format('d-m-Y');
  
  
  }
  

  
	public static function retire_accents($str)   {

    setlocale(LC_ALL, "en_GB.UTF-8");
    $str = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $str);
    return preg_replace("/['^]([a-z])/ui", '\1', $str);
    
  }



}
