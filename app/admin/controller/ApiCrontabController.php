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
     *   1 Web开发 PHP Html/css JavaScript JQuery XML
     *   2 系统服务器 Linux nginx apache lnmp一键安装包
     *   3 数据库  Mysql
     *   4 项目管理 git svn
     *   5 心情随笔
     */
    public function grab(){

    }

    /**
     * 百度主动推送
     */
    public function push_baidu(){
        $portal_post=Db::name('portal_post');
        $postIDArr=$portal_post->where(['post_status'=>1,'is_push'=>0,'delete_time'=>0])->limit(10)->column('id');
        if(empty($postIDArr))
            exit;

        //推送
        $server_name=config('server_name');
        for($i=0;$i<count($postIDArr);$i++){
            if($postIDArr[$i]<10000){
                $idNum=str_pad($postIDArr[$i],5,"0",STR_PAD_LEFT);
            }else{
                $idNum=$postIDArr[$i];
            }

            $url=[$server_name.'/post/'.$idNum.'.html'];
            $api = 'http://data.zz.baidu.com/urls?site=blog.sanyeyang.com&token=7mY3JcFRnWWPvWFM';
            $ch = curl_init();
            $options =  array(
                CURLOPT_URL => $api,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => implode("\n", $url),
                CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
            );
            curl_setopt_array($ch, $options);
            $result = curl_exec($ch);
            $resultArr=json_decode($result,true);
            if(isset($resultArr['success'])){
                $portal_post->where(['id'=>$postIDArr[$i]])->update([
                    'is_push'=>1,
                    'push_time'=>time()
                ]);
            }
        }
    }
}