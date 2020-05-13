<?php
/** op-unit-crawler:/DB.class.php
 *
 * @created   2019-05-31
 * @version   1.0
 * @package   op-unit-crawler
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */

/** namespace
 *
 * @created   2019-05-31
 */
namespace OP\UNIT\CRAWLER;

/** Used class.
 *
 */
use OP\OP_CORE;

/** DB
 *
 * @created   2019-05-31
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */
class DB
{
	/** trait.
	 *
	 */
	use OP_CORE;

	static function &Session()
	{
		return $_SESSION['OP']['UNIT']['CRAWLER'];
	}

	static function Register($parsed, $http_status_code=null)
	{
		//	...
		$session =& self::Session();

		//	...
		$scheme = $parsed['scheme'] ?? null;
		$domain = $parsed['host']   ?? null;
		$port   = $parsed['port']   ?? 80;
		$path   = $parsed['path']   ?? '/';
		$query  = $parsed['query']  ?? '';

		//	...
		$https  = $scheme === 'https' ? true: false;

		//	...
		$session['count'] = 1 + (int)$session['count'];

		//	...
		if( empty($domain) ){
			D('Domain is empty.');
			return false;
		};

		//	...
		if( empty($path) ){
			D('Path is empty.');
			return false;
		};

		//	...
		if( empty($session['domains'][$domain]['https']) and $https ){
			$session['domains'][$domain]['https'] = true;
		};

		//	...
		if(!isset($session['domains'][$domain]['ports'][$port]['paths'][$path]['queries'][$query]) ){
			$session['domains'][$domain]['ports'][$port]['paths'][$path]['queries'][$query] = $http_status_code;
		};

		//	...
		return true;
	}

	static function Domain()
	{

	}

	static function URLs($config)
	{
		//	...
		if(!$domain = ($config['domain'] ?? null) ){
			D('Has not been set domain in config.');
		};

		//	...
		$session =& self::Session();

		//	...
		$urls = [];

		//	...
		foreach( $session['domains'][$domain] ?? [] as $port => $domains ){

			//	...
			$scheme = empty($domains['https']) ? 'http':'https';

			//	...
			foreach( $domains as $port => $ports ){
				//	...
				foreach( $ports['paths'] as $path => $queries ){
					//	...
					foreach( $queries['queries'] as $query => $http_status_code ){
						//	...
						$url = "{$scheme}://{$domain}:{$port}{$path}?{$query}";

						//	...
						if( $http_status_code !== null and empty($config['force']) ){
							continue;
						};

						//	...
						$urls[] = $url;
					};
				};
			};
		};

		//	...
		return $urls;
	}

	static function Debug()
	{
		D(self::Session());
	}
}
