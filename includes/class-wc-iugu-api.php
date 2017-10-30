<?php
/**
 * Iugu API
 *
 * @package Iugu_WooCommerce\Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API class.
 */
class WC_Iugu_API {

	/**
	 * API URL.
	 *
	 * @var string
	 */
	protected $api_url = 'https://api.iugu.com/v1/';

	/**
	 * JS Library URL.
	 *
	 * @var string
	 */
	protected $js_url = 'https://js.iugu.com/v2.js';

	/**
	 * Gateway class.
	 *
	 * @var WC_Payment_Gateway
	 */
	protected $gateway;

	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $method = '';

	/**
	 * Logger.
	 *
	 * @var WC_Logger
	 */
	protected $logger = null;

	/**
	 * Constructor.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway.
	 * @param string             $method  Method name.
	 */
	public function __construct( $gateway = null, $method = '' ) {
		$this->gateway = $gateway;
		$this->method  = $method;
	}

	/**
	 * Get API URL.
	 *
	 * @return string
	 */
	public function get_api_url() {
		return $this->api_url;
	}

	/**
	 * Get JS Library URL.
	 *
	 * @return string
	 */
	public function get_js_url() {
		return $this->js_url;
	}

	/**
	 * Get Iugu credit card interest rates.
	 *
	 * @return array
	 */
	public function get_interest_rate() {
		$rates = apply_filters( 'iugu_woocommerce_interest_rates', array(
			'2'  => 10,
			'3'  => 11,
			'4'  => 12,
			'5'  => 13,
			'6'  => 15,
			'7'  => 16,
			'8'  => 17,
			'9'  => 18,
			'10' => 20,
			'11' => 21,
			'12' => 22,
		) );

		return $rates;
	}

	/**
	 * Get transaction rate.
	 *
	 * @return float
	 */
	public function get_transaction_rate() {
		$rate = isset( $this->gateway->transaction_rate ) ? $this->gateway->transaction_rate : 7;

		return wc_format_decimal( $rate );
	}

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	public function using_supported_currency() {
		return 'BRL' === get_woocommerce_currency();
	}

	/**
	 * Check if order contains subscriptions.
	 *
	 * @param  int $order_id Order ID.
	 *
	 * @return bool
	 */
	public function order_contains_subscription( $order_id ) {
		return wcs_order_contains_subscription( $order_id ) || wcs_order_contains_resubscribe( $order_id );
	}

	/**
	 * Check if order contains pre-orders.
	 *
	 * @param  int $order_id Order ID.
	 *
	 * @return bool
	 */
	public function order_contains_pre_order( $order_id ) {
		return class_exists( 'WC_Pre_Orders_Order' ) && WC_Pre_Orders_Order::order_contains_pre_order( $order_id );
	}

	/**
	 * Only numbers.
	 *
	 * @param  string|int $string string to extract numbers.
	 *
	 * @return string|int
	 */
	protected function only_numbers( $string ) {
		return preg_replace( '([^0-9])', '', $string );
	}

	/**
	 * Send email notification.
	 *
	 * @param string $subject Email subject.
	 * @param string $title   Email title.
	 * @param string $message Email message.
	 */
	public function send_email( $subject, $title, $message ) {
		$mailer = WC()->mailer();
		$mailer->send( get_option( 'admin_email' ), $subject, $mailer->wrap_message( $title, $message ) );
	}

	/**
	 * Get customer payment method id
	 *
	 * @return string Payment Method Id
	 */
	public function get_customer_payment_method_id() {
		// @codingStandardsIgnoreStart
		$customer_id = get_user_meta( get_current_user_id(), '_iugu_customer_id', true );
		// @codingStandardsIgnoreEnd
		$endpoint    = 'customers/' . $customer_id;
		$response    = $this->do_request( $endpoint, 'GET' );
		$data        = isset( $response['body'] ) ? json_decode( $response['body'], true ) : array();

		return isset( $data['default_payment_method_id'] ) ? $data['default_payment_method_id'] : '';
	}

