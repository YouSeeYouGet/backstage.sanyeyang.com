<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;

use think\Db;
use think\Exception;
use think\Request;
use think\Url;
use tree\Tree;

class ApiCrontabController extends AdminBaseController
{
    public function _initialize(){

    }

    /**
     * 爬虫脚本
     *   1 Web开发
     *   2 系统运维
     *   3 数据库
     *   4 开发工具
     */
    public function grabnews(){

    }
}