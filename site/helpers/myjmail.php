<?php
/**
 * @package     Joomla.Site
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.mail.mail');

class MyJMail extends JMail
{
	public function __construct()
  {
    $this->Priority = 3;
    $this->CharSet = "utf-8";
    $this->Timeout      =  60; // set the timeout (seconds)
    $this->IsSMTP();
    $this->Host       = "ssl0.ovh.net"; // SMTP server
    $this->SMTPSecure = 'tls';      
    $this->Port       = 587;
    $this->SMTPOptions = array(
          'ssl' => [
              'verify_peer'  => false,
              'verify_peer_name'  => false,
              'allow_self_signed' => true,
          ],
      );      
    $this->SMTPAuth   = true;
    $this->XMailer    = "Microsoft Outlook 14.0";
    $this->Username   = "info%gumsparis.asso.fr"; // SMTP account username
    $this->Password   = "meropista747";        // SMTP account password

    $this->Encoding = "base64";
    $this->IsHTML(TRUE);

    $this->From       = "mail@gumsparis.asso.fr";
    $this->Sender     = $this->From;
    $this->FromName   = "GUMS Paris";
    $this->Helo       = "GUMS";

    $this->DKIM_domain = 'gumsparis.asso.fr';
    $this->DKIM_private = '/home/gumspari/dkim/dkim_private.pem';
    $this->DKIM_selector = 'phpmailer';
    $this->DKIM_passphrase = '';
    $this->DKIM_identity = $this->From;


  }
	    
  
}
