<?php

function generate_xray_last_conf($key_data, $srv_data) {
	return json_encode([
		"log" => ["loglevel" => "error"],
		"inbounds" => array([
			"listen" => "127.0.0.1",
			"port" => 10808,
			"protocol" => "socks",
			"settings" => ["udp" => true],
		]),
		"outbounds" => array([
			"protocol" => "vless",
			"settings" => [
				"vnext" => array([
					"address" => $srv_data->host_name,
					"port" => 443,
					"users" => array([
						"id" => $key_data->id,
						"flow" => "xtls-rprx-vision",
						"encryption" => "none",
					])
				])
			],
			"streamSettings" => [
				"network" => "tcp",
				"security" => "reality",
				"realitySettings" => [
					"fingerprint" => "chrome",
					"serverName" => $srv_data->xray_params->site,
					"publicKey" => $srv_data->xray_params->pub_key,
					"shortId" => $srv_data->xray_params->short_id,
					"spiderX" =>  "",
				]
			]
		]),
	], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

function generate_xray_native_conf($key_data, $srv_data) {
	return generate_xray_last_conf($key_data, $srv_data);
}

function generate_xray_full_conf($key_data, $srv_data) {
	return json_encode([
		"containers" => array([
			"container" => "amnezia-xray",
			"xray" => [
				"last_config" => generate_xray_last_conf(
					$key_data, $srv_data),
				"site" => $srv_data->xray_params->site],
		]),
		"defaultContainer" => "amnezia-xray",
		"description" => preg_replace(
			"/([A-Z][A-Z])/", "$1 XRay", $srv_data->key_name),
		"dns1" => $srv_data->dns1,
		"dns2" => $srv_data->dns2,
		"hostName" => $srv_data->host_name,
	], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

?>
