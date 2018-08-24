<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;

use think\Db;
use think\Exception;
use think\Request;
use think\Url;
use tree\Tree;

class TemplateController extends AdminBaseController
{

    protected $nav;
    protected $user;
    protected $link;
    protected $nav_menu;
    protected $portal_tag;
    protected $server_host;
    protected $server_root;
    protected $portal_post;
    protected $portal_tag_post;
    protected $portal_category;
    protected $portal_category_post;

    public function _initialize()
    {
        $this->nav = Db::name('nav');
        $this->user = Db::name('user');
        $this->link = Db::name('link');
        $this->nav_menu = Db::name('nav_menu');
        $this->portal_tag = Db::name('portal_tag');
        $this->server_host=config('server_host');
        $this->server_root=config('server_root');
        $this->portal_post = Db::name('portal_post');
        $this->portal_tag_post = Db::name('portal_tag_post');
        $this->portal_category = Db::name('portal_category');
        $this->portal_category_post = Db::name("portal_category_post");
    }

    /**
     * 生成模板
     * @return mixed
     */
    public function createtemplate()
    {
        //网站信息
        $siteInfo  = cmf_get_option('site_info');

        //导航
        $navMainInfo=$this->nav->where(['is_main'=>1])->find();
        $navMenuList=$this->nav_menu->where(['nav_id'=>$navMainInfo['id'],'status'=>1])->order('list_order asc')->select();
        $navMenuList=$navMenuList->toArray();
        $mainNavName=urlencode($navMainInfo['name']);

        //友情链接
        $linksList = $this->link->where(['status' => 1])->order('list_order asc')->select();
        $linksList = $linksList->toArray();

        $this->assign('siteInfo',$siteInfo);
        $this->assign('year',date('Y',time()));
        $this->assign('linksList',$linksList);
        $this->assign('mainNavName',$mainNavName);
        $this->assign('navMenuList',$navMenuList);
        $this->assign('server_name',config('server_name'));
        $this->assign('server_host',config('server_host'));

        $this->_create_index();
        $this->_create_news_list();
        $this->_create_news_tag();
        $this->_create_news_detail();
        exit;
    }

    /**
     * 生成首页
     */
    private function _create_index()
    {
        //最新发布
        $newNewsList=$this->portal_post->where(['post_status'=>1,'delete_time'=>0])->order('is_top desc,top_time desc,published_time desc')->select();
        $newNewsList = $newNewsList->toArray();
        $newNewsCount=$this->portal_post->where(['post_status'=>1,'delete_time'=>0])->order('is_top desc,top_time desc,published_time desc')->count();
        if($newNewsCount>0){
            foreach ($newNewsList as $k => $v) {
                if($v['id']<10000){
                    $idNum=str_pad($v['id'],5,"0",STR_PAD_LEFT);
                }else{
                    $idNum=$v['id'];
                }
                $userInfo=$this->user->where(['id'=>$v['user_id']])->find();
                $moreArr=json_decode($v['more'],true);
                if(!empty($moreArr['thumbnail'])){
                    if(strpos($moreArr['thumbnail'],'http')!==false){
                        $thumbnail=$moreArr['thumbnail'];
                    }else{
                        $thumbnail=config('img_url').'/upload/'.$moreArr['thumbnail'];
                    }
                }else{
                    $thumbnail=config('default_img');
                }

                $newNewsList[$k]['thumbnail']=$thumbnail;
                $newNewsList[$k]['post_title']=trim($v['post_title']);
                $newNewsList[$k]['url']=config('server_name').'/post/'.$idNum.'.html';
                $newNewsList[$k]['published_time']=date('Y年m月d日',$v['published_time'])." &#8211; ".getStrTime($v['published_time'])." ".date('H:i',$v['published_time']);
                $newNewsList[$k]['user_nickname']=$userInfo['user_nickname'];
                if(!empty($userInfo['avatar'])){
                    if(strpos($userInfo['avatar'],'http')!==false){
                        $avatar=$userInfo['avatar'];
                    }else{
                        $avatar=config('img_url').'/upload/'.$userInfo['avatar'];
                    }
                }else{
                    $avatar=config('default_avatar');
                }
                $newNewsList[$k]['avatar']=$avatar;
            }

            $pageNum=ceil($newNewsCount/10);
            for($i=0;$i<$pageNum;$i++){
                $t=$i+1;
                $newNewsData=[];
                foreach($newNewsList as $k=>$v){
                    if($k>=$i*10&&$k<$t*10){
                        $newNewsData[$k]=$v;
                    }
                }
                if($i==0){
                    $htmlPath='../../'.$this->server_root.'/';
                }else{
                    $htmlPath='../../'.$this->server_root.'/page/'.$t.'/';
                    if(!is_dir($htmlPath))
                        mkdir($htmlPath,0777,true);
                }

                if($t==1&&$t!=$pageNum){
                    $lastPage='';
                    $nextPage=$t+1;
                }else if($t==$pageNum){
                    $lastPage='/page/'.($t-1);
                    $nextPage=$pageNum;
                }else{
                    $lastPage='/page/'.($t-1);
                    $nextPage=$t+1;
                }
                $t2=$t3=$t4=$t5='';
                if(($t+1)>$pageNum)
                    $t2='style="display:none"';

                if(($t+2)>$pageNum)
                    $t3='style="display:none"';

                if(($t+3)>$pageNum)
                    $t4='style="display:none"';

                $page='
                    <div class="pagebar">
                        <a href="'.config('server_name').'" title="首页">首页</a>
                        <a href="'.config('server_name').$lastPage.'" title="上一页"><<</a>
                        <a href="'.config('server_name').'/page/'.$t.'" title="第'.$t.'页"  class="this-page">'.$t.'</a>
                        <a href="'.config('server_name').'/page/'.($t+1).'" title="第'.($t+1).'页" '.$t2.'>'.($t+1).'</a>
                        <a href="'.config('server_name').'/page/'.($t+2).'" title="第'.($t+2).'页" '.$t3.'>'.($t+2).'</a>

                        <a href="'.config('server_name').'/page/'.($t+3).'" title="第'.($t+3).'页" '.$t4.'>'.($t+3).'</a>
                        <a href="'.config('server_name').'/page/'.$nextPage.'" title="下一页">>></a>
                        <a href="'.config('server_name').'/page/'.$pageNum.'" title="尾页">尾页</a>
                    </div>
                ';
                $this->assign('page',$page);
                $this->assign('newNewsData',$newNewsData);
                $this->buildHtml('index', $htmlPath, 'template:index', 'utf-8');
            }
        }
    }

