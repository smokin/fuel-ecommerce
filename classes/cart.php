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
namespace Ecommerce;

/**
 * Shopping cart class
 *
 * The cart class permits items to be added to a session that stays active while
 * a user is browsing your site. These items can be retrieved and displayed in a
 * standard "shopping cart" format, allow the user to update the quantity or
 * remove items from the cart.
 *
 * Note: This class is fundamentally based on the shopping cart class from the
 * CodeIgniter framework. Beyond that, there have been several tweaks and
 * fixes to the class to make it even better.
 *
 * @package     E-Commerce
 * @version     1.0
 * @author      Chase "Syntaqx" Hutchins
 */
class Cart {

	/**
	 * Cached configuration
	 *
	 * @var   array
	 */
	protected static $config;

	/**
	 * The users shopping cart
	 *
	 * @var   array
	 */
	protected static $cart_contents;

	/**
	 * Class constructor
	 *
	 * @return  void
	 */
	public static function _init($load_config = true)
	{
		if($load_config === true)
		{
			static::$config = \Config::load('cart', true);
		}

		static::$cart_contents = array();

		// Grab the shopping cart array from the session table, if it exists
		if(($cart_contents = \Session::get('cart_contents')) !== null)
		{
			static::$cart_contents = (array) \Session::get('cart_contents');
		}
		// No cart exists, so we'll set some base values
		else
		{
			static::$cart_contents['cart_total'] = 0;
			static::$cart_contents['total_items'] = 0;
		}
	}

	/**
	 * Insert item(s) into the shopping cart and save the changes
	 *
	 * @access  public
	 * @param   array
	 * @return  boolean
	 */
	public static function insert($items = array())
	{
		if(!is_array($items) or count($items) === 0)
		{
			return false;
		}

		$save_cart = false;

		// You can either insert a single product using a one-dimensional array,
		// or multiple products using a multi-dimensional one. The way we
		// determine the array type is by looking for a required array key named
		// "id" at the top level. If it's not found, we'll assume it's a
		// multi-dimensional array
		if(isset($items['id']))
		{
			if(static::_insert($items) === true)
			{
				$save_cart = true;
			}
		}
		else
		{
			foreach($items as $value)
			{
				if(is_array($value) and isset($value['id']))
				{
					if(static::_insert($value) === true)
					{
						$save_cart = true;
					}
				}
			}
		}

		if($save_cart === true)
		{
			static::save_cart();
			
			return true;
		}

		return false;
	}

