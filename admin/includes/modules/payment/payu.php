<?php

/**
 * The administration side of the PayU payment module
 */
class osC_Payment_payu extends osC_Payment_Admin {
	
	/**
	 * The administrative title of the payment module
	 *
	 * @var string
	 * @access private
	 */
	var $_title;
	
	/**
	 * The code of the payment module
	 *
	 * @var string
	 * @access private
	 */
	var $_code = 'payu';
	
	/**
	 * The developers name
	 *
	 * @var string
	 * @access private
	 */
	var $_author_name = 'Pawel‚ Antczak';
	
	/**
	 * The developers address
	 *
	 * @var string
	 * @access private
	 */
	var $_author_www = 'http://antczak.org';
	
	/**
	 * The status of the module
	 *
	 * @var boolean
	 * @access private
	 */
	var $_status = false;
	
	/**
	 * Constructor
	 */
	function osC_Payment_payu() {
		global $osC_Language;
		
		$this->_title = $osC_Language->get ( 'payment_payu_title' );
		$this->_description = $osC_Language->get ( 'payment_payu_description' );
		$this->_method_title = $osC_Language->get ( 'payment_payu_method_title' );
		$this->_status = (defined ( 'MODULE_PAYMENT_PAYU_STATUS' ) && (MODULE_PAYMENT_PAYU_STATUS == '1') ? true : false);
		$this->_sort_order = (defined ( 'MODULE_PAYMENT_PAYU_SORT_ORDER' ) ? MODULE_PAYMENT_PAYU_SORT_ORDER : null);
	}
	
	/**
	 * Checks to see if the module has been installed
	 *
	 * @access public
	 * @return boolean
	 */
	function isInstalled() {
		return ( bool ) defined ( 'MODULE_PAYMENT_PAYU_STATUS' );
	}
	
	/**
	 * Installs the module
	 *
	 * @access public
	 * @see osC_Payment_Admin::install()
	 */
	function install() {
		global $osC_Database;
		
		parent::install ();
		
		$osC_Database->simpleQuery ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Enable PayU Module', 'MODULE_PAYMENT_PAYU_STATUS', '-1', 'Do you want to accept PayU payments?', '6', '1', 'osc_cfg_use_get_boolean_value', 'osc_cfg_set_boolean_value(array(1, -1))', now())" );
		$osC_Database->simpleQuery ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('POS ID', 'MODULE_PAYMENT_PAYU_POS_ID', '', 'POS ID given by PayU.', '6', '2', now())" );
		$osC_Database->simpleQuery ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('KEY #1', 'MODULE_PAYMENT_PAYU_KEY_1', '', 'First key given by PayU.', '6', '3', now())" );
		$osC_Database->simpleQuery ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('KEY #2', 'MODULE_PAYMENT_PAYU_KEY_2', '', 'Second key given by PayU.', '6', '4', now())" );
		$osC_Database->simpleQuery ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('POS Authentication Key', 'MODULE_PAYMENT_PAYU_POS_AUTH_KEY', '', 'POS Authentication Key given by PayU.', '6', '5', now())" );
		$osC_Database->simpleQuery ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display', 'MODULE_PAYMENT_PAYU_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '6', now())" );
	}
	
	/**
	 * Return the configuration parameter keys in an array
	 *
	 * @access public
	 * @return array
	 */
	function getKeys() {
		if (! isset ( $this->_keys )) {
			$this->_keys = array (
					'MODULE_PAYMENT_PAYU_STATUS',
					'MODULE_PAYMENT_PAYU_POS_ID',
					'MODULE_PAYMENT_PAYU_KEY_1',
					'MODULE_PAYMENT_PAYU_KEY_2',
					'MODULE_PAYMENT_PAYU_POS_AUTH_KEY',
					'MODULE_PAYMENT_PAYU_SORT_ORDER' 
			);
		}
		
		return $this->_keys;
	}
}
?>

