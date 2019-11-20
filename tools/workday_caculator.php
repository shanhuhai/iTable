<?php
/**
 * Author: shanhuhai
 * Date: 15/11/2019 09:28
 * Mail: 441358019@qq.com
 */

$startDay = date('Y-m-d', time());
//$startDay = '2019-1-1';
$endDay = isset($argv[1]) ? $argv[1] : date('Y-m-d', strtotime( (date('Y', time())).'-12-31'));

$startMonth = date('Y-m', strtotime($startDay));
$endMonth = date('Y-m', strtotime($endDay));
//
//echo $startDay;
//echo "\n";
//echo $endDay;
//echo "\n";
//
//echo $startMonth;
//echo "\n";
//echo $endMonth;

// 计算所有的月
$startTime = strtotime($startDay);
$endTime = strtotime($endDay);
$months = array();
$months[] = $startMonth; // 当前月;
while( ($startTime = strtotime('+1 month', $startTime)) <= $endTime){
    $months[] = date('Y-m',$startTime); // 取得递增月;
}

// 计算所有的日
$startTime = strtotime($startDay);
$endTime = strtotime($endDay);
$days = array();
$days[] = $startDay; // 当前月;
while( ($startTime = strtotime('+1 day', $startTime)) <= $endTime){
    $days[] = date('Y-m-d',$startTime); // 取得递增月;
}

//获取所有的节假日和调班信息

$holidays = [
    'work'=>[],
    'holiday'=>[]
];
foreach ($months as $month) {
    list($y, $m) = explode('-', $month);
    $t = getHolidays($y, $m);
    $holidays = array_merge_recursive($holidays,  getHolidays($y, $m));
    usleep(3000);
}

$holidays['work'] = array_values(array_unique($holidays['work']));
$holidays['holiday'] = array_values(array_unique($holidays['holiday']));

$workDays = [];
foreach ($days as $day) {
    //echo $day;exit;
    $week = (int) date('w',strtotime($day));
    if ( $week != 6 && $week != 0 && !in_array($day, $holidays['work'])) {
        $workDays[]  = $day;
    }
}

echo '距离 '.$endDay.' 总计 '.count($days).' 天， '.count($workDays).' 个工作日。'.PHP_EOL;

function getHolidays($year='2019',$month='12'){
    $url = "http://opendata.baidu.com/api.php?query={$year}-{$month}&resource_id=6018&format=json";
    $str = fetchUrl($url, '../storage/app/'.url2fileName($url).'.json','GBK');
	$arr = json_decode($str,true); //获取到数组格式的数据。

    if (!isset($arr['data'][0]['holiday'])) {
        return [];
    }
	$holiday = $arr['data'][0]['holiday'];
    if(isset($holiday['list'])) {
        return[];
    }
	//筛选出放假的日期 和 补班的日期
    $r_arr = [];
    foreach ($holiday as $k => $v) {
        foreach ($v['list'] as $key => $value) {
            if($value['status'] == 1){ //获取假期
                $r_arr['holiday'][] = $value['date'];
            }elseif($value['status'] == 2){ //获取补班的日期
                $r_arr['work'][] = $value['date'];
            }
        }
    }
	return $r_arr;
}


function curl_get_content($url, $refer = ''){
    $ch = curl_init();
    $options = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_URL => $url,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_REFERER => $refer,
        CURLOPT_USERAGENT => "Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)"
    );
    curl_setopt_array($ch, $options);
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}

function str_charset($in_charset, $out_charset, $str_or_arr){
    $lang = array(&$in_charset, &$out_charset);
    foreach ($lang as &$l){
        switch (strtolower(substr($l, 0, 2))){
            case 'gb': $l = 'gbk';
                break;
            case 'bi': $l = 'big5';
                break;
            case 'ut': $l = 'utf-8';
                break;
        }
    }
    if(is_array($str_or_arr)){
        foreach($str_or_arr as &$v){
            $v = str_charset($in_charset, $out_charset.'//IGNORE', $v);
        }
    } else {
        $str_or_arr = iconv($in_charset, $out_charset.'//IGNORE', $str_or_arr);
    }
    return $str_or_arr;
}

function writeFile($file, $data){
    $dir = dirname($file);
    if(!is_dir($dir)){
        mkdir($dir, 0755, true);
    }
    $result = @file_put_contents($file, $data);
    $result && chmod($file, 0755);
    return $result;
}

function fetchUrl($url, $file, $charset = 'utf-8', $refer = '', $cache = true){
    if (file_exists($file) && $cache) {
        $content = file_get_contents($file);
    } else {
        $content = curl_get_content($url, $refer);
        if ($charset != 'utf-8') {
            $content = str_charset($charset, 'utf-8', $content);
        }
        if($cache){
            writeFile($file, $content);
        }
    }
    return $content;
}

function url2fileName($url){
    $urlinfo =parse_url( $url);
    $urlinfo['path'] = str_replace(array('/','?'), array('_','_'), $urlinfo['path']);
    return implode('_', $urlinfo);
}