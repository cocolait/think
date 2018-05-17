# think
thinkPHP 5.0.x 功能扩展包（目前已有分页扩展，App token验证扩展）
## 链接
- 博客：http://www.mgchen.com
- github：https://github.com/cocolait
- gitee：http://gitee.com/cocolait

# 安装
```php
composer require cocolait/think
```

# 版本要求
> PHP >= 5.4
> 依赖 TP5.0.x

# 使用说明
> 该扩展包只适合thinkPHP 5.0.x版本
- 分页扩展包可以应用到 thinkPHP 5.0.x 各个版本
- token类依赖redis扩展 必须支持redis服务 thinkPHP 版本必须大于 V5.0.11

# 分页使用案例
```php
<?php
//在控制器用使用
$page = $request->param('page',1,'int');//获取分页上的page参数
$request = Request::instance();//获取请求体对象
$start = 0;
$limit = 10;//默认显示多少条
$where = ['uid' => ['>',1]];
if ($page != 1) {
    $start = ($page-1) * $limit;
}
// 根据筛选条件查询数据
$data = Db::table('user')->where($where)->limit($start,$limit)->select();

// 查询总记录数
$total = Db::table('user')->where($where)->count();

$pageNum = 0;
// 计算总页数
if ($total > 0) {
    $pageNum = ceil($total/$limit);
}

// 分页输出显示 不管有没有查询条件写法都是一致的 只需要把请求体放到第三个参数就行
$pageShow = \cocolait\extend\page\Send::instance(['total'=>$total,'limit' => $limit])->render($page,$pageNum,$request->param());

```
# token使用案例
```php
<?php
// token有心跳机制 当用户登录成功后在token有效期之类会根据心跳值来叠加token的生命周期
// 获取用户登录后的token 分配给前端
$data = \cocolait\extend\auth\Token::instance()->getAccessToken($uid);
// 验证token
$token = 'apMuQuY3SgKqNzYm4s6qGzJNsgY57nYehhKKJrMjQhHeCzY-2l85_l';
$data = \cocolait\extend\auth\Token::instance()->checkAccessToken($token);
```
## 分页效果图
![可以点击](https://gitee.com/uploads/images/2018/0516/204533_28e59dfd_588912.png "3.png")

## 分页自定义样式
- 最外层div[class='page']
- 首页|上一页|下一页|末尾 按钮样式 a.class['cp-button']
- 禁止点击class['cp-disabled']
- 第几页 span.class['cp-page-index']
- 共多少页 span.class['cp-page-total']
- 跳转按钮 button.class['cp-jump']
