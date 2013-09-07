<?php
// error_reporting(0);
/**
 * 易信公共平台整合库
 * @author Ligboy (ligboy@gmail.com)
 * @license 本库的很多思路来自于网上的其他热心人士的贡献，大家任意使用，我本人放弃所有权利，如果您心情好，给我留个署名也行。
 *
 */


class Yixin {
    /* 配置参数  */
    /**
     *
     * @var array
     * @example array('token'=>'易信接口密钥','account'=>'易信公共平台账号','password'=>'易信公共平台密码','webtoken'=>"易信公共平台网页url的token");
     */
    private $yixinOptions=array('token'=>'rqerwer', 'appid'=>'', 'appsecret'=>'');	//
    public $debug =  false;  //调试开关

    /* 静态常量 */
    const MSGTYPE_TEXT = 'text';
    const MSGTYPE_IMAGE = 'image';
    const MSGTYPE_LOCATION = 'location';
    const MSGTYPE_LINK = 'link';
    const MSGTYPE_EVENT = 'event';
    const MSGTYPE_MUSIC = 'music';
    const MSGTYPE_NEWS = 'news';
    const MSGTYPE_VOICE = 'voice';
    const MSGTYPE_VIDEO = 'video';
    const MSGTYPE_GOODS = 'goods';
    const MSGTYPE_CARD = 'card';

    /* 私有参数 */
    private $_msg;
    private $_funcflag = false;
    public $_receive;
    private $_logcallback = null;
    private $_api_url = 'https://api.yixin.im/cgi-bin/';
    /**
     * 初始化工作
     * @param array $option  array('token'=>'易信接口密钥');
     */
    function __construct($option=array())
    {
        if (!empty($option))
        {
            $this->yixinOptions = array_merge($this->yixinOptions, $option);
        }
    }


    /**
     * 验证请求签名操作
     * @return boolean
     */
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = $this->yixinOptions['token'];
        $tmpArray = array($token, $timestamp, $nonce);
        sort($tmpArray);
        if(sha1(implode($tmpArray)) == $signature)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * 验证当前请求是否有效(可选)
     * @param bool $return 是否返回
     * @return bool|string
     */
    public function valid($return=false)
    {
        $echoStr = isset($_GET["echostr"])?$_GET["echostr"]: '';
        if ($return)
        {
            if ($echoStr)
            {
                if ($this->checkSignature())
                {
                    return $echoStr;
                }
                else
                {
                    return false;
                }
            }
            else
            {
                return $this->checkSignature();
            }
        }
        else
        {
            if ($echoStr)
            {
                if ($this->checkSignature())
                {
                    die($echoStr);
                }
                else
                {
                    die('No Access');
                }
            }
            else
            {
                if ($this->checkSignature())
                {
                    return true;
                }
                else
                {
                    die('No Access');
                }
            }
        }
    }


    /**
     * 设置发送消息
     * @param array|string $msg 消息数组
     * @param bool $append 是否在原消息数组追加
     * @return array
     */
    public function Message($msg = array(),$append = false){
        if(is_array($msg))
        {
            if ($append){
                $this->_msg = array_merge($this->_msg,$msg);
            }
            else{
                $this->_msg = $msg;
            }
            return $this->_msg;
        }
        else
        {
            return $this->_msg;
        }
    }

    /**
     * 设置星标
     * @param $flag
     * @return $this
     */
    public function setFuncFlag($flag) {
        $this->_funcflag = $flag;
        return $this;
    }

    /**
     * 调试信息日志日记录
     * @param $log
     * @return mixed|null
     */
    private function log($log){
        if ($this->debug && function_exists($this->_logcallback)) {
            if (is_array($log)) $log = print_r($log,true);
            return call_user_func($this->_logcallback,$log);
        }
        return null;
    }

