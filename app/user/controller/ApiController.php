<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class ApiController extends AdminBaseController
{
    public function postHits(){
        $portal_post=Db::name('portal_post');
        $id = input('param.id', 0, 'intval');
        $callback = input('param.callback');
        if (empty($id)) {
            return json([
                'status'=>false,
                'msg'=>'参数错误'
            ]);
        }
        $protalPostInfo=$portal_post->where(['id'=>$id])->find();
        $portal_post->where(['id'=>$id])->update([
            'post_hits'=>$protalPostInfo['post_hits']+1
        ]);
        $protalPostNewInfo=$portal_post->where(['id'=>$id])->find();
        $json=json_encode(['status'=>true,'post_hits'=>$protalPostNewInfo['post_hits']]);
        echo $callback . "({$json})";exit;
    }



}