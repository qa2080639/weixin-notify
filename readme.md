## 企业微信通知PHP版
通过创建企业微信应用来推送通知到微信
* 自定义标题, 方便查看(Server酱不行)
* 不用再挂多个APP(省电)

### 流程
1. 注册企业微信->建立应用->设置自己外部沟通权限
2. 修改目录下的**config.php**填上刚刚注册好的的信息
3. 上传代码到服务器或虚拟主机


推送例子
>http://PATH/push.php?title=test&message=message&token=

### 特性:
* 易配置
* 重复提醒过滤
* token验证
