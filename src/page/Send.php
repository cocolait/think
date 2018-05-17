<?php
namespace cocolait\extend\page;
use think\Request;
/**
 * 自定义扩展分页操作类
 * Created by PhpStorm.
 * User: Chen
 * Date: 2018/5/14 0014
 * Time: 13:00
 * 自定义扩展分页操作类
 * $total 总记录数  $limit 一页显示多少条
 * 数组调用：
    \cocolait\bootstrap\page\Send::instance(['total' => 10, 'limit' => 10])->render($pages,$pageNums,$this->request->param());
 * 参数介绍：
    $page 分页参数 $pageNums  总页数  第三参数是 分页额外参数 字符串或者数组
 *
 * 字符串传参数调用：
    $pageinfo = $pages->render($page,$pageNums, "/keyword/$keyword");
 *
 * Class Send
 * @package cbootstrap\page
 */
class Send{
    /**
     * 静态代理
     * @var
     */
    protected static $instance;

    /**
     * 请求体
     * @var
     */
    protected $request;

    /**
     * 总记录数
     * @var
     */
    protected $total;

    /**
     * 总页数
     * @var
     */
    protected $pageNum;

    /**
     * 当前页
     * @var
     */
    protected $page;

    /**
     * 每页显示条数
     * @var
     */
    protected $limit;

    /**
     * 页面初始化跳转链接
     * @var
     */
    protected $entranceUrl;

    // 初始化
    private function __construct($options){
        if (!defined('THINK_VERSION')) {
            $this->throwException('该扩展只支持ThinkPHP v5.0.x版本');
        } else {
            if (THINK_VERSION < '5.0') {
                $this->throwException('该扩展只支持ThinkPHP v5.0.x版本');
            }
        }
        $this->request = Request::instance();
        if (!defined('BIND_MODULE')) {
            $this->entranceUrl = $this->request->baseFile() . '/' . $this->request->module() . '/' . $this->request->controller() . "/" . $this->request->action();
        } else{
            $this->entranceUrl = $this->request->baseFile() . '/' . $this->request->controller() . "/" . $this->request->action();
        }
        $this->total = $options['total'];
        $this->limit  = $options['limit'];
        $this->pageNum = (int) ceil($this->total/$this->limit);
        $this->page = $this->request->param('page',1);
    }

    /**
     * 获取外部实列
     * @param array $options
     * @return static
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    // 禁止克隆
    private function __clone() {}

    /**
     * 计算上一页
     * @param int $page 分页page参数
     * @return int
     */
    public function prePage($page){
        if($page>1){
            return $page-1;
        }else{
            return 1;
        }
    }

    /**
     * 计算下一页
     * @param int $page 分页page参数
     * @param int $pageNum 总页数  总记录/每页显示条数
     * @return mixed
     */
    protected function nextPage($page,$pageNum){
        if($page < $pageNum){
            return $page+1;
        }else{
            return $pageNum;
        }
    }