	/**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level   Log level.
	 *                        Options: 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'.
	 *                        Defaults to 'info'.
	 */
	public function log( $message, $level = 'info' ) {
		if ( 'yes' === $this->gateway->debug ) {
			if ( empty( $this->logger ) ) {
				$this->logger = wc_get_logger();
			}

			$this->logger->log( $level, $message, array(
				'source' => $this->gateway->id,
			) );
		}
	}

	/**
	 * Do requests in the Iugu API.
	 *
	 * @param  string $endpoint API Endpoint.
	 * @param  string $method   Request method.
	 * @param  array  $data     Request data.
	 * @param  array  $headers  Request headers.
	 *
	 * @return array            Request response.
	 */
	protected function do_request( $endpoint, $method = 'POST', $data = array(), $headers = array() ) {
		$params = array(
			'method'    => $method,
			'sslverify' => false,
			'timeout'   => 60,
			'headers'    => array(
				'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
				'Authorization' => 'Basic ' . base64_encode( $this->gateway->api_token . ':x' ),
			),
		);

		if ( ! empty( $data ) ) {
			$params['body'] = $data;
		}

		if ( ! empty( $headers ) ) {
			$params['headers'] = $headers;
		}

		return wp_remote_post( $this->get_api_url() . $endpoint, $params );
	}

	/**
	 * Build the API params from an array.
	 *
	 * @param  array  $data   Purchase data.
	 * @param  string $prefix Param prefix.
	 *
	 * @return string
	 */
	protected function build_api_params( $data, $prefix = null ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$params = array();

		foreach ( $data as $key => $value ) {
			if ( is_null( $value ) ) {
				continue;
			}

			if ( $prefix && $key && ! is_int( $key ) ) {
				$key = $prefix . '[' . $key . ']';
			} elseif ( $prefix ) {
				$key = $prefix . '[]';
			}

			if ( is_array( $value ) ) {
				$params[] = $this->build_api_params( $value, $key );
			} else {
				$params[] = $key . '=' . rawurlencode( $value );
			}
		}

		return implode( '&', $params );
	}

	/**
	 * Value in cents.
	 *
	 * @param  float $value Value.
	 * @return int
	 */
	protected function get_cents( $value ) {
		return number_format( $value, 2, '', '' );
	}

	/**
	 * Get phone number
	 *
	 * @param  WC_Order $order Order instance.
	 *
	 * @return string
	 */
	protected function get_phone_number( $order ) {
		$phone_number = $this->only_numbers( $order->billing_phone );

		return array(
			'area_code' => substr( $phone_number, 0, 2 ),
			'number'    => substr( $phone_number, 2 ),
		);
	}

	/**
	 * Get CPF or CNPJ.
	 *
	 * @param  WC_Order $order Order instance.
	 *
	 * @return string
	 */
	protected function get_cpf_cnpj( $order ) {
		$wcbcf_settings = get_option( 'wcbcf_settings' );
		$person_type    = intval( $wcbcf_settings['person_type'] );

		if ( 0 !== $person_type ) {
			if ( ( 1 === $person_type && 1 === intval( $order->billing_persontype ) ) || 2 === $person_type ) {
				return $this->only_numbers( $order->billing_cpf );
			}

			if ( ( 1 === $person_type && 2 === intval( $order->billing_persontype ) ) || 3 === $person_type ) {
				return $this->only_numbers( $order->billing_cnpj );
			}
		}

		return '';
	}

