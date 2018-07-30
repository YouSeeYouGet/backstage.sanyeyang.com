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
        set_time_limit(0);
        include_once '../simplewind/vendor/querylist/QueryList.class.php';
        $grabTagList=Db::name('grab_tag')->where(['status'=>1])->select();
        $grabTagList=$grabTagList->toArray();
        foreach($grabTagList as $key=>$val){
            $source= array("content"=>array(".grid-8","html"));
            $grabUrl='http://blog.jobbole.com/tag/'.$val['name'].'/';
            $obj = new \QueryList($grabUrl,$source);
            $data = $obj->jsonArr;
            if(empty($data[0]['content']))
                continue;
            $encode = mb_detect_encoding($data[0]['content'], array("ASCII",'UTF-8','GB2312','GBK','BIG5'));
            if($encode!='UTF-8'){
                $content=iconv($encode,"UTF-8",$data[0]['content']);
            }else{
                $content=$data[0]['content'];
            }

            $user_id=1;
            $post_status=$is_top=0;
            $create_time=$update_time=time();
            $post_source='伯乐在线';
            $imgArr=gPicUrl($content);
            $urlArr=[];
            if(preg_match_all('/<a target="_blank" class="archive-title".*<\/a>/im', $content,$list)){
                for($i=0;$i<count($list[0]);$i++){
                    if (preg_match("/href=\"([^\"]+)/", $list[0][$i], $href)){
                        $urlArr[]=htmlspecialchars_decode($href[1]);
                    }
                    if (preg_match("/>(.*)<\/a>/", $list[0][$i], $title)){
                        $postTitleArr[]=trim($title[1]);
                    }
                }
            }

            $excerpt_source= array("post_excerpt"=>array(".excerpt p","html"));
            $excerpt_obj = new \QueryList($grabUrl,$excerpt_source);
            $excerptList = $excerpt_obj->jsonArr;
            if(empty($excerptList))
                continue;
            foreach($excerptList as $k=>$v) {
                $code = mb_detect_encoding($v['post_excerpt'], array("ASCII", 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
                if ($code != 'UTF-8') {
                    $postExcerptArr[] = iconv($code, "UTF-8", $v['post_excerpt']);
                } else {
                    $postExcerptArr[] = $v['post_excerpt'];
                }
            }

            $post_content_source= array("post_content"=>array(".entry","html"));
            for($i=0;$i<count($urlArr);$i++){
                $post_content_obj = new \QueryList($urlArr[$i],$post_content_source);
                $post_content_array = $post_content_obj->jsonArr;
                if(empty($post_content_array[0]['post_content']))
                    continue;
                $encode = mb_detect_encoding($post_content_array[0]['post_content'], array("ASCII",'UTF-8','GB2312','GBK','BIG5'));
                if($encode!='UTF-8'){
                    $post_content=iconv($encode,"UTF-8",$post_content_array[0]['post_content']);
                }else{
                    $post_content=$post_content_array[0]['post_content'];
                }

                $post_title=$postTitleArr[$i];
                $post_content=htmlspecialchars($post_content);
                $thumbnail=isset($imgArr[$i])?$imgArr[$i]:'';
                $more=json_encode(['thumbnail'=>$thumbnail, 'template'=>'']);

                $portalPostInfo=Db::name('portal_post')->where(['delete_time'=>0,'post_title'=>$post_title])->find();
                if(!empty($portalPostInfo))
                    continue;

                $portalPostID=Db::name('portal_post')->insertGetId([
                    'user_id'=>$user_id,
                    'post_status'=>$post_status,
                    'is_top'=>$is_top,
                    'create_time'=>$create_time,
                    'update_time'=>$update_time,
                    'post_title'=>$post_title,
                    'post_keywords'=>$val['name'],
                    'post_excerpt'=>$postExcerptArr[$i],
                    'post_source'=>$post_source,
                    'post_content'=>$post_content,
                    'more'=>$more
                ]);
                if(!empty($portalPostID)){
                    $categoryPostInfo=Db::name('portal_category_post')->where(['post_id'=>$portalPostID,'category_id'=>$val['category_id'],'status'=>1])->find();
                    if(empty($categoryPostInfo))
                        Db::name('portal_category_post')->insertGetId([
                            'post_id'=>$portalPostID,
                            'category_id'=>$val['category_id'],
                            'status'=>1
                        ]);

                    $tagInfo=Db::name('portal_tag')->where(['status'=>1,'name'=>$val['name']])->find();
                    if(empty($tagInfo)){
                        $tagID=Db::name('portal_tag')->insertGetId([
                            'status'=>1,
                            'name'=>$val['name']
                        ]);
                        if(!empty($tagID)){
                            $tagPostInfo=Db::name('portal_tag_post')->where(['tag_id'=>$tagID,'post_id'=>$portalPostID,'status'=>1])->find();
                            if(empty($tagPostInfo))
                                Db::name('portal_tag_post')->insertGetId([
                                    'tag_id'=>$tagID,
                                    'post_id'=>$portalPostID,
                                    'status'=>1
                                ]);
                        }
                    }
                }
            }
        }
        exit;
    }

    /**
     * 心情随笔
     */
    public function grab_essay(){
        set_time_limit(0);
        include_once '../simplewind/vendor/querylist/QueryList.class.php';

        $source= array("content"=>array(".grid-8","html"));
        $obj = new \QueryList('http://blog.jobbole.com/category/career/',$source);
        $data = $obj->jsonArr;
        if(empty($data[0]['content']))
            exit;
        $encode = mb_detect_encoding($data[0]['content'], array("ASCII",'UTF-8','GB2312','GBK','BIG5'));
        if($encode!='UTF-8'){
            $content=iconv($encode,"UTF-8",$data[0]['content']);
        }else{
            $content=$data[0]['content'];
        }

        $user_id=1;
        $post_status=$is_top=0;
        $create_time=$update_time=time();
        $post_source='伯乐在线';

        $imgArr=gPicUrl($content);
        if(preg_match_all('/<a target="_blank" class="archive-title".*<\/a>/im', $content,$list)){
            for($i=0;$i<count($list[0]);$i++){
                if (preg_match("/href=\"([^\"]+)/", $list[0][$i], $href)){
                    $urlArr[]=htmlspecialchars_decode($href[1]);
                }
                if (preg_match("/>(.*)<\/a>/", $list[0][$i], $title)){
                    $postTitleArr[]=trim($title[1]);
                }
            }
        }

        $excerpt_source= array("post_excerpt"=>array(".excerpt p","html"));
        $excerpt_obj = new \QueryList('http://blog.jobbole.com/category/career/',$excerpt_source);
        $excerptList = $excerpt_obj->jsonArr;
        if(empty($excerptList))
            exit;
        foreach($excerptList as $k=>$v) {
            $code = mb_detect_encoding($v['post_excerpt'], array("ASCII", 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
            if ($code != 'UTF-8') {
                $postExcerptArr[] = iconv($code, "UTF-8", $v['post_excerpt']);
            } else {
                $postExcerptArr[] = $v['post_excerpt'];
            }
        }

        $post_content_source= array("post_content"=>array(".entry","html"));
        for($i=0;$i<count($urlArr);$i++){
            $post_content_obj = new \QueryList($urlArr[$i],$post_content_source);
            $post_content_array = $post_content_obj->jsonArr;
            if(empty($post_content_array[0]['post_content']))
                continue;
            $encode = mb_detect_encoding($post_content_array[0]['post_content'], array("ASCII",'UTF-8','GB2312','GBK','BIG5'));
            if($encode!='UTF-8'){
                $post_content=iconv($encode,"UTF-8",$post_content_array[0]['post_content']);
            }else{
                $post_content=$post_content_array[0]['post_content'];
            }

            $post_title=$postTitleArr[$i];
            $post_content=htmlspecialchars($post_content);
            $thumbnail=isset($imgArr[$i])?$imgArr[$i]:'';
            $more=json_encode(['thumbnail'=>$thumbnail, 'template'=>'']);

            $portalPostInfo=Db::name('portal_post')->where(['delete_time'=>0,'post_title'=>$post_title])->find();
            if(!empty($portalPostInfo))
                continue;

            $portalPostID=Db::name('portal_post')->insertGetId([
                'user_id'=>$user_id,
                'post_status'=>$post_status,
                'is_top'=>$is_top,
                'create_time'=>$create_time,
                'update_time'=>$update_time,
                'post_title'=>$post_title,
                'post_keywords'=>'职场',
                'post_excerpt'=>$postExcerptArr[$i],
                'post_source'=>$post_source,
                'post_content'=>$post_content,
                'more'=>$more
            ]);
            if(!empty($portalPostID)){
                $category_id=5;
                $categoryPostInfo=Db::name('portal_category_post')->where(['post_id'=>$portalPostID,'category_id'=>$category_id,'status'=>1])->find();
                if(empty($categoryPostInfo))
                    Db::name('portal_category_post')->insertGetId([
                        'post_id'=>$portalPostID,
                        'category_id'=>$category_id,
                        'status'=>1
                    ]);

                $tagInfo=Db::name('portal_tag')->where(['status'=>1,'name'=>'职场'])->find();
                if(empty($tagInfo)){
                    $tagID=Db::name('portal_tag')->insertGetId([
                        'status'=>1,
                        'name'=>'职场'
                    ]);
                    if(!empty($tagID)){
                        $tagPostInfo=Db::name('portal_tag_post')->where(['tag_id'=>$tagID,'post_id'=>$portalPostID,'status'=>1])->find();
                        if(empty($tagPostInfo))
                            Db::name('portal_tag_post')->insertGetId([
                                'tag_id'=>$tagID,
                                'post_id'=>$portalPostID,
                                'status'=>1
                            ]);
                    }
                }
            }
        }
        exit;
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
        exit;
    }
}