    /**
     * 生成列表页
     */
    private function _create_news_list()
    {
        $navInfo=$this->nav->where(['is_main'=>1])->find();
        $navMenuList=$this->nav_menu->where(['nav_id'=>$navInfo['id'],'status'=>1])->order('list_order asc')->select();
        $navMenuList=$navMenuList->toArray();
        if(count($navMenuList)>0){
            foreach($navMenuList as $k=>$v){
                $navHrefArr=explode('/',$v['href']);
                $navHrefArr[count($navHrefArr)-1]=urlencode(end($navHrefArr));
                $nav_href=implode('/',$navHrefArr);
                $categoryInfo=$this->portal_category->where(['name'=>$v['name'],'delete_time'=>0,'status'=>1])->find();
                $postIDArr=$this->portal_category_post->where(['category_id'=>$categoryInfo['id'],'status'=>1])->column('post_id');
                $newNewsList=$this->portal_post->where(['post_status'=>1,'delete_time'=>0,'id'=>['in',$postIDArr]])->order('is_top desc,top_time desc,published_time desc')->select();
                $newNewsList = $newNewsList->toArray();

                $newNewsCount=$this->portal_post->where(['post_status'=>1,'delete_time'=>0,'id'=>['in',$postIDArr]])->order('is_top desc,top_time desc,published_time desc')->count();
                if($newNewsCount>0){
                    foreach ($newNewsList as $k1 => $v1) {
                        if($v1['id']<10000){
                            $idNum=str_pad($v1['id'],5,"0",STR_PAD_LEFT);
                        }else{
                            $idNum=$v1['id'];
                        }
                        $userInfo=$this->user->where(['id'=>$v1['user_id']])->find();
                        $moreArr=json_decode($v1['more'],true);
                        if(!empty($moreArr['thumbnail'])){
                            if(strpos($moreArr['thumbnail'],'http')!==false){
                                $thumbnail=$moreArr['thumbnail'];
                            }else{
                                $thumbnail=config('img_url').'/upload/'.$moreArr['thumbnail'];
                            }
                        }else{
                            $thumbnail=config('default_img');
                        }

                        $newNewsList[$k1]['thumbnail']=$thumbnail;
                        $newNewsList[$k1]['post_title']=trim($v1['post_title']);
                        $newNewsList[$k1]['url']=config('server_name').'/post/'.$idNum.'.html';
                        $newNewsList[$k1]['published_time']=date('Y年m月d日',$v1['published_time'])." &#8211; ".getStrTime($v1['published_time'])." ".date('H:i',$v1['published_time']);
                        $newNewsList[$k1]['user_nickname']=$userInfo['user_nickname'];
                        if(!empty($userInfo['avatar'])){
                            if(strpos($userInfo['avatar'],'http')!==false){
                                $avatar=$userInfo['avatar'];
                            }else{
                                $avatar=config('img_url').'/upload/'.$userInfo['avatar'];
                            }
                        }else{
                            $avatar=config('default_avatar');
                        }
                        $newNewsList[$k1]['avatar']=$avatar;
                    }
                    $pageNum=ceil($newNewsCount/10);
                    for($i=0;$i<$pageNum;$i++){
                        $t=$i+1;
                        $newNewsData=[];
                        foreach($newNewsList as $k2=>$v2){
                            if($k2>=$i*10&&$k2<$t*10){
                                $newNewsData[$k2]=$v2;
                            }
                        }
                        if($i==0){
                            $htmlPath='../../'.$this->server_root.$v['href'].'/';
                        }else{
                            $htmlPath='../../'.$this->server_root.$v['href'].'/page/'.$t.'/';
                        }
                        if(!@is_dir($htmlPath))
                            mkdir($htmlPath, 0777,true);

                        if($t==1&&$t!=$pageNum){
                            $lastPage=$nav_href;
                            $nextPage=$t+1;
                        }else if($t==$pageNum){
                            $lastPage=$nav_href.'/page/'.($t-1);
                            $nextPage=$pageNum;
                        }else{
                            $lastPage=$nav_href.'/page/'.($t-1);
                            $nextPage=$t+1;
                        }
                        $nextUrl=config('server_name').$nav_href.'/page/'.$nextPage;
                        $canonical=config('server_name').$nav_href;

                        $t2=$t3=$t4=$t5='';
                        if(($t+1)>$pageNum)
                            $t2='style="display:none"';

                        if(($t+2)>$pageNum)
                            $t3='style="display:none"';

                        if(($t+3)>$pageNum)
                            $t4='style="display:none"';

                        $page='
                            <div class="pagebar">
                                <a href="'.config('server_name').$nav_href.'" title="首页">首页</a>
                                <a href="'.config('server_name').$lastPage.'" title="上一页"><<</a>
                                <a href="'.config('server_name').$nav_href.'/page/'.$t.'" title="第'.$t.'页"  class="this-page">'.$t.'</a>
                                <a href="'.config('server_name').$nav_href.'/page/'.($t+1).'" title="第'.($t+1).'页" '.$t2.'>'.($t+1).'</a>
                                <a href="'.config('server_name').$nav_href.'/page/'.($t+2).'" title="第'.($t+2).'页" '.$t3.'>'.($t+2).'</a>
                                <a href="'.config('server_name').$nav_href.'/page/'.($t+3).'" title="第'.($t+3).'页" '.$t4.'>'.($t+3).'</a>
                                <a href="'.config('server_name').$nav_href.'/page/'.$nextPage.'" title="下一页">>></a>
                                <a href="'.config('server_name').$nav_href.'/page/'.$pageNum.'" title="尾页">尾页</a>
                            </div>
                        ';
                        $this->assign('nextUrl',$nextUrl);
                        $this->assign('canonical',$canonical);
                        $this->assign('page',$page);
                        $this->assign('newNewsData',$newNewsData);
                        $this->assign('categoryInfo',$categoryInfo);
                        $this->buildHtml('index', $htmlPath, 'template:list', 'utf-8');
                    }
                }
            }
        }
    }

