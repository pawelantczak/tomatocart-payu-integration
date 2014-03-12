<?php

  class osC_Payment_payu extends osC_Payment {
    
    var $_title,
        $_code = 'payu',
        $_status = false,
        $_sort_order,
        $_order_id;
          
    static $public_key_cache = array();    
  
    function osC_Payment_payu() {
      global $osC_Database, $osC_Language, $osC_ShoppingCart;
  
      $this->_title = $osC_Language->get('payment_payu_title');
      $this->_method_title = $osC_Language->get('payment_payu_method_title');
      $this->_status = (MODULE_PAYMENT_PAYU_STATUS == '1') ? true : false;
      $this->_sort_order = MODULE_PAYMENT_PAYU_SORT_ORDER;
  
      $this->form_action_url = 'https://www.platnosci.pl/paygw/UTF/NewPayment';
        
    }
  
  }
?>
