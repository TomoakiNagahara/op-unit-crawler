<?php
/**
 * unit-crawler:/crawler.class.php
 *
 * @created   2019-05-30
 * @version   1.0
 * @package   unit-crawler
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */

/** namespace
 *
 * @created   2019-05-30
 */
namespace OP\UNIT;

/** Used class.
 *
 */
use OP\OP_CORE;
use OP\OP_UNIT;
use OP\IF_UNIT;
use OP\Unit;

/** Crawler
 *
 * @created   2019-05-30
 * @version   1.0
 * @package   unit-crawler
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */
class Crawler implements IF_UNIT
{
	/** trait.
	 *
	 */
	use OP_CORE, OP_UNIT;

	/** URL
	 *
	 * @created  2019-06-12
	 * @return  \OP\UNIT\URL
	 */
	static function URL()
	{
		//	...
		static $_url;

		//	...
		return $_url ?? $_url = new \OP\UNIT\URL();
	}

	static private function _RegisterLink($url, $mime, &$html, $config)
	{
		//	...
		if(!$doc_root = $config['document_root'] ?? null ){
			return;
		};

		//	...
		$doc_root = '/'.trim($doc_root, '/').'/';

		//	...
		$parsed = self::URL()->Path($url);

		//	...
		switch( $mime ){
			case 'text/css':
				self::_RegisterLinkStyle($parsed, $html, $doc_root);
			return;

			case 'text/html':
				$matches = null;
				preg_match_all('|<style>(.+)</style>|is', $html, $matches, PREG_SET_ORDER);
				foreach( $matches ?? [] as $match ){
					self::_RegisterLinkStyle($parsed, $match[1], $doc_root);
					$orig = $match[0];
					$path = '<style>'.$match[1].'</style>';
					$html = str_replace($orig, $path, $html);
				};
			break;

			default:
			return;
		};

		//	...
		$lists = [];
		$lists[] = ['tag'=>'|(<a ([^>]+)>(.*?)</a>)|is'          , 'attr'=>'href'];
		$lists[] = ['tag'=>'|(<link ([^>]+)>)|is'                , 'attr'=>'href'];
		$lists[] = ['tag'=>'|(<script ([^>]+)>(.*?)</script>)|is', 'attr'=>'src'];

		//	...
		foreach( $lists as $list ){
			//	...
			$matches = null;
			if(!$total = preg_match_all($list['tag'], $html, $matches, PREG_SET_ORDER) ){
				continue;
			};

			//	...
			for($i=0; $i<$total; $i++){
				//	...
				$attr   = $matches[$i][2];

				//	...
				$match = null;

				//	...
				if(!preg_match('/'.$list['attr'].'=("|\')(.+?)\1/', $attr, $match) ){
					continue;
				};

				//	...
				self::_RegisterLinkTouch($parsed, $match[2]);

				//	...
				if(!$doc_root ){
					continue;
				};

				//	...
				if( $rootpath = self::_DocumentRootPath($parsed, $match[2], $doc_root) ){

					//	...
					$orig = $list['attr'].'='.$match[1].$match[2].$match[1];
					$path = $list['attr'].'='.$match[1].$rootpath.$match[1];

					//	...
					$html = str_replace($orig, $path, $html);
				};
			};
		};
	}

	static private function _RegisterLinkStyle($parsed, &$html, $doc_root)
	{
		//	...
		$matches = null;

		//	...
		preg_match_all('/url\((.+)\)/i', $html, $matches, PREG_SET_ORDER);

		//	...
		foreach( $matches ?? [] as $match ){
			//	...
			self::_RegisterLinkTouch($parsed, $match[1]);

			//	...
			if( $rootpath = self::_DocumentRootPath($parsed, $match[1], $doc_root) ){

				//	...
				$orig = $match[0];
				$path = "url({$rootpath})";

				//	...
				$html = str_replace($orig, $path, $html);
			};
		};
	}

