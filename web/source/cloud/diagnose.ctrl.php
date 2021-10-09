<?php

/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

load()->classs('cloudapi');
load()->model('cloud');
load()->model('setting');

$dos = array('display', 'testapi');
$do = in_array($do, $dos) ? $do : 'display';
permission_check_account_user('system_cloud_diagnose');

if ('testapi' == $do) {
	$starttime = microtime(true);
	$response = cloud_request(CLOUD_API_DOMAIN);
	$endtime = microtime(true);
	iajax(0, '请求接口成功，耗时 ' . (round($endtime - $starttime, 5)) . ' 秒');
} else {
	if ($_W['ispost']){
		if ($_GPC['submit']) {
			$result = cloud_reset_siteinfo();
			$api = new CloudApi();
			$api->deleteCer();
			if (is_error($result)) {
				empty($_W['isajax']) ? itoast($result['message'], '', 'error') : iajax(-1, $result['message']);
			} else {
				empty($_W['isajax']) ? itoast('重置成功', 'refresh', 'success') : iajax(0, '重置成功');
			}
		}
	}
	if (empty($_W['setting']['site'])) {
		$_W['setting']['site'] = array();
	}
	$checkips = array('openapi.w7.cc');
	$apiurl = CLOUD_API_DOMAIN . '/site/diagnose/ping?version=' . IMS_VERSION . '&siteurl=' . urlencode(trim($_W['siteroot'], '/')) . '&date=' . $_W['timestamp'];
	if ($_W['isajax']) {
		$message = array(
			'version' => array(
				'IMS_VERSION' => IMS_VERSION,
				'IMS_FAMILY' => IMS_FAMILY,
				'IMS_RELEASE_DATE' => IMS_RELEASE_DATE,
			),
			'siteroot' => $_W['siteroot'],
			'key' => $_W['setting']['site']['key'],
			'token' => substr($_W['setting']['site']['token'], 0, 5) . '*****' . substr($_W['setting']['site']['token'], -5, 5),
			'checkips' => $checkips,
			'DNS' => function_exists('gethostbyname') ? STATUS_ON: STATUS_OFF,
			'check_server' => array(),
		);
		$response = ihttp_get($apiurl . "&jsonpcallback=?");
		if (is_error($response)) {
			iajax(-1, "访问接口失败, 错误: {$response['message']}");
		}
		$jsonp = substr($response['content'], strpos($response['content'], '('));
		$message['check_server'] = json_decode(trim($jsonp,'();'), true);
		iajax(0, $message);
	}
	template('cloud/diagnose');
}