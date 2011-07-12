<?php

/**
 * E-Commerce is a lightweight package for the FuelPHP framework to provide your
 * application with e-commerce logic
 * 
 * @package    E-Commerce
 * @version    1.0
 * @author     Syntaqx Development Team
 * @license    MIT License
 * @copyright  2011 Syntaqx Development
 */

Autoloader::add_core_namespace('Ecommerce');

Autoloader::add_classes(array(
	'Ecommerce\\Cart'              => __DIR__.'/classes/cart.php',
));

/* End of file bootstrap.php */