	static private function _RegisterLinkTouch($parsed, $href)
	{
		do{
			//	...
			if( $href === $parsed['path'] ){
				continue;
			};

			//	FQDN
			if( preg_match('|^[a-z+]+://|', $href) ){
				self::Register(array_merge(parse_url($href), ['scheme' => $parsed['scheme']]));
				continue;
			};

			//	Scheme less path. (FQDN)
			if( strpos($href, '//') === 0 ){
				self::Register(array_merge(parse_url($href), ['scheme' => $parsed['scheme']]));
				continue;
			};

			//	Separate to path and query.
			if( $pos   = strpos($href, '?') ){
				$path  = substr($href, 0, $pos);
				$query = substr($href, $pos+1 );
			}else{
				$path  = $href;
				$query = '';
			};

			//	Document root path.
			if( strpos($path, '/') === 0 ){
				self::Register(array_merge($parsed, ['path'=>$path,'query'=>$query]));
				continue;
			};

			//	Current relative path.
			if( strpos($path, './') === 0 ){
				//	Remove "./"
				$path = substr($path, 2);

				//	Check if end of path is slash.
				if( strrpos($parsed['path'], '/') === strlen($parsed['path'])-1 ){
					$parent_dir = rtrim($parsed['path'], '/').'/';
				}else{
					$parent_dir = dirname($parsed['path']).'/';
				};

				//	Join to parent dir and path.
				$path = $parent_dir . $path;
			};

			//	Parent relative path.
			if( strpos($href, '../') === 0 ){
				//	Remove "../"
				$path = substr($path, 3);

				//	...
				if( dirname($parsed['path']) === '/' ){
					\OP\Debug::Set('path',"Path is not has parent directory. ({$parsed['path']}, $path)");
					continue;
				};

				/*
				//	Check if end of path is slash.
				if( strrpos($parsed['path'], '/') === strlen($parsed['path'])-1 ){

				}else{

				};
				*/
			};

			//	Same path.
			if( $path === $parsed['path'] ){
				//	Different queries.
				if( $query !== $parsed['query'] ){
					self::Register(array_merge($parsed, ['query'=>$query]));
				};
				continue;
			};

			//	...
			self::Register(array_merge($parsed, ['path'=>$path,'query'=>$query]));

		}while(false);
	}

	static private function _DocumentRootPath($parsed, $path, $doc_root)
	{
		//	FQDN
		foreach( ['//','http://','https://'] as $fqdn ){
			//	...
			if( strpos($path, $fqdn) === 0 ){
				return;
			};
		};

		//	Current path
		if( strpos($path, './') === 0 ){
			return;
		};

		//	Parent path
		if( strpos($path, '../') === 0 ){
			return;
		};

		//	Document root path
		if( strpos($path, '/') === 0 ){
			return '/' . trim($doc_root, '/') .'/'. ltrim($path, '/');
		};
	}

	/** Automatically
	 *
	 * @param string   $host
	 * @param array    $config
	 * @param callable $callback
	 */
	static function Auto($host, $config, $callback)
	{
		//	...
		$parsed = self::URL()->Parse($host);

		//	...
		$host = self::URL()->Host()->Ai($parsed['host']);

		//	...
		$timestamp = date(_OP_DATE_TIME_, strtotime(' -30 days '));

		//	...
		$cond = [];
		$cond['where'][] = "t_host.ai = $host";
		$cond['where'][] = "t_url.crawled < $timestamp";
		$cond['order']   = 't_url.http_status_code, t_url.timestamp, t_url.score desc';

		//	...
		for( $i=0; $i<3; $i++ ){
			//	...
			if(!$record = self::URL()->Record($cond) ){
				return;
			};

			//	...
			$url = self::URL()->Build($record);

			//	...
			if(!$http = self::Fetch($url) ){
				D("Fetch URL was failed. ($url)");
				continue;
			};

			//	...
			switch( $http['head']['status'] ){
				case 302:
					self::URL()->Register( array_merge($parsed, ['path'=>$http['head']['location']]) );
					self::URL()->DB()->Debug();
					break;
			};

			//	...
			if(!$ai = self::URL()->Ai($url) ){
				D($url);
				return;
			};

			//	...
			$update = [];
			$update['http_status_code'] = $http['head']['status'];
			$update['crawled'] = gmdate(_OP_DATE_TIME_);
			self::URL()->Update($ai, $update);
			self::URL()->DB()->Debug();

			//	...
			self::_RegisterLink($url, $http['head']['mime'], $http['body'], $config);

			//	...
			if( $callback ){
				call_user_func($callback, $url, $http);
			};

			//	...
			return;
		};

		//	...
		return;

		//	...
		if(!self::Register($parsed) ){
			D("URL", $url);
		};

		//	...
		$config['domain'] = $parsed['host'];

		//	...
		foreach( self::DB()->URLs($config) as $url ){
			//	...
			if(!$http = self::Fetch($url) ){
				continue;
			};

			//	...
			self::_RegisterLink($url, $http['head']['mime'], $http['body'], $config);

			//	...
			if( $callback ){
				call_user_func($callback, $url, $http);
			};

			//	...
		//	return;
		};
	}

	static function DB()
	{
		//	...
		static $_DB;

		//	...
		if( empty($_DB) ){
			//	...
			require_once(__DIR__.'/DB.class.php');

			//	...
			$_DB = new CRAWLER\DB();
		};

		//	...
		return $_DB;
	}

	static function Register($parsed)
	{
		//	...
		self::URL()->Register($parsed);

		//	...
	//	return self::DB()->Register($parsed);
	}

	static function Fetch($url)
	{
		/* @var $curl \OP\UNIT\Curl */
		static $curl;

		//	...
		if(!$curl ){
			$curl = Unit::Instantiate('Curl');
		};

		//	...
		if(!$http = $curl->Get($url, null, ['header'=>1]) ){
			return false;
		};

		//	...
	//	self::DB()->Register(parse_url($url), $http['head']['status']);

		//	...
		return $http;
	}

	static function Debug()
	{
	//	self::DB()->Debug();
	}
}
