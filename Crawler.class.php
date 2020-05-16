<?php
/** op-unit-crawler:/Crawler.class.php
 *
 * @created   2019-05-30
 * @version   1.0
 * @package   op-unit-crawler
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
use OP\Debug;
use function OP\Html;
use function OP\ConvertPath;
use function OP\APP\Request;

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
	use OP_CORE, OP_UNIT, CRAWLER_CORE, CRAWLER_HELPER;

	/** Current url ai.
	 *  Use for referer.
	 *
	 *  @param integer
	 */
	private $_current_ai;

	/** Inherit priority score.
	 *
	 * @var       integer
	 */
	private $_current_score;

	/** Current scheme.
	 *
	 * @var       integer
	 */
	private $_current_scheme;

	/** Register each link in target page.
	 *
	 * @param  string  $url     Target page url
	 * @param  string  $mime    Target page mime
	 * @param  string  $html    Target page html
	 * @param  array   $config  Configuration
	 */
	private function _RegisterLink($url, $mime, &$html, $config)
	{
		//	...
		if(!$doc_root = $config['rewrite_base'] ?? null ){
			\OP\Notice::Set("\$config['rewrite_base'] is empty.");
			return;
		};

		//	'/foo/bar' --> 'foo/bar', '/' --> ''
		$doc_root = trim($doc_root, '/');
		//	'foo/bar' --> '/foo/bar/', '' --> '/'
		$doc_root = $doc_root ? "/{$doc_root}/": '/';

		//	...
		$parsed = $this->URL()->Parse($url);

		//	...
		switch( $mime ){
			//	Content is css.
			case 'text/css':
				$this->_RegisterLinkStyle($parsed, $html, $doc_root);
			return;

			//	Content is html.
			case 'text/html':
				$matches = null;
				preg_match_all('|<style>(.+)</style>|is', $html, $matches, PREG_SET_ORDER);
				//	Loop each style in html.
				foreach( $matches ?? [] as $match ){
					//	Rewrite path of link.
					$this->_RegisterLinkStyle($parsed, $match[1], $doc_root);
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
		$lists['form']   = ['tag'=>'|(<form ([^>]+)>(.*?)</form>)|is'    , 'attr'=>'action'];

		/* Will correspond to OGP.
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
				$this->_RegisterFullPath($parsed, $match[2], $doc_root);

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
				if( $rootpath = $this->_DocumentRootPath($parsed, $match[2], $doc_root) ){

					//	...
					$orig = $list['attr'].'='.$match[1].$match[2].$match[1];
					$path = $list['attr'].'='.$match[1].$rootpath.$match[1];

					//	Touch html source code.
					$html = str_replace($orig, $path, $html);
				}else{
					//	Not need to replace.
				//	D( $rootpath, $parsed, $match[2], $doc_root );
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
	private function _RegisterLinkStyle($parsed, &$html, $doc_root)
	{
		//	...
		$matches = null;

		//	url(/images/bg.png)
		//	(.*?) <-- Why necessary empty string?
		preg_match_all('/url\((.*?)\)/is', $html, $matches, PREG_SET_ORDER);

		//	...
		foreach( $matches ?? [] as $match ){
			//	...
			$link = $match[1];
			$link = trim($link);

			//	Ignore empty string.
			if( empty($link) ){
				continue;
			}

			//	...
			if( $link[0] === '"' or $link[0] === "'" ){
				$link = trim($link, '"\'');
			};

			//	...
			$this->_RegisterFullPath($parsed, $link, $doc_root);

			//	Change to new document root path.
			if( $rootpath = $this->_DocumentRootPath($parsed, $link, $doc_root) ){

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
	 */
	private function _RegisterFullPath($parsed, $href)
	{
			//	...
			if( strpos($href, '#') === 0 ){
				return;
			};

			//	...
			if( strpos($href, 'mailto:') === 0 ){
				return;
			};

			//	...
			if( $href === $parsed['path'] ){
				return;
			};

			//	FQDN
			if( preg_match('|^[a-z+]+://|', $href) ){
				$merged = array_merge(['scheme' => $parsed['scheme']], parse_url($href));
				return $this->Register($merged);
			};

			//	Scheme less path. (FQDN)
			if( strpos($href, '//') === 0 ){
				$merged = array_merge(parse_url($href), ['scheme' => $parsed['scheme']]);
				return $this->Register($merged);
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
			$path  = $this->URL()->Parse($href)['path'];
			$query = $this->URL()->Parse($href)['query'];
			*/

			//	Document root path.
			if( strpos($path, '/') === 0 ){
				return $this->Register(array_merge($parsed, ['path'=>$path,'query'=>$query]));
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

			//	What is this needed for?
			/*
			if( dirname($parsed['path']) === '/' ){
				\OP\Debug::Set('path',"Path is not has parent directory. ({$parsed['path']}, $path)");
				continue;
			};
			*/

			//	Same path.
			if( $path === $parsed['path'] ){
				//	Different queries.
				if( $query !== $parsed['query'] ){
					$this->Register(array_merge($parsed, ['query'=>$query]));
				};
				return;
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
			return $this->Register(array_merge($parsed, ['path'=>$path,'query'=>$query]));
	}

	/** Change document root path And remove FQDN.
	 *
	 *  <pre>
	 *  http://example.com/foo/bar --> /example.com/foo/bar
	 *  </pre>
	 *
	 * @param  array  $parsed
	 * @param  string $path
	 * @param  string $rewrite_base
	 * @return string $doc_root_path
	 */
	private function _DocumentRootPath($parsed, $path, $doc_root)
	{
		//	Check include scheme FQDN.
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
				$this->Register($path);

				//	...
				$host = $this->URL()->Parse($path)['host'];

				//	...
				$ai = $this->URL()->Host()->Ai($host);

				//	Increment score.
				$this->URL()->Host()->Update($ai, [' score + 1 ']);

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
	 * @param   array      $config
	 * @return  array      $condition
	 */
	private function _AutoCondition($config)
	{
		//	...
		$cond = [];

		//	...
		if( $ai = $config['ai'] ?? null ){

			//	...
			$cond['where'][] = " t_url.ai = $ai ";

		}else if( $url = $config['url'] ?? null ){

			//	...
			$cond['where'][] = ' t_url.ai = ' . $this->URL()->Ai($url);

		}else{

			//	...
			$host = $config['host'] ?? $this->URL()->Host()->Get(["ai > 0", 'order' => "score desc"])['host'];

			//	...
			if( $host ){

				//	Only the URL of this host name is acquired.
				$host = $this->URL()->Host()->Ai($host);

				//	...
				$request = \OP\Request();

				//	Cycle period to crawl.
				if( $cycle     = $request['cycle'] ?? null ){
					$cycle     = Env::Get('crawler')['cycle'] ?? ' -30 days ';
					$time      = strtotime($cycle, Env::Time());
					$timestamp = gmdate(_OP_DATE_TIME_, $time);
				}

				//	...
			//	$cond['order']   = 't_url.http_status_code, t_url.timestamp, t_url.score desc';
				$cond['order']   = 't_url.http_status_code, t_url.score desc';

				//	...
				$cond['where'][] = "t_url.host = $host";

				//	...
				if( $timestamp ?? null ){
					$cond['where'][] = "t_url.crawled < $timestamp ";
				}else{
					$cond['where'][] = "t_url.crawled is null ";
				}
			}
		};

		//	...
		return $cond;
	}

	/** Automatically
	 *
	 * @param string   $host
	 * @param array    $config
	 * @param callable $callback
	 */
	function Auto($config, $callback)
	{
		//	...
		if(!$cond = $this->_AutoCondition($config) ){
			return;
		};

		//	...
		$limit = $config['limit'] ?? 1;

		//	...
		if( $limit > 1000 ){
			$limit = 1000;
		};

		//	...
		for( $i=0; $i<$limit; $i++ ){
			//	...
			if(!$record = $this->URL()->Record($cond) ){
				return;
			};

			//	Calculate score.
			$score = $record['score'] > 0 ? $record['score']: 1;
			$score = $score > 100 ? 1: $score;

			//	Inherit source record score.
			$this->_current_score = $score;

			//	Inherit source record scheme.
			$this->_current_scheme = $record['scheme'];

			//	...
			$url = $this->URL()->Build($record);
			$ai  = $record['ai'];
			$this->_current_ai = $ai;

			//	...
			if(!$http = $this->_AutoHttp($record) ){
				continue;
			};

			/*
			if( $record['form'] ){
				D($url, $record, $http);
			}
			*/

			//	...
			$this->_RegisterLink($url, $http['head']['mime'] ?? null, $http['body'], $config);

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

	/** Register target URL.
	 *
	 * @param     array  $parsed
	 * @return    number $ai
	 */
	function Register($parsed)
	{
		//	...
		if( is_string($parsed) ){
			$parsed = $this->URL()->Parse($parsed);
		}

		//	Set referer url ai.
		$parsed['referer'] = $this->_current_ai;

		//	Inherit current record scheme.
		if( $parsed['scheme'] !== 'https' and $this->_current_scheme === 'https' ){
			$parsed['scheme']  =  $this->_current_scheme;
		}

		//	...
		if(!$ai = $this->URL()->Register($parsed, $this->_current_score) ){
			return;
		};

		//	...
		return $ai;
	}

	/** Fetch by t_url record.
	 *
	 * @param  array $record
	 * @return array $http
	 */
	function Fetch($record)
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
			$method = 'Post';
		};

		//	...
		if(!$record['scheme'] ){
			$record['scheme'] = 'http';
		};

		//	...
		$url = $this->URL()->Build($record);

		//	Get referer url.
		if( $referer = $record['referer'] ?? '' ){
			$referer = $this->URL()->Get($referer);
		}

		//	Generate option.
		$option = [];
		$option['header'] = 1;
		$option['cookie'] = ConvertPath('asset:/cache/cookie/', false).$record['host'];
		$option['referer'] = $referer;
		$option['ua']      = Env::Get('crawler')['useragent'];
		$option['timeout'] = 10;

		//	Generate POST data if method is Post.
		$data = ( $method === 'Post' ) ? array_merge($query, $form ?? []): null;

		/* @var $curl \OP\UNIT\Curl */
		$curl = $this->Unit('Curl');

		//	Execute Curl.
		$http = $curl->{$method}($url, $data, $option);

		//	...
		if( Debug::isDebug(__CLASS__) ){
			D( ['method'=>$method, 'URL'=>$url, 'data'=>$data, 'option'=>$option, 'record'=>$record], $http);
		}

		/* Do in CRAWLER_HELPER::_AutoHttp()
		//	...
		if( isset($http['head']['status']) ){
			//	Remove transfer ai. This case is login form transfer.
			if( $http['head']['status'] === '200' and $record['transfer'] ){
				$this->URL()->URL()->Update($record['ai'], ['transfer'=>null]);
			}
		}else{
			D($url, $data, $option, $http);
		}
		*/

		//	...
		if(!$http ){
			return false;
		};

		//	Check if headless browser.
		if( $record['headless'] ?? null ){
			//	Check if mime. Execute only when text/html.
			if( $http['head']['mime'] === 'text/html' ){
				$http['body'] = $this->Unit('Google')->Chrome($url);
			};
		};

		//	...
		return $http;
	}

	/** Debug
	 *
	 */
	static function Debug()
	{
	//	$this->DB()->Debug();
		\OP\Debug::Out();
	//	$this->URL()->DB()->Query();
	}
}
