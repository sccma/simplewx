<?php

//decode by http://www.guifox.com/
defined('IN_IA') or exit('Access Denied');
class Han_articleModuleReceiver extends WeModuleReceiver
{
	public function receive()
	{
		$type = $this->message['type'];
	}
}