    /**
     * 生成标签页
     */
    private function _create_news_tag()
    {
        $portalTagList=$this->portal_tag->where(['status'=>1])->select();
        $portalTagList=$portalTagList->toArray();
        if(count($portalTagList)>0){
            foreach($portalTagList as $k=>$v){
                $name=urlencode($v['name']);
                $tag_href='/post/tag/'.$name;

                $postIDArr=$this->portal_tag_post->where(['tag_id'=>$v['id'],'status'=>1])->column('post_id');
                $newNewsList=$this->portal_post->where(['post_status'=>1,'delete_time'=>0,'id'=>['in',$postIDArr]])->order('is_top desc,top_time desc,published_time desc')->select();
                $newNewsList = $newNewsList->toArray();

                $newNewsCount=$this->portal_post->where(['post_status'=>1,'delete_time'=>0,'id'=>['in',$postIDArr]])->order('is_top desc,top_time desc,published_time desc')->count();
                if($newNewsCount>0){
                    foreach ($newNewsList as $k1 => $v1) {
                        if($v1['id']<10000){
                            $idNum=str_pad($v1['id'],5,"0",STR_PAD_LEFT);
                        }else{
                            $idNum=$v1['id'];
                        }
                        $userInfo=$this->user->where(['id'=>$v1['user_id']])->find();
                        $moreArr=json_decode($v1['more'],true);
                        if(!empty($moreArr['thumbnail'])){
                            if(strpos($moreArr['thumbnail'],'http')!==false){
                                $thumbnail=$moreArr['thumbnail'];
                            }else{
                                $thumbnail=config('img_url').'/upload/'.$moreArr['thumbnail'];
                            }
                        }else{
                            $thumbnail=config('default_img');
                        }

                        $newNewsList[$k1]['thumbnail']=$thumbnail;
                        $newNewsList[$k1]['post_title']=trim($v1['post_title']);
                        $newNewsList[$k1]['url']=config('server_name').'/post/'.$idNum.'.html';
                        $newNewsList[$k1]['published_time']=date('Y年m月d日',$v1['published_time'])." &#8211; ".getStrTime($v1['published_time'])." ".date('H:i',$v1['published_time']);
                        $newNewsList[$k1]['user_nickname']=$userInfo['user_nickname'];

                        if(!empty($userInfo['avatar'])){
                            if(strpos($userInfo['avatar'],'http')!==false){
                                $avatar=$userInfo['avatar'];
                            }else{
                                $avatar=config('img_url').'/upload/'.$userInfo['avatar'];
                            }
                        }else{
                            $avatar=config('default_avatar');
                        }
                        $newNewsList[$k1]['avatar']=$avatar;
                    }

                    $pageNum=ceil($newNewsCount/10);
                    for($i=0;$i<$pageNum;$i++){
                        $t=$i+1;
                        $newNewsData=[];
                        foreach($newNewsList as $k2=>$v2){
                            if($k2>=$i*10&&$k2<$t*10){
                                $newNewsData[$k2]=$v2;
                            }
                        }

                        if($i==0){
                            $htmlPath='../../'.$this->server_root.'/post/tag/'.$v['name'].'/';
                        }else{
                            $htmlPath='../../'.$this->server_root.'/post/tag/'.$v['name'].'/page/'.$t.'/';
                        }
                        if(!@is_dir($htmlPath))
                            mkdir($htmlPath, 0777,true);

                        if($t==1&&$t!=$pageNum){
                            $lastPage=$tag_href;
                            $nextPage=$t+1;
                        }else if($t==$pageNum){
                            $lastPage=$tag_href.'/page/'.($t-1);
                            $nextPage=$pageNum;
                        }else{
                            $lastPage=$tag_href.'/page/'.($t-1);
                            $nextPage=$t+1;
                        }
                        $nextUrl=config('server_name').$tag_href.'/page/'.$nextPage;
                        $canonical=config('server_name').$tag_href;

                        $pageStyle='';
                        if(count($newNewsData)<1)
                            $pageStyle='style="display:none"';

                        $t2=$t3=$t4=$t5='';
                        if(($t+1)>$pageNum)
                            $t2='style="display:none"';

                        if(($t+2)>$pageNum)
                            $t3='style="display:none"';

                        if(($t+3)>$pageNum)
                            $t4='style="display:none"';

                        $page='
                            <div class="pagebar" '.$pageStyle.'>
                                <a href="'.config('server_name').$tag_href.'" title="首页">首页</a>
                                <a href="'.config('server_name').$lastPage.'" title="上一页"><<</a>
                                <a href="'.config('server_name').$tag_href.'/page/'.$t.'" title="第'.$t.'页"  class="this-page">'.$t.'</a>
                                <a href="'.config('server_name').$tag_href.'/page/'.($t+1).'" title="第'.($t+1).'页" '.$t2.'>'.($t+1).'</a>
                                <a href="'.config('server_name').$tag_href.'/page/'.($t+2).'" title="第'.($t+2).'页" '.$t3.'>'.($t+2).'</a>
                                <a href="'.config('server_name').$tag_href.'/page/'.($t+3).'" title="第'.($t+3).'页" '.$t4.'>'.($t+3).'</a>
                                <a href="'.config('server_name').$tag_href.'/page/'.$nextPage.'" title="下一页">>></a>
                                <a href="'.config('server_name').$tag_href.'/page/'.$pageNum.'" title="尾页">尾页</a>
                            </div>
                        ';
                        $this->assign('nextUrl',$nextUrl);
                        $this->assign('canonical',$canonical);
                        $this->assign('page',$page);
                        $this->assign('newNewsData',$newNewsData);
                        $this->assign('tagInfo',$portalTagList[$k]);
                        $this->buildHtml('index', $htmlPath, 'template:tag', 'utf-8');
                    }
                }
            }
        }
    }

