<?php
/*
*
*/
require_once("config.inc");
require_once("interfaces.inc");
require_once("globals.inc");
require_once("services.inc");
require_once("service-utils.inc");
require_once("util.inc");

function zerotier_sync() {
	global $config;

	if (is_service_running("zerotier")) {
		stop_service("zerotier");
	}

	if ($config['installedpackages']['zerotier']['enabled'] == 'true') {
		start_service("zerotier");
	}
}

function zerotier_install() {
	global $config;

	$config['installedpackages']['zerotier']['enabled'] = 'true';
	$config['installedpackages']['zerotier']['experimental'] = 'true';

	$zerotier = <<<EOD
#!/bin/sh

# $FreeBSD: head/net/zerotier/files/zerotier.in 454856 2017-11-24 23:17:50Z dbaio $
#
# PROVIDE: zerotier
# REQUIRE: NETWORKING
# BEFORE: SERVERS
# KEYWORD: shutdown
#
# Add these lines to /etc/rc.conf.local or /etc/rc.conf
# to enable this service:
#
# zerotier_enable (bool):       Set to NO by default.
#                               Set it to YES to enable zerotier.

. /etc/rc.subr

name=zerotier
rcvar=zerotier_enable

pidfile="/var/db/\${name}-one/\${name}-one.pid"

load_rc_config \${name}

command="/usr/local/sbin/\${name}-one"

command_args="-d"

run_rc_command "$1"
EOD;

	$fd = fopen("/usr/local/etc/rc.d/zerotier", "w");
	fwrite($fd, $zerotier);
	fclose($fd);
	chmod("/usr/local/etc/rc.d/zerotier", 0555);

	$fd = fopen("/etc/rc.conf.local", "a");
	fwrite($fd, "zerotier_enable=\"YES\"\n");
	fclose($fd);

	$file_contents = file_get_contents("/etc/inc/util.inc");
	$file_contents = preg_replace('/(\^enc\|)/', "^zt|$1", $file_contents);
	file_put_contents("/etc/inc/util.inc", $file_contents);

	write_config("[zerotier] Package installed.");
}

function zerotier_deinstall() {
	exec("killall zerotier-one");

	$file_contents = file_get_contents("/etc/rc.conf.local");
	$file_contents = str_replace("zerotier_enable=\"YES\"\n","",$file_contents);
	file_put_contents("/etc/rc.conf.local",$file_contents);

	$file_contents = file_get_contents("/etc/inc/util.inc");
	$file_contents = preg_replace('/\^zt\|(\^enc\|)/', "$1", $file_contents);
	file_put_contents("/etc/inc/util.inc", $file_contents);
}

function zerotier_kill() {
	if (is_service_running("zerotier")) {
		// stop_service("zerotier");
		exec("killall zerotier-one");
	}
}

function zerotier_start() {
	if ($config['installedpackages']['zerotier']['enabled'] == 'true') {
		if (!is_service_running("zerotier")) {
			start_service("zerotier");
		}
	}
}

# Used by Status Page
function zerotier_listnetworks() {
	$networks = [];
	if (is_service_running("zerotier"))
	{
		exec("/usr/local/bin/zerotier-cli -j listnetworks", $json);
		$networks = json_decode(implode('', $json));
	}
	return $networks;
}

function zerotier_listpeers() {
	$peers = [];
	if (is_service_running("zerotier")) {
		exec("/usr/local/bin/zerotier-cli -j /peer", $json);
		$peers = json_decode(implode('', $json));
	}
	return $peers;
}

function zerotier_join_network($network) {
	$auth_secret = file_get_contents('/var/db/zerotier-one/authtoken.secret');
	$output = [];
	if (is_service_running("zerotier")) {
		exec("/usr/local/bin/curl -X POST --header \"X-ZT1-Auth: ${auth_secret}\" http://localhost:9993/network/${network}", $json);
		$output = json_decode(implode('', $json));
	}
	return $output;
}

function zerotier_leave_network($network) {
	$auth_secret = file_get_contents('/var/db/zerotier-one/authtoken.secret');
	$output = [];
	if (is_service_running("zerotier")) {
		exec("/usr/local/bin/curl -X DELETE --header \"X-ZT1-Auth: ${auth_secret}\" http://localhost:9993/network/${network}", $json);
		$output = json_decode(implode('', $json));
	}
	return $output;
}