	/**
	 * Insert an item into the shopping cart
	 *
	 * @access  protected
	 * @param   array
	 * @return  boolean
	 */
	protected static function _insert($items = array())
	{
		// Was any cart data passed?
		if(!is_array($items) or count($items) === 0)
		{
			return false;
		}

		// Does the $items array contain the required keys?
		if(!isset($items['id']) or !isset($items['qty']) or !isset($items['price']) or !isset($items['name']))
		{
			return false;
		}

		// Sanitize the quantity, as it should only ever be a numeric value
		// without any leading zeroes
		$items['qty'] = trim(preg_replace('/([^0-9])/i', '', $items['qty']));
		$items['qty'] = trim(preg_replace('/(^[0]+)/i', '', $items['qty']));

		// Let's see if it came out right! And if it did, do we have a quantity?
		if(!is_numeric($items['qty']) or (int) $items['qty'] === 0)
		{
			return false;
		}

		// Typecast the quantity to an integer
		$items['qty'] = (int) $items['qty'];

		// Validate our rule logics
		foreach(static::$config['rules'] as $key => $regex)
		{
			if(!preg_match('/^['.$regex.']+$/i', $items[$key]))
			{
				return false;
			}
		}

		// Prepare the price, removing any non-float character, as well as all
		// leading zeroes
		$items['price'] = trim(preg_replace('/([^0-9\.])/i', '', $items['price']));
		$items['price'] = trim(preg_replace('/(^[0]+)/i', '', $items['price']));

		// Let's see if we still have a numeric value!
		if(!is_numeric($items['price']))
		{
			return false;
		}

		// Typecast the price to a float
		$items['price'] = (float) $items['price'];

		// We now need to create a unique identifier for the item being inserted
		// into the cart. Every time something is added to the cart, it is
		// stored in the master cart array. Each row in the cart array, however,
		// must have a unique index that identifies not only a particular
		// products, but makes it possible to store identical products with
		// different options. For example, what if someone buys two identical
		// t-shirts (same product id), but in different sizes? The product ID
		// (and other attributes, like the name) will be identical for both
		// sizes, because it's the same shirt. The only difference will be the
		// size. Internally, we need to treat identical submissions, but with
		// different options, as a unique product. Our solution is to convert
		// the options array to a string and MD5 it along with the product
		// ID. This becomes the unique "row ID".
		if(isset($items['options']) and count($items['options']))
		{
			$rowid = md5($items['id'].implode('', $items['options']));
		}
		// No options were submitted so we simply MD5 the product ID.
		// Technically, we don't need to MD5 the ID in this case, but it makes
		// sense to standardize the format of array indexes for both conditions.
		else
		{
			$rowid = md5($items['id']);
		}

		// Now that we have our unique "row ID", we'll add our cart items to the
		// master array.

		// Let's unset this first, just to make sure our index contains only the
		// data from this submission
		unset(static::$cart_contents[$rowid]);

		// Create a new index with our new row ID
		static::$cart_contents[$rowid]['rowid'] = $rowid;

		// Add the new items to the cart array
		foreach($items as $key => $value)
		{
			static::$cart_contents[$rowid][$key] = $value;
		}

		// And we did it!
		return true;
	}

	/**
	 * Update the cart
	 * 
	 * This function permits the quantity of a given item to be changed.
	 * Typically it is called from the "view cart" page if a user makes changes
	 * to the quantity before checkout. That array must contain the product ID
	 * and the quantity for each item.
	 *
	 * @access  public
	 * @param   array
	 * @return  boolean
	 */
	public static function update($items = array())
	{
		if(!is_array($items) or count($items) === 0)
		{
			return false;
		}

		$save_cart = false;

		// You can either update a single product using a one-dimensional array,
		// or multiple products using a multi-dimensional one. The way we
		// determine the array type is by looking for a required array keys
		// named "rowid" and "qty" at the top level. If it's not found, we'll
		// assume it's a multi-dimensional array
		if(isset($items['rowid']) && isset($items['qty']))
		{
			if(static::_update($items) === true)
			{
				$save_cart = true;
			}
		}
		else
		{
			foreach($items as $value)
			{
				if(is_array($value) and isset($value['rowid']) and isset($value['qty']))
				{
					if(static::_update($value) === true)
					{
						$save_cart = true;
					}
				}
			}
		}

		if($save_cart === true)
		{
			static::save_cart();

			return true;
		}

		return false;
	}

	/**
	 * Update the cart
	 * 
	 * This function permits the quantity of a given item to be changed.
	 * Typically it is called from the "view cart" page if a user makes changes
	 * to the quantity before checkout. That array must contain the product ID
	 * and the quantity for each item.
	 *
	 * @access  protected
	 * @param   array
	 * @return  boolean
	 */
	protected static function _update($items = array())
	{
		// Without these array indexes there is nothing we can do
		if(!isset($items['qty']) or !isset($items['rowid']) or !isset(static::$cart_contents[$items['rowid']]))
		{
			return false;
		}

		// Sanitize the quantity, as it should only ever be a numeric value
		// without any leading zeroes
		$items['qty'] = trim(preg_replace('/([^0-9])/i', '', $items['qty']));
		$items['qty'] = trim(preg_replace('/(^[0]+)/i', '', $items['qty']));

		// Let's see if it came out right! And if it did, do we have a quantity?
		if(!is_numeric($items['qty']) or (int) $items['qty'] === 0)
		{
			return false;
		}

		// Typecast the quantity to an integer
		$items['qty'] = (int) $items['qty'];

		// Is the new quantity different than what is already saved in the cart?
		// If it's the same, then we don't need to do anything
		if(static::$cart_contents[$items['rowid']]['qty'] == $items['qty'])
		{
			return false;
		}

		// Is the quantity zero? If so, we'll remove the item from the cart
		if($items['qty'] <= 0)
		{
			unset(static::$cart_contents[$items['rowid']]);
		}
		// Otherwise, we're updating!
		else
		{
			static::$cart_contents[$items['rowid']]['qty'] = $items['qty'];
		}

		return true;
	}

