<?php
class osC_Payment_payu extends osC_Payment {
	var $_title, $_code = 'payu', $_status = false, $_sort_order, $_order_id;
	static $public_key_cache = array ();
	function osC_Payment_payu() {
		global $osC_Database, $osC_Language, $osC_ShoppingCart;
		
		$this->_title = $osC_Language->get ( 'payment_payu_title' );
		$this->_method_title = $osC_Language->get ( 'payment_payu_method_title' );
		$this->_status = (MODULE_PAYMENT_PAYU_STATUS == '1') ? true : false;
		$this->_sort_order = MODULE_PAYMENT_PAYU_SORT_ORDER;
		
		$this->form_action_url = 'https://secure.payu.com/paygw/UTF/NewPayment';
		
		$osC_Language->load ( 'modules-payment' );
	}
	
	// Select payment method
	function selection() {
		return array (
				'id' => $this->_code,
				'module' => $this->_method_title 
		);
	}
	
	// Save transaction id
	function confirmation() {
		$this->_order_id = osC_Order::insert ();
	}
	
	// Set variables for provider
	function process_button() {
		global $osC_ShoppingCart, $osC_Customer;
		
		$params = array (
				'pos_id' => MODULE_PAYMENT_PAYU_POS_ID,
				'pos_auth_key' => MODULE_PAYMENT_PAYU_POS_AUTH_KEY,
				'session_id' => md5 ( $this->_order_id . $osC_ShoppingCart->getTotal () ),
				'amount' => $osC_ShoppingCart->getTotal (),
				'desc' => STORE_NAME,
				'order_id' => $this->_order_id,
				'first_name' => $osC_ShoppingCart->getBillingAddress ( 'firstname' ),
				'last_name' => $osC_ShoppingCart->getBillingAddress ( 'lastname' ),
				'email' => $osC_Customer->getEmailAddress (),
				'client_ip' => $_SERVER ['REMOTE_ADDR'] 
		);
		
		$process_button_string = '';
		foreach ( $params as $key => $value ) {
			$key = trim ( $key );
			$value = trim ( $value );
			$process_button_string .= osc_draw_hidden_field ( $key, $value );
			$process_button_string .= "\n";
		}
		
		return $process_button_string;
	}
	
	// Receive callback
	// app.antczak.org/tomato/checkout.php?callback&module=payu&status=error&errorCode=%error%&orderId=%orderId%
	function callback() {
		global $osC_ShoppingCart, $osC_Currencies, $osC_Language, $osC_Tax, $messageStack, $osC_Database;
		
		switch ($_GET ['status']) {
			case "error" :
				$error_message = $osC_Language->get ( 'payment_payu_error' ) . $this->_getErrorMessage ( $_GET ['errorCode'] );
				osC_Order::process ( $_GET ['orderId'], ORDERS_STATUS_CANCELLED, $error_message );
				$messageStack->add_session ( 'shopping_cart', $error_message, 'error' );
				osc_redirect ( osc_href_link ( FILENAME_CHECKOUT, 'cart', 'SSL', null, null, true ) );
				break;
			case "ok" :
				osC_Order::process ( $_GET ['orderId'], ORDERS_STATUS_PREPARING, $osC_Language->get ( 'payment_payu_initially_confirmed' ) );
				$osC_ShoppingCart->reset ( true );
				osc_redirect ( osc_href_link ( FILENAME_CHECKOUT, 'success', 'SSL' ) );
				break;
			case "update" :
				echo "OK";
				break;
		}
	}
	function process() {
	}
	function _getErrorMessage($errorCode) {
		if (! isset ( $this->_error_messages )) {
			$this->_loadErrorMessages ();
		}
		
		return $this->_error_messages [$errorCode];
	}
	function _loadErrorMessages() {
		$this->_error_messages = array (
				'100' => 'brak lub błędna wartość parametru pos_id',
				'101' => 'brak parametru session_id',
				'102' => 'brak parametru ts',
				'103' => 'brak lub błędna wartość parametru sig',
				'104' => 'brak parametru desc',
				'105' => 'brak parametru client_ip',
				'106' => 'brak parametru first_name',
				'107' => 'brak parametru last_name',
				'108' => 'brak parametru street',
				'109' => 'brak parametru city',
				'110' => 'brak parametru post_code',
				'111' => 'brak parametru amount (lub/oraz amount_netto dla usługi SMS)',
				'112' => 'błędny numer konta bankowego',
				'113' => 'brak parametru email',
				'114' => 'brak numeru telefonu',
				'200' => 'inny chwilowy błąd',
				'201' => 'inny chwilowy błąd bazy danych',
				'202' => 'POS o podanym identyfikatorze jest zablokowany',
				'203' => 'niedozwolona wartość pay_type dla danego parametru pos_id',
				'204' => 'podana metoda płatności (wartość pay_type) jest chwilowo zablokowana dla danego parametru pos_id, np. przerwa konserwacyjna bramki płatniczej',
				'205' => 'kwota transakcji mniejsza od wartości minimalnej',
				'206' => 'kwota transakcji większa od wartości maksymalnej',
				'207' => 'przekroczona wartość wszystkich transakcji dla jednego klienta w ostatnim przedziale czasowym',
				'208' => 'POS działa w wariancie ExpressPayment lecz nie nastąpiła aktywacja tego wariantu współpracy (czekamy na zgodę działu obsługi klienta)',
				'209' => 'błędny numer pos_id lub pos_auth_key',
				'211' => 'nieprawidłowa waluta transakcji',
				'212' => 'próba utworzenia transakcji częściej niż raz na minutę - dla nieaktywnej firmy',
				'500' => 'transakcja nie istnieje',
				'501' => 'brak autoryzacji dla danej transakcji',
				'502' => 'transakcja rozpoczęta wcześniej',
				'503' => 'autoryzacja do transakcji była już przeprowadzana',
				'504' => 'transakcja anulowana wcześniej',
				'505' => 'transakcja przekazana do odbioru wcześniej',
				'506' => 'transakcja już odebrana',
				'507' => 'błąd podczas zwrotu środków do Klienta',
				'508' => 'Klient zrezygnował z płatności',
				'599' => 'błędny stan transakcji, np. nie można uznać transakcji kilka razy lub inny, prosimy o kontakt',
				'999' => 'inny błąd krytyczny - prosimy o kontakt',
				'777' => 'utworzenie transakcji spowoduje przekroczenie limitu transakcji dla firmy w trakcie weryfikacji, weryfikacja odbędzie się w ciągu jednego dnia roboczego' 
		);
	}
}

?>
