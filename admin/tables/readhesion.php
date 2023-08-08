<?php
defined('_JEXEC') or die;
class TableReadhesion extends JTable
{
	public function __construct(&$db)
	{
		parent::__construct('#__comprofiler', 'id', $db);
	}

}
