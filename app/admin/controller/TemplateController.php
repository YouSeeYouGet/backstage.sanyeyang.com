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
    protected $server_root;
    protected $portal_post;
    protected $portal_tag_post;
    protected $portal_category;
    protected $portal_category_post;

    public function _initialize()
    {
        parent::_initialize();
        $this->nav = Db::name('nav');
        $this->user = Db::name('user');
        $this->link = Db::name('link');
        $this->nav_menu = Db::name('nav_menu');
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
        //导航
        $navMainInfo=$this->nav->where(['is_main'=>1])->find();
        $navMenuList=$this->nav_menu->where(['nav_id'=>$navMainInfo['id'],'status'=>1])->order('list_order asc')->select();
        $navMenuList=$navMenuList->toArray();
        $mainNavName=urlencode($navMainInfo['name']);


        //友情链接
        $linksList = $this->link->where(['status' => 1])->order('list_order asc')->select();
        $linksList = $linksList->toArray();

        $this->assign('linksList',$linksList);
        $this->assign('mainNavName',$mainNavName);
        $this->assign('navMenuList',$navMenuList);
        $this->assign('server_name',config('server_name'));

        $this->_create_index();
        $this->_create_news_list();
        $this->_create_news_detail();
        exit;
    }

    /**
     * 生成首页
     */
    private function _create_index()
    {
        //网站信息
        $siteInfo  = cmf_get_option('site_info');
        $this->assign('siteInfo',$siteInfo);

        //最新发布
        $newNewsList=$this->portal_post->where(['post_status'=>1,'delete_time'=>0,'is_top'=>['neq',1]])->order('is_top desc,published_time desc')->limit(20)->select();
        $newNewsList = $newNewsList->toArray();
        if(!empty($newNewsList)){
            foreach ($newNewsList as $k => $v) {
                if($v['id']<10000){
                    $idLen=strlen($v['id']);
                    $idNum=str_pad($v['id'],5,"0",STR_PAD_LEFT);
                }else{
                    $idNum=$v['id'];
                }
                $userInfo=$this->user->where(['id'=>$v['user_id']])->find();
                $moreArr=json_decode($v['more'],true);
                if(!empty($moreArr['thumbnail'])){
                    $thumbnail=config('img_url').'/upload/'.$moreArr['thumbnail'];
                }else{
                    $thumbnail=config('default_img');
                }

                $newNewsList[$k]['thumbnail']=$thumbnail;
                $newNewsList[$k]['post_title']=trim($v['post_title']);
                $newNewsList[$k]['url']=config('server_name').'/post/'.$idNum.'.html';
                $newNewsList[$k]['published_time']=date('Y年m月d日',$v['published_time'])." &#8211; ".getStrTime($v['published_time'])." ".date('H:i',$v['published_time']);
                $newNewsList[$k]['user_nickname']=$userInfo['user_nickname'];
            }

            $this->assign('newNewsList',$newNewsList);
        }

        //生成模板
        $this->buildHtml('index', '../../'.$this->server_root.'/', 'template:index', 'utf-8');
        exit;
    }

    /**
     * 生成列表页
     */
    private function _create_news_list()
    {
        include_once '../simplewind/vendor/pinyin/pinyin.class.php';
        $Pinyin=new \Pinyin();

        $navInfo=$this->nav->where(['is_main'=>1])->find();
        $navMenuList=$this->nav_menu->where(['nav_id'=>$navInfo['id'],'status'=>1,'href'=>['neq','/']])->order('list_order asc')->select();
        $navMenuList=$navMenuList->toArray();
        foreach($navMenuList as $k=>$v){
            if(!@file_exists('../../'.$this->server_root.$v['href'])){
                mkdir('../../'.$this->server_root.$v['href'], 0777,true);
            }

            $categoryInfo=$this->portal_category->where(['name'=>$v['name'],'delete_time'=>0,'status'=>1])->find();
            $this->assign('categoryInfo',$categoryInfo);

            $postIDArr=$this->portal_category_post->where(['category_id'=>$categoryInfo['id'],'status'=>1])->column('post_id');

            //热门文章
            $hotNewsList=$this->portal_post->where(['id'=>['in',$postIDArr],'post_status'=>1,'delete_time'=>0])->order('post_hits desc, post_like desc,comment_count desc')->limit(20)->select();
            $hotNewsList = $hotNewsList->toArray();
            foreach ($hotNewsList as $k2 => $v2) {
                $userInfo=$this->user->where(['id'=>$v2['user_id']])->find();
                $user_nickname=$Pinyin::getPinyin($userInfo['user_nickname']);
                $moreArr=json_decode($v2['more'],true);

                if(!empty($moreArr['thumbnail'])){
                    $thumbnail=config('img_url').'/upload/'.$moreArr['thumbnail'];
                }else{
                    $thumbnail=config('default_img');
                }

                $hotNewsList[$k2]['thumbnail']=$thumbnail;
                $hotNewsList[$k2]['post_title']=trim($v2['post_title']);
                $hotNewsList[$k2]['url']='/'.$user_nickname.'/article/details/'.substr(md5($v2['create_time']),-8);
                $hotNewsList[$k2]['published_time']=date('Y-m-d H:i:s',$v2['published_time']);
            }
            $this->assign('hotNewsList',$hotNewsList);

            $newsList=$this->portal_post->where(['id'=>['in',$postIDArr],'post_status'=>1,'delete_time'=>0])->order('published_time desc')->select();
            $newsList=$newsList->toArray();
            $num = count($newsList);

            $page = ceil($num / 20);
            for ($i = 0; $i < $page; $i++) {
                $newsData = [];
                foreach ($newsList as $k1 => $v1) {
                    $t = $i + 1;
                    if (20 * $i <= $k1 && $k1 < 20 * $t) {
                        $userInfo=$this->user->where(['id'=>$v1['user_id']])->find();
                        $user_nickname=$Pinyin::getPinyin($userInfo['user_nickname']);
                        $moreArr=json_decode($v1['more'],true);
                        if(!empty($moreArr['thumbnail'])){
                            $thumbnail=config('img_url').'/upload/'.$moreArr['thumbnail'];
                        }else{
                            $thumbnail=config('default_img');
                        }

                        $newsData[$k1]['thumbnail']=$thumbnail;
                        $newsData[$k1]['post_title']=trim($v1['post_title']);
                        $newsData[$k1]['url']='/'.$user_nickname.'/article/details/'.substr(md5($v1['create_time']),-8);
                        $newsData[$k1]['published_time']=date('Y-m-d H:i:s',$v1['published_time']);
                        $newsData[$k1]['post_excerpt']=$v1['post_excerpt'];
                        $newsData[$k1]['post_hits']=$v1['post_hits'];
                        $newsData[$k1]['user_nickname']=$userInfo['user_nickname'];
                    }
                }
                $this->assign('newsData',$newsData);

                $nexti = $i + 1;
                if ($i == 0) {
                    $listHtmlName = 'index';
                } else {
                    $listHtmlName = 'index-'. $nexti;
                }

                if ($i < 2) {
                    $listIndexUrl =$v['href'].'/index.html';
                } else {
                    $listIndexUrl =$v['href'].'/'.'index-'.($nexti-1) . ".html";
                }

                $display =$display_2=$display_3=$display_4 = '';
                if ($page == 1) {
                    $display = 'style="display:none"';
                } else {
                    if ($nexti + 1 > $page) {
                        $display_2 = 'style="display:none"';
                    }

                    if ($nexti + 2 > $page) {
                        $display_3 = 'style="display:none"';
                    }

                    if ($nexti + 3 > $page) {
                        $display_4 = 'style="display:none"';
                    }
                }

                $fenye = '
					<li ' . $display . '>
						<a href="' . $listIndexUrl . '">&lt;</a>
					</li>
					<li ' . $display . ' class="active current">
					    <span class="current">'.$nexti.'</span>
					</li>
					<li ' . $display . ' ' . $display_2 . '>
						<a href="' . $v['href']. '/'.'index-'.($nexti+1).'.html" >' . ($nexti + 1) . '</a>
					</li>
					<li ' . $display . ' ' . $display_3 . '>
						<a href="' . $v['href']. '/'.'index-'.($nexti+2).'.html" >' . ($nexti + 2) . '</a>
					</li>
					<li ' . $display . ' ' . $display_4 . '>
						<a href="' . $v['href']. '/'.'index-'.($nexti+3).'.html" >&gt;</a>
					</li>
				';

                $this->assign('fenye',$fenye);
                $this->buildHtml($listHtmlName, '../../'.$this->server_root.$v['href'].'/', 'template:list', 'utf-8');
            }
        }
    }

    /**
     * 生成详情页
     */
    private function _create_news_detail()
    {
        include_once '../simplewind/vendor/pinyin/pinyin.class.php';
        $Pinyin=new \Pinyin();

        $categoryID=$this->portal_category->where(['name'=>'首页轮播图','delete_time'=>0,'status'=>1])->value('id');
        $postIDArr=$this->portal_category_post->where(['category_id'=>$categoryID,'status'=>1])->column('post_id');
        $protalPostList=$this->portal_post->where(['id'=>['not in',$postIDArr],'post_status'=>1,'delete_time'=>0])->order('published_time desc')->select();
        $protalPostList=$protalPostList->toArray();
        $protalPostData=[];
        foreach($protalPostList as $k=>$v){
            $protalPostData['id']=trim($v['id']);
            $protalPostData['post_title']=trim($v['post_title']);
            $protalPostData['post_keywords']=trim($v['post_keywords']);
            $protalPostData['post_excerpt']=trim($v['post_excerpt']);
            $protalPostData['post_hits']=trim($v['post_hits']);
            $protalPostData['published_time']=date('Y-m-d H:i:s',$v['published_time']);
            $protalPostData['post_content']=htmlspecialchars_decode($v['post_content']);

            $img_data = $this->_gPicUrl(htmlspecialchars_decode($v['post_content']));
            if (!empty($img_data)) {
                $img_replace_array = array();
                for ($i = 0; $i < count($img_data); $i++) {

                    // 放到替换数组
                    array_push($img_replace_array, array(
                        'old' => 'src="' . $img_data[$i] . '"',
                        'new' => 'src="'.config('img_url') . '/upload/' . $img_data[$i] . '"'
                    ));
                }
                // 替换图片
                foreach ($img_replace_array as $key_replace => $value_replace) {
                    $protalPostList[$k]['post_content'] = str_replace($value_replace['old'], $value_replace['new'], htmlspecialchars_decode($protalPostList[$k]['post_content'] ));
                }
            }
            $protalPostData['post_content']=htmlspecialchars_decode($protalPostList[$k]['post_content']);
            $protalPostData['before_url']='';
            $protalPostData['last_url']='';
            if($k==0&&isset($protalPostList[$k+1])){
                $userInfo=$this->user->where(['id'=>$protalPostList[$k+1]['user_id']])->find();
                $user_nickname=$Pinyin::getPinyin($userInfo['user_nickname']);
                $protalPostData['before_url']='';
                $protalPostData['last_url']='/'.$user_nickname.'/article/details/'.substr(md5($protalPostList[$k+1]['create_time']),-8);
            }else if($k==(count($protalPostList)-1)&&isset($protalPostList[$k-1])){
                $userInfo=$this->user->where(['id'=>$protalPostList[$k-1]['user_id']])->find();
                $user_nickname=$Pinyin::getPinyin($userInfo['user_nickname']);
                $protalPostData['before_url']='/'.$user_nickname.'/article/details/'.substr(md5($protalPostList[$k-1]['create_time']),-8);
                $protalPostData['last_url']='';
            }else if(isset($protalPostList[$k+1])&&isset($protalPostList[$k-1])){
                $before_userInfo=$this->user->where(['id'=>$protalPostList[$k-1]['user_id']])->find();
                $last_userInfo=$this->user->where(['id'=>$protalPostList[$k+1]['user_id']])->find();
                $before_user_nickname=$Pinyin::getPinyin($before_userInfo['user_nickname']);
                $last_user_nickname=$Pinyin::getPinyin($last_userInfo['user_nickname']);
                $protalPostData['before_url']='/'.$before_user_nickname.'/article/details/'.substr(md5($protalPostList[$k-1]['create_time']),-8);
                $protalPostData['last_url']='/'.$last_user_nickname.'/article/details/'.substr(md5($protalPostList[$k+1]['create_time']),-8);
            }


            //相关文章
            $tagIDArr=$this->portal_tag_post->where(['post_id'=>$v['id'],'status'=>1])->column('tag_id');
            $postIDArr=$this->portal_tag_post->where(['tag_id'=>['in',$tagIDArr],'status'=>1,'post_id'=>['neq',$v['id']]])->column('post_id');
            $aboutNewsList=$this->portal_post->where(['id'=>['in',$postIDArr],'post_status'=>1,'delete_time'=>0])->order('post_hits desc, post_like desc,comment_count desc')->limit(20)->select();
            $aboutNewsList = $aboutNewsList->toArray();
            foreach ($aboutNewsList as $k2 => $v2) {
                $userInfo=$this->user->where(['id'=>$v2['user_id']])->find();
                $user_nickname=$Pinyin::getPinyin($userInfo['user_nickname']);
                $aboutNewsList[$k2]['post_title']=trim($v2['post_title']);
                $aboutNewsList[$k2]['url']='/'.$user_nickname.'/article/details/'.substr(md5($v2['create_time']),-8);
                $aboutNewsList[$k2]['published_time']=date('Y-m-d H:i:s',$v2['published_time']);
            }

            $userInfo=$this->user->where(['id'=>$protalPostList[$k]['user_id']])->find();
            $user_nickname=$Pinyin::getPinyin($userInfo['user_nickname']);
            $protalPostData['user_name']=$userInfo['user_nickname'];
            if(!@file_exists('../../'.$this->server_root.'/'.$user_nickname.'/article/details/'.substr(md5($v['create_time']),-8))){
                mkdir('../../'.$this->server_root.'/'.$user_nickname.'/article/details/'.substr(md5($v['create_time']),-8), 0777,true);
            }
            $this->assign('aboutNewsList',$aboutNewsList);
            $this->assign('protalPostData',$protalPostData);
            $this->buildHtml('index', '../../'.$this->server_root.'/'.$user_nickname.'/article/details/'.substr(md5($v['create_time']),-8).'/', 'template:detail', 'utf-8');
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

    //提取图片
    private function _gPicUrl($content)
    {
        $pattern = '/src=\"([\s\S]+?)\"/';//正则
        $result = preg_match_all($pattern, $content, $match);//匹配图片
        $result = empty($result) ? false : $match[1];//返回所有图片的路径
        return $result;
    }
}