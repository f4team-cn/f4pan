<p align="center">
  <a href="https://www.f4team.cn/"><img src="https://www.f4team.cn/logo/logo-hdpi.png" width="500" height="200" alt="F4Team"></a>
</p>



***************************************

<h2 align="center">

`F4Pan`，是一个获取下载链接的工具

</h2>


## ⚠ 免责声明
* `F4Pan`(下称本项目)使用的接口全部来自于官方，无任何破坏接口的行为<br>
* 本项目所有代码全部开源，仅供学习参考使用，请遵守相关的法律法规，禁止商用，若无视声明使用此项目所造成的一切后果均与作者无关<br>
* 本项目需要登录账号，具有一定风险，包括但不限于限速，封号，限制相关功能等<br>
* 本项目，包括其开发者、贡献者和附属个人或实体，特此明确否认与任何形式的非法行为有任何关联、支持或认可。本免责声明适用于可能违反地方、国家或国际法律、法规或道德准则的F4Pan项目的任何使用或应用。<br>
* 本项目是一个开源软件项目，旨在促进其预期用例中的合法和道德应用程序。每个用户都有责任确保其使用F4Pan符合其管辖范围内的所有适用法律和法规。<br>
* 对于用户违反法律或从事任何形式的非法活动的任何行为，本项目的开发者和贡献者不承担任何责任。用户对自己的行为和使用F4Pan可能产生的任何后果负全部责任。<br>
* 此外，本项目(包括其开发人员、贡献者和用户)提供的任何讨论、建议或指导都不应被解释为法律建议。强烈建议用户寻求独立的法律顾问，以了解其行为的法律影响，并确保遵守有关的法律和条例。<br>
* 通过使用或访问本项目，用户承认并同意免除开发人员、贡献者和附属个人或实体因使用或滥用该项目而产生的任何和所有责任，包括因其行为而产生的任何法律后果。<br>
* 请负责任地、依法使用本项目。


## 🚧 所需环境
* PHP >= 8.0
* Mysql
* Redis
* Curl
  <br>⚠ 安装Mysql与Redis后若还未通过环境检查请在对应版本的`php.ini`中启用对应的拓展，需要的PHP拓展有`fileinfo`和`redis`


## 🔧 安装

本项目使用了`thinkphp8.0`框架<br>
Nginx伪静态（单独部署后端）:
```
location ~* (runtime|application)/{
	return 403;
}
location / {
	if (!-e $request_filename){
		rewrite  ^(.*)$  /index.php?s=$1  last;   break;
	}
}
```
Nginx伪静态（前端+后端）:
```
location ~* (runtime|application)/{
    return 403;
}
location /api {
    rewrite  ^(.*)$  /index.php?s=$1  last;   break;
}
location / {
    index index.html;
    try_files $uri $uri/ /index.html;
}
```
### 🔧 手动构建
本项目`前后端分离`的架构<br>
可从`Releases`页面下载完整包<br>

1. 解压到网站目录下
2. 设置运行目录为`/public`
3. 连接服务器ssh，cd到网站目录，执行`composer install`命令，等待依赖安装完成
4. 设置伪静态
5. 访问`http(s)://你的域名/#/install`跟随引导进行安装

如果使用`宝塔面板`进行安装，在执行`composer install`前应去`禁用函数`页面删除`putenv`和`proc_open`函数

## ⚠️ Tips
动态密钥获取方法:
1. 登录后台，进入apikey管理页面，新增一个apikey
2. GET访问`/api/public/get_parse_key?apikey={apikey}`获取动态解析密钥

## 📦 前端更新方法
前往[f4pan-web](https://github.com/f4team-cn/f4pan-web)仓库的`actions`页面下载最新的构建版本
解压到`public`文件夹下更新

## ✔️ 反馈
### 欢迎提交BUG
可通过`Issues`或 [Telegram](https://t.me/f4pan_project) 与我们取得联系

## 🔗 相关仓库
前端 [f4pan-web](https://github.com/f4team-cn/f4pan-web)

后端 [f4pan](￶https://github.com/f4team-cn/f4pan)

# ©️ 最终解释权归F4Team所有
进入我们的[官网](https://www.f4team.cn/)
