<?php
/** op-unit-crawler:/sample/private/index.php
 *
 * @created   2020-02-02
 * @version   1.0
 * @package   op-unit-crawler
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */

/** namespace
 *
 */
namespace OP;

//	...
include('../breadcrumbs.phtml');

//	...
if( empty($_SESSION['is_login']) ){
	include('reject.phtml');
	return;
};

//	...
include('index.phtml');
