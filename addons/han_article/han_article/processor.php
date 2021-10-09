<?php

//decode by http://www.guifox.com/
defined('IN_IA') or exit('Access Denied');
class Han_articleModuleProcessor extends WeModuleProcessor
{
	public function respond()
	{
		$content = $this->message['content'];
	}
}