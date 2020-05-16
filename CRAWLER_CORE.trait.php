<?php
/** op-unit-crawler:/CRAWLER_CORE.class.php
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

/** CRAWLER_CORE
 *
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */
trait CRAWLER_CORE
{
	/** URL
	 *
	 * @return  \OP\UNIT\URL
	 */
	static function URL()
	{
		//	...
		static $_url;

		//	...
		return $_url ?? $_url = new \OP\UNIT\URL();
	}
}