    /**
     * @name 获取易信服务器发来的信息
     * @return mixed
     */
    public function getRev()
    {
        $postStr = file_get_contents("php://input");
        $this->log($postStr);
        if (!empty($postStr))
        {
            $this->_receive = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
        return $this;
    }

    /**
     * 获取消息发送者
     * @return string or boolean
     */
    public function getRevFrom()
    {
        if ($this->_receive)
        {
            return $this->_receive['FromUserName'];
        }
        else
        {
            return false;
        }
    }

    /**
     * 获取消息接受者
     * @return string or boolean
     */
    public function getRevTo()
    {
        if ($this->_receive)
        {
            return $this->_receive['ToUserName'];
        }
        else
        {
            return false;
        }
    }

    /**
     * 获取接收消息的类型
     */
    public function getRevType()
    {
        if (isset($this->_receive['MsgType']))
        {
            return $this->_receive['MsgType'];
        }
        else
        {
            return false;
        }
    }

    /**
     * 获取消息ID
     */
    public function getRevID() {
        if (isset($this->_receive['MsgId']))
            return $this->_receive['MsgId'];
        else
            return false;
    }

    /**
     * 获取消息发送时间
     */
    public function getRevCtime() {
        if (isset($this->_receive['CreateTime']))
        {
            return $this->_receive['CreateTime'];
        }
        else{
            return false;
        }
    }

    /**
     * 获取接收消息内容正文
     */
    public function getRevContent(){
        if (isset($this->_receive['Content']))
        {
            return $this->_receive['Content'];
        }
        else{
            return false;
        }
    }

    /**
     * 获取接收消息图片
     */
    public function getRevPic(){
        if (isset($this->_receive['PicUrl'])){
            return $this->_receive['PicUrl'];
        }
        else{
            return false;
        }
    }

    /**
     * 获取接收消息链接
     */
    public function getRevLink(){
        if (isset($this->_receive['Url']))
        {
            return array(
                'url'=>$this->_receive['Url'],
                'title'=>$this->_receive['Title'],
                'description'=>$this->_receive['Description']
            );
        }
        else{
            return false;
        }
    }

    /**
     * 获取接收地理位置
     * @return array('x'=>'','y'=>'','scale'=>'','label'=>'')
     */
    public function getRevGeo(){
        if (isset($this->_receive['Location_X'])){
            return array(
                'x'=>$this->_receive['Location_X'],
                'y'=>$this->_receive['Location_Y'],
                'scale'=>$this->_receive['Scale'],
                'label'=>$this->_receive['Label']
            );
        }
        else{
            return false;
        }
    }

    /**
     * 获取接收事件推送
     * @return array 成功返回事件数组，失败返回false
     */
    public function getRevEvent(){
        if (isset($this->_receive['Event']))
        {
            return array(
                'event'=>$this->_receive['Event'],
                'key'=>$this->_receive['EventKey'],
            );
        }
        else{
            return false;
        }
    }

    /**
     * 获取接收语言推送
     * @return array|bool
     */
    public function getRevVoice()
    {
        if (isset($this->_receive['MediaId']))
        {
            return array(
                'mediaid'=>$this->_receive['MediaId'],
                'format'=>$this->_receive['Format'],
            );
        }
        else
        {
            return false;
        }
    }

    /**
     * XML特殊字符过滤
     * @param $str
     * @return string
     */
    private static function xmlSafeStr($str)
    {
        return '<![CDATA['.preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/",'',$str).']]>';
    }

    /**
     * 数据XML编码
     * @param mixed $data 数据
     * @return string
     */
    private static function data_to_xml($data)
    {
        $xml = '';
        foreach ($data as $key => $val)
        {
            is_numeric($key) && $key = "item id=\"$key\"";
            $xml    .=  "<$key>";
            $xml    .=  ( is_array($val) || is_object($val)) ? self::data_to_xml($val)  : self::xmlSafeStr($val);
            list($key, ) = explode(' ', $key);
            $xml    .=  "</$key>";
        }
        return $xml;
    }

    /**
     * XML编码
     * @param mixed $data 数据
     * @param string $root 根节点名
     * @param string $item 数字索引的子节点名
     * @param string $attr 根节点属性
     * @param string $id   数字索引子节点key转换的属性名
     * @return string
     */
    private function xml_encode($data, $root='xml', $item='item', $attr='', $id='id')
    {
        if(is_array($attr))
        {
            $_attr = array();
            foreach ($attr as $key => $value)
            {
                $_attr[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $_attr);
        }
        $attr = trim($attr);
        $attr = empty($attr) ? '' : " {$attr}";
        $xml = null;
        $xml .= "<{$root}{$attr}>";
        $xml   .= self::data_to_xml($data, $item, $id);
        $xml   .= "</{$root}>";
        return $xml;
    }

    /**
     * 设置回复消息
     * Examle: $obj->text('hello')->reply();
     * @param string $text
     * @return $this
     */
    public function text($text='')
    {
        $FuncFlag = $this->_funcflag ? 1 : 0;
        $msg = array(
            'ToUserName' => $this->getRevFrom(),
            'FromUserName'=>$this->getRevTo(),
            'MsgType'=>self::MSGTYPE_TEXT,
            'Content'=>$text,
            'CreateTime'=>time(),
            'FuncFlag'=>$FuncFlag
        );
        $this->Message($msg);
        return $this;
    }

    /**
     * 设置回复音乐
     * @param string $title
     * @param string $desc
     * @param string $musicurl
     * @param string $hgmusicurl
     * @return $this
     */
    public function music($title,$desc,$musicurl,$hgmusicurl='') {
        $FuncFlag = $this->_funcflag ? 1 : 0;
        $msg = array(
            'ToUserName' => $this->getRevFrom(),
            'FromUserName'=>$this->getRevTo(),
            'CreateTime'=>time(),
            'MsgType'=>self::MSGTYPE_MUSIC,
            'Music'=>array(
                'Title'=>$title,
                'Description'=>$desc,
                'MusicUrl'=>$musicurl,
                'HQMusicUrl'=>$hgmusicurl
            ),
            'FuncFlag'=>$FuncFlag
        );
        $this->Message($msg);
        return $this;
    }

    /**
     * 设置回复图文
     * @param array $newsData
     * @return $this
     * @example 数组结构:
     *  array(
     *  	[0]=>array(
     *  		'Title'=>'msg title',
     *  		'Description'=>'summary text',
     *  		'PicUrl'=>'http://www.domain.com/1.jpg',
     *  		'Url'=>'http://www.domain.com/1.html'
     *  	),
     *  	[1]=>....
     *  )
     */
    public function news($newsData=array())
    {
        $FuncFlag = $this->_funcflag ? 1 : 0;
        $count = count($newsData);

        $msg = array(
            'ToUserName' => $this->getRevFrom(),
            'FromUserName'=>$this->getRevTo(),
            'MsgType'=>self::MSGTYPE_NEWS,
            'CreateTime'=>time(),
            'ArticleCount'=>$count,
            'Articles'=>$newsData,
            'FuncFlag'=>$FuncFlag
        );
        $this->Message($msg);
        return $this;
    }

    /**
     *
     * 向易信服务器回复消息
     * Example: $this->text('msg tips')->reply();
     * @param array|string $msg 要发送的信息, 默认取$this->_msg
     * @param bool $return 是否返回信息而输出  默认：false
     * @return string
     */
    public function reply($msg=array(),$return = false)
    {
        if (empty($msg))
        {
            $msg = $this->_msg;
        }
        $xmldata=  $this->xml_encode($msg);
        $this->log($xmldata);
        if ($return)
        {
            return $xmldata;
        }
        else
        {
            echo $xmldata;
        }
    }

    private static  function getTextArea($text,$str_start,$str_end){
        if(empty($text)||empty($str_start))
        {
            return false;
        }
        $start_pos=@strpos($text,$str_start);
        if($start_pos===false){
            return false;
        }
        $end_pos=strpos($text,$str_end, $start_pos);
        if($end_pos>$start_pos && $end_pos!==false)
        {
            $begin_pos=$start_pos+strlen($str_start);
            return substr($text, $begin_pos,$end_pos-$begin_pos);
        }
        else
        {
            return false;
        }
    }


}


/**
 * Rolling Curl Request Class
 * @author Ligboy (ligboy@gamil.com)
 * @copyright
 * @example
 *
 *
 */
class CurlHttp {


    /* 单线程请求设置项 */

    /* 并发请求设置项 */
    private $limitCount = 10; //并发请求数量
    public $returninfoswitch = false;  //是否返回请求信息，开启后单项请求返回结果为:array('info'=>请求信息, 'result'=>返回内容, 'error'=>错误信息)

    //私有属性
    private $singlequeue = null;
    private $rollqueue = null;
    private $_requstItems = null;
    private $_callback = null;
    private $_result;
    private $_referer = null;
    private $_cookies = array();
    private $_resheader;
    private $_reqheader = array();
    private $_resurl;
    private $_redirect_url;
    private $referer;

    private $_singleoptions = array(
        CURLOPT_RETURNTRANSFER => true,         // return web page
        CURLOPT_HEADER         => true,        // don't return headers
// 			CURLOPT_FOLLOWLOCATION => true,         // follow redirects
        CURLOPT_NOSIGNAL      =>true,
        CURLOPT_ENCODING       => "",           // handle all encodings
        CURLOPT_USERAGENT      => "",           // who am i
        CURLOPT_AUTOREFERER    => true,         // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,          // timeout on connect
        CURLOPT_TIMEOUT        => 120,          // timeout on response
        CURLOPT_MAXREDIRS      => 10,           // stop after 10 redirects
        CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl
        CURLOPT_SSL_VERIFYPEER => false,        //
    );
    private $_rolloptions = array(
        CURLOPT_RETURNTRANSFER => true,         // return web page
        CURLOPT_HEADER         => false,        // don't return headers
// 			CURLOPT_FOLLOWLOCATION => true,         // follow redirects
        CURLOPT_NOSIGNAL      =>true,
        CURLOPT_ENCODING       => "",           // handle all encodings
        CURLOPT_USERAGENT      => "",           // who am i
        CURLOPT_AUTOREFERER    => true,         // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,          // timeout on connect
        CURLOPT_TIMEOUT        => 120,          // timeout on response
        CURLOPT_MAXREDIRS      => 10,           // stop after 10 redirects
        CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl
        CURLOPT_SSL_VERIFYPEER => false,        //
    );


    function singleInit($options = array()) {
        if (!$this->singlequeue) {
            $this->singlequeue = curl_init();
        }
        if ($options) {
            $this->_singleoptions = array_merge($this->_singleoptions, $options);
        }
    }
    function rollInit($options = array()) {
        if(!$this->rollqueue){
            $this->rollqueue = curl_multi_init();
        }
        if ($options) {
            $this->_rolloptions = array_merge($this->_rolloptions, $options);
        }
    }

    /**
     * @name 返回Header数组
     * @param resource $ch
     * @param $result
     * @return string
     */
    private function getResRawHeader($ch, $result) {
        $ch_info = curl_getinfo($ch);
        $header_size = $ch_info["header_size"];
        $rawheader = substr($result, 0, $ch_info['header_size']);
        return $rawheader;
    }

    /**
     * @name 返回Header数组
     * @param resource $ch
     * @param $result
     * @return string
     */
    private function getResHeader($ch, $result) {
        $header = array();
        $rawheader = $this->getResRawHeader($ch, $result);
        if(preg_match_all('/([^:\s]+): (.*)/i', $rawheader, $header_match)){
            for($i=0;$i<count($header_match[0]);$i++){
                $header[$header_match[1][$i]] = $header_match[2][$i];
            }
        }
        return $header;
    }

    /**
     * @name 返回网页主体内容
     * @param resource $ch
     * @param $result
     * @return string 网页主体内容
     */
    private function getResBody($ch, $result) {
        $ch_info = curl_getinfo($ch);
        $body = substr($result, -$ch_info['download_content_length']);
        return $body;
    }

    /**
     * @name 返回网页主体内容
     * @param resource $ch
     * @param $result
     * @return array 网页主体内容
     */
    private function getResCookies($ch, $result) {
        $rawheader = $this->getResRawHeader($ch, $result);
        $cookies = array();
        if(preg_match_all('/Set-Cookie:(?:\s*)([^=]*?)=([^\;]*?);/i', $rawheader, $cookie_match)){
            for($i=0;$i<count($cookie_match[0]);$i++){
                $cookies[$cookie_match[1][$i]] = $cookie_match[2][$i];
            }
        }
        return $cookies;
    }

    private function setReqCookies($ch, $reqcookies = array()) {
        $reqCookiesString = "";
        if(!empty($reqcookies)){
            if(is_array($reqcookies)){
                foreach ($reqcookies as $key => $val){
                    $reqCookiesString .=  $key."=".$val."; ";
                }
                curl_setopt($ch, CURLOPT_COOKIE, $reqCookiesString);
            }
        }elseif(!empty($this->_cookies)) {
            foreach ($this->_cookies as $key => $val){
                $reqCookiesString .=  $key."=".$val."; ";
            }
            curl_setopt($ch, CURLOPT_COOKIE, $reqCookiesString);
        }
    }
    private function setResCookies($ch) {
        if(!empty($reqcookies)&&is_array($reqcookies)){
            $this->_cookies = array_merge($this->_cookies, $reqcookies);
        }
    }

    /**
     * @param unknown $url
     * @param mixed $postfields
     * @param string $referer
     * @param array $reqcookies
     * @param array $reqheader
     * @return unknown
     */
    function post($url, $postfields=null, $referer=null, $reqcookies=null, $reqheader=array())
    {
        $this->singlequeue = curl_init($url);
        $options = array(
            CURLOPT_RETURNTRANSFER => true,         // return web page
            CURLOPT_HEADER         => true,        // don't return headers
// 				CURLOPT_FOLLOWLOCATION => true,         // follow redirects
            CURLOPT_ENCODING       => "",           // handle all encodings
            CURLOPT_USERAGENT      => "",     // who am i
            CURLOPT_AUTOREFERER    => true,         // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,          // timeout on connect
            CURLOPT_TIMEOUT        => 120,          // timeout on response
            CURLOPT_MAXREDIRS      => 10,           // stop after 10 redirects
            CURLOPT_POST            => true,            // i am sending post data
            CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl
            CURLOPT_SSL_VERIFYPEER => false,        //
        );
        curl_setopt_array($this->singlequeue, $options);
        curl_setopt($this->singlequeue, CURLOPT_POSTFIELDS, $postfields);   // this are my post vars
        if($referer){
            curl_setopt($this->singlequeue, CURLOPT_REFERER, $referer);
        }
        elseif ($this->referer){
            curl_setopt($this->singlequeue, CURLOPT_REFERER, $this->referer);
        }

        $this->setReqheader($this->singlequeue, $reqheader);
        $this->setReqCookies($this->singlequeue, $reqcookies);

        $result = curl_exec($this->singlequeue);
        $resCookies = $this->getResCookies($this->singlequeue, $result);;
        if (is_array($resCookies)&&!empty($resCookies)) {
            $this->_cookies = array_merge($this->_cookies ,$resCookies);
        }
        $resHeader = $this->getResHeader($this->singlequeue, $result);
        if (is_array($resHeader)&&!empty($resHeader)) {
            $this->_resheader = $resHeader;
        }
        $this->_result = $this->getResBody($this->singlequeue, $result);
        curl_close($this->singlequeue);
        $this->singlequeue = null;
        return $this->_result;

    }

    /**
     * @param unknown $url
     * @param unknown $referer
     * @param null $reqcookies
     * @param array $reqheader
     * @return unknown
     */
    function get($url, $referer=null, $reqcookies=null, $reqheader=array())
    {
        $this->singlequeue = curl_init($url);
        $options = array(
            CURLOPT_RETURNTRANSFER => true,         // return web page
            CURLOPT_HEADER         => true,        // don't return headers
// 				CURLOPT_FOLLOWLOCATION => true,         // follow redirects
            CURLOPT_ENCODING       => "",           // handle all encodings
            CURLOPT_USERAGENT      => "",     // who am i
            CURLOPT_AUTOREFERER    => true,         // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,          // timeout on connect
            CURLOPT_TIMEOUT        => 120,          // timeout on response
            CURLOPT_MAXREDIRS      => 10,           // stop after 10 redirects
            CURLOPT_POST            => false,            // i am sending post data
            CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl
            CURLOPT_SSL_VERIFYPEER => false,        //
            CURLOPT_REFERER        =>$referer,
        );
        curl_setopt_array($this->singlequeue, $options);
        if($referer){
            curl_setopt($this->singlequeue, CURLOPT_REFERER, $referer);
        }
        elseif ($this->referer){
            curl_setopt($this->singlequeue, CURLOPT_REFERER, $this->referer);
        }
        $this->setReqheader($this->singlequeue, $reqheader);
        $this->setReqCookies($this->singlequeue, $reqcookies);

        $result = curl_exec($this->singlequeue);
        $resCookies = $this->getResCookies($this->singlequeue, $result);
        if (is_array($resCookies)&&!empty($resCookies)) {
            $this->_cookies = array_merge($this->_cookies ,$resCookies);
        }
        $resHeader = $this->getResHeader($this->singlequeue, $result);
        if (is_array($resHeader)) {
            $this->_resheader = $resHeader;
        }
        $this->_result = $this->getResBody($this->singlequeue, $result);
        curl_close($this->singlequeue);
        $this->singlequeue = null;
        return $this->_result;
    }
    /**
     * 并发行的curl方法
     * @param unknown $requestArray
     * @param string $callback
     * @return multitype:multitype:
     */
    function rollRequest($requestArray, $callback="")
    {
        $this->_requstItems = $requestArray;
        $requestArrayKeys = array_keys($requestArray);
        $this->rollqueue = curl_multi_init();
        $map = array();
        for ($i=0;$i<$this->limitCount && !empty($requestArrayKeys);$i++)
        {
            $keyvalue = array_shift($requestArrayKeys);
            $this->addToRollQueue( $requestArray, $keyvalue, $map );

        }

        $responses = array();
        do {
            while (($code = curl_multi_exec($this->rollqueue, $active)) == CURLM_CALL_MULTI_PERFORM) ;

            if ($code != CURLM_OK) { break; }

            // 找到刚刚完成的任务句柄
            while ($done = curl_multi_info_read($this->rollqueue)) {
                // 处理当前句柄的信息、错误、和返回内容
                $info = curl_getinfo($done['handle']);
                $error = curl_error($done['handle']);
                if ($this->_callback)
                {
                    //调用callback函数处理当前句柄的返回内容，callback函数参数有：（返回内容, 队列id）
                    $result = call_user_func($this->_callback, curl_multi_getcontent($done['handle']), $map[(string) $done['handle']]);
                }
                else
                {
                    //如果callback为空，直接返回内容
                    $result = curl_multi_getcontent($done['handle']);
                }
                if ($this->returninfoswitch) {
                    $responses[$map[(string) $done['handle']]] = compact('info', 'error', 'result');
                }
                else
                {
                    $responses[$map[(string) $done['handle']]] = $result;
                }

                // 从队列里移除上面完成处理的句柄
                curl_multi_remove_handle($this->rollqueue, $done['handle']);
                curl_close($done['handle']);
                if (!empty($requestArrayKeys))
                {
                    $addkey = array_shift($requestArrayKeys);
                    $this->addToRollQueue ( $requestArray, $addkey, $map );
                }
            }

            // Block for data in / output; error handling is done by curl_multi_exec
            if ($active > 0) {
                curl_multi_select($this->rollqueue, 0.5);
            }

        } while ($active);

        curl_multi_close($this->rollqueue);
        $this->rollqueue = null;
        return $responses;
    }
    /**
     * @param requestArray
     * @param map
     * @param keyvalue
     */
    private function addToRollQueue($requestArray, $keyvalue, &$map) {
        $ch = curl_init();
        curl_setopt_array($ch, $this->_rolloptions);
        //检查提交方式，并设置对应的设置，为空的话默认采用get方式
        if ("post" === $requestArray[$keyvalue]['method'])
        {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestArray[$keyvalue]['postfields']);
        }
        else
        {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }


        if($requestArray[$keyvalue]['referer']){
            curl_setopt($ch, CURLOPT_REFERER, $requestArray[$keyvalue]['referer']);
        }
        elseif ($this->referer){
            curl_setopt($ch, CURLOPT_REFERER, $this->referer);
        }
        $this->setReqheader($ch, $requestArray[$keyvalue]['header']);
        //cookies设置
        $this->setReqCookies($ch, $requestArray[$keyvalue]['cookies']);

        curl_setopt($ch, CURLOPT_URL, $requestArray[$keyvalue]['url']);
        curl_setopt($ch, CURLOPT_REFERER, $requestArray[$keyvalue]['referer']);
        curl_multi_add_handle($this->rollqueue, $ch);
        $map[(string) $ch] = $keyvalue;
    }

    /**
     * 返回当前并行数
     * @return the $limitCount
     */
    public function getRollLimitCount() {
        return $this->limitCount;
    }

    /**
     * 设置并发性请求数量
     * @param number $limitCount
     * @return $this
     */
    public function setRollLimitCount($limitCount) {
        $this->limitCount = $limitCount;
        return $this;
    }

    /**
     * 设置回调函数
     * @param field_type $_callback
     * @return $this
     */
    public function setCallback($_callback) {
        $this->_callback = $_callback;
        return $this;
    }

    public function getResult() {
        return $this->_result;
    }

    public function getRawHeader() {
        return $this->_resheader;
    }

    public function getCookies() {
        return $this->_cookies;
    }

    public function setCookies($_cookies) {
        $this->_cookies = $_cookies;
        return $this;
    }

    /**
     * @param $header
     * @return $this
     */
    public function setHeader($header) {
        $this->_reqheader = array_merge($this->_reqheader, $header);
        return $this;
    }

    /**
     * @param resource $ch
     * @param array $reqheader
     * @return $this
     */
    private function setReqheader($ch, $reqheader) {
        $reqheader = array_merge($this->_reqheader, $reqheader);
        if (is_array($reqheader)) {
            $rawReqHeader = array();
            foreach ($reqheader as $key => $value){
                $rawReqHeader[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $rawReqHeader);
            $this->_reqheader = array();
        }
        return $this;
    }
}
