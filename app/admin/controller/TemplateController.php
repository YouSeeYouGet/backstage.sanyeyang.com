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
        $this->assign('siteInfo',$siteInfo);

        //导航
        $navMainInfo=$this->nav->where(['is_main'=>1])->find();
        $navMenuList=$this->nav_menu->where(['nav_id'=>$navMainInfo['id'],'status'=>1])->order('list_order asc')->select();
        $navMenuList=$navMenuList->toArray();

        $this->assign('navMenuList',$navMenuList);
        $this->assign('server_name',config('server_name'));

        $this->_create_index();
        $this->_create_news_list();exit;
        $this->_create_news_detail();
        return $this->fetch();
    }

    /**
     * 生成首页
     */
    private function _create_index()
    {
        include_once '../simplewind/vendor/pinyin/pinyin.class.php';
        $Pinyin=new \Pinyin();

        //轮播图
        $categoryID=$this->portal_category->where(['name'=>'首页轮播图','delete_time'=>0,'status'=>1])->value('id');
        $postIDArr=$this->portal_category_post->where(['category_id'=>$categoryID,'status'=>1])->column('post_id');
        $slideList=$this->portal_post->where(['id'=>['in',$postIDArr],'post_status'=>1,'delete_time'=>0])->order('published_time desc')->select();
        $slideList = $slideList->toArray();
        foreach ($slideList as $k => $v) {
            $userInfo=$this->user->where(['id'=>$v['user_id']])->find();
            $user_nickname=$Pinyin::getPinyin($userInfo['user_nickname']);
            $moreArr=json_decode($v['more'],true);
            if(!empty($moreArr['thumbnail'])){
                $thumbnail=config('img_url').'/upload/'.$moreArr['thumbnail'];
            }else{
                $thumbnail=config('default_img');
            }

            $slideList[$k]['thumbnail']=$thumbnail;
            $slideList[$k]['post_title']=trim($v['post_title']);
            $slideList[$k]['url']='/'.$user_nickname.'/article/details/'.date('Ymd',$v['published_time']);
            $slideList[$k]['published_time']=date('Y-m-d H:i:s',$v['published_time']);
        }
        $this->assign('slideList',$slideList);

        //置顶推荐
        $topNewsList=$this->portal_post->where(['post_status'=>1,'is_top'=>1,'delete_time'=>0])->order('published_time desc')->limit(4)->select();
        $topNewsList = $topNewsList->toArray();
        foreach ($topNewsList as $k => $v) {
            $userInfo=$this->user->where(['id'=>$v['user_id']])->find();
            $user_nickname=$Pinyin::getPinyin($userInfo['user_nickname']);
            $moreArr=json_decode($v['more'],true);

            if(!empty($moreArr['thumbnail'])){
                $thumbnail=config('img_url').'/upload/'.$moreArr['thumbnail'];
            }else{
                $thumbnail=config('default_img');
            }

            $topNewsList[$k]['thumbnail']=$thumbnail;
            $topNewsList[$k]['post_title']=trim($v['post_title']);
            $topNewsList[$k]['url']='/'.$user_nickname.'/article/details/'.date('Ymd',$v['published_time']);
            $topNewsList[$k]['published_time']=date('Y-m-d H:i:s',$v['published_time']);
        }
        $this->assign('topNewsList',$topNewsList);

        //最新发布
        $newNewsList=$this->portal_post->where(['post_status'=>1,'delete_time'=>0])->order('published_time desc')->limit(20)->select();
        $newNewsList = $newNewsList->toArray();
        if(!empty($newNewsList)){
            foreach ($newNewsList as $k => $v) {
                $userInfo=$this->user->where(['id'=>$v['user_id']])->find();
                $user_nickname=$Pinyin::getPinyin($userInfo['user_nickname']);
                $moreArr=json_decode($v['more'],true);
                if(!empty($moreArr['thumbnail'])){
                    $thumbnail=config('img_url').'/upload/'.$moreArr['thumbnail'];
                }else{
                    $thumbnail=config('default_img');
                }

                $newNewsList[$k]['thumbnail']=$thumbnail;
                $newNewsList[$k]['post_title']=trim($v['post_title']);
                $newNewsList[$k]['url']='/'.$user_nickname.'/article/details/'.date('Ymd',$v['published_time']);
                $newNewsList[$k]['published_time']=date('Y-m-d H:i:s',$v['published_time']);
            }
            $this->assign('newNewsList',$newNewsList);
        }

        //热门文章
        $hotNewsList=$this->portal_post->where(['post_status'=>1,'delete_time'=>0])->order('post_hits desc, post_like desc,comment_count desc')->limit(20)->select();
        $hotNewsList = $hotNewsList->toArray();
        foreach ($hotNewsList as $k => $v) {
            $userInfo=$this->user->where(['id'=>$v['user_id']])->find();
            $user_nickname=$Pinyin::getPinyin($userInfo['user_nickname']);
            $moreArr=json_decode($v['more'],true);

            if(!empty($moreArr['thumbnail'])){
                $thumbnail=config('img_url').'/upload/'.$moreArr['thumbnail'];
            }else{
                $thumbnail=config('default_img');
            }

            $hotNewsList[$k]['thumbnail']=$thumbnail;
            $hotNewsList[$k]['post_title']=trim($v['post_title']);
            $hotNewsList[$k]['url']='/'.$user_nickname.'/article/details/'.date('Ymd',$v['published_time']);
            $hotNewsList[$k]['published_time']=date('Y-m-d H:i:s',$v['published_time']);
        }
        $this->assign('hotNewsList',$hotNewsList);

        //友情链接
        $linksList = $this->link->where(['status' => 1])->order('list_order asc')->select();
        $linksList = $linksList->toArray();
        $this->assign('linksList',$linksList);

        //生成模板
        $this->buildHtml('index', '../../'.$this->server_root.'/', 'template:index', 'utf-8');
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
                $hotNewsList[$k2]['url']='/'.$user_nickname.'/article/details/'.date('Ymd',$v2['published_time']);
                $hotNewsList[$k2]['published_time']=date('Y-m-d H:i:s',$v2['published_time']);
            }
            $this->assign('hotNewsList',$hotNewsList);

            $newsList=$this->portal_post->where(['id'=>['in',$postIDArr],'post_status'=>1,'delete_time'=>0])->order('published_time desc')->select();
            $newsList=$newsList->toArray();
            $num = count($newsList);

            $page = ceil($num / 3);
            for ($i = 0; $i < $page; $i++) {
                $newsData = [];
                foreach ($newsList as $k1 => $v1) {
                    $t = $i + 1;
                    if (3 * $i <= $k1 && $k1 < 3 * $t) {
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
                        $newsData[$k1]['url']='/'.$user_nickname.'/article/details/'.date('Ymd',$v1['published_time']);
                        $newsData[$k1]['published_time']=date('Y-m-d H:i:s',$v1['published_time']);
                        $newsData[$k1]['post_excerpt']=$v1['post_excerpt'];
                        $newsData[$k1]['post_hits']=$v1['post_hits'];
                    }
                }
                $this->assign('newsData',$newsData);

                $nexti = $i + 1;
                if ($i == 0) {
                    $listHtmlName = 'index';
                    $listUrl = $v['href'].'/index.html';
                } else {
                    $listHtmlName = 'index-'. $nexti;
                    $listUrl =$v['href'].'/'.'index-'. $nexti . ".html";
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
        if (!empty($this->newsPostids)) {
            $top10 = $this->posts_model->where(['softid' => $this->softid, 'id' => ['in', $this->newsPostids], 'post_status' => 1])->order('istop desc,post_date desc')->limit(10)->select();
            $top10 = $top10->toArray();
            foreach ($top10 as $k => $v) {
                $top10[$k]['detailsName'] = substr(md5($v['id']), -10) . '.html';
            }

            $newsPostsList = $this->posts_model->where(['softid' => $this->softid, 'id' => ['in', $this->newsPostids], 'post_status' => 1])->order('istop desc,post_date desc')->select();
            $newsPostsList = $newsPostsList->toArray();
            foreach ($newsPostsList as $k => $v) {
                $img_data = $this->_gPicUrl($v['post_content']);
                if (!empty($img_data)) {
                    $img_replace_array = array();
                    for ($i = 0; $i < count($img_data); $i++) {
                        $img_info = explode('/', $img_data[$i]);
                        $img_name = array_pop($img_info);
                        if (strstr($img_name, '?')) {
                            $img_name = reset(explode('?', $img_name));
                        }
                        $img_data[$i] = str_replace('/data','',$img_data[$i]);
                        if(is_file("." . $img_data[$i])){
                            copy("." . $img_data[$i], $this->new_images . $img_name);
                        }
                        // 放到替换数组
                        array_push($img_replace_array, array(
                            'old' => 'src="' . $img_data[$i] . '"',
                            'new' => 'src="../other_images/' . date('Ymd', time()) . '/' . $img_name . '"'
                        ));
                    }
                    // 替换图片
                    foreach ($img_replace_array as $key_replace => $value_replace) {
                        $newsPostsList[$k]['post_content'] = str_replace($value_replace['old'], $value_replace['new'], $newsPostsList[$k]['post_content']);
                    }
                }
            }

            foreach ($newsPostsList as $k => $v) {
                $this->top10 = $top10;
                $this->newsInfo = $v;
                $detailsName = substr(md5($v['id']), -10);
                $this->assign(array(
                    'detail_logo_style'=>$this->detail_logo_style,
                    'newsInfo'=>$this->newsInfo,
                    'top10'=>$this->top10,
                ));
                $this->buildHtml($detailsName, './home/' . $this->softid . '/news/', 'template:news_detail', 'utf-8');
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
     * 获取文章ids
     * @return array
     */
    private function _getPostids()
    {
        $portalCategoryPostList = $this->portal_category_post->where(['status' => 1])->field('post_id,category_id')->select();
        $hotNewsIds = array();
        foreach ($portalCategoryPostList as $k => $v) {
            $name = $this->portal_category->where(['id' => $v['category_id'], 'status' => 1])->value('name');
            if ($name == '轮播图') {
                $slideIds[] = $v['category_id'];
            }
        }
        $result = [
            0 => $hotNewsIds,
        ];
        return $result[] = $result;
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