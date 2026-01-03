<?php

function generate_awg_native_internal_conf($key_data, $srv_data) {
	return
		"[Interface]" . PHP_EOL .
		"Address = " . $key_data->IP . "/32" . PHP_EOL .
		"DNS = \$PRIMARY_DNS, \$SECONDARY_DNS" . PHP_EOL .
		"PrivateKey = " . $key_data->priv_key . PHP_EOL .
		"Jc = " . $srv_data->awg_params->Jc . PHP_EOL .
		"Jmin = " . $srv_data->awg_params->Jmin . PHP_EOL .
		"Jmax = " . $srv_data->awg_params->Jmax . PHP_EOL .
		"S1 = " . $srv_data->awg_params->S1 . PHP_EOL .
		"S2 = " . $srv_data->awg_params->S2 . PHP_EOL .
		"H1 = " . $srv_data->awg_params->H1 . PHP_EOL .
		"H2 = " . $srv_data->awg_params->H2 . PHP_EOL .
		"H3 = " . $srv_data->awg_params->H3 . PHP_EOL .
		"H4 = " . $srv_data->awg_params->H4 . PHP_EOL .
		PHP_EOL .
		"[Peer]" . PHP_EOL .
		"PublicKey = " . $srv_data->awg_params->pub_key . PHP_EOL .
		"PresharedKey = " . $srv_data->awg_params->psk_key . PHP_EOL .
		"AllowedIPs = 0.0.0.0/0, ::/0" . PHP_EOL .
		"Endpoint = " . $srv_data->host_name . ":" . 
			$srv_data->awg_params->port . PHP_EOL .
		"PersistentKeepalive = 25" . PHP_EOL;
}

function generate_awg_last_conf($key_data, $srv_data) {
	return json_encode([
		"H1" => $srv_data->awg_params->H1,
		"H2" => $srv_data->awg_params->H2,
		"H3" => $srv_data->awg_params->H3,
		"H4" => $srv_data->awg_params->H4,
		"Jc" => $srv_data->awg_params->Jc,
		"Jmax" => $srv_data->awg_params->Jmax,
		"Jmin" => $srv_data->awg_params->Jmin,
		"S1" => $srv_data->awg_params->S1,
		"S2" => $srv_data->awg_params->S2,
		"clientId" => $key_data->pub_key,
		"client_ip" => $key_data->IP,
		"client_priv_key" => $key_data->priv_key,
		"client_pub_key" => $key_data->pub_key,
		"config" => generate_awg_native_internal_conf($key_data, $srv_data),
		"hostName" => $srv_data->host_name,
		"mtu" => $srv_data->awg_params->MTU,
		"port" => (int)$srv_data->awg_params->port,
		"psk_key" => $srv_data->awg_params->psk_key,
		"server_pub_key" => $srv_data->awg_params->pub_key,
	], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

function generate_awg_native_conf($key_data, $srv_data) {
	$internal_conf = generate_awg_native_internal_conf($key_data, $srv_data);
	return preg_replace("/\\\$PRIMARY_DNS, \\\$SECONDARY_DNS/",
		$srv_data->dns1.", ".$srv_data->dns2, $internal_conf);
}

function generate_awg_full_conf($key_data, $srv_data, $key_num) {
	return json_encode([
		"containers" => array([
			"awg" => [
				"H1" => $srv_data->awg_params->H1,
				"H2" => $srv_data->awg_params->H2,
				"H3" => $srv_data->awg_params->H3,
				"H4" => $srv_data->awg_params->H4,
				"Jc" => $srv_data->awg_params->Jc,
				"Jmax" => $srv_data->awg_params->Jmax,
				"Jmin" => $srv_data->awg_params->Jmin,
				"S1" => $srv_data->awg_params->S1,
				"S2" => $srv_data->awg_params->S2,
				"last_config" => generate_awg_last_conf(
					$key_data, $srv_data),
				"port" => $srv_data->awg_params->port,
				"transport_proto" => "udp"],
			"container" => "amnezia-awg"
		]),
		"defaultContainer" => "amnezia-awg",
		"description" => preg_replace(
			"/([A-Z][A-Z])/", "$1 ".$key_num, $srv_data->key_name),
		"dns1" => $srv_data->dns1,
		"dns2" => $srv_data->dns2,
		"hostName" => $srv_data->host_name,
	], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

?>
