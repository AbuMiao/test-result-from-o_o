<?php
namespace core\tools;

class ClsTimerTools
{
	static public function getDeltaTimeInMS($t1, $t2){
		return ClsTimerTools::microtimeFloat($t2) - ClsTimerTools::microtimeFloat($t1);
    }
    /**
	 * Simple function to replicate PHP 5 behaviour
	 */
	static function microtimeFloat($t)
	{
	    list($usec, $sec) = explode(" ", $t);
	    return ((float)$usec + (float)$sec);
	}
}
?>