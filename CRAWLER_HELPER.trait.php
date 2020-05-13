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
			case 28: // Connection timed out
				break;

			case 51: // SSL: No alternative certificate
				//	...
				$update = [];
				$update[] = " scheme = 0 ";
				$this->URL()->Update($record['ai'], $update);
				break;

			default:
				Notice::Set("Not implemented yet. ({$errno})");
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
D($http);
		//	...
		if( $errno = $http['errno'] ?? null ){
			$this->_CurlError($errno, $record);
		}

		//	Decrement score.
		switch( $http_status_code = $http['head']['status'] ?? 0 ){
			case 0:
				D($http, $record);
				$score = 1;
				break;

			case 200:
				$score = 100;
				break;

			case 302:
				$score = 10;
				break;

			case 301:
			case 403:
			case 404:
			case 405: // Method Not Allowed
			case 414: // Request-URI Too Long
				$score = 10000;
				break;

			default:
				D($http);
				$score = 100000;
				break;
		}

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

			case 301: // Moved Permanently.
			case 302: // Temporary Redirect(Only GET  method)
			case 307: // Temporary Redirect(Keep POST method)
			case 303: // Upload progress page
				//	...
				if(!$location = $http['head']['location'] ){
					throw new \Exception("Empty location.");
				};

				//	Register transfer location.
				if( $ai = $this->_RegisterFullPath($record, $location) ){

					//	Remove http status code to crawl again.
					$update = [];
					$update['http_status_code'] = null;
					$this->URL()->Update($ai, $update);

					//	...
					if( $ai == $record['ai'] ){
						//	If scheme downgrade.
						if( $record['scheme'] === 'https' and strpos($location, 'http://') === 0 ){
							//	Do downgrade.
							$update = [];
							$update['http_status_code'] = null;
							$update['scheme'] = 0;
							$this->URL()->Update($record['ai'], $update);
						}

						/*
						//	Change scheme to https from http.
						if( $scheme === 'http' and $record['scheme'] === 'https' ){


							Notice::Set('Is this need? When?'); // Maybe overwrite scheme


							$update = [];
							$update['http_status_code'] = null;
							$update['scheme'] = $record['scheme'];
							$this->URL()->Update($record['ai'], $update);
						};
						*/
					}else{
						//	...
						$update = [];
						$update['transfer'] = $ai;
						$this->URL()->Update($record['ai'], $update);

						/*
						//	...
						$record['transfer'] = $ai;
						$this->URL()->Register($record);
						*/

						/*
						//	...
						$update = [];
						$update['transfer'] = $ai;
						$this->URL()->Update($record['ai'], $update);

						//	...
						if( $record['form'] ){
							//	...
							$form = $this->URL()->Form()->Ai($record['form']);

							//	...
							$update = [];
							$update['form'] = $form;
							$this->URL()->Update($ai, $update);

							D($ai, $update);
						}
						*/
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
					if(!$this->URL()->Ai( array_merge($record, ['form'=>$form]) ) ){
						//	Update
						$update = [];
						$update['http_status_code'] = null;
						$update['form'] = $this->URL()->Form()->Ai($form);
						$this->URL()->Update($record['ai'], $update);
					};
				};
				break;

				//	...
			default:
		};

		//	...
		return $http;
	}
}
