<?php
/*---------------------------------------------------------------------------+
| Pimped-Fusion Content Management System
| Copyright (C) 2009 - 2010
| http://www.pimped-fusion.net
+----------------------------------------------------------------------------+
| Filename: user_theme_include.php
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

if ($profile_method == "input") {
	if ($settings['userthemes'] == 1 || iADMIN) {
		$theme_files = makefilelist(THEMES, ".|..|templates", true, "folders");
		array_unshift($theme_files, "Default");
		echo "<tr>\n";
		echo "<td class='tbl'>".$locale['uf_theme']."</td>\n";
		echo "<td class='tbl'><select name='user_theme' class='textbox' style='width:100px;'>\n".makefileopts($theme_files, (isset($user_data['user_theme']) ? $user_data['user_theme'] : ""))."\n</select></td>\n";
		echo "</tr>\n";
	}
} elseif ($profile_method == "display") {
	// Not shown in profile
} elseif ($profile_method == "validate_insert") {
	$db_fields .= ", user_theme";
	$db_values .= ", '".((isset($_POST['user_theme']) && ($settings['userthemes'] == 1 || iADMIN)) ? stripinput(trim($_POST['user_theme'])) : "Default")."'";
} elseif ($profile_method == "validate_update") {
	$db_values .= ", user_theme='".((isset($_POST['user_theme']) && ($settings['userthemes'] == 1 || iADMIN)) ? stripinput(trim($_POST['user_theme'])) : "Default")."'";
}
?>