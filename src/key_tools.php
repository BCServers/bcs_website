<?php

function get_awg_keys($user_data, $server_name) {
	return array_column($user_data->awg_key_data, null,
		'srv_name')[$server_name]->key_data ?? null;
}

function get_xray_keys($user_data, $server_name) {
	return array_column($user_data->xray_key_data, null,
		'srv_name')[$server_name]->key_data ?? null;
}

function make_relay($srv_data, $relay_srv_data) {
	$relay = clone $srv_data;
	$relay->key_name = preg_replace(
		"/([A-Z][A-Z]).*/", "$1 (relay)", $srv_data->key_name);
	$relay->host_name = $relay_srv_data->host_name;
	return $relay;
}

function base64_encode_url($string) {
	return str_replace(['+','/','='], ['-','_',''], base64_encode($string));
}

function encode_config($full_config) {
	$str = gzcompress($full_config);
	$header = pack('N', strlen($str));
	$encoded = base64_encode_url($header.$str);
	return "vpn://" . $encoded;
}

?>
