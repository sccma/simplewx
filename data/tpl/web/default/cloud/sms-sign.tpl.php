<?php defined('IN_IA') or exit('Access Denied');?><?php (!empty($this) && $this instanceof WeModuleSite || 0) ? (include $this->template('common/header', TEMPLATE_INCLUDEPATH)) : (include template('common/header', TEMPLATE_INCLUDEPATH));?>
<style>
.color-error {
	color: #f15333;
}
.color-warning {
	color: #f2af5a;
}
</style>
<div class="we7-page-title">短信管理</div>
<div class="sms-sign" ng-controller="smsSign" ng-cloak>
	<div class="alert we7-page-alert">
		<p> <i class="wi wi-info"></i>发送短信内容时必须带签名，未创建签名。系统自动默认签名为【微擎】</p>
		<p> <i class="wi wi-info"></i>创建签名前请去应用市场购买签名，并提交审核。审核未通过，自动退款至用户交易币</p>
		<p> <i class="wi wi-info"></i>提交签名审核，预计2小时完成审核。审核时间为周一至周六9:00-18:00（不包含法定节假日）</p>
	</div>
	<div class="search-box we7-margin-bottom">
		<select class="we7-margin-right" ng-model="status_audit" ng-change="getSign(1)">
			<option value="">审核状态</option>
			<option ng-repeat="(key, item) in statusText" ng-value="key">{{item}}</option>
		</select>
		<div class="search-form">
			<div class="input-group we7-margin-right nice-select"  we7-date-range-picker ng-model="date" ng-change="getSign(1)">
				<span class="current">{{date.startDate + '至' + date.endDate}}</span>
			</div>
		</div>
		<a href="//s.w7.cc/goods-7.html?type=1" class="btn btn-primary" target="_blank">购买签名</a>
	</div>
	<table class="table we7-table">
		<tr>
			<th>签名</th>
			<th>审核状态</th>
			<th>提交时间</th>
			<th>操作</th>
		</tr>
		<tr ng-repeat="smssign in smsSigns">
			<td>{{smssign.sign}}</td>
			<td>
				<span class="we7-circle" ng-class="smssign.status == -1 ? 'bg-error' : (smssign.status == 2 ? 'bg-warning' : 'bg-green')"></span>{{statusText[smssign.status]}}
				<i title="{{'拒绝原因：' + smssign.refuse_reason}}" data-toggle="tooltip" data-placement="bottom"  ng-show="smssign.status == -1" class="wi wi-info color-error"></i>
			</td>
			<td>{{smssign.createtime}}</td>
			<td>
				<div class="link-group">
					<a href="javascript:;" ng-click="editSignModuleShow(smssign)" ng-if="smssign.can_remove">修改</a>
					<a href="javascript:;" ng-if="!smssign.can_remove">---</a>
				</div>
			</td>
		</tr>
		<tr ng-if="smsSigns | we7IsEmpty">
			<td colspan="100">
				<div class="text-center">暂无数据</div>
			</td>
		</tr>
	</table>
	<we7-page conf="page2"></we7-page>
	<div class="modal fade" id="content" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="we7-modal-dialog modal-dialog we7-form">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
					<div class="modal-title">修改签名</div>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<input type="text" ng-model="content" class="form-control" placeholder="签名内容" />
						<span class="help-block"></span>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-primary" data-dismiss="modal" ng-click="editSign()">确定</button>
					<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	require(['moment'], function() {
		angular.module('smsApp').value('config', {
			apiUrl: "<?php  echo url('cloud/sms-sign')?>do=",
		});
		angular.bootstrap($('.sms-sign'), ['smsApp']);
	})
</script>
<?php (!empty($this) && $this instanceof WeModuleSite || 0) ? (include $this->template('common/footer', TEMPLATE_INCLUDEPATH)) : (include template('common/footer', TEMPLATE_INCLUDEPATH));?>