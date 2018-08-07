<?php

define( 'ABSPATH', getenv('WP_DEVELOP_DIR' ));

require_once __DIR__ . '/../../src/vendor/autoload.php';

// disable xdebug backtrace
if ( function_exists( 'xdebug_disable' ) ) {
    xdebug_disable();
}

require_once ABSPATH . 'wp-includes/formatting.php';


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class UnitTester extends \Codeception\Actor
{
    use _generated\UnitTesterActions;

   /**
    * Define custom actions here
    */
}
