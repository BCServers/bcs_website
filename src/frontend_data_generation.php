<?php

require "awg_tools.php";
require "xray_tools.php";
require "key_tools.php";

function load_json($file) {
	return json_decode(file_get_contents($file));
}

function find_user($username, $USER_DB) {
	return array_column($USER_DB, null, 'username')[$username] ?? null;
}

function find_server($server_name, $SERVER_DB) {
	return array_column($SERVER_DB, null, 'srv_name')[$server_name] ?? null;
}

function generate_awg_keys($user_awg_key_data, $SERVER_DB, $relay_name) {
	$keys = array();

	foreach($user_awg_key_data as $ukd) {
		$srv = find_server($ukd->srv_name, $SERVER_DB);
		$relay_srv = make_relay($srv, find_server($relay_name, $SERVER_DB));

		usort($ukd->key_data, // sort by IP
			function ($a, $b) {
				$last_digit = function($ip) {return ip2long($ip) % 256;};
				return $last_digit($a->IP) - $last_digit($b->IP);
			}
		);

		$is_srv_relay = ($srv->srv_name === $relay_name);
		$keys[$ukd->srv_name] = array();

		foreach($ukd->key_data as $i => $key_data) {
			$key['main']['native'] =
				generate_awg_native_conf($key_data, $srv);
			$key['relay']['native'] = $is_srv_relay ? '' :
				generate_awg_native_conf($key_data, $relay_srv);

			$key['main']['encoded'] = encode_config(
				generate_awg_full_conf($key_data, $srv, $i+1));
			$key['relay']['encoded'] = $is_srv_relay ? '' : encode_config(
				generate_awg_full_conf($key_data, $relay_srv, $i+1));

			array_push($keys[$ukd->srv_name], $key);
		}
	}
	return $keys;
}

function generate_xray_keys($user_xray_key_data, $SERVER_DB, $relay_name) {
	$keys = array();

	foreach($user_xray_key_data as $ukd) {
		$srv = find_server($ukd->srv_name, $SERVER_DB);
		$relay_srv = make_relay($srv, find_server($relay_name, $SERVER_DB));

		$is_srv_relay = ($srv->srv_name === $relay_name);
		$keys[$ukd->srv_name] = array();

		foreach($ukd->key_data as $i => $key_data) {
			$key['main']['native'] =
				generate_xray_native_conf($key_data, $srv);
			$key['relay']['native'] = $is_srv_relay ? '' :
				generate_xray_native_conf($key_data, $relay_srv);

			$key['main']['encoded'] = encode_config(
				generate_xray_full_conf($key_data, $srv, $i+1));
			$key['relay']['encoded'] = $is_srv_relay ? '' : encode_config(
				generate_xray_full_conf($key_data, $relay_srv, $i+1));

			array_push($keys[$ukd->srv_name], $key);
		}
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

	foreach($SERVER_DB as $srv) {
		$srv_name = $srv->srv_name;

		$user_has_awg_keys = array_key_exists($srv_name, $awg_keys);
		$user_has_xray_keys = array_key_exists($srv_name, $xray_keys);
		if (!($user_has_awg_keys || $user_has_xray_keys)) continue;

		$frontend_data['access_srv_data'][$srv_name]['name'] =
			$srv->display_name;
		$frontend_data['access_srv_data'][$srv_name]['location'] =
			$srv->location;
		$frontend_data['access_srv_data'][$srv_name]['description'] =
			$srv->description;
		$frontend_data['access_srv_data'][$srv_name]['display_order'] =
			$srv->display_order;

		$frontend_data['access_srv_data'][$srv_name]['awg_keys'] =
			$user_has_awg_keys ? $awg_keys[$srv_name] : NULL;
		$frontend_data['access_srv_data'][$srv_name]['xray_keys'] =
			$user_has_xray_keys ? $xray_keys[$srv_name] : NULL;
	}

	usort($frontend_data['access_srv_data'], 
		function ($a, $b) {return $a['display_order'] - $b['display_order'];});

	return $frontend_data;
}

?>