function zerotier_controller_listnetworks() {
	$networks = [];
	$network_list = [];

	if (is_service_running("zerotier")) {
		exec("/usr/local/bin/zerotier-cli -j /controller/network", $json);
		$network_list = json_decode(implode('', $json));
	}

	foreach($network_list as $network) {
		exec("/usr/local/bin/zerotier-cli -j /controller/network/${network}", $json2);
		$networks[] = json_decode(implode('', $json2));
		unset($json2);
	}

	return $networks;
}

function zerotier_controller_network($network) {
	$auth_secret = file_get_contents('/var/db/zerotier-one/authtoken.secret');

	$output = [];
	// if (is_service_running("zerotier")) {
	// 	exec("/usr/local/bin/curl --header \"X-ZT1-Auth: ${auth_secret}\" http://localhost:9993/controller/network/${network}", $json);
	// 	$output = json_decode(implode('', $json));
	// }

	if (is_service_running("zerotier")) {
		$curl = curl_init("http://localhost:9993/controller/network/".$network);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'X-ZT1-Auth: ' .  $auth_secret
			)
		);

		$curl_out = curl_exec($curl);
		curl_close($curl);
		$output = json_decode($curl_out);
	}
	return $output;
}

function zerotier_controller_createnetwork($network, $networkID) {
	global $id;
	$auth_secret = file_get_contents('/var/db/zerotier-one/authtoken.secret');
	$output = [];
	if (is_service_running("zerotier")) {
		exec("/usr/local/bin/zerotier-cli -j /status", $json);
		$status = json_decode(implode('', $json));
		$controller_id = $status->address;
		$network_id = $controller_id.$networkID;
		$post_data = json_encode($network);
		$curl = curl_init("http://localhost:9993/controller/network/".$network_id);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($post_data),
			'X-ZT1-Auth: ' .  $auth_secret
			)
		);

		$curl_out = curl_exec($curl);
		curl_close($curl);
		$output = json_decode($curl_out);
	}
	return $output;
}
function zerotier_controller_deletenetwork($id) {
	$auth_secret = file_get_contents('/var/db/zerotier-one/authtoken.secret');
	$output = [];
	if (is_service_running("zerotier")) {
		exec("/usr/local/bin/curl -X DELETE --header \"X-ZT1-Auth: ${auth_secret}\" http://localhost:9993/controller/network/${id}", $json);
		$output = json_decode(implode('', $json));
	}
	return $output;
}

function zerotier_controller_network_members($network) {
	global $id;
	$auth_secret = file_get_contents('/var/db/zerotier-one/authtoken.secret');
	$output = [];
	$members = [];

	if (is_service_running("zerotier")) {

		$curl = curl_init("http://localhost:9993/controller/network/".$network."/member");
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'X-ZT1-Auth: ' .  $auth_secret
			)
		);

		$curl_out = curl_exec($curl);
		curl_close($curl);
		$members = json_decode($curl_out);


		foreach($members as $key => $value) {
			unset($curl);
			unset($response);
			$curl = curl_init("http://localhost:9993/controller/network/".$network."/member/".$key);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
				'X-ZT1-Auth: ' .  $auth_secret
				)
			);

			$response = curl_exec($curl);
			curl_close($curl);
			$output[] = json_decode($response);
		}
	}
	return $output;
}
function zerotier_controller_member_toggle($network, $member, $key) {
	$auth_secret = file_get_contents('/var/db/zerotier-one/authtoken.secret');
	$output = [];


	if (is_service_running("zerotier")) {
		$curl = curl_init("http://localhost:9993/controller/network/".$network."/member/".$member);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'X-ZT1-Auth: ' .  $auth_secret
			)
		);

		$response = curl_exec($curl);
		curl_close($curl);
		$current = json_decode($response, true);
		$new = !($current[$key]);

		$post_data = json_encode([$key => $new]);
		$curl = curl_init("http://localhost:9993/controller/network/".$network."/member/".$member);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($post_data),
				'X-ZT1-Auth: ' .  $auth_secret
				)
			);

		$response = curl_exec($curl);
		curl_close($curl);
		$output[] = json_decode($response);
	}
	return $output;
}
?>
