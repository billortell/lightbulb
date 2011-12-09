<?php

//
// @title Lightbulb CMS
// @author Chris Frazier <chris@chrisfrazier.me>
// @license http://www.gnu.org/copyleft/gpl.html GPL
//

$debug = false;

if ($debug == true) {
	ini_set('display_errors', true);
	ini_set('display_startup_errors', true);
	error_reporting(E_ALL);
} else {
	ini_set('display_errors', false);
	ini_set('display_startup_errors', false);
	error_reporting(E_ALL ^ E_NOTICE);
}

define("ROOT_DIR", dirname(__FILE__).'/../');
define("CODE_DIR", ROOT_DIR.'/_edison/');
define("PAGE_DIR", ROOT_DIR.'_content/pages/');
define("POST_DIR", ROOT_DIR.'_content/posts/');
define("LAYOUT_DIR", ROOT_DIR.'_content/layouts/');

require_once CODE_DIR.'config.php';
if ($template_engine == 'mustache') require_once CODE_DIR.'ext/mustache/mustache.php';
elseif ($template_engine == 'twig') require_once CODE_DIR.'ext/twig/lib/Twig/Autoloader.php';
require_once CODE_DIR.'ext/markdown/markdown.php';
require_once CODE_DIR.'page.php';
require_once CODE_DIR.'lightbulb.php';