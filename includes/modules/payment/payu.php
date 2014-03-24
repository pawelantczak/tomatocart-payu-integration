<?php

require_once 'Hashids/Hashids.php';

class osC_Payment_payu extends osC_Payment {

    var $_title, $_code = 'payu', $_order_id, $_callback_url, $_new_payment, $_get_payment;

    function osC_Payment_payu() {
        global $osC_Language;

        $this->_title = $osC_Language->get('payment_payu_title');
        $this->_method_title = $osC_Language->get('payment_payu_method_title');
        $this->_status = (MODULE_PAYMENT_PAYU_STATUS == '1') ? true : false;

        $this->_new_payment = 'NewPayment';
        $this->_get_payment = 'Payment/get';
        $this->_callback_url = 'https://secure.payu.com/paygw/UTF/';
        $this->form_action_url = $this->_callback_url . $this->_new_payment;

        $osC_Language->load('modules-payment');
    }

    function selection() {
        return array(
            'id' => $this->_code,
            'module' => $this->_method_title
        );
    }

    function confirmation() {
        $this->_order_id = osC_Order::insert();
    }

    function process_button() {
        global $osC_ShoppingCart, $osC_Customer;

        $params = array(
            'pos_id' => MODULE_PAYMENT_PAYU_POS_ID,
            'pos_auth_key' => MODULE_PAYMENT_PAYU_POS_AUTH_KEY,
            'session_id' => base64_encode($this->_order_id),
            'amount' => number_format($osC_ShoppingCart->getTotal(), 2, '', ''),
            'desc' => STORE_NAME,
            'order_id' => $this->_order_id,
            'first_name' => $osC_ShoppingCart->getBillingAddress('firstname'),
            'last_name' => $osC_ShoppingCart->getBillingAddress('lastname'),
            'email' => $osC_Customer->getEmailAddress(),
            'client_ip' => $_SERVER ['REMOTE_ADDR']
        );

        $process_button_string = '';
        foreach ($params as $key => $value) {
            $key = trim($key);
            $value = trim($value);
            $process_button_string .= osc_draw_hidden_field($key, $value) . "\n";
        }
        return $process_button_string;
    }

    function callback() {
        global $osC_ShoppingCart, $osC_Language, $messageStack;

        switch ($_GET ['action']) {
            case "error" :
                $order_id = $_GET['order_id'];
                $error_code = $_GET['error_code'];
                $error_message = $osC_Language->get('payment_payu_error') . ' ' . $this->_getErrorMessage($error_code);
                osC_Order::process($order_id, ORDERS_STATUS_CANCELLED, $error_message);
                $messageStack->add_session('shopping_cart', $error_message, 'error');

                osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'cart', 'SSL', null, null, true));

                break;
            case "ok" :
                $order_id = $_GET['order_id'];
                $osC_ShoppingCart->reset(true);

                osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'success', 'SSL'));

                break;
            case "update" :
                $session_id = $_POST['session_id'];
                $order_id = base64_decode($session_id);
                $order_id = $order_id[0];

                $response = $this->_getTransactionDetails($session_id);

                if ($response['status'] == 'OK') {

                    $transaction_status = $response['trans']['status'];
                    $transaction_desc = $osC_Language->get('payment_payu_payment_status') . ' ' . $this->_getTransactionStatus($transaction_status);

                    switch ($transaction_status) {
                        case 99:
                            osC_Order::process($order_id, ORDERS_STATUS_PAID, $transaction_desc);
                            break;
                        case 1:
                        case 4:
                        case 5:
                            osC_Order::process($order_id, ORDERS_STATUS_PROCESSING, $transaction_desc);
                            break;
                        case 2:
                        case 3:
                        case 7:
                        case 888:
                            osC_Order::process($order_id, ORDERS_STATUS_CANCELLED, $transaction_desc);
                    }
                }

                echo "OK";

                break;
        }
    }

    function _getTransactionDetails($session_id) {
        $time_stamp = time();
        $sig = md5(MODULE_PAYMENT_PAYU_POS_ID . $session_id . $time_stamp . MODULE_PAYMENT_PAYU_KEY_1);

        $fields = array('pos_id' => MODULE_PAYMENT_PAYU_POS_ID,
            'session_id' => $session_id,
            'ts' => $time_stamp,
            sig => $sig);

        $post_fields = '';
        foreach ($fields as $key => $value) {
            $post_fields .= $key . "=" . $value . "&";
        }

        $options = array(
            CURLOPT_SSL_VERIFYHOST => 1,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $post_fields
        );

        $ch = curl_init($this->_callback_url . $this->_get_payment);

        curl_setopt_array($ch, $options);

        $content = curl_exec($ch);

        curl_close($ch);

        $xml = simplexml_load_string($content);
        $json = json_encode($xml);
        $response = json_decode($json, TRUE);

        return $response;
    }

    function _getTransactionStatus($transaction_status) {
        if (!isset($this->_transaction_statuses)) {
            $this->_loadTransactionStatuses();
        }

        return $this->_transaction_statuses[$transaction_status];
    }

    function _loadTransactionStatuses() {
        $this->_transaction_statuses = array(
            '1' => 'nowa',
            '2' => 'anulowana',
            '3' => 'odrzucona',
            '4' => 'rozpoczęta',
            '5' => 'oczekuje na odbiór',
            '7' => 'płatność zwrócona, otrzymano środki od klienta po wcześniejszym anulowaniu transakcji, lub nie było możliwości zwrotu środków w sposób automatyczny, sytuacje takie będą monitorowane i wyjaśniane przez zespół PayU',
            '99' => 'płatność odebrana - zakończona',
            '888' => 'błędny status - prosimy o kontakt'
        );
    }

    function _getErrorMessage($errorCode) {
        if (!isset($this->_error_messages)) {
            $this->_loadErrorMessages();
        }

        return $this->_error_messages[$errorCode];
    }

    function _loadErrorMessages() {
        $this->_error_messages = array(
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