	/**
	 * Save the cart array to the session table
	 * 
	 * @access  protected
	 * @return  boolean
	 */
	protected static function save_cart()
	{
		unset(static::$cart_contents['total_items']);
		unset(static::$cart_contents['cart_total']);

		// Set the base count of our total items
		static::$cart_contents['total_items'] = count(static::$cart_contents);

		// Let's add up the individual prices and set the cart sub-total
		$total = 0;

		foreach(static::$cart_contents as $key => $value)
		{
			// Make sure the array contains the proper indexes
			if(!is_array($value) or !isset($value['price']) or !isset($value['qty']))
			{
				continue;
			}

			$total += ($value['price'] * $value['qty']);

			// Set the subtotal
			static::$cart_contents[$key]['sub_total'] = (static::$cart_contents[$key]['price'] * static::$cart_contents[$key]['qty']);

			// If we have more than a single product, increment the total items
			static::$cart_contents['total_items'] += ($value['qty'] - 1);
		}

		// Set the cart total
		static::$cart_contents['cart_total'] = $total;

		// Is our cart empty? If so we can delete it from the session
		if(count(static::$cart_contents) <= 2)
		{
			\Session::delete('cart_contents');
			return false;
		}

		// If we made it this far, it means that our cart has data.
		// Let's pass it to the Session class so it can be stored.
		\Session::set('cart_contents', static::$cart_contents);

		return true;
	}

	/**
	 * Cart Total
	 *
	 * Total cost of the current carts contents
	 *
	 * @access  public
	 * @return  integer
	 */
	public static function total()
	{
		return static::$cart_contents['cart_total'];
	}

	/**
	 * Total Items
	 *
	 * Returns the total number of items in the cart
	 *
	 * @access  public
	 * @return  float
	 */
	public static function total_items()
	{
		return static::$cart_contents['total_items'];
	}

	/**
	 * Cart Contents
	 *
	 * Returns the contents of our cart
	 *
	 * @access  public
	 * @return  array
	 */
	public static function contents()
	{
		$cart = static::$cart_contents;

		unset($cart['total_items']);
		unset($cart['cart_total']);

		return $cart;
	}

	/**
	 * Has options
	 *
	 * Returns true if the rowid passed to this function correlates to an item
	 * that has options associated with it.
	 *
	 * @access  public
	 * @return  boolean
	 */
	public static function has_options($rowid = '')
	{
		if(!isset(static::$cart_contents[$rowid]) or !isset(static::$cart_contents[$rowid]['options']) or count(static::$cart_contents[$rowid]['options']) === 0)
		{
			return false;
		}

		return true;
	}

	/**
	 * Product options
	 *
	 * Returns the array of options, for a particular product row id
	 *
	 * @access  public
	 * @return  array
	 */
	public static function product_options($rowid = '')
	{
		if(static::has_options($rowid) === false)
		{
			return array();
		}

		return (array) static::$cart_contents[$rowid]['options'];
	}

	/**
	 * Destroy the cart
	 *
	 * Empties the cart and kills the session
	 *
	 * @access  public
	 * @return  void
	 */
	public static function destroy()
	{
		\Session::delete('cart_contents');
		static::_init(false);
	}
}

/* End of file cart.php */