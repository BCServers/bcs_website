<?php

require "awg_tools.php";
require "xray_tools.php";
require "key_tools.php";

function load_json($file) {
	return json_decode(file_get_contents($file));
}

function find_user($username, $USER_DB) {
	return array_column($USER_DB, null,
		'username')[$username] ?? null;
}

function find_server($server_name, $SERVER_DB) {
	return array_column($SERVER_DB, null,
		'srv_name')[$server_name] ?? null;
}

function generate_awg_keys($user_awg_key_data, $SERVER_DB, $relay_name) {
	$keys = array();

	for($i = 0; $i < count($user_awg_key_data); ++$i) {
		$this_srv_key_data = $user_awg_key_data[$i]->key_data;
		$this_srv_name = $user_awg_key_data[$i]->srv_name;
		$this_srv_data = find_server($this_srv_name, $SERVER_DB);
		$this_relay_srv_data = make_relay($this_srv_data, 
			find_server($relay_name, $SERVER_DB));

		usort($this_srv_key_data,
			function ($a, $b) {
				$last_digit = function($ip) {return ip2long($ip) % 256;};
				return $last_digit($a->IP) - $last_digit($b->IP);
			}
		);

		$this_srv_keys = array();

		for($j = 0; $j < count($this_srv_key_data); ++$j) {
			$this_srv_keys[$j]['main']['native'] = generate_awg_native_conf(
				$this_srv_key_data[$j], $this_srv_data);
			$this_srv_keys[$j]['relay']['native'] =
				($this_srv_data->srv_name === $relay_name) ? '' :
					generate_awg_native_conf(
						$this_srv_key_data[$j], $this_relay_srv_data);

			$this_srv_keys[$j]['main']['encoded'] = encode_config(
				generate_awg_full_conf(
					$this_srv_key_data[$j], $this_srv_data, $j+1));
			$this_srv_keys[$j]['relay']['encoded'] =
				($this_srv_data->srv_name === $relay_name) ? '' :
					encode_config(generate_awg_full_conf(
						$this_srv_key_data[$j], $this_relay_srv_data, $j+1));
		}
		$keys[$this_srv_name] = $this_srv_keys;
	}
	return $keys;
}


function generate_xray_keys($user_xray_key_data, $SERVER_DB, $relay_name) {
	$keys = array();

	for($i = 0; $i < count($user_xray_key_data); ++$i) {
		$this_srv_key_data = $user_xray_key_data[$i]->key_data;
		$this_srv_name = $user_xray_key_data[$i]->srv_name;
		$this_srv_data = find_server($this_srv_name, $SERVER_DB);
		$this_relay_srv_data = make_relay($this_srv_data, 
			find_server($relay_name, $SERVER_DB));

		$this_srv_keys = array();

		for($j = 0; $j < count($this_srv_key_data); ++$j) {
			$this_srv_keys[$j]['main']['native'] = generate_xray_native_conf(
				$this_srv_key_data[$j], $this_srv_data);
			$this_srv_keys[$j]['relay']['native'] =
				($this_srv_data->srv_name === $relay_name) ? '' :
					generate_xray_native_conf(
						$this_srv_key_data[$j], $this_relay_srv_data);

			$this_srv_keys[$j]['main']['encoded'] = encode_config(
				generate_xray_full_conf(
					$this_srv_key_data[$j], $this_srv_data, $j+1));
			$this_srv_keys[$j]['relay']['encoded'] = 
				($this_srv_data->srv_name === $relay_name) ? '' :
					encode_config(generate_xray_full_conf(
						$this_srv_key_data[$j], $this_relay_srv_data, $j+1));
		}
		$keys[$this_srv_name] = $this_srv_keys;
	}
	return $keys;
}

function generate_frontend_data($user_data, $SERVER_DB, $relay_name) {
	$frontend_data = array();
	$frontend_data['user_real_name'] = $user_data->real_name;
	$frontend_data['access_srv_data'] = array();

	$awg_keys = generate_awg_keys(
		$user_data->awg_key_data, $SERVER_DB, $relay_name);
	$xray_keys = generate_xray_keys(
		$user_data->xray_key_data, $SERVER_DB, $relay_name);


	for($i = 0; $i < count($SERVER_DB); ++$i) {
		$srv_name = $SERVER_DB[$i]->srv_name;

		$frontend_data['access_srv_data'][$srv_name]['name'] =
			$SERVER_DB[$i]->display_name;
		$frontend_data['access_srv_data'][$srv_name]['location'] =
			$SERVER_DB[$i]->location;
		$frontend_data['access_srv_data'][$srv_name]['description'] =
			$SERVER_DB[$i]->description;
		$frontend_data['access_srv_data'][$srv_name]['display_order'] =
			$SERVER_DB[$i]->display_order;

		$this_srv_awg_keys = [];
		if (array_key_exists($srv_name, $awg_keys)) {
			$this_srv_awg_keys = $awg_keys[$srv_name];
		}

		$frontend_data['access_srv_data'][$srv_name]['awg_keys'] =
			$this_srv_awg_keys;
		$frontend_data['access_srv_data'][$srv_name]['xray_keys'] =
			array_key_exists($srv_name, $xray_keys) ? 
				$xray_keys[$srv_name] : NULL;
	}

	usort($frontend_data['access_srv_data'], 
		function ($a, $b) {return $a['display_order'] - $b['display_order'];});


	return $frontend_data;
}

?>
