<?php
/** op-unit-crawler:/function/CalcSleep.php
 *
 * @version   1.0
 * @package   op-unit-crawler
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */

/** namespace
 *
 */
namespace OP;

/** Calculate the average sleep time.
 *
 */
function CalcSleep(){
	//	...
	static $sum = 0, $count = 0;

	//	...
	$sleep = rand(1,10);

	//	...
	$sum += $sleep;
	$count++;

	D($sum);

	//	...
	return $sleep;
}
