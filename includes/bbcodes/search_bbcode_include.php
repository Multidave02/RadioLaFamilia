<?php
/*---------------------------------------------------------------------------+
| Pimped-Fusion Content Management System
| Copyright (C) 2009 - 2010
| http://www.pimped-fusion.net
+----------------------------------------------------------------------------+
| Filename: search_bbcode_include.php
| Version: Pimped Fusion v0.09.00
+----------------------------------------------------------------------------+
| based on PHP-Fusion CMS v7.01 by Nick Jones
| http://www.php-fusion.co.uk/
+----------------------------------------------------------------------------+
| This program is released as free software under the Affero GPL license.
| You can redistribute it and/or modify it under the terms of this license
| which you can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this copyright header is
| strictly prohibited without written permission from the original author(s).
+---------------------------------------------------------------------------*/
if (!defined("PIMPED_FUSION")) { die("Access Denied"); }

include LOCALE.LOCALESET."bbcodes/search.php";

if (!function_exists("search_on")) {
	function search_on($where) {
		global $settings;
		if ($where == "all") {
			include LOCALE.LOCALESET."search.php";
			return $locale['407'];
		} else {
			include LOCALE.LOCALESET."search/".$where.".php";
			foreach ($locale as $key => $value) {
        if (preg_match("/400/", $key)) $name = $key;
      }
			return $locale[$name];
		}
	}
}

$text = preg_replace('#\[search\](.*?)([\r\n]*)\[/search\]#si', '<strong>'.$locale['bb_search_prefix'].' <a href=\''.BASEDIR.'search.php?stext=\1\2&amp;method=AND&amp;stype=all&forum_id=0&datelimit=0&fields=2&sort=datestamp&order=0&chars=50\' title=\'\1\2\'>\1\2</a></strong>', $text);
$text = preg_replace('#\[search=(.*?)\](.*?)([\r\n]*)\[/search\]#sie', "'<strong>".$locale['bb_search_prefix']." <a href=\'".BASEDIR."search.php?stext=\\2\\3&amp;method=AND&amp;stype=\\1&forum_id=0&datelimit=0&fields=2&sort=datestamp&order=0&chars=50\' title=\'\\2\\3\'>\\2\\3</a> ".$locale['bb_search_suffix']." '.search_on('\\1').'</strong>'", $text);
?>