	/**
	 * Check if the customer is a "company".
	 *
	 * @param  WC_Order $order Order instance.
	 *
	 * @return bool
	 */
	protected function is_a_company( $order ) {
		$wcbcf_settings = get_option( 'wcbcf_settings' );

		if ( ( '1' === $wcbcf_settings['person_type'] && '2' === $order->billing_persontype ) || '3' === $wcbcf_settings['person_type'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the invoice due date.
	 *
	 * @return string
	 */
	protected function get_invoice_due_date() {
		$days = 'credit-card' !== $this->method ? intval( $this->gateway->deadline ) : 1;

		return date( 'd-m-Y', strtotime( '+' . $days . ' day' ) );
	}

	/**
	 * Get the invoice data.
	 *
	 * @param  WC_Order $order Order instance.
	 *
	 * @return array
	 */
	protected function get_invoice_data( $order ) {
		$items        = array();
		$phone_number = $this->get_phone_number( $order );
		$data         = array(
			'email'            => $order->billing_email,
			'due_date'         => $this->get_invoice_due_date(),
			'return_url'       => $this->gateway->get_return_url( $order ),
			'expired_url'      => str_replace( '&#038;', '&', $order->get_cancel_order_url() ),
			'notification_url' => WC()->api_request_url( get_class( $this->gateway ) ),
			'ignore_due_email' => true,
			'payable_with'     => 'credit-card' === $this->method ? 'credit_card' : 'bank_slip',
			'custom_variables' => array(
				array(
					'name'  => 'order_id',
					'value' => $order->id,
				),
			),
			'payer'      => array(
				'name'         => $order->billing_first_name . ' ' . $order->billing_last_name,
				'phone_prefix' => $phone_number['area_code'],
				'phone'        => $phone_number['number'],
				'email'        => $order->billing_email,
				'address'      => array(
					'street'   => $order->billing_address_1,
					'number'   => $order->billing_number,
					'city'     => $order->billing_city,
					'state'    => $order->billing_state,
					'country'  => isset( WC()->countries->countries[ $order->billing_country ] ) ? WC()->countries->countries[ $order->billing_country ] : $order->billing_country,
					'zip_code' => $this->only_numbers( $order->billing_postcode ),
				),
			),
		);

		$cpf_cnpj = $this->get_cpf_cnpj( $order );
		if ( $cpf_cnpj ) {
			$data['payer']['cpf_cnpj'] = $cpf_cnpj;
		}

		if ( $this->is_a_company( $order ) ) {
			$data['payer']['name'] = $order->billing_company;
		}

		// Force only one item.
		if ( 'yes' === $this->gateway->send_only_total ) {
			$items[] = array(
				/* translators: %s: order number */
				'description' => sprintf( __( 'Order %s', 'iugu-woocommerce' ), $order->get_order_number() ),
				'price_cents' => $this->get_cents( $order->get_total() ),
				'quantity'    => 1,
			);
		} else {
			// Products.
			if ( 0 < count( $order->get_items() ) ) {
				foreach ( $order->get_items() as $order_item ) {
					if ( $order_item['qty'] ) {
						$item_total = $this->get_cents( $order->get_item_total( $order_item, false ) );

						if ( 0 > $item_total ) {
							continue;
						}

						$item_name = $order_item['name'];
						$item_meta = new WC_Order_Item_Meta( $order_item['item_meta'] );

						$meta = $item_meta->display( true, true );
						if ( $meta ) {
							$item_name .= ' - ' . $meta;
						}

						$items[] = array(
							'description' => $item_name,
							'price_cents' => $item_total,
							'quantity'    => $order_item['qty'],
						);
					}
				}
			}

			// Fees.
			if ( 0 < count( $order->get_fees() ) ) {
				foreach ( $order->get_fees() as $fee ) {
					$fee_total = $this->get_cents( $fee['line_total'] );

					if ( 0 > $fee_total ) {
						continue;
					}

					$items[] = array(
						'description' => $fee['name'],
						'price_cents' => $fee_total,
						'quantity'    => 1,
					);
				}
			}

			// Taxes.
			if ( 0 < count( $order->get_taxes() ) ) {
				foreach ( $order->get_taxes() as $tax ) {
					$tax_total = $this->get_cents( $tax['tax_amount'] + $tax['shipping_tax_amount'] );

					if ( 0 > $tax_total ) {
						continue;
					}

					$items[] = array(
						'description' => $tax['label'],
						'price_cents' => $tax_total,
						'quantity'    => 1,
					);
				}
			}

			// Shipping Cost.
			if ( method_exists( $order, 'get_total_shipping' ) ) {
				$shipping_cost = $this->get_cents( $order->get_total_shipping() );
			} else {
				$shipping_cost = $this->get_cents( $order->get_shipping() );
			}

			if ( 0 < $shipping_cost ) {
				$items[] = array(
					/* translators: %s: shipping method name */
					'description' => sprintf( __( 'Shipping via %s', 'iugu-woocommerce' ), $order->get_shipping_method() ),
					'price_cents' => $shipping_cost,
					'quantity'    => 1,
				);
			}
		}

		$data['items'] = $items;

		$data = apply_filters( 'iugu_woocommerce_invoice_data', $data );

		return $data;
	}

	/**
	 * Create an invoice.
	 *
	 * @param  WC_Order $order Order data.
	 *
	 * @return string          Invoice ID.
	 */
	protected function create_invoice( $order ) {
		$invoice_data = $this->get_invoice_data( $order );

		$this->log( 'Creating an invoice on Iugu for order ' . $order->get_order_number() . ' with the following data: ' . wc_print_r( $invoice_data, true ) );

		$invoice_data = $this->build_api_params( $invoice_data );
		$response     = $this->do_request( 'invoices', 'POST', $invoice_data );

		if ( is_wp_error( $response ) ) {
			$this->log( 'WP_Error while trying to generate an invoice: ' . $response->get_error_message() );
		} elseif ( 200 === intval( $response['response']['code'] ) && 'OK' === $response['response']['message'] ) {
			$invoice = json_decode( $response['body'], true );
			$this->log( 'Invoice created successfully!' );

			return $invoice['id'];
		}

		$this->log( 'Error while generating the invoice for order ' . $order->get_order_number() . ': ' . wc_print_r( $response, true ) );

		return '';
	}

	/**
	 * Get invoice status.
	 *
	 * @param  string $invoice_id Invoice ID.
	 *
	 * @return string
	 */
	public function get_invoice_status( $invoice_id ) {
		$this->log( 'Getting invoice status from Iugu. Invoice ID: ' . $invoice_id );

		$response = $this->do_request( 'invoices/' . $invoice_id, 'GET' );

		if ( is_wp_error( $response ) ) {
			$this->log( 'WP_Error while trying to get an invoice status: ' . $response->get_error_message() );
		} elseif ( 200 === intval( $response['response']['code'] ) && 'OK' === $response['response']['message'] ) {
			$invoice = json_decode( $response['body'], true );
			$this->log( 'Invoice status recovered successfully!' );

			return sanitize_text_field( $invoice['status'] );
		}

		$this->log( 'Error while getting the invoice status. Invoice ID: ' . $invoice_id . '. Response: ' . wc_print_r( $response, true ) );

		return '';
	}

	/**
	 * Get charge data.
	 *
	 * @param  WC_Order $order  Order instance.
	 * @param  array    $posted Posted data.
	 *
	 * @return array
	 */
	protected function get_charge_data( $order, $posted = array() ) {
		$invoice_id = $this->create_invoice( $order );

		if ( '' === $invoice_id ) {
			$this->log( 'Error while doing the charge for order ' . $order->get_order_number() . ': Missing the invoice ID.' );

			return array();
		}

		$data = array(
			'invoice_id' => $invoice_id,
		);

		// Credit Card.
		if ( 'credit-card' === $this->method ) {
			if ( isset( $posted['iugu_token'] ) ) {
				// Credit card token.
				$data['token'] = sanitize_text_field( $posted['iugu_token'] );

				// Installments.
				if ( isset( $posted['iugu_card_installments'] ) && 1 < $posted['iugu_card_installments'] ) {
					$data['months'] = absint( $posted['iugu_card_installments'] );
				}
			}

			// Payment method ID.
			if ( isset( $posted['customer_payment_method_id'] ) ) {
				$data['customer_payment_method_id'] = $posted['customer_payment_method_id'];
			}
		}

		// Bank Slip.
		if ( 'bank-slip' === $this->method ) {
			$data['method'] = 'bank_slip';
		}

		$data = apply_filters( 'iugu_woocommerce_charge_data', $data );

		return $data;
	}

	/**
	 * Create Charge.
	 *
	 * @param  WC_Order $order  Order instance.
	 * @param  array    $posted Posted data.
	 *
	 * @return array
	 */
	public function create_charge( $order, $posted = array() ) {
		$this->log( 'Doing charge for order ' . $order->get_order_number() . '...' );

		$charge_data = $this->get_charge_data( $order, $posted );

		if ( empty( $charge_data ) ) {
			return array(
				'errors' => array(
					__( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'iugu-woocommerce' ),
				),
			);
		}

		$charge_data = $this->build_api_params( $charge_data );
		$response    = $this->do_request( 'charge', 'POST', $charge_data );

		if ( is_wp_error( $response ) ) {
			$this->log( 'WP_Error while trying to do a charge: ' . $response->get_error_message() );
		} elseif ( isset( $response['body'] ) && ! empty( $response['body'] ) ) {
			$charge = json_decode( $response['body'], true );
			$this->log( 'Charge created successfully!' );

			return $charge;
		}

		$this->log( 'Error while doing the charge for order ' . $order->get_order_number() . ': ' . wc_print_r( $response, true ) );

		return array(
			'errors' => array(
				__( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'iugu-woocommerce' ),
			),
		);
	}

	/**
	 * Create customer in Iugu API.
	 *
	 * @param  WC_Order $order Order data.
	 * @return string
	 */
	protected function create_customer( $order ) {
		$this->log( 'Creating customer...' );

		$data = array(
			'email'          => $order->billing_email,
			'name'           => trim( $order->billing_first_name . ' ' . $order->billing_last_name ),
			'set_as_default' => true,
		);

		$cpf_cnpj = $this->get_cpf_cnpj( $order );
		if ( $cpf_cnpj ) {
			$data['cpf_cnpj'] = $cpf_cnpj;
		}

		$data          = apply_filters( 'iugu_woocommerce_customer_data', $data, $order );
		$customer_data = $this->build_api_params( $data );
		$response      = $this->do_request( 'customers', 'POST', $customer_data );

		if ( is_wp_error( $response ) ) {
			$this->log( 'WP_Error while trying create a customer: ' . $response->get_error_message() );
		} elseif ( isset( $response['body'] ) && ! empty( $response['body'] ) ) {
			$customer = json_decode( $response['body'], true );
			$this->log( 'Customer created successfully!' );

			return $customer['id'];
		}

		$this->log( 'Error while creating the customer for order ' . $order->get_order_number() . ': ' . wc_print_r( $response, true ) );

		return '';
	}

	/**
	 * Get customer ID.
	 *
	 * @param  WC_Order $order Order data.
	 *
	 * @return string          Customer ID.
	 */
	public function get_customer_id( $order ) {
		$user_id = $order->get_user_id();

		// Try get a saved customer ID.
		if ( 0 < $user_id ) {
			// @codingStandardsIgnoreStart
			$customer_id = get_user_meta( $user_id, '_iugu_customer_id', true );
			// @codingStandardsIgnoreEnd

			if ( $customer_id ) {
				return $customer_id;
			}
		}

		// Create customer in Iugu.
		$customer_id = $this->create_customer( $order );

		// Save the customer ID.
		if ( 0 < $user_id ) {
			// @codingStandardsIgnoreStart
			update_user_meta( $user_id, '_iugu_customer_id', $customer_id );
			// @codingStandardsIgnoreEnd
		}

		return $customer_id;
	}

	/**
	 * Create a custom payment method.
	 *
	 * @param  WC_Order $order      Order data.
	 * @param  string   $card_token Credit card token.
	 *
	 * @return string               Payment method ID.
	 */
	public function create_customer_payment_method( $order, $card_token ) {
		$this->log( 'Creating customer payment method for order ' . $order->get_order_number() . '...' );

		$customer_id = $this->get_customer_id( $order );

		$data = array(
			'customer_id' => $customer_id,
			/* translators: %s: order number */
			'description' => sprintf( __( 'Payment method created for order %s', 'iugu-woocommerce' ), $order->get_order_number() ),
			'token'       => $card_token,
		);

		$data         = apply_filters( 'iugu_woocommerce_customer_payment_method_data', $data, $customer_id, $order );
		$payment_data = $this->build_api_params( $data );
		$response     = $this->do_request( 'customers/' . $customer_id . '/payment_methods', 'POST', $payment_data );

		if ( is_wp_error( $response ) ) {
			$this->log( 'WP_Error while trying create a customer payment method: ' . $response->get_error_message() );
		} elseif ( isset( $response['body'] ) && ! empty( $response['body'] ) ) {
			$payment_method = json_decode( $response['body'], true );
			$this->log( 'Customer payment method created successfully!' );

			return $payment_method['id'];
		}

		$this->log( 'Error while creating the customer payment method for order ' . $order->get_order_number() . ': ' . wc_print_r( $response, true ) );

		return '';
	}

	/**
	 * Process the payment.
	 *
	 * @param  int $order_id Order ID.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order  = wc_get_order( $order_id );
		$charge = $this->create_charge( $order, wp_unslash( $_POST ) ); // WPCS: CSRF ok, input var okay.

		if ( isset( $charge['errors'] ) && ! empty( $charge['errors'] ) ) {
			$errors = is_array( $charge['errors'] ) ? $charge['errors'] : array( $charge['errors'] );

			foreach ( $charge['errors'] as $error ) {
				if ( is_array( $error ) ) {
					foreach ( $error as $_error ) {
						wc_add_notice( '<strong>' . esc_html( $this->gateway->title ) . '</strong>: ' . $_error, 'error' );
					}
				} else {
					wc_add_notice( '<strong>' . esc_html( $this->gateway->title ) . '</strong>: ' . $error, 'error' );
				}
			}

			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}

		// Save transaction data.
		if ( 'bank-slip' === $this->method ) {
			$payment_data = array_map(
				'sanitize_text_field',
				array(
					'pdf' => $charge['pdf'],
				)
			);

			update_post_meta( $order->id, __( 'Iugu Bank Slip URL', 'iugu-woocommerce' ), $payment_data['pdf'] );
		} else {
			$payment_data = array_map(
				'sanitize_text_field',
				array(
					'installments' => isset( $_POST['iugu_card_installments'] ) ? wp_unslash( $_POST['iugu_card_installments'] ) : '1', // WPCS: CSRF ok, input var okay, sanitization ok.
				)
			);
		}

		update_post_meta( $order->id, '_iugu_wc_transaction_data', $payment_data );
		update_post_meta( $order->id, '_transaction_id', sanitize_text_field( $charge['invoice_id'] ) );

		WC()->cart->empty_cart();

		if ( 'bank-slip' === $this->method ) {
			$order->update_status( 'on-hold', __( 'Iugu: The customer generated a bank slip, awaiting payment confirmation.', 'iugu-woocommerce' ) );
		} else {
			if ( true === $charge['success'] ) {
				$order->add_order_note( __( 'Iugu: Invoice paid successfully by credit card.', 'iugu-woocommerce' ) );
				$order->payment_complete();
			} else {
				$order->update_status( 'failed', __( 'Iugu: Credit card declined.', 'iugu-woocommerce' ) );
			}
		}

		return array(
			'result'   => 'success',
			'redirect' => $this->gateway->get_return_url( $order ),
		);
	}

	/**
	 * Update order status.
	 *
	 * @param int    $order_id       Order ID.
	 * @param string $invoice_status Invoice status.
	 *
	 * @return bool
	 */
	protected function update_order_status( $order_id, $invoice_status ) {
		$order          = wc_get_order( $order_id );
		$invoice_status = strtolower( $invoice_status );
		$order_status   = $order->get_status();
		$order_updated  = false;

		$this->log( 'Iugu payment status for order ' . $order->get_order_number() . ' is now: ' . $invoice_status );

		switch ( $invoice_status ) {
			case 'pending':
				if ( ! in_array( $order_status, array( 'on-hold', 'processing', 'completed' ), true ) ) {
					if ( 'bank-slip' === $this->method ) {
						$order->update_status( 'on-hold', __( 'Iugu: The customer generated a bank slip, awaiting payment confirmation.', 'iugu-woocommerce' ) );
					} else {
						$order->update_status( 'on-hold', __( 'Iugu: Invoice paid by credit card, waiting for operator confirmation.', 'iugu-woocommerce' ) );
					}

					$order_updated = true;
				}

				break;
			case 'paid':
				if ( ! in_array( $order_status, array( 'processing', 'completed' ), true ) ) {
					$order->add_order_note( __( 'Iugu: Invoice paid successfully.', 'iugu-woocommerce' ) );

					// Changing the order for processing and reduces the stock.
					$order->payment_complete();
					$order_updated = true;
				}

				break;
			case 'canceled':
				$order->update_status( 'cancelled', __( 'Iugu: Invoice canceled.', 'iugu-woocommerce' ) );
				$order_updated = true;

				break;
			case 'partially_paid':
				$order->update_status( 'on-hold', __( 'Iugu: Invoice partially paid.', 'iugu-woocommerce' ) );
				$order_updated = true;

				break;
			case 'refunded':
				$order->update_status( 'refunded', __( 'Iugu: Invoice refunded.', 'iugu-woocommerce' ) );
				$this->send_email(
					/* translators: %s: order number */
					sprintf( esc_html__( 'Invoice for order %s was refunded', 'iugu-woocommerce' ), $order->get_order_number() ),
					__( 'Invoice refunded', 'iugu-woocommerce' ),
					/* translators: %s: order number */
					sprintf( esc_html__( 'Order %s has been marked as refunded by Iugu.', 'iugu-woocommerce' ), $order->get_order_number() )
				);
				$order_updated = true;

				break;
			case 'expired':
				$order->update_status( 'failed', __( 'Iugu: Invoice expired.', 'iugu-woocommerce' ) );
				$order_updated = true;

				break;
		}

		// Allow custom actions when update the order status.
		do_action( 'iugu_woocommerce_update_order_status', $order, $invoice_status, $order_updated );

		return $order_updated;
	}

	/**
	 * Payment notification handler.
	 */
	public function notification_handler() {
		ob_clean();

		if ( isset( $_REQUEST['event'] ) && isset( $_REQUEST['data']['id'] ) && 'invoice.status_changed' === sanitize_text_field( wp_unslash( $_REQUEST['event'] ) ) ) { // WPCS: CSRF ok, input var ok.
			global $wpdb;

			header( 'HTTP/1.1 200 OK' );

			$invoice_id = sanitize_text_field( wp_unslash( $_REQUEST['data']['id'] ) ); // WPCS: CSRF ok, input var ok.
			$order_id   = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_transaction_id' AND meta_value = '%s'", $invoice_id ) ); // WPCS: db call ok, cache ok.
			$order_id   = intval( $order_id );

			if ( $order_id ) {
				$invoice_status = $this->get_invoice_status( $invoice_id );

				if ( $invoice_status ) {
					$this->update_order_status( $order_id, $invoice_status );
					exit();
				}
			}
		}

		wp_die( esc_html__( 'The request failed!', 'iugu-woocommerce' ), esc_html__( 'The request failed!', 'iugu-woocommerce' ), array(
			'response' => 200,
		) );
	}
}
