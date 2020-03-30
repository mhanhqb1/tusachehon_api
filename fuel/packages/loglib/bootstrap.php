<?php
/**
 * Fuel
 *
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.7
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2013 Fuel Development Team
 * @link       http://fuelphp.com
 */

Autoloader::add_core_namespace('LogLib');
Autoloader::add_classes(array(
	'LogLib\\LogLib' => __DIR__ . '/classes/loglib.php',	
	'LogLib\\Compress' => __DIR__ . '/classes/compress.php'	
));