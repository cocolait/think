# think-page
thinkPHP 5.0.x 自定义分页扩展
## 链接
- 博客：http://www.mgchen.com
- github：https://github.com/cocolait
- gitee：http://gitee.com/cocolait

# 安装
```php
composer require cocolait/think_page
```

# 版本要求
> PHP >= 5.4
> 依赖 TP5.0.x

# 使用说明
> 该扩展包只适合thinkPHP 5.0.x版本

# 使用案例
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
$pageShow = \cocolait\bootstrap\page\Send::instance(['total'=>$total,'limit' => $limit])->render($page,$pageNum,$request->param());

```

## 自定义样式
- 最外层div[class='page']
- 首页|上一页|下一页|末尾 按钮样式 a.class['cp-button']
- 禁止点击class['cp-disabled']
- 第几页 span.class['cp-page-index']
- 共多少页 span.class['cp-page-total']
- 跳转按钮 button.class['cp-jump']