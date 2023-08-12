<?php
/**
 * @version		$Id: view.pdf.php 11371 2008-12-30 01:31:50Z ian $
 * @package		Joomla
 * @subpackage	Content
 * @copyright	Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport( 'joomla.application.component.view');
use \Joomla\CMS\Factory;

class InscriptionsViewCertificats extends JViewLegacy
{

 function display( $tpl = NULL )
   {
   
    InscriptionsHelper::checkUser();
    $app =Factory::getApplication();     
            
    $file = trim($app->input->get("f", "", "RAW"));
    if (! is_file($file)) {
      echo 'Fichier '.$file.' non trouvé';
    } else {
      $ext = pathinfo($file, PATHINFO_EXTENSION);
      if ($ext=="pdf") {    
        header('Content-Type: application/pdf');
        readfile($file);
        exit;    
      } elseif($ext=="jpg") {
        header('Content-Type: image/jpeg');
        readfile($file);
        exit;          
      }
    }
/*    
    jimport('tcpdf.tcpdf_import');
    $pdf = new TCPDF_IMPORT("P", "mm", "A4", true);

    $pdf->importPDF(JPATH_BASE . "/images/comprofiler/plug_cbfilefield/62/Certificat_Medical_2017_594e6718cc54f.pdf");
    $pdf->Output("joomla.pdf", "I");
*/    
   }  
}	
?>
