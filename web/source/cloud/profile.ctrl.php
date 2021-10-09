<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('cloud');
load()->func('communication');

$dos = array('site');
$do = in_array($do, $dos) ? $do : 'site';

if ('site' == $do) {
	if (!empty($_W['setting']['site']['key']) && !empty($_W['setting']['site']['token'])) {
		$site_info = cloud_site_info();
		if (is_error($site_info)) {
			if ($_W['isajax']) {
				iajax(-1, '获取站点信息失败: ' . $site_info['message']);
			}
			message('获取站点信息失败: ' . $site_info['message'], url('cloud/diagnose'), 'error');
		}
		if (empty($site_info['services'])) {
			$site_info['services'] = '无';
		}
	} else {
		if ($_W['isajax']) {
			iajax(-1, '注册信息丢失, 请通过"重置站点ID和通信密钥"重新获取 !');
		}
		message('注册信息丢失, 请通过"重置站点ID和通信密钥"重新获取 !', url('cloud/diagnose'), 'error');
	}
	if ($_W['isajax']) {
		iajax(0, $site_info);
	}
	template('cloud/site');
}