    /**
     * 分页显示输出
     * @param int $page  分页page参数
     * @param int $pageNum  总页数  总记录/每页显示条数
     * @param null $query 分页额外参数 array|string array = $this->request->param()  string = "/keywords/{$keywords}"
     * @return string
     */
    public function render($page ,$pageNum ,$query = null){
        if ($pageNum > 0) {
            $html = "<div class='page'>";
            $html .= "<style>.page a.cp-disabled{
                color: #DDDDDD;
}</style>";
            if(empty($query)){
                $query = '';
                if ($page > 1) {
                    $html.="<a class='cp-button' href='{$this->entranceUrl}?page=1'>首页</a>&nbsp;&nbsp;";
                    $html.="<a class='cp-button' href='{$this->entranceUrl}?page=".$this->prePage($page)."'>上一页</a>&nbsp;&nbsp;";
                } else {
                    $html.="<a href='javascript:;' class='cp-button cp-disabled'>首页</a>&nbsp;&nbsp;";
                    $html.="<a href='javascript:;' class='cp-button cp-disabled'>上一页</a>&nbsp;&nbsp;";
                }

                if ($page < $pageNum && $page >= 1) {
                    $html.="<a class='cp-button' href='{$this->entranceUrl}?page=".$this->nextPage($page, $pageNum)."'>下一页</a>&nbsp;&nbsp;";
                } else {
                    $html.="<a class='cp-button cp-disabled' href='javascript:;'>下一页</a>&nbsp;&nbsp;";
                }

                if ($page == $pageNum) {
                    $html.="<a class='cp-button cp-disabled' href='javascript:;'>尾页</a>&nbsp;&nbsp;";
                } else {
                    $html.="<a class='cp-button' href='{$this->entranceUrl}?page=".$pageNum."'>尾页</a>&nbsp;&nbsp;";
                }
            }else{
                if (is_array($query)) {
                    if (isset($query['page'])) unset($query['page']);
                    if ($query) {
                        $page_html = '&';
                        foreach ($query as $k => $v) {
                            if ($v) {
                                $page_html .= $k .  '=' . $v  . '&';
                            }
                        }
                        if (strlen($page_html) > 1) {
                            $page_html = substr($page_html,0,-1);
                        }
                        $query = $page_html;
                    } else {
                        $query = '';
                    }
                }
                if ($page > 1) {
                    $html.="<a class='cp-button' href='{$this->entranceUrl}" . "?page=1" .$query."'>首页</a>&nbsp;&nbsp;";
                    $html.="<a class='cp-button' href='{$this->entranceUrl}" . '?page=' .$this->prePage($page).$query."'>上一页</a>&nbsp;&nbsp;";
                } else {
                    $html.="<a href='javascript:;' class='cp-button cp-disabled'>首页</a>&nbsp;&nbsp;";
                    $html.="<a href='javascript:;' class='cp-button cp-disabled'>上一页</a>&nbsp;&nbsp;";
                }

                if ($page < $pageNum && $page >= 1) {
                    $html.="<a class='cp-button' href='{$this->entranceUrl}". '?page=' . $this->nextPage($page, $pageNum).$query."'>下一页</a>&nbsp;&nbsp;";
                } else {
                    $html.="<a class='cp-button cp-disabled' href='javascript:;'>下一页</a>&nbsp;&nbsp;";
                }

                if ($page == $pageNum) {
                    $html.="<a class='cp-button cp-disabled' href='javascript:;'>尾页</a>&nbsp;&nbsp;";
                } else {
                    $html.="<a class='cp-button' href='{$this->entranceUrl}". '?page=' . $pageNum.$query."'>尾页</a>&nbsp;&nbsp;&nbsp;&nbsp;";
                }
            }

            $html.="第".'<span class="cp-page-index">' . $page . '</span>'."页/共".'<span class="cp-page-num">' . $pageNum. '</span>' ."页&nbsp;&nbsp;&nbsp;";
            $html.="共". '<span class="cp-page-total">' . $this->total.'</span>' . "条记录&nbsp;&nbsp;&nbsp;";
            $html.="跳转到&nbsp;&nbsp;&nbsp;<input name='page' id='cp_page' min=1 max=".$pageNum." style='width:45px;' type='number'/>&nbsp;&nbsp;&nbsp;页&nbsp;&nbsp;&nbsp;<button onclick='jump();' class='cp-jump'>跳转</button>";
            $html.='</div>';
            $str = "<script>
				function jump()
				{
					var p = document.getElementById('cp_page').value;
					if(p>0 && p <=".$this->pageNum.")
					{
						location.href ='{$this->entranceUrl}/page/'+p+'{$query}';
					} else {
					    var _html = '查询数据共' + $pageNum + '页,无法跳转到第' + p + '页';
					    alert(_html);
					}
				}
			</script>";
            return $html.$str;
        } else {
            return '';
        }
    }

    /**
     * 抛出异常
     * @param $error
     * @throws \Exception
     */
    protected function throwException($error) {
        throw new \Exception($error);
    }
}