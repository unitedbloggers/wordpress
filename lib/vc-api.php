<?php

require_once(dirname(__FILE__) . '/url.php');

if (!extension_loaded('json')) {
	require_once(dirname(__FILE__) . '/json.php');
	if(!function_exists('vicomi_json_decode')) {

		function vicomi_json_decode($data) {
			$json = new JSON;
			return $json->unserialize($data);
		}
	}
	
} else {
	if(!function_exists('vicomi_json_decode')) {
		
		function vicomi_json_decode($data) {
			return json_decode($data);
		}
	}
}

if(!class_exists('VicomiAPI')) {

	class VicomiAPI {
		var $api_version = '1.1';

		function VicomiAPI() {
			$this->last_error = null;
			$this->api_url = 'http://dashboard.vicomi.com/';
			//$this->api_url = 'http://localhost:3000/';
		}
		
		function get_user_api_key($username, $password) {
	        $response = $this->call('users/sign_in.json', array(
					'user[email]'    => $username,
					'user[password]'    => $password,
	        ), true);
	        return $response;

	    }

	    function plugin_activate($access_token, $plugin_type, $uid) {

	    	$params = array(
					'platform'    => 'wordpress',
					'event'    => 'activate',
					'plugin' => $plugin_type,
					'uid'    => $uid);

	    	if($access_token && $access_token != undefined) {
	    		$params['access_token'] = $access_token;
	    	}

	        $response = $this->call('plugin/track.json', $params, true);
	        return $response;

	    }

	    function plugin_deactivate($access_token, $plugin_type, $uid) {
	        $params = array(
					'platform'    => 'wordpress',
					'event'    => 'deactivate',
					'plugin' => $plugin_type,
					'uid'    => $uid);

	    	if($access_token && $access_token != undefined) {
	    		$params['access_token'] = $access_token;
	    	}

	        $response = $this->call('plugin/track.json', $params, true);
	        return $response;
	    }

	    function plugin_uninstall($access_token, $plugin_type, $uid) {
	       $params = array(
					'platform'    => 'wordpress',
					'event'    => 'uninstall',
					'plugin' => $plugin_type,
					'uid'    => $uid);

	    	if($access_token && $access_token != undefined) {
	    		$params['access_token'] = $access_token;
	    	}

	        $response = $this->call('plugin/track.json', $params, true);
	        return $response;
	    }

		function call($method, $args=array(), $post=false) {
			$url = $this->api_url . $method . '/';

			foreach ($args as $key=>$value) {
				if (empty($value)) unset($args[$key]);
			}

			if (!$post) {
				$url .= '?' . _vcc_get_query_string($args);
				$args = null;
			}

			if (!($response = _vcc_urlopen($url, $args)) || !$response['code']) {
				$this->last_error = 'Unable to connect to the Vicomi API servers';
				return false;
			}

			if ($response['code'] != 200) {
				if ($response['code'] == 500) {
					if (!empty($response['headers']['X-Sentry-ID'])) {
					    $this->last_error = 'Vicomi returned a bad response (HTTP '.$response['code'].', ReferenceID: '.$response['headers']['X-Sentry-ID'].')';
					    return false;
					}
				} elseif ($response['code'] == 400) {
					$data = vicomi_json_decode($response['data']);
					if ($data && $data->message) {
						$this->last_error = $data->message;
					} else {
						$this->last_error = "Vicomi returned a bad response (HTTP ".$response['code'].")";
					}
					return false;
				}
				$this->last_error = "Vicomi returned a bad response (HTTP ".$response['code'].")";
				return false;
			}

			$data = vicomi_json_decode($response['data']);

			if (!$data) {
				$this->last_error = 'No valid JSON content returned from Vicomi';
				return false;
			}

			if (!$data->succeeded) {
				if (!$data->message) {
					$this->last_error = '(No error message was received)';
				} else {
					$this->last_error = $data->message;
				}
				return false;
			}
			
			$this->last_error = null;

			return $data->message;
		}

		function get_last_error() {
			if (empty($this->last_error)) return;
			if (!is_string($this->last_error)) {
				return var_export($this->last_error);
			}
			return $this->last_error;
		}
	}
}


?>
