<?php
/** op-unit-crawler:/CRAWLER_HELPER.class.php
 *
 * @version   1.0
 * @package   op-unit-crawler
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */

/** namespace
 *
 */
namespace OP\UNIT;

/** Used class.
 *
 */
use OP\Notice;
use function OP\APP\Request;
use function OP\Decode;

/** CRAWLER_HELPER
 *
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */
trait CRAWLER_HELPER
{
	/** Score to zero
	 *
	 * @created   2020-05-12
	 */
	private function _ScoreZero()
	{
		/* @var $db \OP\IF_DATABASE */
		$db = $this->URL()->URL()::DB();

		//	...
		$config = [];
		$config['table'] = 't_url';
		$config['limit'] = 1;
		$config['field'] = 'max(score)';
		$config['where'] = 'delete is null';
		$record = $db->Select($config);

		//	...
		$score  = $record['MAX(`score`)'];
		$config = [];
		$config['table'] = 't_url';
		$config['limit'] = 1;
		$config['where'] = " score = $score ";
		$config['set']['score'] = 0;
		$db->Update($config);
	}

	/** Curl Error
	 *
	 * @created  2020-05-12
	 * @param    integer      $errno
	 * @param    array        $record
	 */
	private function _CurlError($errno, $record)
	{
		//	...
		switch( $errno ){
			case 3:  // URL format is malformed
			case 6:  // Couldn't resolve host
			case 7:  // Failed to connect
			case 18: // Transfer closed with outstanding read data remaining
			case 28: // Connection timed out
				break;

			case 51: // SSL: No alternative certificate
				//	...
				$update = [];
				$update[] = " scheme = 0 ";
				$this->URL()->Update($record['ai'], $update);
				break;

			default:
				Notice::Set("This curl error is not implemented yet. ({$errno})");
		}
	}

	/** Fetch http content.
	 *
	 * @param    array        $record
	 * @throws  \Exception
	 * @return   array      [head, body]
	 */
	private function _AutoHttp($record)
	{
		//	...
		$this->_ScoreZero();

		//	...
		$http = $this->Fetch($record);

		//	...
		if( $errno = $http['errno'] ?? null ){
			$this->_CurlError($errno, $record);
		}

		//	...
		$score = $http_status_code = $http['head']['status'] ?? 0;

		//	...
		$timestamp = gmdate(_OP_DATE_TIME_);

		//	...
		$update = [];
		$update[] = " http_status_code = {$http_status_code} ";
		$update[] = " crawled          = {$timestamp}        ";
		$update[] = " score            - {$score}            ";
		$this->URL()->Update($record['ai'], $update);

		//	...
		switch( $http['head']['status'] ?? null ){
			case 200:
				//	Remove if set transfer.
				if( $record['transfer'] ){
					//	This case is login form.
					$this->URL()->Update($record['ai'], [" transfer is null "]);
				}
				break;

			case 301: // Moved Permanently
			case 302: // Temporary Redirect(Only GET  method)
			case 307: // Temporary Redirect(Keep POST method)
			case 303: // See Other - Upload progress page
				//	...
				if(!$location = $http['head']['location'] ){
					throw new \Exception("Empty location.");
				};

				//	Check if the scheme is the only difference.
				$parsed = $this->URL()->Parse($location);
				$p = $this->URL()->Build($parsed, ['scheme'=>null]);
				$r = $this->URL()->Build($record, ['scheme'=>null]);
				//	For camel case --> http://example.com/%XX --> https://example.com/%xx
				$p = strtolower($p);
				$r = strtolower($r);
				//	&amp; --> &
				$p = Decode($p);
				$r = Decode($r);

				//	Are the URLs the same?
				//	http://example.com --> https://example.com
				$io = $p === $r;

				//	for debug
				if( $io ){
					//	only difference of just scheme.
				}else{
					D($io);
					D($r);
					D($p);
				}

				//	If only un match scheme.
				if( $io ){

					//	Update scheme and,
					//	Remove http status code, for crawl again.
					$update = [];
					$update['scheme'] = $this->URL()->Scheme()->Ai($parsed['scheme']);
					$update['http_status_code'] = null;
					$this->URL()->Update($record['ai'], $update);

					//	https complementation plan
					$scheme = $update['scheme'];
					$host   = $this->URL()->Host()->Ai($parsed['host']);
					$config = [];
					$config['table'] = 't_url';
					$config['limit'] = -1;
					$config['set'][]   = " scheme = $scheme ";
					$config['where'][] = " scheme = 0       ";
					$config['where'][] = " host   = $host   ";
					$io = $this->URL()->DB()->Update($config);

				}else{

					//	Register transfer location.
					$ai = $this->_RegisterFullPath($record, $location);

					//	Update transfer location to source url record.
					$update = [];
					$update['transfer'] = $ai;
					$this->URL()->Update($record['ai'], $update);
				}
				break;

			case 400: // Bad request
			case 403:
			case 404:
			case 405: // Method Not Allowed
			case 414: // Request-URI Too Long
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
					if(!$this->URL()->Ai( array_merge($record, ['form'=>$form]) ) ){
						//	Update
						$update = [];
						$update['http_status_code'] = null;
						$update['form'] = $this->URL()->Form()->Ai($form);
						$this->URL()->Update($record['ai'], $update);
					};
				};
				break;

			case 500: // Internal Server Error
				break;

				//	...
			default:
		};

		//	...
		return $http;
	}
}
