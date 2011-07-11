<?php

return array(

	/**
	 * Regular expression rules to validate product values before allowing them
	 * to be in the cart.
	 * 
	 * Note: Not totally sure we should impose these rules, but it seems prudent
	 * to standardize IDs as well as naming conventions.
	 */
	'rules' => array(
		'id' => '\.a-z0-9_-', // alpha-numeric, dashes, underscores, or periods
		'name' => '\.\:\-_ a-z0-9', // alpha-numeric, dashes, underscores, colons or periods
	),

);

/* End of file cart.php */