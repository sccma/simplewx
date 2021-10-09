<?php defined('IN_IA') or exit('Access Denied');?>
	<?php  if(!empty($_W['setting']['copyright']['statcode'])) { ?><?php  echo $_W['setting']['copyright']['statcode'];?><?php  } ?>
	<?php  if(!empty($_GPC['m']) && !in_array($_GPC['m'], array('keyword', 'special', 'welcome', 'default', 'userapi')) || defined('IN_MODULE')) { ?>
	<script>
		if(typeof $.fn.tooltip != 'function' || typeof $.fn.tab != 'function' || typeof $.fn.modal != 'function' || typeof $.fn.dropdown != 'function') {
			require(['bootstrap']);
		}
		$('[data-toggle="tooltip"]').tooltip()
	</script>
	<?php  } ?>
	<?php  if(!defined('IN_MODULE')) { ?>
	<script>
		$(document).ready(function() {
			if($('select').niceSelect) {
				$('select').niceSelect();
			}
		});
	</script>
	<script>
		$(document).ready(function() {
			$('.is-main-page').click(function() {
				util.message('最新版系统功能已经迁移到控制台，请前往控制台操作。<p><<a class="btn btn-link" href="https://user.w7.cc/v2/console/<?php  echo $_W['setting']['site']['key'];?>/go-license-console">如果你的浏览器没有自动跳转，请点击此链接</a></p>', 'https://user.w7.cc/v2/console/<?php  echo $_W['setting']['site']['key'];?>/go-license-console', 'info')

				setTimeout(function() {
					window.location.href = 'https://user.w7.cc/v2/console/<?php  echo $_W['setting']['site']['key'];?>/go-license-console'
				}, 3000)
			})
		});
	</script>
	<?php  } ?>