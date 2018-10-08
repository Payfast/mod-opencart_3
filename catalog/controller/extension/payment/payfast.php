<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */

class ControllerExtensionPaymentPayFast extends Controller
{
    var $pfHost = '';

    function __construct( $registry )
    {
        parent::__construct( $registry );
        $this->pfHost = ( $this->config->get( 'payment_payfast_sandbox' ) ? 'sandbox' : 'www' ) . '.payfast.co.za';

    }

    public function index()
    {
        $this->load->language( 'extension/payment/payfast' );

        $data[ 'text_sandbox' ] = $this->language->get( 'text_sandbox' );

        $data[ 'button_confirm' ] = $this->language->get( 'button_confirm' );

        $data[ 'sandbox' ] = $this->config->get( 'payment_payfast_sandbox' );

        $data[ 'action' ] = 'https://' . $this->pfHost . '/eng/process';

        $this->load->model( 'checkout/order' );

        $order_info = $this->model_checkout_order->getOrder( $this->session->data[ 'order_id' ] );

        if ( $order_info )
        {
            $order_info['currency_code'] = 'ZAR';

            $data['recurring'] = false;
            foreach ( $this->cart->getProducts() as $product )
            {
                if ( $product['recurring'] )
                {
                    $data['recurring'] = true;

                    if ( $product['recurring']['frequency'] == 'month' )
                    {
                        $frequency = 3;
                    }

                    if ( $product['recurring']['frequency'] == 'year' )
                    {
                        $frequency = 6;
                    }

                    $cycles = $product['recurring']['duration'];

                    $recurring_amount = $product['recurring']['price'];

                    $custom_str3 = $product['recurring']['recurring_id'];

                    $custom_str4 = $this->session->data[ 'order_id' ];

                    $custom_str5 = $product['product_id'];

                    $this->db->query("INSERT INTO `" . DB_PREFIX . "order_recurring` SET `order_id` = '" . $this->session->data[ 'order_id' ] . "', `reference` = '" . $this->session->data[ 'order_id' ] . "', `product_id` = '" . $product['product_id'] . "',
                     `product_name` = '" . $product['name'] ."', `product_quantity` = '" . $product['quantity'] . "', `recurring_id` = '" . $product['recurring']['recurring_id'] . "',
                      `recurring_name` = '" . $product['recurring']['name'] . "', `recurring_description` = '" . $product['recurring']['name'] . "',
                      `recurring_frequency` = '" . $frequency . "', `recurring_cycle` = '1', `recurring_duration` = '" . $cycles . "',
                      `recurring_price` = '" . $recurring_amount . "', `status` = '6', `date_added` = NOW()");
                }
            }

            if ( !$this->config->get( 'payment_payfast_sandbox' ) )
            {
                $merchant_id = $this->config->get( 'payment_payfast_merchant_id' );
                $merchant_key = $this->config->get( 'payment_payfast_merchant_key' );

            }
            else
            {
                $merchant_id = '10000100';
                $merchant_key = '46f0cd694581a';
            }

            $return_url = $this->url->link( 'checkout/success' );
            $cancel_url = $this->url->link( 'checkout/checkout', '', 'SSL' );
            $notify_url = $this->url->link( 'extension/payment/payfast/callback', '', 'SSL' );
            $name_first = html_entity_decode( $order_info[ 'payment_firstname' ], ENT_QUOTES, 'UTF-8' );
            $name_last = html_entity_decode( $order_info[ 'payment_lastname' ], ENT_QUOTES, 'UTF-8' );
            $email_address = $order_info[ 'email' ];
            $m_payment_id = $this->session->data[ 'order_id' ];
            $amount = $this->currency->format( $order_info[ 'total' ], $order_info[ 'currency_code' ], '', false );
            $item_name = $this->config->get( 'config_name' ) . ' - #' . $this->session->data[ 'order_id' ];
            $item_description = $this->language->get( 'text_sale_description' );
            $custom_str1 = $this->session->data[ 'order_id' ];

            $payArray = array(
                'merchant_id' => $merchant_id, 'merchant_key' => $merchant_key, 'return_url' => $return_url,
                'cancel_url' => $cancel_url, 'notify_url' => $notify_url, 'name_first' => $name_first,
                'name_last' => $name_last, 'email_address' => $email_address, 'm_payment_id' => $m_payment_id,
                'amount' => $amount, 'item_name' => html_entity_decode( $item_name ),
                'item_description' => html_entity_decode( $item_description ), 'custom_str1' => $custom_str1
            );

            if ( $data['recurring'] )
            {
                $payArray['custom_str2'] = date( 'Y-m-d' );
                $payArray['custom_str3'] = $custom_str3;
                $payArray['custom_str4'] = $custom_str4;
                $payArray['custom_str5'] = $custom_str5;
                $payArray['subscription_type'] = '1';
                $payArray['billing_date'] = date( 'Y-m-d' );
                $payArray['recurring_amount'] = $recurring_amount;
                $payArray['frequency'] = $frequency;
                $payArray['cycles'] = $cycles;
            }

            $secureString = '';
            foreach ( $payArray as $k => $v )
            {
                $secureString .= $k . '=' . urlencode( trim( $v ) ) . '&';
                $data[ $k ] = $v;
            }

            $passphrase = $this->config->get( 'payment_payfast_passphrase' );
            if ( !empty( $passphrase ) && !$this->config->get( 'payment_payfast_sandbox' ) )
            {
                $secureString = $secureString . 'passphrase=' . urlencode( $this->config->get( 'payment_payfast_passphrase' ) );
            }
            else
            {
                $secureString = substr( $secureString, 0, -1 );
            }

            $securityHash = md5( $secureString );
            $data[ 'signature' ] = $securityHash;
            $data[ 'user_agent' ] = 'OpenCart 3.0';

            if ( file_exists( DIR_TEMPLATE . $this->config->get( 'config_template' ) . '/template/extension/payment/payfast' ) )
            {
                return $this->load->view( $this->config->get( 'config_template' ) . '/template/extension/payment/payfast',
                    $data );
            }
            else
            {
                return $this->load->view( 'extension/payment/payfast', $data );
            }

        }
    }

    /**
     * callback
     *
     * ITN callback handler
     *
     * @date 07/08/2017
     * @version 2.0.0
     * @access public
     *
     * @author  PayFast
     *
     */
    public function callback()
    {
        if ( $this->config->get( 'payment_payfast_debug' ) )
        {
            $debug = true;
        }
        else
        {
            $debug = false;
        }
        define( 'PF_DEBUG', $debug );
        include( 'payfast_common.inc' );
        $pfError = false;
        $pfErrMsg = '';
        $pfDone = false;
        $pfData = array();
        $pfParamString = '';
        if ( isset( $this->request->post[ 'custom_str1' ] ) )
        {
            $order_id = $this->request->post[ 'custom_str1' ];
        }
        else
        {
            $order_id = 0;
        }


        pflog( 'PayFast ITN call received' );

        //// Notify PayFast that information has been received
        if ( !$pfError && !$pfDone )
        {
            header( 'HTTP/1.0 200 OK' );
            flush();
        }

        //// Get data sent by PayFast
        if ( !$pfError && !$pfDone )
        {
            pflog( 'Get posted data' );

            // Posted variables from ITN
            $pfData = pfGetData();
            $pfData[ 'item_name' ] = html_entity_decode( $pfData[ 'item_name' ] );
            $pfData[ 'item_description' ] = html_entity_decode( $pfData[ 'item_description' ] );
            pflog( 'PayFast Data: ' . print_r( $pfData, true ) );

            if ( $pfData === false )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_ACCESS;
            }
        }

        //// Verify security signature
        if ( !$pfError && !$pfDone )
        {
            pflog( 'Verify security signature' );
            $passphrase = $this->config->get( 'payment_payfast_passphrase' );
            $pfPassphrase = empty( $passphrase ) ? null : $passphrase;

            $server = $this->config->get( 'payment_payfast_sandbox' ) ? 'test' : 'live';

            // If signature different, log for debugging
            if ( !pfValidSignature( $pfData, $pfParamString, $pfPassphrase, $server ) )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_INVALID_SIGNATURE;
            }
        }

