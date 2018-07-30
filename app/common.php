<?php
/**
 * 格式化时间
 * @return string
 */
function getStrTime($time){
    if(empty($time))
        $time=time();

    $no = date("H", $time);
    if ($no > 0 && $no <= 6) {
        return "凌晨";
    }
    if ($no > 6 && $no < 12) {
        return "上午";
    }
    if ($no >= 12 && $no <= 18) {
        return "下午";
    }
    if ($no > 18 && $no <= 24) {
        return "晚上";
    }
}


/**
 * 提取图片
 * @param $content
 * @return bool|int
 */
function gPicUrl($content)
{
    $pattern = '/src=\"([\s\S]+?)\"/';//正则
    $result = preg_match_all($pattern, $content, $match);//匹配图片
    $result = empty($result) ? false : $match[1];//返回所有图片的路径
    return $result;
}