    /**
     * 生成详情页
     */
    private function _create_news_detail()
    {
        $newNewsList=$this->portal_post->where(['post_status'=>1,'delete_time'=>0])->order('is_top desc,top_time desc,published_time desc')->select();
        $newNewsList = $newNewsList->toArray();
        if(count($newNewsList)>0){
            foreach ($newNewsList as $k => $v) {
                if($v['id']<10000){
                    $idNum=str_pad($v['id'],5,"0",STR_PAD_LEFT);
                }else{
                    $idNum=$v['id'];
                }
                $userInfo=$this->user->where(['id'=>$v['user_id']])->find();
                $moreArr=json_decode($v['more'],true);
                if(!empty($moreArr['thumbnail'])){
                    if(empty($http)){
                        $thumbnail=config('img_url').'/upload/'.$moreArr['thumbnail'];
                    }else{
                        $thumbnail=$moreArr['thumbnail'];
                    }
                }else{
                    $thumbnail=config('default_img');
                }

                $newNewsList[$k]['idNum']=$idNum;
                $newNewsList[$k]['thumbnail']=$thumbnail;
                $newNewsList[$k]['post_title']=trim($v['post_title']);
                $newNewsList[$k]['url']=config('server_name').'/post/'.$idNum.'.html';
                $newNewsList[$k]['published_time']=date('Y年m月d日',$v['published_time'])."<br />".getStrTime($v['published_time'])." ".date('H:i',$v['published_time']);
                $newNewsList[$k]['user_nickname']=$userInfo['user_nickname'];
                if(!empty($userInfo['avatar'])){
                    if(strpos($userInfo['avatar'],'http')!==false){
                        $avatar=$userInfo['avatar'];
                    }else{
                        $avatar=config('img_url').'/upload/'.$userInfo['avatar'];
                    }
                }else{
                    $avatar=config('default_avatar');
                }
                $newNewsList[$k]['avatar']=$avatar;

                //处理多余Html标签
                $newNewsList[$k]['post_content']=$this->dealHtmlTag($v['post_content']);

                //处理图片
                $img_data = $this->_gPicUrl(htmlspecialchars_decode($v['post_content']));
                if (!empty($img_data)) {
                    $img_replace_array = array();
                    for ($i = 0; $i < count($img_data); $i++) {
                        if(strpos($img_data[$i],'http')!==false){
                            //外部链接
                            if(strpos($img_data[$i],'http://jbcdn2.b0.upaiyun.com')!==false){
                                $picId=rand(1,953);
                                $picInfo=Db::name('pic')->where(['id'=>$picId])->find();
                                $new='src="'.config('img_url') . '/upload/' . $picInfo['img'] . '"';
                            }else{
                                $new='src="'. $img_data[$i] . '"';
                            }
                        }else{
                            $new='src="'.config('img_url') . '/upload/' . $img_data[$i] . '"';
                        }

                        // 放到替换数组
                        array_push($img_replace_array, array(
                            'old' => 'src="' . $img_data[$i] . '"',
                            'new' => $new
                        ));
                    }
                    // 替换图片
                    foreach ($img_replace_array as $key_replace => $value_replace) {
                        $newNewsList[$k]['post_content'] = str_replace($value_replace['old'], $value_replace['new'], htmlspecialchars_decode($newNewsList[$k]['post_content'] ));
                    }
                }
                $newNewsList[$k]['post_content']=htmlspecialchars_decode($newNewsList[$k]['post_content']);

            }

            foreach($newNewsList as $k=>$v){
                $htmlPath='../../'.$this->server_root.'/post/';
                if(!is_dir($htmlPath))
                    mkdir($htmlPath,0777,true);

                $prevData=isset($newNewsList[$k-1])?$newNewsList[$k-1]:['post_title'=>'','url'=>''];
                $nextData=isset($newNewsList[$k+1])?$newNewsList[$k+1]:['post_title'=>'','url'=>''];

                //相关标签
                $tagIDArr=$this->portal_tag_post->where(['post_id'=>$v['id'],'status'=>1])->column('tag_id');
                $tagList=$this->portal_tag->where(['id'=>['in',$tagIDArr],'status'=>1])->limit(10)->select();
                $tagList = $tagList->toArray();
                foreach($tagList as $k1=>$v1){
                    $name=urlencode($v1['name']);
                    $tagList[$k1]['url']=config('server_name').'/post/tag/'.$name;
                }

                //相关文章
                $portalTagInfo=$this->portal_tag_post->where(['post_id'=>$v['id']])->find();
                $postIDArr=$this->portal_tag_post->where(['tag_id'=>$portalTagInfo['tag_id'],'status'=>1])->column('post_id');
                $postList=$this->portal_post->where(['post_status'=>1,'delete_time'=>0,'id'=>['in',$postIDArr]])->order('is_top desc,top_time desc,published_time desc')->limit(10)->select();
                $postList = $postList->toArray();
                foreach($postList as $k2=>$v2){
                    if($v2['id']<10000){
                        $idNum2=str_pad($v2['id'],5,"0",STR_PAD_LEFT);
                    }else{
                        $idNum2=$v2['id'];
                    }
                    $postList[$k2]['post_title']=trim($v2['post_title']);
                    $postList[$k2]['url']=config('server_name').'/post/'.$idNum2.'.html';
                }

                $this->assign('postList',$postList);
                $this->assign('tagList',$tagList);
                $this->assign('prevData',$prevData);
                $this->assign('nextData',$nextData);
                $this->assign('data',$newNewsList[$k]);
                $this->buildHtml($v['idNum'], $htmlPath, 'template:detail', 'utf-8');
            }
        }
    }

