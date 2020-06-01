<?php
/** op-unit-crawler:/sample/login/index.php
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
$is_login = include('is_login.php');

//	...
if( $is_login ){
	include('success.phtml');
}else{
	include('form.phtml');
};
