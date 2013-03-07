<?php

/**
 * 雅虎相关关键词采集类
 * 
 * @author Jason W.
 * @version 1.0.1
 * @category control + model
 * @date 2012.05.08
 */

class control {
	
	private $_get;
	
	private $_post;
	
	/**
	 * 页面模版名称 *
	 */
	private $_template = 'form';
	
	/**
	 * 伪造浏览器agent信息 *
	 */
	private $_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:12.0) Gecko/20100101 Firefox/12.0';
	
	/**
	 * 伪造请求来源页面 *
	 */
	private $_referer = 'http://www.yahoo.co.jp/';
	
	/**
	 * 执行第 $_setup 个用户输入的关键词 *
	 */
	private $_setup = 1;
	
	/**
	 * 执行第 $_setup 个用户关键词的第 $_deep 层深度采集 *
	 */
	private $_deep = 1;
	
	private $_time = 3;
	
	public function __construct($args = array()) {
		
		if (! empty ( $_GET )) 
			$this->_get = $_GET;

		if (! empty ( $_POST )) 
			$this->_post = $_POST;
		
		if (! empty ( $args ['agent'] )) 
			$this->_agent = $args ['agent'];
		
		if (! empty ( $args ['referer'] )) 
			$this->_referer = $args ['referer'];
		
		if (! empty ( $args ['template'] )) 
			$this->_template = $args ['template'];
	}
	
	public function _init() {
		$this->display ( $this->_template );
	}
	
	/**
	 * 组织用户输入关键词，并写入缓存文件
	 */
	public function organize_user_key() {
		
		if (! empty ( $this->_post ['s'] )) {
			$this->do_cache('delete', 'log');
			$this->do_cache('delete', 'config');
			$this->do_cache('delete', 'user_keyword');
			$this->do_cache('delete', 'user_keyword2');
			$this->do_cache('delete', 'user_keyword3');
			$this->do_cache('delete', 'user_keyword4');
			$this->do_cache('delete', 'data');
			$arr_key = explode ( "\n", $this->_post ['s'] );
			$result = $this->do_cache ( 'write', 'user_keyword', json_encode ( $arr_key ) );
			
			if ($result) {
				$this->return_msg ( 'true' );
			} else {
				$this->return_msg ( $result );
			}
		}
	}
	
	/**
	 * 执行关键词采集
	 */
	public function do_keyword() {
		
		if ($this->do_cache ( 'is_exists', 'config' )) {
			$config = json_decode ( $this->do_cache ( 'read', 'config' ) );
			$filename = $config->filename;
			$this->_deep = $config->deep;
		} else $filename = 'user_keyword';
		
		$this->_time = rand(5, 15);
		
		// $this->return_msg ( '正在对第' . $this->_setup . '个关键词进行第' . $this->_deep
		// . '轮深度采集' );
		
		$keywords = json_decode ( $this->do_cache ( 'read', $filename ), true );
		if (is_array ( $keywords )) {
			
			if (empty ( $keywords [0] )) {
				if ($filename == 'user_keyword') {
					$this->do_cache ( 'delete', 'config' );
					$this->return_msg ( '<h3>所有关键词执行完毕！<br/><a href="?a=show_result_list" target="_blank">查看采集结果</a><br/><a href="?a=_init">重试</a></h3>', false );
				} else {
					$this->do_cache ( 'delete', $filename );
					$this->_deep --;
					$this->_deep = $this->_deep == 0 ? 1 : $this->_deep;
					
					if ($this->_deep == 1) {
						$filename = "user_keyword";
					} else {
						$filename = 'user_keyword' . $this->_deep;
					}
					
					$this->put_config ( array (
							'deep' => $this->_deep,
							'filename' => $filename 
					) );
					$this->return_msg ( '当前层级执行完毕，将对下一个关键词执行递归采集！' );
				}
			} else {
				$k = $this->get_result ( $keywords [0] );
				if (is_array ( $k ) && count ( $k )) {
					
					$this->save_data($k);
					
					$r = $this->do_cache ( 'write', 'user_keyword' . ($this->_deep + 1), json_encode ( $k ) );
					
					if ($r === true) {
						$this->_deep ++;
						$args = array (
								'deep' => $this->_deep,
								'filename' => 'user_keyword' . $this->_deep 
						);
						
						$this->put_config ( $args );
						$this->del_keyword ( $keywords, $filename );
						
						$this->return_msg ( '关键词' . $keywords [0] . '采集成功，将对查询结果进行递归采集' );
					}
				} else {
					$this->del_keyword ( $keywords, $filename );
					$this->return_msg ( '抓取失败，没有相关关键词！关键词：' . $keywords [0] . '，抓取结果：' . $k );
				}
			}
		} else {
			$this->return_msg ( '获取用户输入的关键词列表失败！文件名：' . $filename . '，文件内容：' . json_encode ( $keywords ) );
		}
	}
	
	public function show_result_list() {
		$file = !empty($_GET['f']) ? $_GET['f'] : 'data';
		$string = $this->do_cache('read', $file);
		header("Content-type:text/html;charset=utf-8");
		if ($string) {
			$string = json_decode($string, true);
			echo join("<br/>", $string);
		} else {
			echo "没有采集到任何结果！";
		}
	}
	
	/**
	 * 请求雅虎并抓取结果
	 *
	 * @param string $keyword
	 *        	单个关键词
	 */
	private function get_result($keyword) {
		include ("snoopy.class.php");
		$snoopy = new Snoopy ();
		$keyword = str_replace(' ', '+', $keyword);
		$url = "http://search.yahoo.co.jp/search?p={$keyword}&aq=-1&oq=&ei=UTF-8&fr=top_ga1_sa&x=wrt";
		$snoopy->agent = $this->_agent;
		$snoopy->referer = $this->_referer;
		$snoopy->fetch ( $url );
		
		$result = trim ( $snoopy->results );
		$string = $this->get_mid_str ( '<div id="Si2">', '<div id="Sz">', $result );
		
		$this->do_cache ( 'write', 'log', array (
				'url' => $url,
				'keyword' => $keyword,
				'result' => $result,
				'string' => $string 
		) );
		
		if (false !== $string) {
			preg_match_all ( '/<a(.*?)href="(.*?)"(.*?)>(.*?)<\/a>/i', $string, $matches );
			$k = array ();
			foreach ( $matches [4] as $v ) {
				$k [] = strip_tags ( $v );
			}
			return $k;
		} else {
			return false;
		}
	}
	
	/**
	 * 保存采集结果
	 *
	 * @param array $key_list        	
	 */
	private function save_data($key_list) {
		
		if (! empty ( $key_list )) {
			$data = $this->do_cache ( 'read', 'data' );
			
			if (!empty($data)) {
				$data = json_decode ( $data, true );
			} else {
				$data = array ();
			}

			$data = array_merge ( $data, $key_list );
			$this->do_cache ( 'write', 'data', $data );
		}
	}
	
	/**
	 * 剔除当前缓存文件的第一个关键词（采集过的）
	 *
	 * @param array $keywords
	 * @param string $filename
	 */
	private function del_keyword($keywords, $filename) {
		$keywords = array_splice ( $keywords, 1 );
		$this->do_cache ( 'write', $filename, $keywords );
	}
	
	/**
	 * 存储配置信息，将参数中的值追加或覆盖到现有配置数据中，不存在则自动创建
	 *
	 * @param array $args
	 *        	需要追加或更新的参数
	 */
	private function put_config($args) {
		if (is_array ( $args ) && count ( $args )) {
			
			$config = json_decode ( $this->do_cache ( 'read', 'config' ), true );
			
			if (is_array ( $config )) {
				$args = array_merge ( $config, $args );
			}
			
			$this->do_cache ( 'write', 'config', json_encode ( $args ) );
		}
	}
	
	/**
	 * 模版渲染
	 *
	 * @param string $tpl        	
	 */
	private function display($tpl) {
		
		include_once $tpl . '.tpl.php';
	}
	
	/**
	 * 字符串截取
	 *
	 * @param $L string
	 *        	开始唯一标记
	 * @param $R string
	 *        	结束唯一标记
	 * @param $str string
	 *        	源字符串
	 */
	private function get_mid_str($L, $R, $str) {
		$int_l = strpos ( $str, $L );
		$int_r = strpos ( $str, $R );
		if ($int_l > - 1 && $int_l > - 1) {
			$str_put = substr ( $str, $int_l + strlen ( $L ), ($int_r - $int_l - strlen ( $L )) );
			return $str_put;
		} else {
			return false;
		}
	}
	
	/**
	 * 缓存操作
	 *
	 * @param $type string
	 *        	操作类型，write,read,is_exists
	 * @param $name string
	 *        	文件名，不含扩展名
	 * @param $value string
	 *        	缓存内容
	 */
	private function do_cache($type, $name, $value = '') {
		
		$cache_file = $name . '.cache';
		
		switch ($type) {
			case 'write' :
				if (! $handle = fopen ( $cache_file, 'wb+' )) {
					exit ( "不能打开文件 {$cache_file}" );
				}
				
				if (is_array ( $value ))
					$value = json_encode ( $value );
				
				if (fwrite ( $handle, $value ) === FALSE) {
					exit ( "不能写入到文件 {$cache_file}" );
				}
				
				return true;
				break;
			case 'read' :
				if (true === $this->do_cache ( 'is_exists', $name )) {
					$handle = fopen ( $cache_file, "rb" );
					$contents = fread ( $handle, filesize ( $cache_file ) );
				} else {
					return false;
				}
				
				if (! $contents) return false;
				else return $contents;
				break;
			case 'is_exists' :
				if (file_exists ( $cache_file )) return true;
				else return false;
				break;
			case 'delete' :
				if (file_exists ( $cache_file ))
					unlink ( $cache_file );
				break;
		}
	}
	
	/**
	 * 返回结果给用户
	 *
	 * @param string $msg        	
	 * @param bool $goon
	 *        	是否继续循环请求
	 * @param unknown_type $ajax
	 *        	是否ajax返回
	 */
	public function return_msg($msg, $goon = true, $ajax = true) {
		if ($ajax == true) {
			echo json_encode ( array (
					'msg' => $msg,
					'goon' => $goon === true ? 1 : 0, 
					'time'=>' <span style="color:#999;font-size:12px;font-style:italic">'.date('Y-m-d H:i:s').'</span>'
			) );
		} else {
			return $msg;
		}
	}
}

?>