        //// Verify source IP (If not in debug mode)
        if ( !$pfError && !$pfDone && !PF_DEBUG )
        {
            pflog( 'Verify source IP' );

            if ( !pfValidIP( $_SERVER[ 'REMOTE_ADDR' ] ) )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_SOURCE_IP;
            }
        }
        //// Get internal cart
        if ( !$pfError && !$pfDone )
        {
            // Get order data
            $this->load->model( 'checkout/order' );
            $order_info = $this->model_checkout_order->getOrder( $order_id );

            pflog( "Purchase:\n" . print_r( $order_info, true ) );
        }

        //// Verify data received
        if ( !$pfError )
        {
            pflog( 'Verify data received' );

            $pfValid = pfValidData( $this->pfHost, $pfParamString );

            if ( !$pfValid )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_ACCESS;
            }
        }

        //// Check data against internal order
        if ( !$pfError && !$pfDone )
        {
            pflog( 'Check data against internal order' );

            if ( empty( $pfData['token'] ) || strtotime( $pfData['custom_str2'] ) <= strtotime( gmdate( 'Y-m-d' ). '+ 2 days' ) )
            {
                $amount = $this->currency->format( $order_info['total'], 'ZAR', '', false );
            }

            if ( !empty( $pfData['token'] ) && strtotime( $pfData['custom_str2'] ) > strtotime( gmdate( 'Y-m-d' ). '+ 2 days' ) )
            {
                $recurring = $this->getOrderRecurringByReference( $pfData['m_payment_id'] );
                $amount = $this->currency->format( $recurring['recurring_price'], 'ZAR', '', false );
            }

            // Check order amount
            if ( !pfAmountsEqual( $pfData[ 'amount_gross' ], $amount ) )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_AMOUNT_MISMATCH;
            }

        }

        //// Check status and update order
        if ( !$pfError && !$pfDone )
        {
            pflog( 'Check status and update order' );

            $transaction_id = $pfData[ 'pf_payment_id' ];

            if ( empty( $pfData['token'] ) )
            {
                switch ($pfData['payment_status']) {
                    case 'COMPLETE':
                        pflog('- Complete');

                        // Update the purchase status
                        $order_status_id = $this->config->get('payment_payfast_completed_status_id');

                        break;

                    case 'FAILED':
                        pflog('- Failed');

                        // If payment fails, delete the purchase log
                        $order_status_id = $this->config->get('payment_payfast_failed_status_id');

                        break;

                    case 'PENDING':
                        pflog('- Pending');

                        // Need to wait for "Completed" before processing
                        break;

                    default:
                        // If unknown status, do nothing (safest course of action)
                        break;
                }
                if (!$order_info['order_status_id']) {
                    $this->model_checkout_order->addOrderHistory($order_id, $order_status_id);

                } else {
                    $this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
                }
                return true;
            }

            if ( isset( $pfData['token'] ) && $pfData['payment_status'] == 'COMPLETE' )
            {
                $recurring = $this->getOrderRecurringByReference($pfData['m_payment_id']);

                $this->db->query("INSERT INTO `" . DB_PREFIX . "order_recurring_transaction` SET `order_recurring_id` = '" . $recurring['order_recurring_id'] . "', `date_added` = NOW(), `amount` = '" . $pfData['amount_gross'] . "', `type` = '1'");

                //update recurring order status to active
                $this->db->query("UPDATE `" . DB_PREFIX . "order_recurring` SET `status` = 1 WHERE `order_id` = '" . $pfData['custom_str4'] . "' AND `product_id` = '" . $pfData['custom_str5'] . "'");

                $order_status_id = $this->config->get('payment_payfast_completed_status_id');
                if ( !$order_info['order_status_id'] )
                {
                    $this->model_checkout_order->addOrderHistory( $order_id, $order_status_id );

                } else
                {
                    $this->model_checkout_order->addOrderHistory( $order_id, $order_status_id );
                }
                return true;
            }
        }
        else
        {
            $this->model_checkout_order->addOrderHistory( $order_id, $this->config->get( 'config_order_status_id' ) );
            pflog( "Errors:\n" . print_r( $pfErrMsg, true ) );
            return false;
        }

        if ( $pfData['payment_status'] == 'CANCELLED' )
        {
            $recurring = $this->getOrderRecurringByReference($pfData['m_payment_id']);

            $this->db->query("INSERT INTO `" . DB_PREFIX . "order_recurring_transaction` SET `order_recurring_id` = '" . $recurring['order_recurring_id'] . "', `date_added` = NOW(), `type` = '5'");

            //update recurring order status to cancelled
            $this->db->query("UPDATE `" . DB_PREFIX . "order_recurring` SET `status` = 3 WHERE `order_recurring_id` = '" . $recurring['order_recurring_id'] . "' LIMIT 1");

        }
    }

    public function getOrderRecurringByReference( $reference )
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_recurring` WHERE `reference` = '" . $this->db->escape($reference) . "'");

        return $query->row;
    }
}

?>