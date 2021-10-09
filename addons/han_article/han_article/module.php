<?php

//decode by http://www.guifox.com/
defined('IN_IA') or exit('Access Denied');
class Han_articleModule extends WeModule
{
	public function fieldsFormDisplay($rid = 0)
	{
	}
	public function fieldsFormValidate($rid = 0)
	{
		return '';
	}
	public function fieldsFormSubmit($rid)
	{
	}
	public function ruleDeleted($rid)
	{
	}
	public function settingsDisplay($settings)
	{
		global $_W, $_GPC;
		if (checksubmit()) {
			$input = array_elements(array('name', 'logo', 'description', 'link', 'is_qd', 'is_mb', 'qdlink', 'zzuid', 'exstyle', 'ds_type', 'yc_type', 'xs_type', 'send_times', 'reply_times', 'declaration', 'not_limit_category', 'curr_site_appid', 'curr_site_appsecret'), $_GPC);
			$input['name'] = trim($input['name']);
			$input['logo'] = trim($input['logo']);
			$input['description'] = trim($input['description']);
			$input['ds_type'] = trim($input['ds_type']);
			$input['link'] = trim($input['link']);
			$input['is_qd'] = trim($input['is_qd']);
			$input['is_mb'] = trim($input['is_mb']);
			$input['qdlink'] = trim($input['qdlink']);
			$input['yc_type'] = trim($input['yc_type']);
			$input['xs_type'] = trim($input['xs_type']);
			$input['send_times'] = trim($input['send_times']);
			$input['reply_times'] = trim($input['reply_times']);
			$input['zzuid'] = trim($input['zzuid']);
			$input['exstyle'] = trim($input['exstyle']);
			$input['declaration'] = trim($input['declaration']);
			$input['not_limit_category'] = trim($input['not_limit_category']);
			$input['curr_site_appsecret'] = trim($input['curr_site_appsecret']);
			$input['curr_site_appid'] = trim($input['curr_site_appid']);
			$setting = $this->module['config'];
			$setting['bbs'] = $input;
			if ($this->saveSettings($setting)) {
				message('保存参数成功', 'refresh');
			}
		}
		$config = $this->module['config']['bbs'];
		include $this->template('setting');
	}
}