    /**
     *  创建静态页面
     * @access protected
     * @htmlfile 生成的静态文件名称
     * @htmlpath 生成的静态文件路径
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @return string
     */
    protected function buildHtml($htmlfile = '', $htmlpath = '', $templateFile = '')
    {
        $content = $this->fetch($templateFile);
        $htmlpath = !empty($htmlpath) ? $htmlpath : HTML_PATH;
        $htmlfile = $htmlpath . $htmlfile . '.html';
        file_put_contents($htmlfile, $content);
        return $content;
    }

    /**
     * 处理Html标签
     * @param $postContent
     * @return mixed|string
     */
    protected function dealHtmlTag($postContent){
        if(empty($postContent))
            return $postContent;
        $postContent=htmlspecialchars_decode($postContent);
        preg_match_all('%<div class="copyright-area">(.*?)</div>%si', $postContent, $search1);
        $postContent = str_replace($search1[0], '', $postContent);

        preg_match_all('%<blockquote class="rewards">(.*?)</blockquote>%si', $postContent, $search2);
        $postContent = str_replace($search2[0], '', $postContent);

        preg_match_all('%<div id="rewardbox">(.*?)</div>%si', $postContent, $search3);
        $postContent = str_replace($search3[0], '', $postContent);

        preg_match_all('%<div class="post-adds">(.*?)</div>%si', $postContent, $search4);
        $postContent = str_replace($search4[0], '', $postContent);

        preg_match_all('%<div id="author-bio">(.*?)</div>%si', $postContent, $search5);
        $postContent = str_replace($search5[0], '', $postContent);

        preg_match_all('%<div class="author-bio-info">(.*?)</div>%si', $postContent, $search6);
        $postContent = str_replace($search6[0], '', $postContent);
        return $postContent;
    }


    /**
     * 提取图片
     * @param $content
     * @return bool|int
     */
    protected function _gPicUrl($content)
    {
        $pattern = '/src=\"([\s\S]+?)\"/';//正则
        $result = preg_match_all($pattern, $content, $match);//匹配图片
        $result = empty($result) ? false : $match[1];//返回所有图片的路径
        return $result;
    }
}