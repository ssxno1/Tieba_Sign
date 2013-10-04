<?php
if(!defined('IN_KKFRAME')) exit();

$date = date('Ymd', TIMESTAMP+900);
$mdate = date('Y-m-d', TIMESTAMP+900);
$uid = 0;
$_uid = getSetting('mail_uid') ? getSetting('mail_uid') : 1;
while($_uid){
	$user = DB::fetch_first("SELECT uid, username, email FROM member WHERE uid='{$uid}'");
	if(check_if_msg($user)) sendmsg($user);
	$_uid = DB::result_first("SELECT uid FROM member WHERE uid>'{$_uid}' ORDER BY uid ASC LIMIT 0,1");
	saveSetting('mail_uid', $_uid);
}
define('CRON_FINISHED', true);
function check_if_msg($user){
	global $date, $uid;
	$setting = get_setting($user['uid']);
	if($setting['send_mail']) return true;
	if(!$setting['error_mail']) return false;
	$error_num = DB::result_first("SELECT COUNT(*) FROM sign_log WHERE status!='2' AND status!='-2' AND date='{$date}' AND uid='{$uid}'");
	if($error_num > 0) return true;
}
function sendmsg($user){
	global $date, $mdate, $uid;
	$log = array();
	$query = DB::query("SELECT * FROM sign_log l LEFT JOIN my_tieba t ON t.tid=l.tid WHERE l.uid='{$uid}' AND l.date='{$date}' ORDER BY l.status DESC, l.tid ASC");
	$i = 1;
	$message = <<<EOF
<html><body>
<style type="text/css">
div.wrapper * { font: 12px "Microsoft YaHei", arial, helvetica, sans-serif; word-break: break-all; }
div.wrapper a { color: #15c; text-decoration: none; }
div.wrapper a:active { color: #d14836; }
div.wrapper a:hover { text-decoration: underline; }
div.wrapper p { line-height: 20px; margin: 0 0 .5em; text-align: center; }
div.wrapper .sign_title { font-size: 20px; line-height: 24px; }
div.wrapper .result_table { width: 85%; margin: 0 auto; border-spacing: 0; border-collapse: collapse; }
div.wrapper .result_table td { padding: 10px 5px; text-align: center; border: 1px solid #dedede; }
div.wrapper .result_table tr { background: #d5d5d5; }
div.wrapper .result_table tbody tr { background: #efefef; }
div.wrapper .result_table tbody tr:nth-child(odd) { background: #fafafa; }
</style>
<div class="wrapper">
<p class="sign_title">����ǩ������ - ǩ������</p>
<p>{$mdate}<br>���д�������ǩ��ʧ�ܣ��������������� Cookie �����Ϣ</p>
<table class="result_table">
<thead><tr><td style="width: 40px">#</td><td>����</td><td style="width: 75px">״̬</td><td style="width: 75px">����</td></tr></thead>
<tbody>
EOF;
	while($result = DB::fetch($query)){
		$message .= '<tr><td>'.($i++)."</td><td><a href=\"http://tieba.baidu.com/f?kw={$result[unicode_name]}\" target=\"_blank\">{$result[name]}</a></td><td>"._status($result['status']).'</td><td>'._exp($result['exp']).'</td></tr>';
		$log[] = $result;
	}
	$message .= '</tbody></table></div></body></html>';
	$res = send_mail($user['email'], "[{$mdate}] ����ǩ������ - {$user[username]} - ǩ������", $message);
	echo $res ? '�ʼ����ͳɹ�<br>' : '�ʼ�����ʧ��<br>';
}
function _status($status){
	switch($status){
		case -2:	return '����ǩ��';
		case -1:	return '�޷�ǩ��';
		case 0:		return '��ǩ��';
		case 1:		return 'ǩ��ʧ��';
		case 2:		return '��ǩ��';
	}
}
function _exp($exp){
	return $exp == 0 ? '-' : '+'.$exp;
}