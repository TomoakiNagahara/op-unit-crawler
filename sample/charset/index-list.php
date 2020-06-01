<?php
/** op-unit-crawler:/sample/charset/index-list.php
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
foreach( glob('*\.html') as $file ){
	//	...
	$name = explode('.', $file)[0];

	//	...
	printf('<a href="%s">%s</a>', $file, $name);
};
