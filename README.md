<p align="center">
  <br>
  <b>Ucer-admin</b>
  <br>
  <a href="https://www.codehaoshi.com">
    <img src="https://yousails.com/banners/brand.png" width=350>
  </a>
  
  <br>
  <a href="https://www.codehaoshi.com">
    <img src="http://ww1.sinaimg.cn/large/6d86d850gw1fao8va0fv0j208y0aw74v.jpg" width=200>
  </a>
</p>

---

# ucer admin

## 项目描述
该项目原名太平洋后台管理系统,现如今正式更名 ucer 后台管理系统。代码完全开源

* 产品名称：ucer 后台管理系统
* 项目代码：ucer-admin
* 官方地址：http://codehaoshi.com


## 运行环境

- Thinkphp5.0.10
- Nginx 1.8+
- PHP 5.6+
- Mysql 5.6+

## 开发环境部署/安装

本项目代码使用 PHP 框架 [Thinkphp 5.0.10](https://www.kancloud.cn/manual/thinkphp5/ 开发，本地开发环境使用 [lnmp]。

### 基础安装

#### 1. 克隆源代码

克隆源代码到本地：

    > git clone https://github.com/Ucer/ucer-admin.git

#### 2. 配置本地的环境
1). 初始化数据库

 找到 public/uploads/sql_data/init.sql 并将其导入到你的数据库中

2). 数据库配置
```
$ cd youprojectdir;
$ cp database.example.php application/database.php
```

编辑 application/database.php 配置自己的数据库账号密码
**注意** 只需要修改将下面的 xxx 替换成你自己的信息
```shell
    'type'           => 'mysql',
    // 服务器地址
    'hostname'       => 'xxx',

    // 数据库名
    'database'       => 'xxx',
    // 用户名
    'username'       => 'xxx',
    // 密码
    'password'       => 'xxx',
    // 端口
    'hostport'       => '',
    // 连接dsn
    'dsn'            => '',
    // 数据库连接参数
    'params'         => [],
    // 数据库编码默认采用utf8
    'charset'        => 'utf8',
    // 数据库表前缀
    'prefix'         => 'pc_',
```


### 链接地址

* 管理后台：http://xxx/admin
* 前台地址：http://xxx/mobile

后台管理员账号密码默认 admin admin 。 登录后请自行修改

