<?php
/** op-unit-crawler:/sample/login/is_login.php
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
$password_list = include('password.php');

//	...
$username = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;

//	...
if( isset($password_list[$username]) and $password_list[$username] === $password ){
	$is_login = true;
}else{
	$is_login = false;
};

//	...
if( $is_login ?? null ){
	$_SESSION['is_login'] = true;
	$_SESSION['username'] = $username;
};

//	...
return $_SESSION['is_login'] ?? null;
