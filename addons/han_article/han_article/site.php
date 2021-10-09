<?php

//decode by http://www.guifox.com/
defined('IN_IA') or exit('Access Denied');
session_start();
class Han_articleModuleSite extends WeModuleSite
{
	public $_weid = '';
	private $_debug_flag = 0;
	public function __construct()
	{
		global $_W, $_GPC;
		if ($_GPC['fseller'] == 'view') {
			$this->_debug_flag = 1;
		}
		$this->_weid = $_W['uniacid'];
		$string = $_SERVER['REQUEST_URI'];
		if ($this->_debug_flag == 0 && (strpos($string, 'app') == true && !strstr($string, 'do=notifyapi') && !strstr($string, 'chk_buy') && !strstr($string, 'getsvsopenid'))) {
			$useragent = addslashes($_SERVER['HTTP_USER_AGENT']);
			if (strpos($useragent, 'MicroMessenger') === false && strpos($useragent, 'Windows Phone') === false) {
				message('非法访问，请通过微信打开！');
				die;
			}
			if ($_W['openid'] == null || $_W['openid'] == "") {
				if ($_SESSION['from_svs_openid']) {
					$_W['openid'] = $_SESSION['from_svs_openid'];
				} else {
					$this->doMobileGetsvsopenid();
				}
			}
		}
	}
	public function get_html($url)
	{
		if (function_exists('curl_init')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$return = curl_exec($ch);
			curl_close($ch);
			return $return;
		}
		return false;
	}
	public function doMobileGetsvsopenid()
	{
		global $_W, $_GPC;
		$appid = $this->module['config']['curr_site_appid'];
		$appsecret = $this->module['config']['curr_site_appsecret'];
		if ($appid && $appsecret) {
			$back_url = $_W['siteroot'] . 'app' . substr($this->createMobileUrl('getsvsopenid', array()), 1);
			$back_url = urlencode($back_url);
			if ($_GET['code']) {
				$code = $_GET['code'];
				$code_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appid . '&secret=' . $appsecret . '&code=' . $code . '&grant_type=authorization_code';
				$uwr_arr = json_decode($this->get_html($code_url), 1);
				if ($_W['account']['level'] != 4) {
					if (!$uwr_arr['unionid']) {
						$u_info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $uwr_arr['access_token'] . '&openid=' . $uwr_arr['openid'] . '&lang=zh_CN';
						$u_info_arr = json_decode($this->get_html($u_info_url), 1);
						$uwr_arr['unionid'] = $u_info_arr['unionid'];
					}
					if ($uwr_arr['unionid']) {
						$sql = "SELECT openid,follow FROM " . tablename('mc_mapping_fans') . " WHERE openid!='" . $uwr_arr['openid'] . "' and unionid= " . $uwr_arr['unionid'] . " and uniacid='" . $this->_weid . "' limit 1";
						$user_data = pdo_fetch($sql);
						$_SESSION['from_svs_openid'] = $user_data['openid'];
					}
				} else {
					if ($uwr_arr['openid']) {
						$_SESSION['from_svs_openid'] = $uwr_arr['openid'];
					}
				}
			} else {
				$code_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid . '&redirect_uri=' . $back_url . '&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
				header('location:' . $code_url);
				exit;
			}
		}
	}
	public function getmainhuo()
	{
		$host = $_SERVER['HTTP_HOST'];
		$host = strtolower($host);
		if (strpos($host, '/') !== false) {
			$parse = @parse_url($host);
			$host = $parse['host'];
		}
		$topleveldomaindb = array('com', 'edu', 'gov', 'int', 'mil', 'net', 'org', 'biz', 'info', 'pro', 'name', 'museum', 'coop', 'aero', 'xxx', 'idv', 'mobi', 'cc', 'me', 'top');
		$str = '';
		foreach ($topleveldomaindb as $v) {
			$str .= ($str ? '|' : '') . $v;
		}
		$matchstr = "[^\.]+\.(?:(" . $str . ")|\w{2}|((" . $str . ")\.\w{2}))$";
		if (preg_match("/" . $matchstr . "/ies", $host, $matchs)) {
			$domain = $matchs['0'];
		} else {
			$domain = $host;
		}
		return $domain;
	}
	public function L($info)
	{
		load()->func('logging');
		logging_run($info);
	}
	public function getsq()
	{
		$domain = $this->getmainhuo();
		$check_host = 'https://www.we8cc.com/w7mauth.php?m=han_article&dm=' . $domain;
		$check_info = file_get_contents($check_host);
		if (trim($check_info) == '') {
			$ch = curl_init();
			$timeout = 5;
			curl_setopt($ch, CURLOPT_URL, $check_host);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$check_info = curl_exec($ch);
			curl_close($ch);
		}
		if ($check_info !== '0') {
//			echo $check_info . base64_decode('5Z+f5ZCN5pyq5o6I5p2DLOiBlOezu1FR77yaMzM1NDk4ODM4Me+8jOi0reS5sOato+eJiOaOiOadgw==');
//			die;
		}
	}
	private function getConfigArr($key = '')
	{
		global $_W, $_GPC;
		$para_data = pdo_fetch("SELECT * FROM " . tablename('uni_account_modules') . " WHERE module = :module AND uniacid = :uniacid", array(':module' => 'han_article', ':uniacid' => $_W['uniacid']));
		$para_data = unserialize($para_data['settings']);
		if ($key) {
			return $para_data[$key];
		}
		return $para_data;
	}
	public function doMobileArticle0()
	{
		global $_W, $_GPC;
		if ($op == 'add') {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 10;
			$myartlist = pdo_fetchall("SELECT distinct a.id,a.* FROM " . tablename('han_article') . "  as a left join " . tablename('han_artshc') . " as s on a.id=s.aid and a.weid=s.weid  WHERE  a.is_fb =1 and a.weid={$_W['uniacid']} and s.uid={$member['uid']} ORDER BY s.shctime DESC,a.createtime DESC limit " . ($pindex - 1) * $psize . ',' . $psize);
			foreach ($myartlist as $k => $v) {
				$bk = pdo_fetch("SELECT name FROM " . tablename('han_article_category') . " WHERE id =" . $v['pcate']);
				$myartlist[$k]['bkname'] = $bk['name'];
			}
			$myartcount = count($myartlist);
			$pager = myPagination($myartcount, $pindex, $psize);
		}
	}
	public function doWebCategory()
	{
		global $_W, $_GPC;
		$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		$weid = $_W['uniacid'];
		if ($op == 'display') {
			if (!empty($_GPC['displayorder'])) {
				foreach ($_GPC['displayorder'] as $id => $displayorder) {
					$update = array('displayorder' => $displayorder);
					pdo_update('han_article_category', $update, array('id' => $id));
				}
				message('版块排序更新成功！', 'refresh', 'success');
			}
			$children = array();
			$category = pdo_fetchall("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid =$weid ORDER BY parentid, displayorder DESC, id");
			foreach ($category as $index => $row) {
				if (!empty($row['parentid'])) {
					$children[$row['parentid']][] = $row;
					unset($category[$index]);
				}
			}
		} else {
			if ($op == 'post') {
				load()->func('tpl');
				load()->model('mc');
				$groups = mc_groups();
				$parentid = intval($_GPC['parentid']);
				$id = intval($_GPC['id']);
				$setting = uni_setting($_W['uniacid'], array('default_site'));
				$site_styleid = pdo_fetchcolumn('SELECT styleid FROM ' . tablename('site_multi') . ' WHERE id = :id', array(':id' => $setting['default_site']));
				if ($site_styleid) {
					$site_template = pdo_fetch("SELECT a.*,b.name,b.sections FROM " . tablename('site_styles') . ' AS a LEFT JOIN ' . tablename('site_templates') . ' AS b ON a.templateid = b.id WHERE a.uniacid = :uniacid AND a.id = :id', array(':uniacid' => $_W['uniacid'], ':id' => $site_styleid));
				}
				$styles = pdo_fetchall("SELECT a.*, b.name AS tname, b.title FROM " . tablename('site_styles') . ' AS a LEFT JOIN ' . tablename('site_templates') . ' AS b ON a.templateid = b.id WHERE a.uniacid = :uniacid', array(':uniacid' => $_W['uniacid']), 'id');
				if (!empty($id)) {
					$category = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE id = '$id' AND uniacid =$weid ");
					if (empty($category)) {
						message('版块不存在或已删除', '', 'error');
					}
				}
				if (!empty($parentid)) {
					$parent = pdo_fetch("SELECT id, name FROM " . tablename('han_article_category') . " WHERE id = '$parentid'");
					if (empty($parent)) {
						message('抱歉，上级版块不存在或是已经被删除！', $this->createWebUrl('category', array('do' => 'display')), 'error');
					}
				}
				$category['type'] = explode(',', $category['type']);
				$rgrpid = unserialize($category['rgrpid']);
				$wgrpid = unserialize($category['wgrpid']);
				$keywords = pdo_fetchcolumn('SELECT content FROM ' . tablename('rule_keyword') . ' WHERE id = :id AND uniacid = :uniacid ', array(':id' => $category['kid'], ':uniacid' => $weid));
				if (checksubmit('submit')) {
					if (empty($_GPC['cname'])) {
						message('抱歉，请输入版块名称！');
					}
					$data = array('uniacid' => $weid, 'name' => $_GPC['cname'], 'displayorder' => intval($_GPC['displayorder']), 'parentid' => intval($parentid), 'description' => $_GPC['description'], 'bzuid' => intval($_GPC['bzuid']), 'rgrpid' => serialize($_GPC['rgroups']), 'wgrpid' => serialize($_GPC['wgroups']), 'is_ds' => $_GPC['is_ds'], 'is_fcheck' => $_GPC['is_fcheck'], 'is_reply' => $_GPC['is_reply'], 'is_rcheck' => $_GPC['is_rcheck'], 'jf0' => $_GPC['jf0'], 'jf1' => $_GPC['jf1'], 'jf2' => $_GPC['jf2'], 'jf3' => $_GPC['jf3'], 'jf4' => $_GPC['jf4'], 'rule' => $_GPC['rule'], 'l_template' => $_GPC['l_template'], 'a_template' => $_GPC['a_template'], 'templatefile' => "list/list" . $_GPC['l_template'], 'a_templatefile' => "detail/detail" . $_GPC['a_template'], 'thumb' => $_GPC['thumb'], 'createtime' => TIMESTAMP);
					if (!empty($_GPC['keyword'])) {
						$rule['uniacid'] = $weid;
						$rule['name'] = '帖子版块：' . $_GPC['cname'] . ' 触发规则';
						$rule['module'] = 'news';
						$rule['status'] = 1;
						$keyword = array('uniacid' => $weid);
						$keyword['module'] = 'news';
						$keyword['content'] = $_GPC['keyword'];
						$keyword['status'] = 1;
						$keyword['type'] = 1;
						$keyword['displayorder'] = 1;
						$reply['title'] = $_GPC['cname'];
						$reply['description'] = $_GPC['description'];
						$reply['thumb'] = $_GPC['thumb'];
						$reply['displayorder'] = $_GPC['displayorder'];
						$reply['url'] = murl('entry/module/index', array('m' => 'han_article', 'cid' => $id));
					}
					if (!empty($id)) {
						unset($data['parentid']);
						pdo_delete('rule', array('id' => $category['rid'], 'uniacid' => $weid));
						pdo_delete('rule_keyword', array('rid' => $category['rid'], 'uniacid' => $weid));
						pdo_delete('news_reply', array('rid' => $category['rid']));
						if (!empty($_GPC['keyword'])) {
							pdo_insert('rule', $rule);
							$rid = pdo_insertid();
							$keyword['rid'] = $rid;
							pdo_insert('rule_keyword', $keyword);
							$kid = pdo_insertid();
							$reply['rid'] = $rid;
							$alist = pdo_fetchall("SELECT * FROM " . tablename('han_article') . " WHERE weid= $weid AND ccate=$id  ORDER BY displayorder ASC limit 8 ");
							if ($alist) {
								foreach ($alist as $par) {
									$reply2['title'] = $par['title'];
									$reply2['description'] = $par['description'];
									$reply2['thumb'] = $par['thumb'];
									$reply2['url'] = murl('entry/module/detail', array('m' => 'han_article', 'id' => $par['id']));
									$reply2['rid'] = $rid;
									pdo_insert('news_reply', $reply2);
								}
							} else {
								pdo_insert('news_reply', $reply);
								$data['rid'] = $rid;
								$data['kid'] = $kid;
							}
						} else {
							$data['rid'] = 0;
							$data['kid'] = 0;
						}
						pdo_update('han_article_category', $data, array('id' => $id));
					} else {
						if (!empty($_GPC['keyword'])) {
							pdo_insert('rule', $rule);
							$rid = pdo_insertid();
							$keyword['rid'] = $rid;
							pdo_insert('rule_keyword', $keyword);
							$kid = pdo_insertid();
							$reply['rid'] = $rid;
							$data['rid'] = $rid;
							$data['kid'] = $kid;
						}
						pdo_insert('han_article_category', $data);
						$aid = pdo_insertid();
						if (!empty($_GPC['keyword'])) {
							$alist = pdo_fetchall("SELECT * FROM " . tablename('han_article') . " WHERE weid={$weid} AND ccate={$aid}  ORDER BY displayorder ASC limit 8 ");
							if ($alist) {
								foreach ($alist as $par) {
									$reply2['title'] = $par['title'];
									$reply2['description'] = $par['description'];
									$reply2['thumb'] = $par['thumb'];
									$reply2['url'] = murl('entry/module/detail', array('m' => 'han_article', 'id' => $par[id]));
									$reply2['rid'] = $rid;
									pdo_insert('news_reply', $reply2);
								}
							} else {
								$reply['title'] = $_GPC['cname'];
								$reply['description'] = $_GPC['description'];
								$reply['thumb'] = $_GPC['thumb'];
								$reply['displayorder'] = $_GPC['displayorder'];
								$reply['url'] = murl('entry/module/index', array('m' => 'han_article', 'cid' => $aid));
								pdo_insert('news_reply', $reply);
							}
						}
					}
					message('更新版块成功！', $this->createWebUrl('category', array('do' => 'display')), 'success');
				}
			} elseif ($op == 'delete') {
				load()->func('file');
				$id = intval($_GPC['id']);
				pdo_delete('han_article_category', array('id' => $id));
				message('版块删除成功！', $this->createWebUrl('category', array('do' => 'display')), 'success');
			} elseif ($op == 'fetch') {
				$category = pdo_fetchall("SELECT id, name FROM " . tablename('han_article_category') . " WHERE parentid = '" . intval($_GPC['parentid']) . "' ORDER BY id ASC, displayorder ASC, id ASC ");
				message($category, '', 'ajax');
			}
		}
		include $this->template('category');
	}
	public function doWebArticle()
	{
		global $_W, $_GPC;
		$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		$weid = $_W['uniacid'];
		load()->func('tpl');
		load()->model('mc');
		$category = pdo_fetchall("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid =$weid ORDER BY parentid ASC, displayorder ASC, id ASC ", array(), 'id');
		$parent = array();
		$children = array();
		if (!empty($category)) {
			$children = '';
			foreach ($category as $cid => $cate) {
				if (!empty($cate['parentid'])) {
					$children[$cate['parentid']][] = $cate;
				} else {
					$parent[$cate['id']] = $cate;
				}
			}
		}
		$config = $this->module['config']['bbs'];
		if ($op == 'display') {
			if (!empty($_GPC['displayorder'])) {
				foreach ($_GPC['displayorder'] as $id => $displayorder) {
					pdo_update('han_article', array('displayorder' => $displayorder), array('id' => $id));
				}
				message('帖子排序更新成功！', $this->createWebUrl('Article', array('op' => 'display')), 'success');
			}
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$condition = '';
			$params = array();
			if (!empty($_GPC['keyword'])) {
				$condition .= " AND title LIKE :keyword";
				$params[':keyword'] = "%{$_GPC['keyword']}%";
			}
			if (!empty($_GPC['category']['childid'])) {
				$cid = intval($_GPC['category']['childid']);
				$condition .= " AND ccate = '{$cid}'";
			} elseif (!empty($_GPC['category']['parentid'])) {
				$cid = intval($_GPC['category']['parentid']);
				$condition .= " AND pcate = '{$cid}'";
			}
			$sql = "SELECT * FROM " . tablename('han_article') . " WHERE weid = '{$weid}' $condition ORDER BY zdtime DESC,createtime DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
			$list = pdo_fetchall($sql, $params);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('han_article') . " WHERE weid = '{$weid}'");
			$pager = pagination($total, $pindex, $psize);
		} elseif ($op == 'post') {
			$id = intval($_GPC['id']);
			load()->func('file');
			if ($id > 0) {
				$item = pdo_fetch("SELECT * FROM " . tablename('han_article') . " WHERE id = :id", array(':id' => $id));
				if (empty($item)) {
					message('抱歉，帖子不存在或是已经删除！', '', 'error');
				}
			}
			if (checksubmit('submit')) {
				empty($_GPC['title']) ? message('亲,标题不能为空') : ($title = $_GPC['title']);
				$data = array('weid' => $_W['uniacid'], 'pcate' => intval($_GPC['category']['parentid']), 'ccate' => intval($_GPC['category']['childid']), 'title' => $title, 'content' => htmlspecialchars_decode($_GPC['content'], ENT_QUOTES), 'displayorder' => intval($_GPC['displayorder']), 'content_j' => htmlspecialchars_decode($_GPC['content_j'], ENT_QUOTES), 'open_xs' => intval($_GPC['open_xs']), 'open_yc' => intval($_GPC['open_yc']), 'xsNum' => intval($_GPC['xsNum']), 'gmjf' => $_GPC['gmjf'], 'clickNum' => $_GPC['clickNum'], 'zanNum' => intval($_GPC['zanNum']), 'address' => 'web', 'is_fb' => 1, 'is_zd' => intval($_GPC['is_zd']), 'is_jh' => intval($_GPC['is_jh']), 'createtime' => TIMESTAMP, 'zdtime' => TIMESTAMP, 'replytime' => TIMESTAMP);
				$sensitives = pdo_fetchall("SELECT * FROM " . tablename('han_sensitive') . " WHERE weid = '{$_W['uniacid']}' ");
				if (!empty($sensitives)) {
					foreach ($sensitives as $sens) {
						if (stristr($title, $sens['word'])) {
							message('你的帖子标题包含敏感词，不允许发表！');
							exit;
						}
						if (stristr($data['content'], $sens['word']) || stristr($data['content_j'], $sens['word'])) {
							message('你的帖子内容包含敏感词，不允许发表！');
							exit;
						}
					}
				}
				$data['content'] = str_replace("application/x-shockwave-flash", "", $data['content']);
				$data['content_j'] = str_replace("application/x-shockwave-flash", "", $data['content_j']);
				if ($data['open_yc'] == 0) {
					$data['open_yc'] = 2;
				}
				$bk = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid ={$weid} and id= {$data['pcate']}");
				$embed = array();
				preg_match_all('/<embed\s.*?>/', $data['content'], $embed);
				$imgs = array();
				preg_match_all('/<img\s.*?>/', $data['content'], $imgs);
				if (!empty($imgs)) {
					$pics = array();
					foreach ($imgs as $img) {
						if (is_array($img)) {
							foreach ($img as $ig) {
								$match = array();
								preg_match('/attachment\/(.*?)(\.gif|\.jpg|\.png|\.bmp)/', $ig, $match);
								$matche = array();
								preg_match('/ueditor\/(.*?)(\.gif|\.jpg|\.png|\.bmp)/', $ig, $matche);
								if (!empty($match[1])) {
									$imgurl = tomedia($match[1] . $match[2]);
								} else {
									if (!empty($matche[1])) {
										$imgurl = $_W['siteroot'] . $matche[0];
									} else {
										preg_match('/(http|https):\/\/(.*?)(\.gif|\.jpg|\.png|\.bmp)/', $ig, $match);
										$imgurl = $match[0];
									}
								}
								if (!empty($imgurl)) {
									array_push($pics, $imgurl);
								}
							}
						} else {
							$match = array();
							preg_match('/attachment\/(.*?)(\.gif|\.jpg|\.png|\.bmp)/', $img, $match);
							$matche = array();
							preg_match('/ueditor\/(.*?)(\.gif|\.jpg|\.png|\.bmp)/', $img, $matche);
							if (!empty($match[1])) {
								$imgurl = $match[1] . $match[2];
							} else {
								if (!empty($matche[1])) {
									$imgurl = $_W['siteroot'] . $matche[0];
								} else {
									preg_match('/(http|https):\/\/(.*?)(\.gif|\.jpg|\.png|\.bmp)/', $img, $match);
									$imgurl = $match[0];
								}
							}
							if (!empty($imgurl)) {
								array_push($pics, $imgurl);
							}
						}
					}
					foreach ($pics as $pic) {
						$array = getimagesize($pic);
						if ($array[0] >= 100) {
							$imgarr[] = $pic;
						}
					}
					$data['pics'] = serialize($imgarr);
				}
				if (empty($id)) {
					$zzinfo = mc_fansinfo($config['zzuid']);
					if (!empty($zzinfo)) {
						$data['uid'] = $config['zzuid'];
						$data['author'] = $zzinfo['tag']['nickname'];
						$data['avatar'] = $zzinfo['tag']['avatar'];
						$data['openid'] = $zzinfo['tag']['openid'];
					}
					if ($data['is_jh'] == 1) {
						mc_credit_update($data['uid'], 'credit1', $bk['jf1'], array(1 => "后台发帖奖励积分"));
						mc_credit_update($data['uid'], 'credit1', $bk['jf2'], array(1 => "后台发帖加精华奖励积分"));
					}
					pdo_insert('han_article', $data);
				} else {
					if ($item['is_jh'] == 0 && $data['is_jh'] == 1) {
						mc_credit_update($item['uid'], 'credit1', $bk['jf2'], array(1 => "后台加精华奖励积分"));
					}
					if ($item['is_jh'] == 1 && $data['is_jh'] == 0) {
						mc_credit_update($item['uid'], 'credit1', -$bk['jf2'], array(1 => "后台取消精华扣除积分"));
					}
					pdo_update('han_article', $data, array('id' => $id));
				}
				message('帖子更新成功！', $this->createWebUrl('Article', array('foo' => 'display')), 'success');
			}
		} elseif ($op == 'fb') {
			$id = intval($_GPC['id']);
			$data = array('is_fb' => 1);
			pdo_update('han_article', $data, array('id' => $id));
			message('帖子发表成功！', $this->createWebUrl('Article', array('foo' => 'display')), 'success');
		} elseif ($op == 'delete') {
			load()->func('file');
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT * FROM " . tablename('han_article') . " WHERE id = :id", array(':id' => $id));
			$bk = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid ={$weid} and id= {$row['pcate']}");
			if (empty($row)) {
				message('抱歉，帖子不存在或是已经被删除！');
			}
			if (!empty($row['thumb'])) {
				file_delete($row['thumb']);
			}
			if (!empty($row['rid'])) {
				pdo_delete('rule', array('id' => $row['rid'], 'uniacid' => $_W['uniacid']));
				pdo_delete('rule_keyword', array('rid' => $row['rid'], 'uniacid' => $_W['uniacid']));
				pdo_delete('news_reply', array('rid' => $row['rid']));
			}
			load()->model('mc');
			$handsel = array('module' => 'han_article', 'sign' => md5(iserializer(array('id' => $id))), 'action' => 'write', 'credit_value' => -$bk['jf1'], 'credit_log' => '发表的帖子被删除,扣除赠送积分');
			$formuid = -1;
			mc_handsel($row['uid'], $formuid, $handsel, $_W['uniacid']);
			pdo_delete('han_article', array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		include $this->template('article');
	}
	public function doWebComment()
	{
		global $_GPC, $_W;
		$op = $_GPC['op'] ? $_GPC['op'] : 'list';
		$articleid = $_GPC['articleid'];
		$weid = $_W['uniacid'];
		if ($op == 'list') {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			if (empty($articleid)) {
				$condition = "WHERE weid =" . $weid;
			} else {
				$condition = "WHERE weid = $weid and aid=" . $articleid;
			}
			$status = $_GPC['status'];
			if (!empty($_GPC['keyword'])) {
				$condition .= " WHERE author LIKE '%" . $_GPC['keyword'] . "%'";
			}
			if ($status != '') {
				$condition .= " AND status = '" . $status . "'";
			}
			$list = pdo_fetchall('SELECT * FROM ' . tablename('han_comment') . " " . $condition . " order by createtime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('han_comment') . $condition);
			$pager = pagination($total, $pindex, $psize);
		} elseif ($op == 'view') {
			$articleid = intval($_GPC['articleid']);
			$id = intval($_GPC['id']);
			$art = pdo_fetch("SELECT * FROM " . tablename('han_article') . " WHERE id = :id", array(':id' => $articleid));
			$item = pdo_fetch("SELECT * FROM " . tablename('han_comment') . " WHERE id = :id", array(':id' => $id));
			if (empty($item)) {
				message('抱歉，导航不存在或是已经删除！', '', 'error');
			}
		} elseif ($op == 'delete') {
			if (isset($_GPC['delete'])) {
				$ids = implode(",", $_GPC['delete']);
				$sqls = "delete from  " . tablename('han_comment') . "  where id in(" . $ids . ")";
				pdo_query($sqls);
				message('删除成功！', referer(), 'success');
			}
			$id = intval($_GPC['id']);
			$temp = pdo_delete("han_comment", array('id' => $id));
			message('删除数据成功！', $this->createWebUrl('comment', array('op' => 'list', 'articleid' => $articleid)), 'success');
		} elseif ($op == 'vervify') {
			$id = intval($_GPC['id']);
			$recommed = $_GPC['status'];
			$articleid = $_GPC['articleid'];
			if ($recommed == 1) {
				$msg = '审核';
			}
			if ($id > 0) {
				pdo_update('han_comment', array('status' => $recommed), array('id' => $id));
				message($msg . '成功！', $this->createWebUrl('comment', array('op' => 'list', 'articleid' => $articleid)), 'success');
			}
		} elseif ($op == 'post') {
			$id = intval($_GPC['id']);
			$articleid = $_GPC['articleid'];
			$art = pdo_fetch("SELECT * FROM " . tablename('han_article') . " WHERE id = :id", array(':id' => $articleid));
			if ($id > 0) {
				$item = pdo_fetch("SELECT * FROM " . tablename('han_comment') . " WHERE id = :id", array(':id' => $id));
				if (empty($item)) {
					message('抱歉，评论不存在或是已经删除！', '', 'error');
				}
			}
			if (checksubmit('submit')) {
				$data = array('js_cmt_reply' => $_GPC['js_cmt_reply'], 'updatetime' => TIMESTAMP);
				pdo_update('han_comment', $data, array('id' => $id));
				message('评论作者回复成功！', $this->createWebUrl('comment', array('op' => 'list', 'articleid' => $articleid)), 'success');
			}
		}
		include $this->template('comment');
	}
	public function doWebSensitive()
	{
		global $_W, $_GPC;
		$op = $_GPC['op'] ? $_GPC['op'] : 'display';
		$weid = $_W['uniacid'];
		if ($_GPC['submit'] == '批量导出') {
			$list = pdo_fetchall("SELECT * FROM " . tablename('han_sensitive') . " WHERE weid = '{$_W['uniacid']}'  ORDER BY createtime DESC");
			$str = '';
			$n = 0;
			foreach ($list as $word) {
				$n = $n + 1;
				$str = $str . $word['word'] . '  ';
				if ($n % 10 == 0) {
					$str = $str . "\r\n";
				}
			}
			$filename = 'Allsensitive.txt';
			header("Content-type: text/plain");
			header("Accept-Ranges: bytes");
			header("Content-Disposition: attachment; filename=" . $filename);
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Pragma: no-cache");
			header("Expires: 0");
			exit($str);
		}
		if ($_GPC['submit'] == '批量导入') {
			if (!empty($_FILES['txtfile']['name'])) {
				$tmp_file = $_FILES['txtfile']['tmp_name'];
				$file_types = explode(".", $_FILES['txtfile']['name']);
				$file_type = $file_types[count($file_types) - 1];
				if ($file_type != "txt") {
					message('不是txt文件，重新上传', referer(), 'error');
				}
				$savePath = IA_ROOT . '/addons/han_article/sstword/';
				$str = TIMESTAMP;
				$file_name = $str . "." . $file_type;
				load()->func('file');
				if (!file_move($tmp_file, $savePath . $file_name)) {
					message('上传失败:tmp_file ' . $tmp_file . ' savePath:' . $savePath . $file_name, referer(), 'error');
				}
				$content = file_get_contents(MODULE_URL . '/sstword/' . $file_name);
				$isutf8 = mb_check_encoding($content, 'utf-8');
				if ($isutf8 != 'utf-8') {
					$content = iconv('gb2312', 'utf-8', file_get_contents(MODULE_URL . '/sstword/' . $file_name));
				} else {
					$content = file_get_contents(MODULE_URL . '/sstword/' . $file_name);
				}
				$str = str_replace("\n", "&", str_replace("\r", "", $content));
				$array = array();
				parse_str($str, $array);
				foreach ($array as $key => $val) {
					$data = array('weid' => $_W['uniacid'], 'word' => $key, 'createtime' => TIMESTAMP);
					pdo_insert('han_sensitive', $data);
				}
				message('导入成功', referer(), 'success');
			}
		}
		if ($op == 'display') {
			$sql = "";
			$params = array();
			if (!empty($_GPC['keywords'])) {
				$sql = $sql . ' and `word` like \'%' . $_GPC["keywords"] . '%\'';
			}
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$list = pdo_fetchall("SELECT * FROM " . tablename('han_sensitive') . " WHERE weid = '{$_W['uniacid']}' " . $sql . " ORDER BY createtime DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('han_sensitive') . " WHERE weid = '{$_W['uniacid']}' " . $sql, $params);
			$pager = pagination($total, $pindex, $psize);
		} else {
			if ($op == 'delete') {
				$id = intval($_GPC['id']);
				pdo_delete('han_sensitive', array('id' => $id));
				message("敏感词删除成功!", referer(), "success");
			} else {
				if ($op == 'deleteall') {
					foreach ($_GPC['idArr'] as $k => $id) {
						$id = intval($id);
						pdo_delete("han_sensitive", array("id" => $id));
					}
					message("敏感词删除成功!", referer(), "success");
				} else {
					if ($op == 'add') {
						$data = array('weid' => $_W['uniacid'], 'word' => $_GPC['word'], 'createtime' => TIMESTAMP);
						$reck = pdo_fetchall("SELECT * FROM " . tablename('han_sensitive') . " WHERE weid = '{$_W['uniacid']}' and `word`='{$_GPC['word']}'");
						if (empty($reck)) {
							pdo_insert('han_sensitive', $data);
							message("敏感词新增成功!", referer(), "success");
						} else {
							message('该敏感词已存在!', referer(), 'error');
						}
					}
				}
			}
		}
		include $this->template('sensitive');
	}
	public function doWebSendMsg()
	{
		global $_W, $_GPC;
		$op = $_GPC['op'] ? $_GPC['op'] : 'display';
		$weid = $_W['uniacid'];
		load()->func('tpl');
		load()->model('mc');
		$id = intval($_GPC['id']);
		if ($op == 'post') {
			$groups = mc_groups();
			if (!empty($id)) {
				$sendmsg = pdo_fetch("SELECT * FROM " . tablename('han_art_sendMsg') . " WHERE id = " . $id . " AND weid =" . $weid);
				if (empty($sendmsg)) {
					message('通知不存在或已删除', '', 'error');
				}
			}
			$grpid = unserialize($sendmsg['groupid']);
			if (checksubmit('submit')) {
				$data = array('weid' => $_W['uniacid'], 'title' => $_GPC['title'] ? $_GPC['title'] : '系统通知', 'url' => $_GPC['url'], 'groupid' => serialize($_GPC['groupid']), 'content' => htmlspecialchars_decode($_GPC['content'], ENT_QUOTES), 'createtime' => TIMESTAMP);
				if (!empty($sendmsg)) {
					pdo_update('han_art_sendMsg', $data, array('id' => $sendmsg['id']));
				} else {
					pdo_insert('han_art_sendMsg', $data);
				}
				message('系统通知发送成功！', 'refresh');
			}
		} elseif ($op == 'display') {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$condition = '';
			$params = array();
			if (!empty($_GPC['keyword'])) {
				$condition .= " AND title LIKE :keyword";
				$params[':keyword'] = "%{$_GPC['keyword']}%";
			}
			$sql = "SELECT * FROM " . tablename('han_art_sendMsg') . " WHERE weid = '{$weid}' $condition ORDER BY createtime DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
			$sendlist = pdo_fetchall($sql, $params);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('han_art_sendMsg') . " WHERE weid = '{$weid}'");
			$pager = pagination($total, $pindex, $psize);
		} elseif ($op == 'delete') {
			$id = intval($_GPC['id']);
			pdo_delete('han_art_sendMsg', array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		include $this->template('sendmsg');
	}
	public function doWebMb()
	{
		global $_W, $_GPC;
		$tb = 'han_article_slides';
		$config = $this->module['config']['bbs'];
		$operation = empty($_GPC['op']) ? 'display' : $_GPC['op'];
		if ($operation == "post") {
			if ($_GPC['submit'] == '提交') {
				$slides = array();
				$link = trim($_GPC['link']);
				$slides['weid'] = $_W['uniacid'];
				$slides['name'] = $_GPC['name'];
				$slides['link'] = substr($link, 0, 4) == 'http' ? $link : 'http://' . $link;
				$slides['thumb'] = $_GPC['thumb'];
				$slides['time'] = time();
				$result = pdo_insert($tb, $slides);
				if (!empty($result)) {
					$uid = pdo_insertid();
					message('添加图片成功.', 'refresh', 'success');
				}
			}
		} elseif ($operation == "display") {
			$slide = pdo_fetchall("SELECT * FROM " . tablename($tb) . " WHERE `weid`=:uniacid ORDER BY  `id` DESC", array(':uniacid' => $_W['uniacid']));
		} elseif ($operation == "delete") {
			$id = $_GPC['id'];
			pdo_delete($tb, array('id' => $id, 'weid' => $_W['uniacid']));
			message('删除成功.', $this->createWebUrl('mb', array('op' => 'display')), 'success');
		}
		include $this->template('mb');
	}
	public function doMobileXoauth()
	{
		global $_W, $_GPC;
		if ($_GPC['code'] == "authdeny" || empty($_GPC['code'])) {
			exit("授权失败");
		}
		load()->func('communication');
		$appid = $this->module['config']['api']['appid'];
		$secret = $this->module['config']['api']['secret'];
		$state = $_GPC['state'];
		$code = $_GPC['code'];
		$oauth2_code = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&secret=" . $secret . "&code=" . $code . "&grant_type=authorization_code";
		$content = ihttp_get($oauth2_code);
		$token = @json_decode($content['content'], true);
		if (empty($token) || !is_array($token) || empty($token['access_token']) || empty($token['openid'])) {
			echo '<h1>获取微信公众号授权' . $code . '失败[无法取得token以及openid], 请稍后重试！ 公众平台返回原始数据为: <br />' . $content['meta'] . '<h1>';
			exit;
		}
		$from_user = $token['openid'];
		$weid = $_W['uniacid'];
		setcookie('klhb_openid' . $weid, $from_user, time() + 3600 * (24 * 5));
		$url = $_COOKIE["xoauthURL"];
		header("location:$url");
		exit;
	}
	public function doMobileIndex()
	{
		global $_GPC, $_W;
		$weid = $_W['uniacid'];
		$cid = intval($_GPC['cid']);
		load()->model("mc");
		$userinfo = mc_oauth_userinfo();
		$groups = mc_groups();
		$config = $this->module['config']['bbs'];
		$logo = tomedia($config['logo']);
		$pic = $config['pic'];
		$member = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uid = :uid LIMIT 1", array(':uid' => $_W['member']['uid']));
		$groupid = $member['groupid'];
		$category = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE id = '{$cid}'");
		$result = pdo_fetchall("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid =$weid AND parentid = 0 ORDER BY displayorder ASC, id ASC ");
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$key = trim($_GPC['keyword']);
		setcookie('xoauthURL', $this->createMobileUrl('index'), time() + 3600 * (24 * 5));
		if (!empty($key)) {
			$condition = "AND title LIKE '%{$key}%' ";
		}
		if (!empty($cid)) {
			$list = pdo_fetchall("SELECT * FROM " . tablename('han_article') . " WHERE is_fb =1 and is_zd =0 and weid={$weid} AND pcate={$cid} " . $condition . " ORDER BY replytime DESC LIMIT 0,5");
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('han_article') . " WHERE is_fb =1 and is_zd =0 and weid={$weid} AND pcate={$cid} " . $condition);
			$pager = myPagination($total, $pindex, $psize);
			$endnum = $total;
			$zdlist = pdo_fetchall("SELECT * FROM " . tablename('han_article') . " WHERE is_fb =1 and is_zd =1 and weid={$weid} AND pcate={$cid}  ORDER BY zdtime DESC ");
		} else {
			$list = pdo_fetchall("SELECT * FROM " . tablename('han_article') . " WHERE is_fb =1 and is_zd =0 and weid={$weid} " . $condition . "ORDER BY replytime DESC LIMIT 0,5");
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('han_article') . " WHERE is_fb =1 and is_zd =0 and weid={$weid} " . $condition);
			$pager = myPagination($total, $pindex, $psize);
			$endnum = $total;
			$zdlist = pdo_fetchall("SELECT * FROM " . tablename('han_article') . " WHERE is_fb =1 and is_zd =1 and weid={$weid} ORDER BY zdtime DESC ");
		}
		foreach ($list as $k => $v) {
			$fan = mc_fetch($v['uid']);
			$list[$k]['gender'] = $fan['gender'];
			$list[$k]['groupid'] = $fan['groupid'];
			$bk = pdo_fetch("SELECT name FROM " . tablename('han_article_category') . " WHERE id =" . $v['pcate']);
			$list[$k]['bkname'] = $bk['name'];
			$bzsnum = 0;
			$bzslist = pdo_fetchall("SELECT js_ds_input FROM " . tablename('han_comment') . " WHERE weid={$_W['uniacid']} and aid={$v['id']} and js_ds_input>0 ");
			foreach ($bzslist as $bzs) {
				$bzsnum += $bzs['js_ds_input'];
			}
			$list[$k]['bzsnum'] = $bzsnum;
		}
		$tb = 'han_article_slides';
		$slide = pdo_fetchall("SELECT * FROM " . tablename($tb) . " WHERE `weid`=:uniacid ORDER BY  `id` DESC", array(':uniacid' => $_W['uniacid']));
		$artcount = pdo_fetchcolumn("SELECT count(*) FROM " . tablename('han_article') . " WHERE  is_fb =1 and weid={$weid}");
		$rnumlist = pdo_fetchall("SELECT clickNum FROM " . tablename('han_article') . " WHERE  is_fb =1 and weid={$weid}");
		$rnum = 0;
		foreach ($rnumlist as $rart) {
			$rnum += $rart['clickNum'];
		}
		include $this->template('list/list2');
	}
	public function doMobileGods()
	{
		global $_GPC, $_W;
		$id = intval($_GPC['id']);
		$weid = $_W['uniacid'];
		load()->model('mc');
		$sql = "SELECT * FROM " . tablename('han_article') . " WHERE `id`=:id AND weid = :uniacid";
		$detail = pdo_fetch($sql, array(':id' => $id, ':uniacid' => $_W['uniacid']));
		if ($_GPC['op'] == 'ds') {
			$bk = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid ={$_W['uniacid']} and id= {$detail['pcate']}");
			$result = mc_credit_fetch($_W['member']['uid']);
			$js_ds_input = $_GPC['dsNum'];
			$js_ds_output = -$js_ds_input;
			if ($result['credit1'] < $js_ds_input) {
				$res = array('code' => 'no', 'msg' => '你的积分不足，请查询后打赏！');
				return json_encode($res);
			} else {
				mc_credit_update($detail['uid'], 'credit1', $js_ds_input, array(1 => "打赏"));
				mc_credit_update($_W['member']['uid'], 'credit1', $js_ds_output, array(1 => "打赏"));
			}
			if (empty($_W['fans']['follow'])) {
				mc_oauth_userinfo();
			}
			$data = array('weid' => $weid, 'js_cmt_input' => $_W['fans']['nickname'] . '打赏' . $detail['author'] . $js_ds_input . '积分', 'js_ds_input' => $js_ds_input, 'status' => $_GPC['status'], 'aid' => $id, 'author' => $_W['fans']['nickname'], 'thumb' => $_W['fans']['tag']['avatar'], 'openid' => $_W['fans']['from_user'], 'createtime' => time());
			if ($bk['is_rcheck']) {
				$data['status'] = 0;
			} else {
				$data['status'] = 1;
			}
			pdo_insert('han_comment', $data);
			$res = array('code' => 'yes', 'msg' => '你打赏给帖子作者' . $js_ds_input . '积分！');
			return json_encode($res);
		}
		include $this->template('detail/admire');
	}
	private function get_user_avatar($from_user_openid, $key = '')
	{
		global $_W, $_GPC;
		$condition = " fans.uniacid='" . $_W['uniacid'] . "' and fans.openid='" . $from_user_openid . "'";
		$sql = "SELECT fans.nickname, fans.unionid, mem.credit1, mem.credit2, mem.avatar, fans.tag " . "FROM " . tablename('mc_mapping_fans') . " fans " . "LEFT JOIN " . tablename('mc_members') . " mem ON fans.uid=mem.uid " . "WHERE $condition LIMIT 1";
		$member_arr = pdo_fetch($sql);
		if ($key == 'avatar' && !$member_arr[$key]) {
			if (!empty($member_arr['tag']) && is_string($member_arr['tag'])) {
				if (is_base64($member_arr['tag'])) {
					$member_arr['tag'] = base64_decode($member_arr['tag']);
				}
				if (is_serialized($member_arr['tag'])) {
					$member_arr['tag'] = @iunserializer($member_arr['tag']);
				}
				if (!empty($member_arr['tag']['headimgurl'])) {
					return tomedia($member_arr['tag']['headimgurl']);
				}
			}
			return $_W['siteroot'] . 'web/resource/images/noavatar_middle.gif';
		}
		if ($key) {
			return @$member_arr[$key];
		}
		return $member_arr;
	}
	public function get_fans_pic($url, $openid)
	{
		$hdrs = @get_headers($url);
		$pic_valid = is_array($hdrs) ? preg_match('/^HTTP\\/\\d+\\.\\d+\\s+2\\d\\d\\s+.*$/', $hdrs[0]) : false;
		if (!$pic_valid) {
			return $this->get_user_avatar($openid, 'avatar');
		}
		return $url;
	}
	public function doMobileZan()
	{
		global $_GPC, $_W;
		$op = $_GPC['op'];
		$id = intval($_GPC['id']);
		$num = intval($_GPC['num']);
		$weid = $_W['uniacid'];
		load()->model('mc');
		if ($_GPC['op'] == 'dz') {
			$aid = intval($_GPC['aid']);
			$sql = "SELECT * FROM " . tablename('han_comment') . " WHERE `id`=:id AND weid = :uniacid";
			$comment = pdo_fetch($sql, array(':id' => $id, ':uniacid' => $_W['uniacid']));
			pdo_update('han_comment', array('praise_num' => $comment['praise_num'] + $num), array('id' => $id));
			if ($num > 0) {
				pdo_insert('han_artdz', array('weid' => $_W['uniacid'], 'uid' => $_W['member']['uid'], 'aid' => $aid, 'cid' => $id, 'num' => 1));
			} else {
				pdo_delete('han_artdz', array('weid' => $_W['uniacid'], 'uid' => $_W['member']['uid'], 'aid' => $aid, 'cid' => $id));
			}
			$detail = pdo_fetch("SELECT * FROM " . tablename('han_article') . " WHERE `id`=:id AND weid = :uniacid", array(':id' => $aid, ':uniacid' => $_W['uniacid']));
			$bk = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid ={$_W['uniacid']} and id= {$detail['pcate']}");
			$s_info = mc_fansinfo($comment['openid']);
			$s_uid = $s_info['uid'];
			if (!empty($s_uid)) {
				if ($num > 0) {
					$handsel = array('module' => 'han_article', 'sign' => md5(iserializer(array('id' => $id))), 'action' => 'dzarticle', 'credit_value' => $bk['jf3'], 'credit_log' => '回帖被点赞,赠送积分');
				} else {
					$handsel = array('module' => 'han_article', 'sign' => md5(iserializer(array('id' => $id))), 'action' => 'dzarticle', 'credit_value' => -$bk['jf3'], 'credit_log' => '回帖被取消点赞,扣除赠送积分');
				}
				$formuid = $_W['member']['uid'];
				if ($s_uid != $_W['member']['uid']) {
					mc_handsel($s_uid, $formuid, $handsel, $_W['uniacid']);
				}
			}
			$res = array();
			$res['znum'] = $comment['praise_num'] + $num;
			return json_encode($res);
		} else {
			$sql = "SELECT * FROM " . tablename('han_article') . " WHERE `id`=:id AND weid = :uniacid";
			$detail = pdo_fetch($sql, array(':id' => $id, ':uniacid' => $_W['uniacid']));
			$bk = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid ={$_W['uniacid']} and id= {$detail['pcate']}");
			pdo_update('han_article', array('zanNum' => $detail['zanNum'] + $num), array('id' => $id));
			if ($num > 0) {
				pdo_insert('han_artdz', array('weid' => $_W['uniacid'], 'uid' => $_W['member']['uid'], 'aid' => $id, 'num' => 1));
			} else {
				pdo_delete('han_artdz', array('weid' => $_W['uniacid'], 'uid' => $_W['member']['uid'], 'aid' => $id));
			}
			$s_uid = $detail['uid'];
			if (!empty($s_uid)) {
				if ($num > 0) {
					$handsel = array('module' => 'han_article', 'sign' => md5(iserializer(array('id' => $id))), 'action' => 'dzarticle', 'credit_value' => $bk['jf3'], 'credit_log' => '帖子被点赞,赠送积分');
				} else {
					$handsel = array('module' => 'han_article', 'sign' => md5(iserializer(array('id' => $id))), 'action' => 'dzarticle', 'credit_value' => -$bk['jf3'], 'credit_log' => '帖子被取消点赞,扣除赠送积分');
				}
				$formuid = $_W['member']['uid'];
				if ($s_uid != $_W['member']['uid']) {
					mc_handsel($s_uid, $formuid, $handsel, $_W['uniacid']);
				}
			}
			$res = array();
			$res['znum'] = $detail['zanNum'] + $num;
			return json_encode($res);
		}
	}
	public function doMobilePublish()
	{
		global $_GPC, $_W;
		$id = intval($_GPC['id']);
		$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		$weid = $_W['uniacid'];
		load()->func('tpl');
		$config = $this->module['config']['bbs'];
		$imgsize = intval($GLOBALS['_W']['setting']['upload']['image']['limit']) * 1024;
		$lasttime = pdo_fetchcolumn("SELECT createtime FROM " . tablename('han_article') . " WHERE `uid`=:uid AND weid = :uniacid order by createtime desc limit 1", array(':uid' => $_W['member']['uid'], ':uniacid' => $_W['uniacid']));
		if (!empty($config['send_times']) && TIMESTAMP - $lasttime < $config['send_times']) {
			message('亲,你发帖太频繁了，请稍后再试！', $this->createMobileUrl('index'));
			exit;
		}
		$category = pdo_fetchall("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid =$weid ORDER BY parentid ASC, displayorder ASC, id ASC ", array(), 'id');
		$parent = array();
		$children = array();
		if (!empty($category)) {
			$children = '';
			foreach ($category as $cid => $cate) {
				$wgrpid = unserialize($cate['wgrpid']);
				if (empty($_W['fans']['nickname'])) {
					load()->model('mc');
					$member = mc_oauth_userinfo();
					$member = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ={$_W['uniacid']} and nickname='" . $member['nickname'] . "' limit 1");
				} else {
					$member = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ={$_W['uniacid']} and uid=" . $_W['member']['uid'] . " limit 1");
				}
				if (in_array($member['groupid'], $wgrpid)) {
					if (!empty($cate['parentid'])) {
						$children[$cate['parentid']][] = $cate;
					} else {
						$parent[$cate['id']] = $cate;
					}
				}
			}
		}
		if (!empty($id)) {
			$sql = "SELECT * FROM " . tablename('han_article') . " WHERE `id`=:id AND weid = :uniacid";
			$detail = pdo_fetch($sql, array(':id' => $id, ':uniacid' => $_W['uniacid']));
			$pics = unserialize($detail['pics']);
			foreach ($pics as $key => $pic) {
				$pic = tomedia($pic);
			}
			$bk = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid ={$_W['uniacid']} and id= {$detail['pcate']}");
		}
		if (empty($_W['member']['uid'])) {
			load()->model('mc');
			$member = mc_oauth_userinfo();
			$member = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ={$_W['uniacid']} and nickname='" . $member['nickname'] . "' limit 1");
		} else {
			$member = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ={$_W['uniacid']} and uid=" . $_W['member']['uid'] . " limit 1");
		}
		$groupid = $member['groupid'];
		$sensitives = pdo_fetchall("SELECT * FROM " . tablename('han_sensitive') . " WHERE weid = '{$_W['uniacid']}' ");
		if ($_GPC['submit'] == '直接发表') {
			empty($_GPC['title']) ? message('亲,标题不能为空') : ($title = $_GPC['title']);
			foreach ($sensitives as $sens) {
				if (stristr($_GPC['title'], $sens['word'])) {
					message('你的帖子标题包含敏感词，不允许发表！', $this->createMobileUrl('index'));
					exit;
				}
				if (stristr($_GPC['content'], $sens['word'])) {
					message('你的帖子内容包含敏感词，不允许发表！', $this->createMobileUrl('index'));
					exit;
				}
			}
			$data = array('weid' => $_W['uniacid'], 'pcate' => intval($_GPC['category']['parentid']), 'ccate' => intval($_GPC['category']['childid']), 'title' => $title, 'content' => htmlspecialchars_decode($_GPC['content']), 'uid' => $_W['member']['uid'], 'author' => $_W['fans']['nickname'], 'avatar' => $_W['fans']['tag']['avatar'], 'openid' => $_W['fans']['from_user'], 'address' => 'mobile', 'open_yc' => 2, 'createtime' => TIMESTAMP, 'replytime' => TIMESTAMP);
			if ($data['pcate'] == 0) {
				$cate = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid ={$weid} limit 1");
				$data['pcate'] = $cate['id'];
			}
			$pictures = $_GPC['thumb'];
			$data['pics'] = serialize($pictures);
			if (empty($id)) {
				$bk = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid ={$weid} and id= {$data['pcate']}");
				$wgrpid = unserialize($bk['wgrpid']);
				if (!in_array($groupid, $wgrpid)) {
					message('亲,你没有该版块的发帖权限！', $this->createMobileUrl('index'));
					exit;
				}
				if (is_array($_GPC['thumb'])) {
					$data['thumb'] = $_GPC['thumb'][0];
				} else {
					$data['thumb'] = $_GPC['thumb'];
				}
				if ($bk['is_fcheck']) {
					$data['is_fb'] = 0;
				} else {
					$data['is_fb'] = 1;
				}
				if (pdo_insert('han_article', $data) && !empty($bk['jf1'])) {
					load()->model('mc');
					$aid = pdo_insertid();
					$handsel = array('module' => 'han_article', 'sign' => md5(iserializer(array('id' => $aid))), 'action' => 'write', 'credit_value' => $bk['jf1'], 'credit_log' => '发表文章,赠送积分');
					$formuid = -1;
					mc_handsel($_W['member']['uid'], $formuid, $handsel, $_W['uniacid']);
				}
			} else {
				$tmp_var = $_GPC['category']['parentid'];
				$tmp_var = (int) $tmp_var;
				if (empty($tmp_var)) {
					$data['pcate'] = $detail['pcate'];
				}
				if (is_array($_GPC['thumb'])) {
					$data['thumb'] = $_GPC['thumb'][0];
				} else {
					$data['thumb'] = $_GPC['thumb'];
				}
				pdo_update('han_article', $data, array('id' => $id));
			}
			header('location:' . $this->createMobileUrl('index'));
		}
		if ($_GPC['submit'] == '发表') {
			empty($_GPC['title']) ? message('亲,标题不能为空') : ($title = $_GPC['title']);
			foreach ($sensitives as $sens) {
				if (stristr($title, $sens['word'])) {
					message('你的帖子标题包含敏感词，不允许发表！', $this->createMobileUrl('index'));
					exit;
				}
				if (stristr($_GPC['content'], $sens['word'])) {
					message('你的帖子内容包含敏感词，不允许发表！', $this->createMobileUrl('index'));
					exit;
				}
			}
			$data = array('weid' => $_W['uniacid'], 'pcate' => intval($_GPC['category']['parentid']), 'ccate' => intval($_GPC['category']['childid']), 'title' => $title, 'content' => htmlspecialchars_decode($_GPC['content'], ENT_QUOTES), 'thumb' => $_GPC['thumb'], 'open_xs' => intval($_GPC['open_xs']), 'open_yc' => intval($_GPC['open_yc']), 'xsNum' => intval($_GPC['xsNum']), 'content_j' => htmlspecialchars_decode($_GPC['content_j'], ENT_QUOTES), 'gmjf' => $_GPC['gmjf'], 'uid' => $_W['member']['uid'], 'author' => $_W['fans']['nickname'], 'avatar' => $_W['fans']['tag']['avatar'], 'openid' => $_W['fans']['from_user'], 'address' => 'mobile', 'createtime' => TIMESTAMP, 'replytime' => TIMESTAMP);
			$pictures = $_GPC['thumb'];
			if ($data['pcate'] == 0) {
				$cate = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid ={$weid} limit 1");
				$data['pcate'] = $cate['id'];
			}
			$data['pics'] = serialize($pictures);
			if (empty($id)) {
				$bk = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid ={$weid} and id= {$data['pcate']}");
				$wgrpid = unserialize($bk['wgrpid']);
				if (!in_array($groupid, $wgrpid)) {
					message('亲,你没有该版块的发帖权限！', $this->createMobileUrl('index'));
					exit;
				}
				if (is_array($_GPC['thumb'])) {
					$data['thumb'] = $_GPC['thumb'][0];
				} else {
					$data['thumb'] = $_GPC['thumb'];
				}
				if ($bk['is_fcheck']) {
					$data['is_fb'] = 0;
				} else {
					$data['is_fb'] = 1;
				}
				if (pdo_insert('han_article', $data) && !empty($bk['jf1'])) {
					load()->model('mc');
					$aid = pdo_insertid();
					$handsel = array('module' => 'han_article', 'sign' => md5(iserializer(array('id' => $aid))), 'action' => 'write', 'credit_value' => $bk['jf1'], 'credit_log' => '发表文章,赠送积分');
					$formuid = -1;
					mc_handsel($_W['member']['uid'], $formuid, $handsel, $_W['uniacid']);
					message('发贴成功' . ($data['is_fb'] ? '' : '等待审核'), $this->createMobileUrl('home'), 'success');
				}
			} else {
				$tmp_var = $_GPC['category']['parentid'];
				$tmp_var = (int) $tmp_var;
				if (empty($tmp_var)) {
					$data['pcate'] = $detail['pcate'];
				}
				if (is_array($_GPC['thumb'])) {
					$data['thumb'] = $_GPC['thumb'][0];
				} else {
					$data['thumb'] = $_GPC['thumb'];
				}
				pdo_update('han_article', $data, array('id' => $id));
			}
			header('location:' . $this->createMobileUrl('index'));
		}
		if ($op == 'delete') {
			load()->func('file');
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT * FROM " . tablename('han_article') . " WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，帖子不存在或是已经被删除！');
			}
			if (!empty($row['thumb'])) {
				file_delete($row['thumb']);
			}
			pdo_delete('han_article', array('id' => $id));
			load()->model('mc');
			$handsel = array('module' => 'han_article', 'sign' => md5(iserializer(array('id' => $id))), 'action' => 'write', 'credit_value' => -$bk['jf1'], 'credit_log' => '发表的帖子被删除,扣除赠送积分');
			$formuid = -1;
			mc_handsel($row['uid'], $formuid, $handsel, $_W['uniacid']);
			message('删除成功！', $this->createMobileUrl('index'), 'success');
		} elseif ($op == 'order') {
			pdo_update('han_article', array('is_zd' => 1, 'zdtime' => TIMESTAMP), array('id' => $id));
			header('location:' . $this->createMobileUrl('index'));
		} elseif ($op == 'delorder') {
			pdo_update('han_article', array('is_zd' => 0), array('id' => $id));
			header('location:' . $this->createMobileUrl('index'));
		} elseif ($op == 'addjh') {
			pdo_update('han_article', array('is_jh' => 1), array('id' => $id));
			$s_uid = $detail['uid'];
			if (!empty($s_uid)) {
				load()->model('mc');
				$handsel = array('module' => 'han_article', 'sign' => md5(iserializer(array('id' => $id))), 'action' => 'click', 'credit_value' => $bk['jf2'], 'credit_log' => '帖子被站长/版主加精,赠送积分');
				$formuid = $_W['member']['uid'];
				if ($s_uid != $_W['member']['uid']) {
					mc_handsel($s_uid, $formuid, $handsel, $_W['uniacid']);
				}
			}
			header('location:' . $this->createMobileUrl('index'));
		} elseif ($op == 'deljh') {
			pdo_update('han_article', array('is_jh' => 0), array('id' => $id));
			$s_uid = $detail['uid'];
			if (!empty($s_uid)) {
				load()->model('mc');
				$handsel = array('module' => 'han_article', 'sign' => md5(iserializer(array('id' => $id))), 'action' => 'click', 'credit_value' => -$bk['jf2'], 'credit_log' => '帖子被站长/版主取消加精,扣除原赠送积分');
				$formuid = -1;
				if ($s_uid != $_W['member']['uid']) {
					mc_handsel($s_uid, $formuid, $handsel, $_W['uniacid']);
				}
			}
			header('location:' . $this->createMobileUrl('index'));
		}
		include $this->template('publish');
	}
	public function jljf($credit, $uid)
	{
		global $_GPC, $_W;
		$sqlupdate = "update " . tablename('mc_members') . " set credit1= (credit1+" . $credit . ") where uniacid=" . $_W['uniacid'] . " AND uid=" . $uid;
		pdo_query($sqlupdate);
	}
	public function doMobileList()
	{
		global $_GPC, $_W;
		$weid = $_W['uniacid'];
		$cid = intval($_GPC['cid']);
		$category = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE id = '{$cid}'");
		$result = pdo_fetchall("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid =$weid AND parentid = " . $category['parentid'] . " ORDER BY displayorder ASC, id ASC ");
		$key = $_GPC['keyword'];
		$list = $this->articlelist($cid, $key);
		if (!empty($category['l_template']) && !empty($category['templatefile'])) {
			include $this->template($category['templatefile']);
			exit;
		}
		include $this->template('list/list1');
	}
	private function articlelist($cid, $key)
	{
		global $_GPC, $_W;
		$weid = $_W['uniacid'];
		if ($cid > 0 && empty($key)) {
			$list = pdo_fetchall("SELECT * FROM " . tablename('han_article') . " WHERE weid={$weid} AND ccate={$cid}  ORDER BY createtime DESC ");
			return $list;
		} else {
			$list = pdo_fetchall("SELECT * FROM " . tablename('han_article') . " WHERE weid={$weid} AND ccate={$cid}   AND title LIKE '%{$key}%' ");
			return $list;
		}
	}
	public function doMobileArtList()
	{
		global $_GPC, $_W;
		$weid = $_W['uniacid'];
		$cid = intval($_GPC['cid']);
		load()->model("mc");
		$userinfo = mc_oauth_userinfo();
		$groups = mc_groups();
		$psize = 5;
		$num = $_POST['num'] * 5;
		if ($_POST['num'] != 0) {
			$num += 1;
		}
		if (!empty($cid)) {
			$list = pdo_fetchall("SELECT * FROM " . tablename('han_article') . " WHERE is_fb =1 and is_zd =0 and weid={$weid} AND pcate={$cid}  ORDER BY replytime DESC LIMIT " . $num . ',' . $psize);
			$json_arr = array();
			foreach ($list as $k => $v) {
				$v['title'] = cutstr($v['title']);
				$depc = preg_replace("/<img[^>]*\/>/", "", $v['content']);
				$v['content'] = cutstr($depc, 50, ture);
				$v['createtime'] = get_timedays($v['createtime']);
				$v['dlink'] = $this->createMobileUrl("detail", array("id" => $v['id']), true);
				$fan = mc_fetch($v['uid']);
				if ($fan['gender'] == 1) {
					$v['gender'] = "/addons/han_article/images/nan.png";
				} else {
					if ($fan['gender'] == 2) {
						$v['gender'] = "/addons/han_article/images/nv.png";
					}
				}
				foreach ($groups as $group) {
					if ($group['groupid'] == $fan['groupid']) {
						$v['groupid'] = $group['title'];
					}
				}
				$bk = pdo_fetch("SELECT name FROM " . tablename('han_article_category') . " WHERE id =" . $v['pcate']);
				$v['bkname'] = $bk['name'];
				$bzsnum = 0;
				$bzslist = pdo_fetchall("SELECT js_ds_input FROM " . tablename('han_comment') . " WHERE weid={$_W['uniacid']} and aid={$v['id']} and js_ds_input>0 ");
				foreach ($bzslist as $bzs) {
					$bzsnum += $bzs['js_ds_input'];
				}
				$v['bzsnum'] = $bzsnum;
				$pics = unserialize($v['pics']);
				$imghtml = '<div style="display:none;" id="ne"></div>';
				if (is_array($pics)) {
					if (!empty($pics[0])) {
						$v['pic1'] = strpos($pics[0], "http://") === FALSE ? $_W["attachurl"] . $pics[0] : $pics[0];
						$imghtml .= '<div class="feed-img img-ph" style="width: 31%; height: 112px; margin-right: 2px;"><img src="' . $v['pic1'] . '" alt=""style=" margin-top: 0px; width: 112px; height: auto;"></div>';
					}
					if (!empty($pics[1])) {
						$v['pic2'] = strpos($pics[1], "http://") === FALSE ? $_W["attachurl"] . $pics[1] : $pics[1];
						$imghtml .= '<div class="feed-img img-ph" style="width: 31%; height: 112px; margin-right: 2px;"><img src="' . $v['pic2'] . '" alt=""style=" margin-top: 0px; width: 112px;  height: auto;"></div>';
					}
					if (!empty($pics[2])) {
						$v['pic3'] = strpos($pics[2], "http://") === FALSE ? $_W["attachurl"] . $pics[2] : $pics[2];
						$imghtml .= '<div class="feed-img img-ph" style="width: 31%; height: 112px; margin-right: 2px;"><img src="' . $v['pic3'] . '" alt=""style=" margin-top: 0px; width: 112px;  height: auto;"></div>';
					}
				}
				$v['imgdiv'] = $imghtml;
				$json_arr[] = (object) $v;
			}
			echo json_encode($json_arr);
		} else {
			$list = pdo_fetchall("SELECT * FROM " . tablename('han_article') . " WHERE is_fb =1 and is_zd =0 and weid={$weid} ORDER BY replytime DESC LIMIT " . $num . ',' . $psize);
			$json_arr = array();
			foreach ($list as $k => $v) {
				$v['title'] = cutstr($v['title']);
				$depc = preg_replace("/<img[^>]*\/>/", "", $v['content']);
				$v['content'] = cutstr($depc, 50, ture);
				$v['createtime'] = get_timedays($v['createtime']);
				$v['dlink'] = $this->createMobileUrl("detail", array("id" => $v['id']), true);
				$fan = mc_fetch($v['uid']);
				if ($fan['gender'] == 1) {
					$v['gender'] = "/addons/han_article/images/nan.png";
				} else {
					if ($fan['gender'] == 2) {
						$v['gender'] = "/addons/han_article/images/nv.png";
					}
				}
				foreach ($groups as $group) {
					if ($group['groupid'] == $fan['groupid']) {
						$v['groupid'] = $group['title'];
					}
				}
				$bk = pdo_fetch("SELECT name FROM " . tablename('han_article_category') . " WHERE id =" . $v['pcate']);
				$v['bkname'] = $bk['name'];
				$bzsnum = 0;
				$bzslist = pdo_fetchall("SELECT js_ds_input FROM " . tablename('han_comment') . " WHERE weid={$_W['uniacid']} and aid={$v['id']} and js_ds_input>0 ");
				foreach ($bzslist as $bzs) {
					$bzsnum += $bzs['js_ds_input'];
				}
				$v['bzsnum'] = $bzsnum;
				$pics = unserialize($v['pics']);
				$imghtml = '<div style="display:none;" id="ne"></div>';
				if (is_array($pics)) {
					if (!empty($pics[0])) {
						$v['pic1'] = strpos($pics[0], "http://") === FALSE ? $_W["attachurl"] . $pics[0] : $pics[0];
						$imghtml .= '<div class="feed-img img-ph" style="width: 31%; height: 112px; margin-right: 2px;"><img src="' . $v['pic1'] . '" alt=""style="margin-left: -18.6667px; margin-top: 0px; width: 149.333px; height: auto;"></div>';
					}
					if (!empty($pics[1])) {
						$v['pic2'] = strpos($pics[1], "http://") === FALSE ? $_W["attachurl"] . $pics[1] : $pics[1];
						$imghtml .= '<div class="feed-img img-ph" style="width: 31%; height: 112px; margin-right: 2px;"><img src="' . $v['pic2'] . '" alt=""style="margin-left: -18.6667px; margin-top: 0px; width: 149.333px;  height: auto;"></div>';
					}
					if (!empty($pics[2])) {
						$v['pic3'] = strpos($pics[2], "http://") === FALSE ? $_W["attachurl"] . $pics[2] : $pics[2];
						$imghtml .= '<div class="feed-img img-ph" style="width: 31%; height: 112px; margin-right: 2px;"><img src="' . $v['pic3'] . '" alt=""style="margin-left: -18.6667px; margin-top: 0px; width: 149.333px; height: auto;"></div>';
					}
				}
				$embed = unserialize($v['embed']);
				$embedhtml = '';
				if (is_array($embed) || count($pics1) > 0) {
					$embedhtml .= '<div style="display:none;" id="ne"></div><div>' . $embed[0][0] . '</div>';
				}
				$v['imgdiv'] = $imghtml;
				$pics1 = unserialize($v['pics']);
				$imghtml1 = '';
				if (is_array($pics1) && count($pics1) < 3) {
					if (!empty($pics1[0])) {
						$v['pic0'] = strpos($pics1[0], "http://") === FALSE ? $_W["attachurl"] . $pics1[0] : $pics1[0];
						$imghtml1 .= '<div id=""class="weui-flex w30 right1"><div style="display:none;"id="ne"></div><div class="weui-flex-item  "style="margin:2px 3px"><img src="' . $v['pic0'] . '"   alt=""class="weui_media_appmsg_thumb"width="100%"height="80"></div></div>';
					}
				} else {
					if (!empty($pics1[0])) {
						$v['pic1'] = strpos($pics1[0], "http://") === FALSE ? $_W["attachurl"] . $pics1[0] : $pics1[0];
						$imghtml1 .= '<div id=""class="weui-flex"><div style="display:none;"id="ne"></div><div class="weui-flex-item w30 left1"style="margin:2px 3px"><img src="' . $v['pic1'] . '"  alt=""class="weui_media_appmsg_thumb"width="100%"height="80"></div>';
					}
					if (!empty($pics1[1])) {
						$v['pic2'] = strpos($pics1[1], "http://") === FALSE ? $_W["attachurl"] . $pics1[1] : $pics1[1];
						$imghtml1 .= '<div class="weui-flex-item w30 left1"style="margin:2px 3px"><img src="' . $v['pic2'] . '"  alt=""class="weui_media_appmsg_thumb"width="100%"height="80"></div>';
					}
					if (!empty($pics1[2])) {
						$v['pic3'] = strpos($pics1[2], "http://") === FALSE ? $_W["attachurl"] . $pics1[2] : $pics1[2];
						$imghtml1 .= '<div class="weui-flex-item w30 left1"style="margin:2px 3px"><img src="' . $v['pic3'] . '"  alt=""class="weui_media_appmsg_thumb"width="100%"height="80"></div></div>';
					}
				}
				$v['embeddiv'] = $embedhtml;
				$v['imgdiv1'] = $imghtml1;
				$json_arr[] = (object) $v;
			}
			echo json_encode($json_arr);
		}
	}
	public function doMobileDetail()
	{
		global $_GPC, $_W;
		$op = $_GPC['op'];
		$id = intval($_GPC['id']);
		$s_uid = intval($_GPC['s_uid']);
		$weid = $_W['uniacid'];
		$sql = "SELECT * FROM " . tablename('han_article') . " WHERE `id`=:id AND weid = :uniacid";
		$detail = pdo_fetch($sql, array(':id' => $id, ':uniacid' => $_W['uniacid']));
		$fan = mc_fetch($detail['uid']);
		$bk = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid ={$_W['uniacid']} and id=" . $detail['pcate']);
		$rgrpid = unserialize($bk['rgrpid']);
		if (empty($_W['member']['uid'])) {
			load()->model('mc');
			$member = mc_oauth_userinfo();
			$member = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ={$_W['uniacid']} and nickname='" . $member['nickname'] . "' limit 1");
		} else {
			$member = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ={$_W['uniacid']} and uid=" . $_W['member']['uid'] . " limit 1");
		}
		if (0 && !in_array($member['groupid'], $rgrpid)) {
			message('亲,你还无权阅读该贴，请联系管理员！', $this->createMobileUrl('index'));
			exit;
		}
		$zan = pdo_fetch("SELECT * FROM " . tablename('han_artdz') . " WHERE `aid`=:aid AND weid = :uniacid and uid=:uid", array(':aid' => $id, ':uniacid' => $_W['uniacid'], ':uid' => $member['uid']));
		$shc = pdo_fetch("SELECT * FROM " . tablename('han_artshc') . " WHERE `aid`=:aid and weid = :uniacid and uid=:uid", array(':aid' => $id, ':uniacid' => $_W['uniacid'], ':uid' => $_W['member']['uid']));
		$comment = pdo_fetchall("SELECT * FROM " . tablename('han_comment') . " WHERE `aid`=:aid and weid=:weid and openid=:openid and status=1 order by praise_num desc", array(':aid' => $id, ':weid' => $weid, ':openid' => $_W['openid']));
		if ($op == 'mycom') {
			$fans_info = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') . " WHERE uniacid='{$_W['uniacid']}' AND uid='{$member['uid']}' LIMIT 1");
			$cList = pdo_fetchall("SELECT * FROM " . tablename('han_comment') . " WHERE `aid`=:aid and weid=:weid and openid=:openid and status=1 order by praise_num desc", array(':aid' => $id, ':weid' => $weid, ':openid' => $fans_info['openid']));
		} else {
			$cList = pdo_fetchall("SELECT * FROM " . tablename('han_comment') . " WHERE `aid`=:aid and weid=:weid and status=1 order by praise_num desc", array(':aid' => $id, ':weid' => $weid));
			foreach ($cList as $k => $ccc) {
				if (!empty($ccc['cmid'])) {
					$rec = pdo_fetch("SELECT author FROM " . tablename('han_comment') . " WHERE id=:id ", array(':id' => $ccc['cmid']));
					$cList[$k]['author'] = $ccc['author'] . " @ " . $rec['author'];
				}
				$czan = pdo_fetch("SELECT num FROM " . tablename('han_artdz') . " WHERE `aid`=:aid and cid=:cid AND weid = :uniacid and uid=:uid", array(':aid' => $id, ':cid' => $cList[$k]['id'], ':uniacid' => $_W['uniacid'], ':uid' => $_W['member']['uid']));
				$cList[$k]['zanNum'] = $czan['num'];
			}
		}
		$dscomment = pdo_fetchall("SELECT thumb,sum(js_ds_input) as dsnum,openid  FROM " . tablename('han_comment') . "WHERE `aid`=:aid AND weid = :uniacid AND js_ds_input>0 group by openid ", array(':aid' => $id, ':uniacid' => $_W['uniacid']));
		$dscount = pdo_fetchall("SELECT count(openid) FROM " . tablename('han_comment') . "WHERE `aid`=:aid AND weid = :uniacid AND js_ds_input>0 group by openid", array(':aid' => $id, ':uniacid' => $_W['uniacid']));
		$dscount = count($dscount);
		$buy = pdo_fetch("SELECT * FROM " . tablename('han_articlebuy') . " WHERE aid=:aid and uid=:uid limit 1", array(':aid' => $id, ':uid' => $_W['member']['uid']));
		$config = $this->module['config']['bbs'];
		$pics = unserialize($detail['pics']);
		if (!empty($detail['thumb'])) {
			$detail['thumb'] = tomedia($detail['thumb']);
		} else {
			$detail['thumb'] = '';
		}
		$title = $detail['title'];
		if (!empty($detail['template'])) {
			$_W['template'] = $detail['template'];
		}
		$detail['content'] = preg_replace("/<img(.*?)(http[s]?\:\/\/mmbiz.qpic.cn[^\?]*?)(\?[^\"]*?)?\"/i", '<img $1$2"', $detail['content']);
		$detail['clickNum'] = intval($detail['clickNum']) + 1;
		pdo_update('han_article', array('clickNum' => $detail['clickNum']), array('weid' => $_W['uniacid'], 'id' => $id));
		$_url = $_W['siteroot'] . 'app/' . substr($this->createMobileUrl('detail', array('id' => $id), true), 2) . "&s_uid=" . $_W['member']['uid'];
		$_share = array('desc' => $detail['description'], 'title' => $detail['title'], 'link' => $_url, 'imgUrl' => $detail['thumb']);
		$category = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE id = '{$detail['ccate']}'");
		$msg = 0;
		$cksql = 'SELECT id FROM ' . tablename('mc_handsel') . ' WHERE uniacid = :uniacid AND touid = :touid AND fromuid = :fromuid AND module = :module AND sign = :sign AND action = :action';
		$ckparm = array(':uniacid' => $weid, ':touid' => $_W['member']['uid'], ':fromuid' => -1, ':module' => 'han_article', ':sign' => md5(iserializer(array('id' => $id))), ':action' => 'share');
		$handsel_exists = pdo_fetch($cksql, $ckparm);
		if (!empty($handsel_exists)) {
			$msg = 1;
		}
		if ($_GPC['op'] == 'recom') {
			$cmid = intval($_GPC['cmid']);
		}
		if ($detail['open_yc'] == 0) {
			include $this->template('detail/detail0');
		} elseif (intval($detail['open_yc']) == 1) {
			include $this->template('detail/detail1');
		} elseif (intval($detail['open_yc']) == 2) {
			include $this->template('detail/detail2');
		}
	}
	public function doMobileComment()
	{
		global $_GPC, $_W;
		$artid = intval($_GPC['artid']);
		$weid = $_W['uniacid'];
		$config = $this->module['config']['bbs'];
		$lasttime = pdo_fetchcolumn("SELECT createtime FROM " . tablename('han_comment') . " WHERE `aid`=:aid AND weid = :uniacid AND openid = :openid order by createtime desc limit 1", array(':aid' => $artid, ':uniacid' => $_W['uniacid'], ':openid' => $_W['fans']['from_user']));
		if (!empty($config['reply_times']) && TIMESTAMP - $lasttime < $config['reply_times']) {
			message('亲,你回帖太频繁了，请稍后再试！', $this->createMobileUrl('detail', array('id' => $artid)));
			exit;
		}
		$sql = "SELECT * FROM " . tablename('han_article') . " WHERE `id`=:id AND weid = :uniacid";
		$detail = pdo_fetch($sql, array(':id' => $artid, ':uniacid' => $_W['uniacid']));
		$bk = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid ={$_W['uniacid']} and id= {$detail['pcate']}");
		if (empty($bk['is_reply']) || intval($bk['is_reply']) == 0) {
			message('亲,该帖子不允许回复！', $this->createMobileUrl('detail', array('id' => $artid)));
			exit;
		}
		$status = 1;
		$is_follow = false;
		$mycomments = pdo_fetchall("SELECT * FROM " . tablename('han_comment') . " WHERE `aid`=:aid and weid=:weid AND openid=:openid order by createtime asc ", array(':aid' => $artid, ':weid' => $weid, ':openid' => $openid));
		include $this->template('comment');
	}
	public function doMobileArticlebuy()
	{
		global $_W, $_GPC;
		load()->model('mc');
		$config = $this->module['config']['bbs'];
		$aid = $_GPC['aid'];
		$result_m = mc_credit_fetch($_W['member']['uid']);
		$Articlebuy = pdo_fetch("SELECT * FROM " . tablename('han_article') . " WHERE id=:aid limit 1", array(':aid' => $aid));
		if (empty($config['yc_type'])) {
			message('系统隐藏购买项未设置！', $this->createMobileUrl("detail", array("id" => $aid)), 'error');
		}
		if (!empty($Articlebuy['gmjf'])) {
			$price = -$Articlebuy['gmjf'];
			if ($result_m['credit' . $config['yc_type']] < $Articlebuy['gmjf']) {
				message('积分余额不足！', $this->createMobileUrl("detail", array("id" => $aid)), 'error');
				die;
			}
			$user_data = array('aid' => $aid, 'title' => $Articlebuy['title'], 'uname' => $_W['member']['nickname'], 'price' => $Articlebuy['gmjf'], 'uid' => $_W['member']['uid']);
			mc_credit_update($Articlebuy['uid'], 'credit' . $config['yc_type'], $js_ds_input, array(1 => $_W['member']['nickname'] . "购买帖子" . $Articlebuy['title']));
			mc_credit_update($_W['member']['uid'], 'credit' . $config['yc_type'], $price, array(1 => "购买帖子" . $Articlebuy['title']));
			$result = pdo_insert('han_articlebuy', $user_data);
			if (!empty($result)) {
				message('购买成功!', $this->createMobileUrl("detail", array("id" => $aid)), 'success');
			}
		}
	}
	public function doMobileHome()
	{
		global $_W, $_GPC;
		$uid = intval($_GPC['uid']);
		$op = $_GPC['op'] ? $_GPC['op'] : 'display';
		load()->model("mc");
		$groups = mc_groups();
		$config = $this->module['config']['bbs'];
		if (!empty($uid)) {
			$member = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ={$_W['uniacid']} and uid=" . $uid . " limit 1");
		} else {
			if (empty($_W['member']['uid'])) {
				load()->model('mc');
				$member = mc_oauth_userinfo();
				$member = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ={$_W['uniacid']} and nickname='" . $member['nickname'] . "' limit 1");
			} else {
				$member = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ={$_W['uniacid']} and uid=" . $_W['member']['uid'] . " limit 1");
			}
		}
		$fan = mc_fetch($member['uid']);
		$sex = $fan['gender'];
		$fans_info = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') . " WHERE uniacid='{$_W['uniacid']}' AND uid='{$member['uid']}' LIMIT 1");
		$artcount = pdo_fetchcolumn("SELECT count(*) FROM " . tablename('han_article') . " WHERE  1 and weid={$_W['uniacid']} and uid={$member['uid']}");
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$artlist = pdo_fetchall("SELECT * FROM " . tablename('han_article') . " WHERE 1 and weid={$_W['uniacid']} and uid={$member['uid']} ORDER BY zdtime DESC,createtime DESC limit " . ($pindex - 1) * $psize . ',' . $psize);
		$pager = myPagination($artcount, $pindex, $psize);
		$myctlist = pdo_fetchall("SELECT * FROM " . tablename('han_comment') . " WHERE  weid=:weid AND openid=:openid and status=1 AND js_ds_input=0 order by createtime asc ", array(':weid' => $_W['uniacid'], ':openid' => $fans_info['openid']));
		$mycct = count($myctlist);
		$znum = 0;
		foreach ($artlist as $art) {
			$znum += $art['zanNum'];
		}
		foreach ($myctlist as $myct) {
			$znum += $myct['praise_num'];
		}
		$shcnum = pdo_fetchall("SELECT distinct a.id FROM " . tablename('han_article') . "  as a left join " . tablename('han_artshc') . " as s on a.id=s.aid and a.weid=s.weid  WHERE  a.is_fb =1 and a.weid={$_W['uniacid']} and s.uid={$member['uid']}");
		$shcnum = count($shcnum);
		$zsnum = 0;
		$myzslist = pdo_fetchall("SELECT distinct c.id,c.* FROM " . tablename('han_comment') . "  as c left join " . tablename('han_article') . " as a on a.id=c.aid and a.weid=c.weid  WHERE  a.is_fb =1 and a.weid={$_W['uniacid']} and c.openid='{$fans_info['openid']}' and c.js_ds_input>0");
		foreach ($myzslist as $myzs) {
			$zsnum += $myzs['js_ds_input'];
		}
		$bzsnum = 0;
		$bzslist = pdo_fetchall("SELECT distinct c.id,c.* FROM " . tablename('han_comment') . "  as c left join " . tablename('han_article') . " as a on a.id=c.aid and a.weid=c.weid  WHERE  a.is_fb =1 and a.weid={$_W['uniacid']} and a.uid={$member['uid']} and c.js_ds_input>0 ");
		foreach ($bzslist as $bzs) {
			$bzsnum += $bzs['js_ds_input'];
		}
		$msgnum = 0;
		$sendlist = pdo_fetchall("SELECT * FROM " . tablename('han_art_sendMsg') . " WHERE weid = " . $_W['uniacid']);
		$msglist = array();
		foreach ($sendlist as $sendmsg) {
			$grpid = unserialize($sendmsg['groupid']);
			if (in_array($fan['groupid'], $grpid)) {
				$sendmsg['type'] = 'sys';
				$msglist[] = $sendmsg;
			}
		}
		$msgnum += count($msglist);
		$cmlist = pdo_fetchall("SELECT distinct c.id FROM " . tablename('han_comment') . "  as c left join " . tablename('han_article') . " as a on a.id=c.aid and a.weid=c.weid  WHERE  a.is_fb =1 and a.weid={$_W['uniacid']} and a.uid={$member['uid']} and c.openid<>'" . $fans_info['openid'] . "'");
		$msgnum += count($cmlist);
		$replist = pdo_fetchall("SELECT distinct c.id FROM " . tablename('han_comment') . "  as c left join " . tablename('han_article') . " as a on a.id=c.aid and a.weid=c.weid  WHERE  a.is_fb =1 and a.weid={$_W['uniacid']} and c.openid='{$fans_info['openid']}' and c.js_ds_input=0 ");
		foreach ($replist as $recom) {
			$innerlist = pdo_fetchall("SELECT id FROM " . tablename('han_comment') . " WHERE weid={$_W['uniacid']} and cmid={$recom['id']} and js_ds_input=0 and openid<>'" . $fans_info['openid'] . "'");
			$msgnum += count($innerlist);
		}
		if ($op == 'shc') {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 10;
			$myartlist = pdo_fetchall("SELECT distinct a.id,a.* FROM " . tablename('han_article') . "  as a left join " . tablename('han_artshc') . " as s on a.id=s.aid and a.weid=s.weid  WHERE  a.is_fb =1 and a.weid={$_W['uniacid']} and s.uid={$member['uid']} ORDER BY s.shctime DESC,a.createtime DESC limit " . ($pindex - 1) * $psize . ',' . $psize);
			foreach ($myartlist as $k => $v) {
				$bk = pdo_fetch("SELECT name FROM " . tablename('han_article_category') . " WHERE id =" . $v['pcate']);
				$myartlist[$k]['bkname'] = $bk['name'];
			}
			$myartcount = count($myartlist);
			$pager = myPagination($myartcount, $pindex, $psize);
		} elseif ($op == 'zs') {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 10;
			$myartlist = pdo_fetchall("SELECT distinct a.id,a.* FROM " . tablename('han_article') . "  as a left join " . tablename('han_comment') . " as c on a.id=c.aid and a.weid=c.weid  WHERE  a.is_fb =1 and a.weid={$_W['uniacid']} and c.openid='{$fans_info['openid']}' and c.js_ds_input>0 ORDER BY a.createtime DESC limit " . ($pindex - 1) * $psize . ',' . $psize);
			foreach ($myartlist as $k => $v) {
				$bk = pdo_fetch("SELECT name FROM " . tablename('han_article_category') . " WHERE id =" . $v['pcate']);
				$myartlist[$k]['bkname'] = $bk['name'];
			}
			$myartcount = count($myartlist);
			$pager = myPagination($myartcount, $pindex, $psize);
		} elseif ($op == 'bezs') {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 10;
			$myartlist = pdo_fetchall("SELECT distinct a.id,a.*  FROM " . tablename('han_article') . "  as a left join " . tablename('han_comment') . " as c on a.id=c.aid and a.weid=c.weid  WHERE  a.is_fb =1 and a.weid={$_W['uniacid']} and a.uid={$member['uid']} and c.js_ds_input>0 ORDER BY a.createtime DESC limit " . ($pindex - 1) * $psize . ',' . $psize);
			foreach ($myartlist as $k => $v) {
				$bk = pdo_fetch("SELECT name FROM " . tablename('han_article_category') . " WHERE id =" . $v['pcate']);
				$myartlist[$k]['bkname'] = $bk['name'];
			}
			$myartcount = count($myartlist);
			$pager = myPagination($myartcount, $pindex, $psize);
		}
		if ($op == 'artlist' && $_GPC['opp'] == 'com') {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 10;
			$cartlist = pdo_fetchall("SELECT distinct a.id,a.* FROM " . tablename('han_article') . "  as a left join " . tablename('han_comment') . " as c on a.id=c.aid and a.weid=c.weid  WHERE  a.is_fb =1 and a.weid={$_W['uniacid']} and c.openid='{$fans_info['openid']}' and c.js_ds_input=0 ORDER BY a.createtime DESC limit " . ($pindex - 1) * $psize . ',' . $psize);
			foreach ($cartlist as $k => $v) {
				$bk = pdo_fetch("SELECT name FROM " . tablename('han_article_category') . " WHERE id =" . $v['pcate']);
				$cartlist[$k]['bkname'] = $bk['name'];
			}
			$artcount = count($cartlist);
			$pager = myPagination($artcount, $pindex, $psize);
		}
		if ($op == 'messagelist') {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 10;
			$cmlist = pdo_fetchall("SELECT distinct c.id,c.author,c.thumb,c.js_cmt_input,c.js_ds_input,c.createtime,a.title,a.id as aid FROM " . tablename('han_comment') . "  as c left join " . tablename('han_article') . " as a on a.id=c.aid and a.weid=c.weid  WHERE  a.is_fb =1 and a.weid={$_W['uniacid']} and a.uid={$member['uid']} and c.openid<>'" . $fans_info['openid'] . "'");
			foreach ($cmlist as $recom) {
				$recom['type'] = 'reply';
				if ($recom['js_ds_input'] > 0) {
					$recom['content'] = $recom['author'] . "赞赏了你 ， 金额：" . $recom['js_ds_input'] . "  话题：" . $recom['title'];
				} else {
					$recom['content'] = $recom['author'] . " 在 [" . $recom['title'] . "] @了你：'" . $recom['js_cmt_input'] . "'";
				}
				$msglist[] = $recom;
			}
			$replist = pdo_fetchall("SELECT distinct c.id,c.author,c.thumb,c.js_cmt_input,c.createtime,a.title,a.id as aid FROM " . tablename('han_comment') . "  as c left join " . tablename('han_article') . " as a on a.id=c.aid and a.weid=c.weid  WHERE  a.is_fb =1 and a.weid={$_W['uniacid']} and c.openid='{$fans_info['openid']}'");
			foreach ($replist as $recom) {
				$innerlist = pdo_fetchall("SELECT id,author,thumb,js_cmt_input,createtime FROM " . tablename('han_comment') . " WHERE weid={$_W['uniacid']} and cmid={$recom['id']} and js_ds_input=0 and openid<>'" . $fans_info['openid'] . "'");
				foreach ($innerlist as $inner) {
					$inner['type'] = 'reply';
					$inner['content'] = $inner['author'] . " 在 [" . $recom['title'] . "] @了你：'" . $inner['js_cmt_input'] . "'";
					$inner['title'] = $recom['title'];
					$inner['aid'] = $recom['aid'];
					$msglist[] = $inner;
				}
			}
			$keys = array();
			foreach ($msglist as $key => $value) {
				$keys[$key] = $value['createtime'];
			}
			array_multisort($keys, SORT_DESC, $msglist);
			$count = count($msglist);
			$pager = myPagination($count, $pindex, $psize);
		}
		include $this->template('list/home2');
	}
	public function doMobileShc()
	{
		global $_W, $_GPC;
		$uid = intval($_GPC['uid']);
		load()->func('tpl');
		$config = $this->module['config']['bbs'];
		$op = $_GPC['op'] ? $_GPC['op'] : 'display';
		if (!empty($uid)) {
			$member = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ={$_W['uniacid']} and uid=" . $uid . " limit 1");
		} else {
			if (empty($_W['member']['uid'])) {
				load()->model('mc');
				$member = mc_oauth_userinfo();
				$member = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ={$_W['uniacid']} and nickname='" . $member['nickname'] . "' limit 1");
			} else {
				$member = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ={$_W['uniacid']} and uid=" . $_W['member']['uid'] . " limit 1");
			}
		}
		if ($op == 'add') {
			$aid = intval($_GPC['aid']);
			pdo_insert('han_artshc', array('weid' => $_W['uniacid'], 'uid' => $_W['member']['uid'], 'aid' => $aid, 'shctime' => TIMESTAMP));
			if (pdo_insertid() > 0) {
				$res = array('ok');
				return json_encode($res);
			}
		} elseif ($op == 'del') {
			$aid = intval($_GPC['aid']);
			pdo_delete('han_artshc', array('weid' => $_W['uniacid'], 'uid' => $_W['member']['uid'], 'aid' => $aid));
			$res = array('no');
			return json_encode($res);
		}
		include $this->template('list/shclist');
	}
	public function doMobileAjaxcomment()
	{
		global $_W, $_GPC;
		load()->model('mc');
		$weid = $_W['uniacid'];
		$aid = $_GPC['articleid'];
		$is_follow = false;
		$record = pdo_fetch("SELECT * FROM " . tablename('han_article') . " WHERE id= $aid ");
		$bk = pdo_fetch("SELECT * FROM " . tablename('han_article_category') . " WHERE uniacid ={$_W['uniacid']} and id= {$record['pcate']}");
		if (empty($record)) {
			$res['code'] = 501;
			$res['msg'] = "帖子不存在或者已经被删除。";
			return json_encode($res);
		}
		$result = mc_credit_fetch($_W['member']['uid']);
		$js_ds_input = $_GPC['js_ds_input'];
		$js_ds_output = -$js_ds_input;
		if ($result['credit1'] < $js_ds_input) {
			$res['code'] = 501;
			$res['msg'] = "您的积分不足，你查询积分后打赏！";
			return json_encode($res);
		} else {
			mc_credit_update($record['uid'], 'credit1', $js_ds_input, array(1 => "打赏"));
			mc_credit_update($_W['member']['uid'], 'credit1', $js_ds_output, array(1 => "打赏"));
		}
		if (empty($_W['fans']['follow'])) {
			mc_oauth_userinfo();
		}
		$data = array('weid' => $weid, 'js_cmt_input' => $_GPC['js_cmt_input'], 'js_ds_input' => $js_ds_input, 'status' => $_GPC['status'], 'aid' => $aid, 'author' => $_W['fans']['nickname'], 'thumb' => $_W['fans']['tag']['avatar'], 'openid' => $_W['fans']['from_user'], 'createtime' => time());
		if ($bk['is_rcheck']) {
			$data['status'] = 0;
		} else {
			$data['status'] = 1;
		}
		if ($_GPC['op'] == 'recom') {
			$data['cmid'] = intval($_GPC['cmid']);
			$recom = pdo_fetch("SELECT * FROM " . tablename('han_comment') . " WHERE `id`=:id ", array(':id' => intval($_GPC['cmid'])));
			$data['author'] = $_W['fans']['nickname'];
		}
		if ($js_ds_input > 0) {
			$data['js_cmt_input'] = "写的很好，支持一下，[em_71]";
		}
		pdo_insert('han_comment', $data);
		pdo_update('han_article', array('replytime' => TIMESTAMP), array('id' => $aid));
		$ccount = pdo_fetchcolumn("SELECT count(*) FROM " . tablename('han_comment') . " WHERE `aid`=:aid and weid=:weid and status=1 order by praise_num desc", array(':aid' => $aid, ':weid' => $weid));
		if ($record['plNum'] < $ccount) {
			pdo_update('han_article', array('plNum' => $ccount), array('weid' => $_W['uniacid'], 'id' => $aid));
		}
		$handsel = array('module' => 'han_article', 'sign' => md5(iserializer(array('id' => $aid))), 'action' => 'comment', 'credit_value' => $bk['jf4'], 'credit_log' => '评论帖子,赠送积分');
		$formuid = -1;
		mc_handsel($_W['member']['uid'], $formuid, $handsel, $_W['uniacid']);
		$res['code'] = 200;
		$res['msg'] = "评论成功，由公众帐号筛选后显示！积分+" . $bk['jf4'];
		return json_encode($res);
	}
}
function myPagination($total, $pageIndex, $pageSize = 15, $url = '', $context = array('before' => 5, 'after' => 4, 'ajaxcallback' => '', 'callbackfuncname' => ''))
{
	global $_W;
	$pdata = array('tcount' => 0, 'tpage' => 0, 'cindex' => 0, 'findex' => 0, 'pindex' => 0, 'nindex' => 0, 'lindex' => 0, 'options' => '');
	if ($context['ajaxcallback']) {
		$context['isajax'] = true;
	}
	if ($context['callbackfuncname']) {
		$callbackfunc = $context['callbackfuncname'];
	}
	$pdata['tcount'] = $total;
	$pdata['tpage'] = empty($pageSize) || $pageSize < 0 ? 1 : ceil($total / $pageSize);
	if ($pdata['tpage'] <= 1) {
		return '';
	}
	$cindex = $pageIndex;
	$cindex = min($cindex, $pdata['tpage']);
	$cindex = max($cindex, 1);
	$pdata['cindex'] = $cindex;
	$pdata['findex'] = 1;
	$pdata['pindex'] = $cindex > 1 ? $cindex - 1 : 1;
	$pdata['nindex'] = $cindex < $pdata['tpage'] ? $cindex + 1 : $pdata['tpage'];
	$pdata['lindex'] = $pdata['tpage'];
	if ($context['isajax']) {
		if (!$url) {
			$url = $_W['script_name'] . '?' . http_build_query($_GET);
		}
		$pdata['faa'] = 'href="javascript:;" page="' . $pdata['findex'] . '" ' . ($callbackfunc ? 'onclick="' . $callbackfunc . '(\'' . $_W['script_name'] . $url . '\', \'' . $pdata['findex'] . '\', this);return false;"' : '');
		$pdata['paa'] = 'href="javascript:;" page="' . $pdata['pindex'] . '" ' . ($callbackfunc ? 'onclick="' . $callbackfunc . '(\'' . $_W['script_name'] . $url . '\', \'' . $pdata['pindex'] . '\', this);return false;"' : '');
		$pdata['naa'] = 'href="javascript:;" page="' . $pdata['nindex'] . '" ' . ($callbackfunc ? 'onclick="' . $callbackfunc . '(\'' . $_W['script_name'] . $url . '\', \'' . $pdata['nindex'] . '\', this);return false;"' : '');
		$pdata['laa'] = 'href="javascript:;" page="' . $pdata['lindex'] . '" ' . ($callbackfunc ? 'onclick="' . $callbackfunc . '(\'' . $_W['script_name'] . $url . '\', \'' . $pdata['lindex'] . '\', this);return false;"' : '');
	} else {
		if ($url) {
			$pdata['faa'] = 'href="?' . str_replace('*', $pdata['findex'], $url) . '"';
			$pdata['paa'] = 'href="?' . str_replace('*', $pdata['pindex'], $url) . '"';
			$pdata['naa'] = 'href="?' . str_replace('*', $pdata['nindex'], $url) . '"';
			$pdata['laa'] = 'href="?' . str_replace('*', $pdata['lindex'], $url) . '"';
		} else {
			$_GET['page'] = $pdata['findex'];
			$pdata['faa'] = 'href="' . $_W['script_name'] . '?' . http_build_query($_GET) . '"';
			$_GET['page'] = $pdata['pindex'];
			$pdata['paa'] = 'href="' . $_W['script_name'] . '?' . http_build_query($_GET) . '"';
			$_GET['page'] = $pdata['nindex'];
			$pdata['naa'] = 'href="' . $_W['script_name'] . '?' . http_build_query($_GET) . '"';
			$_GET['page'] = $pdata['lindex'];
			$pdata['laa'] = 'href="' . $_W['script_name'] . '?' . http_build_query($_GET) . '"';
		}
	}
	$html = '<div style="text-align:center;width:100%;"><ul class="pagination pagination-centered pg">';
	$html .= "<li style='display:inline;width:15%;'><a {$pdata['faa']} class=\"pager-nav\">首页</a></li>";
	$html .= "<li style='display:inline;width:34%;'><a {$pdata['paa']} class=\"pager-nav\">&laquo;上一页</a></li>";
	if (!$context['before'] && $context['before'] != 0) {
		$context['before'] = 5;
	}
	if (!$context['after'] && $context['after'] != 0) {
		$context['after'] = 4;
	}
	if ($context['after'] != 0 && $context['before'] != 0) {
		$range = array();
		$range['start'] = max(1, $pdata['cindex'] - $context['before']);
		$range['end'] = min($pdata['tpage'], $pdata['cindex'] + $context['after']);
		if ($range['end'] - $range['start'] < $context['before'] + $context['after']) {
			$range['end'] = min($pdata['tpage'], $range['start'] + $context['before'] + $context['after']);
			$range['start'] = max(1, $range['end'] - $context['before'] - $context['after']);
		}
	}
	$html .= "<li style='display:inline;width:35%;'><a {$pdata['naa']} class=\"pager-nav\">下一页&raquo;</a></li>";
	$html .= "<li style='display:inline;width:15%;'><a {$pdata['laa']} class=\"pager-nav\">尾页</a></li>";
	$html .= '</ul></div>';
	return $html;
}
function get_timedays($pubtime)
{
	$time = time();
	if (idate('Y', $time) != idate('Y', $pubtime)) {
		return date('Y-m-d', $pubtime);
	}
	$seconds = $time - $pubtime;
	$days = idate('z', $time) - idate('z', $pubtime);
	if ($days == 0) {
		if ($seconds < 3600) {
			if ($seconds < 60) {
				if (3 > $seconds) {
					return '刚刚';
				} else {
					return $seconds . '秒前';
				}
			}
			return intval($seconds / 60) . '分钟前';
		}
		return idate('H', $time) - idate('H', $pubtime) . '小时前';
	}
	if ($days == 1) {
		return '昨天 ' . date('H:i', $pubtime);
	}
	if ($days == 2) {
		return '前天 ' . date('H:i', $pubtime);
	}
	if ($days < 7) {
		return $days . '天前';
	}
	return date('n-j H:i', $pubtime);
}