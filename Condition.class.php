<?php
/** op-unit-crawler:/Condition.class.php
 *
 * @created   2020-05-15
 * @version   1.0
 * @package   op-unit-crawler
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */

/** namespace
 *
 */
namespace OP\UNIT\CRAWLER;

/** Used class.
 *
 */
use OP\OP_CORE;
use OP\UNIT\CRAWLER_CORE;

/** Crawler
 *
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */
class Condition
{
	/** trait.
	 *
	 */
	use OP_CORE, CRAWLER_CORE;

	/** Auto
	 *
	 * @created   2020-05-15
	 * @param     array        $condition
	 * @param     array        $config
	 */
	static function Auto($condition):array
	{
		//	...
		if( $condition['ai'] ?? null ){
			$config = self::_Ai($condition);
		}else

		//	...
		if( $condition['url'] ?? null ){
			$config = self::_URL($condition);
		}else

		//	...
		if( $condition['host']?? null ){
			$config = self::_Host($condition);
		}else

		if( $condition['status']?? null ){
			$config = self::_Status($condition);
		}else

		if( $condition['score']?? null ){
			$config = self::_Score($condition);
		}else

		//	...
		if( true ){
			$config = self::_Status($condition);
		}

		//	...
		$config['limit'] = 1;
		$config['where'][] = ' delete is null ';

		//	...
		return $config;
	}

	/** Ai
	 *
	 * @created   2020-05-15
	 * @param     array        $conf
	 */
	static function _Ai($conf)
	{
		//	...
		$ai = $conf['ai'];

		//	...
		$config = [];
		$config['where'][] = " t_url.ai = $ai ";

		//	...
		return $config;
	}

	/** URL
	 *
	 * @created   2020-05-15
	 * @param     array        $conf
	 */
	static function _URL($conf)
	{
		//	...
		$url = $conf['url'];

		//	...
		$parsed = self::URL()->Parse($url);

		//	...
		$host = $parsed['host'];
		$path = $parsed['path'];

		//	...
		$host = self::URL()->Hash($host);
		$path = self::URL()->Hash($path);

		//	...
		$config = [];
		$config['where'][] = " t_host.hash = $host";
		$config['where'][] = " t_path.hash = $path";

		//	...
		return $config;
	}

	/** Host
	 *
	 * @created   2020-05-15
	 * @param     array        $conf
	 */
	static function _Host($conf)
	{
		//	...
		$host = $conf['host'];
		$hash = self::URL()->Hash($host);

		//	...
		$config = [];
		$config['order'][] = ' t_url.score desc               ';
		$config['where'][] = " t_host.hash   =  $hash         ";
		$config['where'][] = ' t_url.http_status_code is null ';

		/*
		if( $conf['crawled'] === 'null' ){
			$config['where'][] = " t_url.crawled is null       ";
		}else{
			$timestamp = \OP\Env::Timestamp(1, '-1 hour');
			$config['where'][] = " t_url.crawled <  $timestamp ";
		}
		*/

		//	...
		return $config;
	}

	/** Status is null record
	 *
	 * @created   2020-05-15
	 * @param     array        $conf
	 */
	static function _Status($conf)
	{
		//	...
		$config = [];
		$config['limit']   = $conf['limit'] ?? 1;
		$config['order']   = ' t_url.created                  ';
		$config['where'][] = " t_url.http_status_code is null ";

		//	...
		return $config;
	}

	/** Score
	 *
	 * @created   2020-05-15
	 * @param     array        $conf
	 */
	static function _Score($conf)
	{
		//	...
		$host = $conf['host'];
		$host = self::URL()->Hash($host);

		//	...
		$config = [];
		$config['limit'] = $conf['limit'] ?? 1;
		$config['where'][] = " t_url.ai > 0 ";
		$config['order'] = 'score desc';

		//	...
		return $config;
	}
}
