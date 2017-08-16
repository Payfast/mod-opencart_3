<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */

class ControllerExtensionPaymentPayFast extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/payfast');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_payfast', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['payfast_merchant_id'])) {
			$data['error_payfast_merchant_id'] = $this->error['payfast_merchant_id'];
		} else {
			$data['error_payfast_merchant_id'] = '';
		}

		if (isset($this->error['payfast_merchant_key'])) {
			$data['error_payfast_merchant_key'] = $this->error['payfast_merchant_key'];
		} else {
			$data['error_payfast_merchant_key'] = '';
		}

		if (isset($this->error['signature'])) {
			$data['error_signature'] = $this->error['signature'];
		} else {
			$data['error_signature'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/payfast', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/payfast', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_payfast_merchant_id'])) {
			$data['payment_payfast_merchant_id'] = $this->request->post['payment_payfast_merchant_id'];
		} else {
			$data['payment_payfast_merchant_id'] = $this->config->get('payment_payfast_merchant_id');
		}

        if (isset($this->request->post['payment_payfast_merchant_key'])) {
            $data['payment_payfast_merchant_key'] = $this->request->post['payment_payfast_merchant_key'];
        } else {
            $data['payment_payfast_merchant_key'] = $this->config->get('payment_payfast_merchant_key');
        }

        if (isset($this->request->post['payment_payfast_passphrase'])) {
			$data['payment_payfast_passphrase'] = $this->request->post['payment_payfast_passphrase'];
		} else {
			$data['payment_payfast_passphrase'] = $this->config->get('payment_payfast_passphrase');
		}

		if (isset($this->request->post['payment_payfast_sandbox'])) {
			$data['payment_payfast_sandbox'] = $this->request->post['payment_payfast_sandbox'];
		} else {
			$data['payment_payfast_sandbox'] = $this->config->get('payment_payfast_sandbox');
		}

		if (isset($this->request->post['payment_payfast_debug'])) {
			$data['payment_payfast_debug'] = $this->request->post['payment_payfast_debug'];
		} else {
			$data['payment_payfast_debug'] = $this->config->get('payment_payfast_debug');
		}

		if (isset($this->request->post['payment_payfast_total'])) {
			$data['payment_payfast_total'] = $this->request->post['payment_payfast_total'];
		} else {
			$data['payment_payfast_total'] = $this->config->get('payment_payfast_total');
		}

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_payfast_completed_status_id'])) {
            $data['payment_payfast_completed_status_id'] = $this->request->post['payment_payfast_completed_status_id'];
        } else {
            $data['payment_payfast_completed_status_id'] = $this->config->get('payment_payfast_completed_status_id');
        }

        if (isset($this->request->post['payment_payfast_failed_status_id'])) {
            $data['payment_payfast_failed_status_id'] = $this->request->post['payment_payfast_failed_status_id'];
        } else {
            $data['payment_payfast_failed_status_id'] = $this->config->get('payment_payfast_failed_status_id');
        }

        if (isset($this->request->post['payment_payfast_canceled_status_id'])) {
            $data['payment_payfast_canceled_status_id'] = $this->request->post['payment_payfast_canceled_status_id'];
        } else {
            $data['payment_payfast_canceled_status_id'] = $this->config->get('payment_payfast_canceled_status_id');
        }

		if (isset($this->request->post['payment_payfast_geo_zone_id'])) {
			$data['payment_payfast_geo_zone_id'] = $this->request->post['payment_payfast_geo_zone_id'];
		} else {
			$data['payment_payfast_geo_zone_id'] = $this->config->get('payment_payfast_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_payfast_status'])) {
			$data['payment_payfast_status'] = $this->request->post['payment_payfast_status'];
		} else {
			$data['payment_payfast_status'] = $this->config->get('payment_payfast_status');
		}

		if (isset($this->request->post['payment_payfast_sort_order'])) {
			$data['payment_payfast_sort_order'] = $this->request->post['payment_payfast_sort_order'];
		} else {
			$data['payment_payfast_sort_order'] = $this->config->get('payment_payfast_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/payfast', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/payfast')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_payfast_merchant_id']) {
			$this->error['payfast_merchant_id'] = $this->language->get('error_payfast_merchant_id');
		}

		if (!$this->request->post['payment_payfast_merchant_key']) {
			$this->error['payfast_merchant_key'] = $this->language->get('error_payfast_merchant_key');
		}

		return !$this->error;
	}

}
?>