<?php
class ControllerExtensionPaymentAlphacommercehub extends Controller {
	public function index() {
		$this->load->language('extension/payment/alphacommercehub');

		$data['text_testmode'] = $this->language->get('text_testmode');
		$data['button_confirm'] = $this->language->get('button_confirm');

		$data['testmode'] = $this->config->get('alphacommercehub_test');

		if (!$this->config->get('alphacommercehub_test')) {
			$data['action'] = 'https://hubuat.alphacommercehub.com.au/pp/bf8cb1c7-33c5-451b-aa48-498c76392f95';
		} else {
			$data['action'] = 'https://hubuat.alphacommercehub.com.au/pp/bf8cb1c7-33c5-451b-aa48-498c76392f95';
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		if ($order_info) {
			$data['business'] = $this->config->get('alphacommercehub_email');
			$data['item_name'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

			$data['products'] = array();

			foreach ($this->cart->getProducts() as $product) {
				$option_data = array();

				foreach ($product['option'] as $option) {
					if ($option['type'] != 'file') {
						$value = $option['value'];
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);
						
						if ($upload_info) {
							$value = $upload_info['name'];
						} else {
							$value = '';
						}
					}

					$option_data[] = array(
						'name'  => $option['name'],
						'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
					);
				}

				$data['products'][] = array(
					'name'     => htmlspecialchars($product['name']),
					'model'    => htmlspecialchars($product['model']),
					'price'    => $this->currency->format($product['price'], $order_info['currency_code'], false, false),
					'quantity' => $product['quantity'],
					'option'   => $option_data,
					'weight'   => $product['weight']
				);
			}

			$data['discount_amount_cart'] = 0;

			$total = $this->currency->format($order_info['total'] - $this->cart->getSubTotal(), $order_info['currency_code'], false, false);
$data['Amount'] = round($order_info['total']);

	if ($this->config->get('alphacommercehub_debug') == 1){
			$secureval = 'N';
			}
			else {
				$secureval = 'Y';
				}
				$data['secure'] = $secureval;
				
			if ($total > 0) {
				$data['products'][] = array(
					'name'     => $this->language->get('text_total'),
					'model'    => '',
					'price'    => $total,
					'quantity' => 1,
					'option'   => array(),
					'weight'   => 0
				);
			} else {
				$data['discount_amount_cart'] -= $total;
			}

			$data['currency_code'] = $order_info['currency_code'];
			$data['first_name'] = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8');
			$data['last_name'] = html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
			$data['address1'] = html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8');
			$data['address2'] = html_entity_decode($order_info['payment_address_2'], ENT_QUOTES, 'UTF-8');
			$data['city'] = html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8');
			$data['zip'] = html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');
			$data['country'] = $order_info['payment_iso_code_2'];
			$data['email'] = $order_info['email'];
			$data['invoice'] = $this->session->data['order_id'] . ' - ' . html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8') . ' ' . html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
			$data['lc'] = $this->session->data['language'];
			$data['return'] = $this->url->link('checkout/success');
			$data['notify_url'] = $this->url->link('extension/payment/alphacommercehub/callback', '', true);
			$data['cancel_return'] = $this->url->link('checkout/checkout', '', true);

			if (!$this->config->get('alphacommercehub_transaction')) {
				$data['paymentaction'] = 'authorization';
			} else {
				$data['paymentaction'] = 'sale';
			}

			$data['custom'] = $this->session->data['order_id'];

			return $this->load->view('extension/payment/alphacommercehub', $data);
		}
	}

	public function callback() {
		if (isset($this->request->post['custom'])) {
			$order_id = $this->request->post['custom'];
		} else {
			$order_id = 0;
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if ($order_info) {
			$request = 'cmd=_notify-validate';

			foreach ($this->request->post as $key => $value) {
				$request .= '&' . $key . '=' . urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
			}

			if (!$this->config->get('alphacommercehub_test')) {
				$curl = curl_init('https://hubuat.alphacommercehub.com.au/pp/bf8cb1c7-33c5-451b-aa48-498c76392f95');
			} else {
				$curl = curl_init('https://hubuat.alphacommercehub.com.au/pp/bf8cb1c7-33c5-451b-aa48-498c76392f95');
			}

			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_TIMEOUT, 30);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

			$response = curl_exec($curl);

			if (!$response) {
				$this->log->write('ALPHACOMMERCEHUB :: CURL failed ' . curl_error($curl) . '(' . curl_errno($curl) . ')');
			}

			if ($this->config->get('alphacommercehub_debug')) {
				$this->log->write('ALPHACOMMERCEHUB :: IPN REQUEST: ' . $request);
				$this->log->write('ALPHACOMMERCEHUB :: IPN RESPONSE: ' . $response);
			}

			if ((strcmp($response, 'VERIFIED') == 0 || strcmp($response, 'UNVERIFIED') == 0) && isset($this->request->post['payment_status'])) {
				$order_status_id = $this->config->get('config_order_status_id');

				switch($this->request->post['payment_status']) {
					case 'Canceled_Reversal':
						$order_status_id = $this->config->get('alphacommercehub_canceled_reversal_status_id');
						break;
					case 'Completed':
						$receiver_match = (strtolower($this->request->post['receiver_email']) == strtolower($this->config->get('alphacommercehub_email')));

						$total_paid_match = ((float)$this->request->post['mc_gross'] == $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false));

						if ($receiver_match && $total_paid_match) {
							$order_status_id = $this->config->get('alphacommercehub_completed_status_id');
						}
						
						if (!$receiver_match) {
							$this->log->write('ALPHACOMMERCEHUB :: RECEIVER EMAIL MISMATCH! ' . strtolower($this->request->post['receiver_email']));
						}
						
						if (!$total_paid_match) {
							$this->log->write('ALPHACOMMERCEHUB :: TOTAL PAID MISMATCH! ' . $this->request->post['mc_gross']);
						}
						break;
					case 'Denied':
						$order_status_id = $this->config->get('alphacommercehub_denied_status_id');
						break;
					case 'Expired':
						$order_status_id = $this->config->get('alphacommercehub_expired_status_id');
						break;
					case 'Failed':
						$order_status_id = $this->config->get('alphacommercehub_failed_status_id');
						break;
					case 'Pending':
						$order_status_id = $this->config->get('alphacommercehub_pending_status_id');
						break;
					case 'Processed':
						$order_status_id = $this->config->get('alphacommercehub_processed_status_id');
						break;
					case 'Refunded':
						$order_status_id = $this->config->get('alphacommercehub_refunded_status_id');
						break;
					case 'Reversed':
						$order_status_id = $this->config->get('alphacommercehub_reversed_status_id');
						break;
					case 'Voided':
						$order_status_id = $this->config->get('alphacommercehub_voided_status_id');
						break;
				}

				$this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
			} else {
				$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('config_order_status_id'));
			}

			curl_close($curl);
		}
	}
}
