<?php

/**
 * E-Commerce is a lightweight package for the FuelPHP framework to provide your
 * application with e-commerce logic
 * 
 * @package    E-Commerce
 * @version    1.0
 * @author     Fuel Syntaqx Development
 * @license    MIT License
 * @copyright  2011 Fuel Syntaqx Development
 */

Autoloader::add_core_namespace('Ecommerce');

Autloader::add_classes(array(
	'Ecommerce\\Cart'              => __DIR__.'/classes/cart.php',
));

/* End of file bootstrap.php */