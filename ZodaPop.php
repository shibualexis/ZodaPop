<?php
/*
  * @author Thane Vo <vothane@gmail.com>
  * @version 0.0.1 alpha
  * @license http://opensource.org/licenses/lgpl-2.1.php
  */
if (!defined( 'PHP_VERSION_ID' ) || PHP_VERSION_ID < 50300)
	die( 'ZodaPop requires PHP 5.3 or higher' );

define( 'ZODAPOP_VERSION_ID', '0.0.1' );

require 'lib/ZodaPop.php';
require 'lib/Connection.php';
require 'lib/HttpPHP.php';
require 'lib/DatabaseSchema.php';
require 'lib/Table.php';
?>
