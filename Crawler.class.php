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
use OP\Env;
use function OP\ConvertPath;

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

	/** Current url ai.
	 *  Use for referer.
	 */
	static $_current_ai;

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

	/** Register each link in target page.
	 *
	 * @param  string  $url     Target page url
	 * @param  string  $mime    Target page mime
	 * @param  string  $html    Target page html
	 * @param  array   $config  Configuration
	 */
	static private function _RegisterLink($url, $mime, &$html, $config)
	{
		//	...
		if(!$doc_root = $config['document_root'] ?? null ){
			return;
		};

		//	'/foo/bar' --> 'foo/bar', '/' --> ''
		$doc_root = trim($doc_root, '/');
		//	'foo/bar' --> '/foo/bar/', '' --> '/'
		$doc_root = $doc_root ? "/{$doc_root}/": '/';

		//	...
		$parsed = self::URL()->Parse($url);

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
			//	D($mime);
			return;
		};

		//	...
		$lists = [];
		$lists['link']   = ['tag'=>'|(<link ([^>]+)>)|is'                , 'attr'=>'href'];
		$lists['a']      = ['tag'=>'|(<a ([^>]+)>(.*?)</a>)|is'          , 'attr'=>'href'];
		$lists['img']    = ['tag'=>'|(<img ([^>]+)>)|is'                 , 'attr'=>'src'];
		$lists['script'] = ['tag'=>'|(<script ([^>]+)>(.*?)</script>)|is', 'attr'=>'src'];

		/*
		<meta property="og:image"       content="/img/cct/index/ogp.jpg">
		*/

		//	Keep original.
		$keep = $html;

		//	...
		foreach( $lists as $tag => $list ){

			//	...
			if( $tag ){
				//	...
			};

			//	Search from copied html. because original html will changed.
			$matches = null;
			if(!$total = preg_match_all($list['tag'], $keep, $matches, PREG_SET_ORDER) ){
				continue;
			};

			//	Loop of each tags.
			for($i=0; $i<$total; $i++){
				//	...
				$attr   = $matches[$i][2];

				//	...
				$match = null;

				//	Extract target attribute.
				if(!preg_match('/'.$list['attr'].'=("|\')(.*?)\1/', $attr, $match) ){
					continue;
				};

				//	...
				self::_RegisterFullPath($parsed, $match[2], $doc_root);

				//	...
				if(!$doc_root ){
					continue;
				};

				/** Change document root path And remove FQDN.
				 *
				 *  <pre>
				 *  http://example.com/foo/bar --> /example.com/foo/bar
				 *  </pre>
				 */
				if( $rootpath = self::_DocumentRootPath($parsed, $match[2], $doc_root) ){

					//	...
					$orig = $list['attr'].'='.$match[1].$match[2].$match[1];
					$path = $list['attr'].'='.$match[1].$rootpath.$match[1];

					//	Touch html source code.
					$html = str_replace($orig, $path, $html);
				};
			};
		};
	}

	/** Register link of style sheet.
	 *
	 * @param array  $parsed
	 * @param string $html
	 * @param string $doc_root
	 */
	static private function _RegisterLinkStyle($parsed, &$html, $doc_root)
	{
		//	...
		$matches = null;

		//	url(/images/bg.png)
		preg_match_all('/url\((.*?)\)/is', $html, $matches, PREG_SET_ORDER);

		//	...
		foreach( $matches ?? [] as $match ){
			//	...
			$link = $match[1];
			$link = trim($link);

			//	...
			if( $link[0] === '"' or $link[0] === "'" ){
				$link = trim($link, '"\'');
			};

			//	...
			self::_RegisterFullPath($parsed, $link, $doc_root);

			//	Change to new document root path.
			if( $rootpath = self::_DocumentRootPath($parsed, $link, $doc_root) ){

				//	...
				$orig = $match[0];
				$path = "url({$rootpath})";

				//	...
				$html = str_replace($orig, $path, $html);
			};
		};
	}

	/** Register FQDN and Path.
	 *
	 * @param array  $parsed
	 * @param string $href
	 * @param string $doc_root
	 */
	static private function _RegisterFullPath($parsed, $href, $doc_root)
	{
		//	...
		$doc_root = rtrim($doc_root,'/');

		//	...
		do{
			//	...
			if( strpos($href, '#') === 0 ){
				continue;
			};

			//	...
			if( strpos($href, 'mailto:') === 0 ){
				continue;
			};

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
			if(($pos   = strpos($href, '?')) !== false ){
				$path  = substr($href, 0, $pos);
				$query = substr($href, $pos+1 );
			}else{
				$path  = $href;
				$query = '';
			};

			//	Separate to hash.
			if( $pos  = strpos($path, '#') ){
				$path = substr($path, 0, $pos);
			};

			/*
			//	...
			$path  = self::URL()->Parse($href)['path'];
			$query = self::URL()->Parse($href)['query'];
			*/

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

			//	Parent path is added to directory slash.
			if( $path === '..' ){
				$path  =  '../';
			};

			//	Parent relative path.
			if( strpos($path, '../') === 0 ){
				//	...
				$current_dir = dirname($parsed['path']);

				//	Adjust parent relative path.
				while( strpos($path, '../') === 0 ){
					//	Remove "../"
					$path = substr($path, 3);

					//	...
					$parent_dir = dirname($current_dir);
				};

				//	...
				$path = rtrim($parent_dir,'/') .'/'. $path;
			};

			//	...
			if( dirname($parsed['path']) === '/' ){
				\OP\Debug::Set('path',"Path is not has parent directory. ({$parsed['path']}, $path)");
				continue;
			};


			//	Same path.
			if( $path === $parsed['path'] ){
				//	Different queries.
				if( $query !== $parsed['query'] ){
					self::Register(array_merge($parsed, ['query'=>$query]));
				};
				continue;
			};

			//	Current relate path.
			if( strpos($path, '/') !== 0 ){

				//	In case of only query.
				if( empty($path) and $query ){
					/**
					 * <a href="?lang=ja">button</a>
					 */
				}else

				//	Check if not closing slash path.
				if( $parsed['path'][strlen($parsed['path'])-1] !== '/' ){
					//	/foo/bar/hoge --> ['foo','bar','hoge']
					$join = explode('/', $parsed['path']);
					//	['foo','bar','hoge'] --> ['foo','bar']
					array_pop($join);
					//	['foo','bar'] --> /foo/bar/
					$parsed['path'] = join('/', $join).'/';
				};

				//	/foo/bar --> /foo/bar/index.html
				$path = $parsed['path'] . $path;
			};

			//	...
			if( $pos  = strpos($path, '#') ){
				$path = substr($path, 0, $pos);
			};

			//	...
			self::Register(array_merge($parsed, ['path'=>$path,'query'=>$query]));

		}while(false);
	}

	/** Calculate new document root path.
	 *
	 * @param  array  $parsed
	 * @param  string $path
	 * @param  string $doc_root
	 * @return string $doc_root
	 */
	static private function _DocumentRootPath($parsed, $path, $doc_root)
	{
		//	FQDN
		foreach( ['//','http://','https://'] as $scheme ){

			//	If matches is has FQDN.
			if( strpos($path, $scheme) !== 0 ){
				continue;
			};

			//	example.com --> http://example.com
			$url = $scheme . $parsed['host'];

			//	 ...
			if( strpos($path, $url) === 0 ){
				//	If matches is current host.
				$path = substr($path, strlen($url));
				$path = $doc_root . ltrim($path,'/');
				return $path;
			}else{
				//	If unmatches is external host.
				self::Register($path);

				//	...
				$host = self::URL()->Parse($path)['host'];

				//	...
				$ai = self::URL()->Host()->Ai($host);

				//	Increment score.
				self::URL()->Host()->Update($ai, [' score + 1 ']);

				//	Change to doc_root by remove scheme. http://example.com --> /example.com
				$path = substr($path, strlen($scheme) -1 );

				//	...
				return $path;
			};
		};

		//	Current relate path.
		if( strpos($path, './') === 0 ){
			return;
		};

		//	Parent relate path.
		if( strpos($path, '../') === 0 ){
			return;
		};

		//	Document root path
		if( strpos($path, '/') === 0 ){
			return '/' . trim($doc_root, '/') .'/'. ltrim($path, '/');
		};
	}

	/** Generate fetch record condition use by Auto().
	 *
	 * @created 2019-07-26
	 * @param   array      $config
	 * @return  array      $condition
	 */
	static private function _AutoCondition($config)
	{
		//	...
		$cond = [];

		//	...
		if( $url = $config['url'] ?? null ){

			//	...
			$cond['where'][] = ' t_url.ai = ' . self::URL()->Ai($url);

		}else if( $ai = $config['ai'] ?? null ){

			//	...
			$cond['where'][] = ' t_url.ai = ' . $ai;

		}else{

			//	...
			$host = $config['host'] ?? self::URL()->Host()->Get(["ai > 0", 'order' => "score desc"])['host'];

			//	...
			if( $host ){

				//	Only the URL of this host name is acquired.
				$host = self::URL()->Host()->Ai($host);

				//	Cycle period to crawl.
				$cycle     = \OP\Env::Get('crawler')['offset'] ?? ' -30 days ';
				$timestamp = date(_OP_DATE_TIME_, strtotime($cycle));

				//	...
				$cond['where'][] = "t_host.ai = $host";
				$cond['where'][] = "t_url.crawled < $timestamp";
				//	$cond['order']   = 't_url.http_status_code, t_url.timestamp, t_url.score desc';
				$cond['order']   = 't_url.http_status_code, t_url.score desc';
			}
		};

		//	...
		return $cond;
	}

	/** Fetch http content.
	 *
	 * @created  2019-07-30
	 * @param    array      $record
	 * @throws  \Exception
	 * @return   array      [head, body]
	 */
	static function _AutoHttp($record)
	{
		//	If 30x status.
		if( strpos($record['http_status_code'], '30') === 0 or $record['http_status_code'] === '404' ){
			if( $record['transfer'] ){
				D($record);
				return;
			};
		};

		//	...
		if(!$http = self::Fetch($record) ){
			D($record);
			return;
		};

		//	...
		$update = [];
		$update['http_status_code'] = $http['head']['status'] ?? null;
		$update['crawled'] = gmdate(_OP_DATE_TIME_, Env::Time());
		self::URL()->Update($record['ai'], $update);

		//	...
		switch( $http['head']['status'] ?? null ){
			case 301: // Moved Permanently.
			case 302: // Temporary Redirect(Only GET  method)
			case 307: // Temporary Redirect(Keep POST method)
			case 303: // Upload progress page
				//	...
				if(!$location = $http['head']['location'] ){
					throw new \Exception("Empty location.");
				};

				//	...
				$scheme = $record['scheme'];

				//	...
				foreach( self::URL()->Parse($location) as $key => $val ){
					if( $val ){
						$record[$key] = $val;
					};
				};

				//	...
				if( $ai = self::Register($record) ){
					//	...
					if( $ai == $record['ai'] ){
						//	Change scheme to https from http.
						if( $scheme === 'http' and $record['scheme'] === 'https' ){
							$update = [];
							$update['http_status_code'] = null;
							$update['scheme'] = $record['scheme'];
							self::URL()->Update($record['ai'], $update);
						};
					}else{
						//	...
						$update = [];
						$update['transfer'] = $ai;
						self::URL()->Update($record['ai'], $update);
					};
				};

				//	...
				break;

			//	Method Not Allowed
			case 405:
				//	Change to allowed method.
				if( $method = $http['head']['allow'] ?? null ){

					//	Rebuild form queries.
					$form = $record['form'] ? parse_str($record['form']): [];
					$form['method'] = $method;
					$form = urldecode(http_build_query($form));

					//	...
					if(!self::URL()->Ai( array_merge($record, ['form'=>$form]) ) ){
						//	Update
						$update = [];
						$update['http_status_code'] = null;
						$update['form'] = self::URL()->Form()->Ai($form);
						self::URL()->Update($record['ai'], $update);
					};
				};
				break;

			//	...
			default:
		};

		//	...
		return $http;
	}

	/** Automatically
	 *
	 * @param string   $host
	 * @param array    $config
	 * @param callable $callback
	 */
	static function Auto($config, $callback)
	{
		//	...
		if(!$cond = self::_AutoCondition($config) ){
			return;
		};

		//	...
		$limit = $config['limit'] ?? 1;

		//	...
		if( $limit > 100 ){
			$limit = 100;
		};

		//	...
		for( $i=0; $i<$limit; $i++ ){
			//	...
			if(!$record = self::URL()->Record($cond) ){
				return;
			};

			//	...
			$url = self::URL()->Build($record);
			$ai  = $record['ai'];
			self::$_current_ai = $ai;

			//	...
			if(!$http = self::_AutoHttp($record) ){
				continue;
			};

			//	...
			self::_RegisterLink($url, $http['head']['mime'] ?? null, $http['body'], $config);

			//	...
			if( $callback ){
				call_user_func($callback, $url, $http);
			};
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
		if(!$ai = self::URL()->Register($parsed) ){
			return;
		};

		//	...
		$update = [];
		$update['referer'] = self::$_current_ai;

		//	...
		$where = ['referer is null'];

		//	...
		self::URL()->Update($ai, $update, $where);

		//	...
		return $ai;
	}

	/** Fetch target content.
	 *
	 * @param  array $record
	 * @return array $http
	 */
	static function Fetch($record)
	{
		//	...
		$method = 'Get';
		$query  = [];
		$form   = [];

		//	...
		if( $record['query'] ){
			//	...
			parse_str($record['query'], $query);
		};

		//	...
		if( $record['form'] ){
			//	...
			parse_str($record['form'], $form);

			//	...
			$method = $form['method'] ?? 'Post';
		};

		//	...
		if(!$record['scheme'] ){
			$record['scheme'] = 'http';
		};

		//	...
		$url = self::URL()->Build($record);

		//	...
		$option = [];
		$option['header'] = 1;
		$option['cookie'] = ConvertPath('asset:/cache/cookie/').$record['host'];

		//	...
		$data = array_merge($query, $form['input'] ?? []) ?? null;

		/* @var $curl \OP\UNIT\Curl */
		$curl = self::Unit('Curl');

		//	...
		if(!$http = $curl->{$method}($url, $data, $option) ){
			return false;
		};

		//	...
		if( $record['headless'] ?? null ){
			//	...
			if( $http['head']['mime'] === 'text/html' ){
				D($url, $http['head']['mime']);
				$http['body'] = self::Unit('Google')->Chrome($url);
			};
		};

		//	...
		return $http;
	}

	static function Debug()
	{
	//	self::DB()->Debug();
	}
}
