<?php
date_default_timezone_set('PRC');

//加载配置
require 'config.php';


if (!empty($config['token'])) {
    if (!isset($_REQUEST['token']) && $_REQUEST['token'] != $config['token']) {
        exit('参数错误');
    }
}

//如果不存在文本就禁止提交
if (!isset($_REQUEST['title'])) {
    exit('标题不能为空');
}

// 频繁推送拦截
$title = $_REQUEST['title'];
$message = isset($_REQUEST['message']) ? $_REQUEST['message'] : '';
$md5 = md5($message);
$now = time();
$expires = 60;
$fileName = 'pushHistory';
if (is_file($fileName)) {
    $str = file_get_contents($fileName);
}
if (isset($str) && strlen($str) > 32) {
    $list = json_decode($str, true);
    foreach ($list as $key => $time) {
        if ($now - $time > $expires) {
            unset($list[$key]);
        }
        if ($key == $md5 && $now - $time < $expires) {
            exit('频繁推送');
        } else {
            $list[$md5] = $now;
        }
    }
} else {
    $list = [$md5 => $now];
}
file_put_contents($fileName, json_encode($list));
echo '开始推送:';

//获取发送数据数组
function getDataArray($MsgArray)
{
    $data = array(
        //要发送给的用户，@all为全部
//        "touser"   => "@all",
        "touser"   => "LuoYuWen",
        //"toparty" => "@all",
        //"totag" => "@all", 
        "msgtype"  => "textcard",
        //改成自己的应用id
        "agentid"  => $MsgArray["agentid"],
        'textcard' => array(
            'title'       => $MsgArray["title"],
            //提示的时间和内容
            'description' => '<div class="gray">' . $MsgArray["time"] . '</div> <div class="normal">' . $MsgArray["msg"] . '</div>',
            //点击模板打开的链接
            'url'         => $MsgArray["url"],
            "btntxt"      => "详情"
        )
    );
    return $data;
}


//curl请求函数，微信都是通过该函数请求
function https_request($url, $data = null)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    if (!empty($data)) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}

/**
 * 开始推送
 */

//替换你的ACCESS_TOKEN
$url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$config['corpid']}&corpsecret={$config['corpsecret']}";
$ACCESS_TOKEN = json_decode(https_request($url), true)["access_token"];
//模板消息请求URL
$url = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=$ACCESS_TOKEN";
$MsgArray = array();

//推送的应用id
$MsgArray["agentid"] = $config['agentid'];

//标题是可选值
if (!isset($_REQUEST['title'])) {
    $MsgArray["title"] = "新提醒";
} else {
    $MsgArray["title"] = $_REQUEST['title'];
}
//推送的文本内容
$MsgArray["msg"] = isset($_REQUEST['message']) ? $_REQUEST['message'] : '';

//推送时间
$MsgArray["time"] = date('Y-m-d h:i:s', time());
$MsgArray["url"] = "{$config['domia']}/msg.php?title={$MsgArray["title"]}&time={$MsgArray["time"]}&msg={$MsgArray["msg"]}";
//转化成json数组让微信可以接收
$json_data = json_encode(getDataArray($MsgArray));
//echo $json_data;exit;
$res = https_request($url, urldecode($json_data));//请求开始
$res = json_decode($res, true);
if ($res['errcode'] == 0 && $res['errcode'] == "ok") {
    echo "发送成功！<br/>";
} else {
    echo "发送失败<br/>";
}