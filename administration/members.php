<?php
/*---------------------------------------------------------------------------+
| Pimped-Fusion Content Management System
| Copyright (C) 2009 - 2010
| http://www.pimped-fusion.net
+----------------------------------------------------------------------------+
| Filename: members.php
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
require_once "../maincore.php";
require_once TEMPLATES."admin_header.php";
require_once INCLUDES."suspend_include.php";
include LOCALE.LOCALESET."admin/members.php";
include LOCALE.LOCALESET."user_fields.php";

if (!checkrights("M") || !defined("iAUTH") || $_GET['aid'] != iAUTH) { redirect("../index.php"); }

// System Variables (Predefined variables for global usage)
$page 			= (isset($_GET['page']) && isnum($_GET['page']) ? $_GET['page'] : 0); // Pimped
$rowstart 		= $page > 0 ? ($page-1) * 20 : "0"; // Pimped
$sortby 		= (isset($_GET['sortby']) ? stripinput($_GET['sortby']) : "all");
$status 		= (isset($_GET['status']) && isnum($_GET['status'] && $_GET['status'] < 9) ? $_GET['status'] : 0);
$user_id		= (isset($_GET['user_id']) && isnum($_GET['user_id']) ? $_GET['user_id'] : false);
$action 		= (isset($_GET['action']) && isnum($_GET['action']) ? $_GET['action'] : "");

// Used for links and redirects in stead of FUSION_SELF
define("USER_MANAGEMENT_SELF", FUSION_SELF.$aidlink."&sortby=".$sortby."&status=".$status."&page=".$page); // Pimped

// User Supension Log
if (isset($_POST['cancel'])) {
	redirect(USER_MANAGEMENT_SELF);
} elseif (isset($_GET['step']) && $_GET['step'] == "log" && $user_id) {
	display_suspend_log($user_id, "all", $rowstart);

// Deactivate Inactive Users
} elseif (isset($_GET['step']) && $_GET['step'] == "inactive"  && !$user_id && $settings['enable_deactivation'] == 1) {
	$inactive = dbcount("(user_id)", DB_USERS, "user_status='0' AND user_level<'".nSUPERADMIN."' AND user_lastvisit<'$time_overdue' AND user_actiontime='0'");
	$action = ($settings['deactivation_action'] == 0 ? $locale['616'] : $locale['615']);
	$button = $locale['614'].($inactive == 1 ? " 1 ".$locale['612'] : " 50 ".$locale['613']);
	if (!$inactive) { redirect(USER_MANAGEMENT_SELF); }
	opentable($locale['580']);
	if ($inactive > 50) {
		$run_times = round($inactive/50);
		echo "<div class='admin-message'>".sprintf($locale['581'], $run_times)."</div>";
	}
	echo "<div class='tbl1'>";
	echo sprintf($locale['610'], $inactive, $settings['deactivation_period'], $settings['deactivation_response'], $action);
	if ($settings['deactivation_action'] == 1) {
		echo "<br />\n".$locale['611'];
		echo "</div>\n<div class='admin-message'><strong>".$locale['617']."</strong>\n".$locale['618']."\n";
		if (checkrights("S9")) { echo $locale['619']; }
	}
	
	echo "</div>\n<div class='tbl1' style='text-align:center;'>\n";
	echo "<form method='post' action='".FUSION_SELF.$aidlink."&amp;step=inactive'>\n";
	echo "<input type='submit' name='cancel' value='".$locale['418']."' class='button' />\n";
	echo "<input type='submit' name='deactivate_users' value='".$button."' class='button' />\n";
	echo "</form>\n</div>\n";
	closetable();
	
	if (isset($_POST['deactivate_users'])) {
	require_once LOCALE.LOCALESET."admin/members_email.php";
	require_once INCLUDES."sendmail_include.php";
	
	$result = dbquery(
		"SELECT user_id, user_name, user_email, user_password FROM ".DB_USERS."
		WHERE user_level<'".nSUPERADMIN."' AND user_lastvisit<'".$time_overdue."' AND user_actiontime='0' AND user_status='0' 
		LIMIT 0,50"
	);
	
	while ($data = dbarray($result)) {
		$code = md5($response_required.$data['user_password']);
		$message = str_replace("[CODE]", $code, $locale['email_deactivate_message']);
		$message = str_replace("[USER_NAME]", $data['user_name'], $message);
		$message = str_replace("[USER_ID]", $data['user_id'], $message);
		
		if (sendemail($data['user_name'], $data['user_email'], $settings['siteusername'], $settings['siteemail'], $locale['email_deactivate_subject'], $message)) {
			$result2 = dbquery("UPDATE ".DB_USERS." SET user_status='7', user_actiontime='".$response_required."' WHERE user_id='".$data['user_id']."'");
			suspend_log($data['user_id'], 7, $locale['621']);
		}
	}
	redirect(FUSION_SELF.$aidlink.($inactive > 50 ? "&amp;step=inactive" : ""));
}
// Add new User
} elseif (isset($_GET['step']) && $_GET['step'] == "add") {
	if (isset($_POST['add_user'])) {
		$error = "";
		
		$username = trim(preg_replace("/ +/i", " ", $_POST['username']));
		
		if ($username == "" || trim($_POST['password1']) == "" || trim($_POST['email']) == "") { $error .= $locale['451']."<br />\n"; }
		
		if (!preg_match("/^[-0-9A-Z_@\s]+$/i", $username)) { $error .= $locale['452']."<br />\n"; }
		
		if (preg_match("/^[0-9A-Z@]{6,20}$/i", $_POST['password1'])) {
			if ($_POST['password1'] != $_POST['password2']) { $error .= $locale['456']."<br />\n"; }
		} else {
			$error .= $locale['457']."<br />\n";
		}
		
		if (!preg_match("/^[-0-9A-Z._%+ÄÖÜäöü]{1,50}@([-0-9A-Z.ÄÖÜäöü]+\.){1,50}([A-Z]){2,6}$/i", $_POST['email'])) {
			$error .= $locale['454']."<br />\n";
		}
		
		$count_u = dbcount("(user_id)", DB_USERS, "user_name="._db($username));
		if ($count_u > 0) { $error = $locale['453']."<br />\n"; }
		
		$count_e = dbcount("(user_id)", DB_USERS, "user_email="._db($_POST['email']));
		if ($count_e > 0) { $error = $locale['455']."<br />\n"; } 

		$profile_method = "validate_insert"; $db_fields = ""; $db_values = "";
		$result = dbquery(
			"SELECT field_name FROM ".DB_USER_FIELDS." tuf
			INNER JOIN ".DB_USER_FIELD_CATS." tufc ON tuf.field_cat = tufc.field_cat_id
			ORDER BY field_cat_order, field_order"
		);
		if (dbrows($result)) {
			while($data = dbarray($result)) {
				if (file_exists(LOCALE.LOCALESET."user_fields/".$data['field_name'].".php")) {
					include_once LOCALE.LOCALESET."user_fields/".$data['field_name'].".php";
				}elseif(file_exists(LOCALE."English/user_fields/".$data['field_name'].".php")) { // Pimped
					include_once LOCALE."English/user_fields/".$data['field_name'].".php";
				}
				if (file_exists(INCLUDES."user_fields/".$data['field_name']."_include.php")) {
					include INCLUDES."user_fields/".$data['field_name']."_include.php";
				}
			}
		}
		
		if ($error == "") {
			$result = dbquery("INSERT INTO ".DB_USERS." (user_name, user_password, user_admin_password, user_email, user_hide_email, user_avatar, user_posts, user_threads, user_joined, user_lastvisit, user_ip, user_rights, user_groups, user_level, user_status, user_actiontime".(isset($db_fields) ? $db_fields : "").") VALUES('$username', '".encrypt_pw($_POST['password1'])."', '', '".$_POST['email']."', '".intval($_POST['hide_email'])."', '', '0', '0', '".time()."', '0', '".USER_IP."', '', '', '".nMEMBER."', '0', '0'".(isset($db_values) ? $db_values : "").")"); // Pimped: nMEMBER and encrypt_pw();
			
			// todo: radio check button to disable the mail
			require_once LOCALE.LOCALESET."admin/members_email.php";
			require_once INCLUDES."sendmail_include.php";
			$subject = $locale['email_create_subject'].$settings['sitename'];
			$replace_this = array("[USER_NAME]", "[PASSWORD]");
			$replace_with = array($username, $_POST['password1']);
			$message = str_replace($replace_this, $replace_with, $locale['email_create_message']);
			sendemail($username, $_POST['email'], $settings['siteusername'], $settings['siteemail'], $subject, $message);
			
			opentable($locale['480']);
			echo "<div style='text-align:center'><br />\n".$locale['481']."<br /><br />\n";
			echo "<a href='members.php".$aidlink."'>".$locale['432']."</a><br /><br />\n";
			echo "<a href='index.php".$aidlink."'>".$locale['433']."</a><br /><br />\n";
			echo "</div>\n";
			closetable();
		} else {
			opentable($locale['480']);
			echo "<div style='text-align:center'><br />\n".$locale['482']."<br /><br />\n".$error."<br />\n";
			echo "<a href='members.php".$aidlink."'>".$locale['432']."</a><br /><br />\n";
			echo "<a href='index.php".$aidlink."'>".$locale['433']."</a><br /><br />\n";
			echo "</div>\n";
			closetable();
		}
	} else {
		opentable($locale['480']);
		member_nav(member_url("add", "")."| ".$locale['480']);
		echo "<form name='addform' method='post' action='".FUSION_SELF.$aidlink."&amp;step=add'>\n";
		echo "<table cellpadding='0' cellspacing='0' class='center'>\n<tr>\n";
		echo "<td class='tbl'>".$locale['u001']."<span style='color:#ff0000'>*</span></td>\n";
		echo "<td class='tbl'><input type='text' name='username' maxlength='30' class='textbox' style='width:200px;' /></td>\n";
		echo "</tr>\n<tr>\n";
		echo "<td class='tbl'>".$locale['u002']."<span style='color:#ff0000'>*</span></td>\n";
		echo "<td class='tbl'><input type='password' name='password1' maxlength='20' class='textbox' style='width:200px;' /></td>\n";
		echo "</tr>\n<tr>\n";
		echo "<td class='tbl'>".$locale['u004']."<span style='color:#ff0000'>*</span></td>\n";
		echo "<td class='tbl'><input type='password' name='password2' maxlength='20' class='textbox' style='width:200px;' /></td>\n";
		echo "</tr>\n<tr>\n";
		echo "<td class='tbl'>".$locale['u005']."<span style='color:#ff0000'>*</span></td>\n";
		echo "<td class='tbl'><input type='text' name='email' maxlength='100' class='textbox' style='width:200px;' /></td>\n";
		echo "</tr>\n<tr>\n";
		echo "<td class='tbl'>".$locale['u006']."</td>\n";
		echo "<td class='tbl'><label><input type='radio' name='hide_email' value='1' />".$locale['u007']."</label> <label><input type='radio' name='hide_email' value='0' checked='checked' />".$locale['u008']."</label></td>\n";
		echo "</tr>\n<tr>\n";
		echo "<td align='center' colspan='2'><br />\n";
		echo "<input type='submit' name='add_user' value='".$locale['480']."' class='button' /></td>\n";
		echo "</tr>\n</table>\n</form>\n";
		closetable();
	}
// View User Profile
} elseif (isset($_GET['step']) && $_GET['step'] == "view" && $user_id) {
	$result = dbquery("SELECT * FROM ".DB_USERS." WHERE user_id='".(int)$user_id."'");
	if (dbrows($result)) { $user_data = dbarray($result); } else { redirect(FUSION_SELF.$aidlink); }
	
	opentable($locale['470']);
	member_nav(member_url("view", $user_id)."|".$user_data['user_name']);
	echo "<table cellpadding='0' cellspacing='1' width='400' class='tbl-border center'>\n<tr>\n";
	if ($user_data['user_avatar'] && file_exists(IMAGES."avatars/".$user_data['user_avatar'])) {
		echo "<td rowspan='5' width='1%' class='tbl'><img src='".IMAGES."avatars/".$user_data['user_avatar']."' alt='' /></td>\n";
	}
	echo "<td width='1%' class='tbl1' style='white-space:nowrap'>".$locale['u001']."</td>\n";
	echo "<td align='right' class='tbl1'>".$user_data['user_name']."</td>\n";
	echo "</tr>\n<tr>\n";
	echo "<td width='1%' class='tbl1' style='white-space:nowrap'></td>\n";
	echo "<td align='right' class='tbl1'>".getuserlevel($user_data['user_level'])."</td>\n";
	echo "</tr>\n<tr>\n";
	echo "<td width='1%' class='tbl1' style='white-space:nowrap'>".$locale['u005']."</td>\n";
	echo "<td align='right' class='tbl1'>".hide_email($user_data['user_email'])."</td>\n";
	echo "</tr>\n<tr>\n";
	echo "<td width='1%' class='tbl1' style='white-space:nowrap'>".$locale['u040']."</td>\n";
	echo "<td align='right' class='tbl1'>".showdate("longdate", $user_data['user_joined'])."</td>\n";
	echo "</tr>\n<tr>\n";
	echo "<td width='1%' class='tbl1' style='white-space:nowrap'>".$locale['u041']."</td>\n";
	echo "<td align='right' class='tbl1'>".($user_data['user_lastvisit'] ? showdate("longdate", $user_data['user_lastvisit']) : $locale['u042'])."</td>\n";
	if ($user_data['user_status'] == "0") {
		echo "</tr>\n<tr>\n";
		echo "<td colspan='".($user_data['user_avatar'] && file_exists(IMAGES."avatars/".$user_data['user_avatar']) ? "3" : "2")."' class='tbl2' style='text-align:center;white-space:nowrap'><a href='".BASEDIR."messages.php?msg_send=".$user_data['user_id']."' title='".$locale['u043']."'>".$locale['u043']."</a></td>\n";
	}
	echo "</tr>\n</table>\n";

	echo "<div style='margin:5px'></div>\n";
	
	$profile_method = "display"; $i = 0; $user_cats = array(); $user_fields = array(); $ob_active = false;
	$result2 = dbquery(
		"SELECT tuf.field_name, tuf.field_cat, tufc.field_cat_name FROM ".DB_USER_FIELDS." tuf
		INNER JOIN ".DB_USER_FIELD_CATS." tufc ON tuf.field_cat = tufc.field_cat_id
		ORDER BY field_cat_order, field_order"
	);
	if (dbrows($result2)) {
		while($data2 = dbarray($result2)) {
			if ($i != $data2['field_cat']) {
				if ($ob_active) {
					$user_fields[$i] = ob_get_contents();
					ob_end_clean();
					$ob_active = false;
				}
				$i = $data2['field_cat'];
				$user_cats[] = array(
					"field_cat_name" => $data2['field_cat_name'],
					"field_cat" => $data2['field_cat']
				);
			}
			if (!$ob_active) {
				ob_start();
				$ob_active = true;
			}
			if (file_exists(LOCALE.LOCALESET."user_fields/".$data2['field_name'].".php")) {
				include LOCALE.LOCALESET."user_fields/".$data2['field_name'].".php";
			}
			if (file_exists(INCLUDES."user_fields/".$data2['field_name']."_include.php")) {
				include INCLUDES."user_fields/".$data2['field_name']."_include.php";
			}
		}
	}

	if ($ob_active) {
		$user_fields[$i] = ob_get_contents();
		ob_end_clean();
	}

	foreach ($user_cats as $category) {
		if (array_key_exists($category['field_cat'], $user_fields) && $user_fields[$category['field_cat']]) {
			echo "<div style='margin:5px'></div>\n";
			echo "<table cellpadding='0' cellspacing='1' width='400' class='tbl-border center'>\n<tr>\n";
			echo "<td colspan='2' class='tbl2'><strong>".$category['field_cat_name']."</strong></td>\n";
			echo "</tr>\n".$user_fields[$category['field_cat']];
			echo "</table>\n";
		}
	}
	
	echo "<div style='margin:5px'></div>\n";
	echo "<table cellpadding='0' cellspacing='1' width='400' class='tbl-border center'>\n<tr>\n";
	echo "<td colspan='2' class='tbl2'><strong>".$locale['u048']."</strong></td>\n";
	echo "</tr>\n<tr>\n";		
	echo "<td width='1%' class='tbl1' style='white-space:nowrap'>".$locale['u049']."</td>\n";
	echo "<td align='right' class='tbl1'>".$user_data['user_ip']."</td>\n";
	echo "</tr>\n<tr>\n";
	echo "<td width='1%' class='tbl1' style='white-space:nowrap' colspan='2' align='center'><a href='".FUSION_SELF.$aidlink."&amp;step=log&amp;user_id=".$user_id."'>".$locale['519']."</a></td>\n";
	echo "</tr>\n</table>\n";
	closetable();
// Edit User Profile
} elseif (isset($_GET['step']) && $_GET['step'] == "edit" && $user_id) {
	$user_data = dbarray(dbquery("SELECT * FROM ".DB_USERS." WHERE user_id='".(int)$_GET['user_id']."'"));
	if (!$user_data || $user_data['user_level'] == nSUPERADMIN) { redirect(FUSION_SELF.$aidlink); }
	if (isset($_POST['savechanges'])) {
		require_once "updateuser.php";
		if ($error == "") {
			opentable($locale['430']);
			echo "<div style='text-align:center'><br />\n";
			echo $locale['431']."<br /><br />\n";
			echo "<a href='members.php".$aidlink."'>".$locale['432']."</a><br /><br />\n";
			echo "<a href='index.php".$aidlink."'>".$locale['433']."</a><br /><br />\n";
			echo "</div>\n";
			closetable();
		} else {
			opentable($locale['430']);
			echo "<div style='text-align:center'><br />\n";
			echo $locale['434']."<br /><br />\n".$error."<br />\n";
			echo "<a href='members.php".$aidlink."'>".$locale['432']."</a><br /><br />\n";
			echo "<a href='index.php".$aidlink."'>".$locale['433']."</a><br /><br />\n";
			echo "</div>\n";
			closetable();
		}
	} else {
		require_once INCLUDES."bbcode_include.php";
		$offset_list = "";
		for ($i = -13; $i < 17; $i++) {
			if ($i > 0) { $offset = "+".$i; } else { $offset = $i; }
			$offset_list .= "<option".($offset == $data['user_offset'] ? " selected='selected'" : "").">".$offset."</option>\n";
		}
		opentable($locale['430']);
		member_nav(member_url("edit", $user_id)."| ".$locale['430']);
		echo "<form name='inputform' method='post' action='".FUSION_SELF.$aidlink."&amp;step=edit&amp;user_id=".$_GET['user_id']."' enctype='multipart/form-data'>\n";
		echo "<table cellpadding='0' cellspacing='0' class='center'>\n";
		echo "<tr>\n<td class='tbl'>".$locale['u001'].":<span style='color:#ff0000'>*</span></td>\n";
		echo "<td class='tbl'><input type='text' name='user_name' value='".$user_data['user_name']."' maxlength='30' class='textbox' style='width:200px;' /></td>\n";
		echo "</tr>\n<tr>\n";
		echo "<td class='tbl'>".$locale['u003'].":</td>\n";
		echo "<td class='tbl'><input type='password' name='user_new_password' maxlength='20' class='textbox' style='width:200px;' /></td>\n";
		echo "</tr>\n<tr>\n";
		echo "<td class='tbl'>".$locale['u004'].":</td>\n";
		echo "<td class='tbl'><input type='password' name='user_new_password2' maxlength='20' class='textbox' style='width:200px;' /></td>\n";
		echo "</tr>\n<tr>\n";
		echo "<td class='tbl'>".$locale['u005'].":<span style='color:#ff0000'>*</span></td>\n";
		echo "<td class='tbl'><input type='text' name='user_email' value='".$user_data['user_email']."' maxlength='100' class='textbox' style='width:200px;' /></td>\n";
		echo "</tr>\n<tr>\n";
		echo "<td class='tbl'>".$locale['u006'].":</td>\n";
		echo "<td class='tbl'><input type='radio' name='user_hide_email' value='1'".($user_data['user_hide_email'] == "1" ? " checked='checked'" : "")." />".$locale['u007']." ";
		echo "<input type='radio' name='user_hide_email' value='0'".($user_data['user_hide_email'] == "0" ? " checked='checked'" : "")." />".$locale['u008']."</td>\n";
		echo "</tr>\n";
		
		if (!$user_data['user_avatar']) {
			echo "<tr>\n";
			echo "<td valign='top' class='tbl'>".$locale['u010'].":</td>\n";
			echo "<td class='tbl'><input type='file' name='user_avatar' class='textbox' style='width:200px;' /><br />\n";
			echo "<span class='small2'>".$locale['u011']."</span><br />\n";
			echo "<span class='small2'>".sprintf($locale['u012'], parsebytesize(30720), 100, 100)."</span></td>\n";
			echo "</tr>\n";
		} else {
			echo "<tr>\n";
			echo "<td valign='top' class='tbl'>".$locale['u010'].":</td>\n";
			echo "<td class='tbl'><img src='".IMAGES."avatars/".$user_data['user_avatar']."' alt='".$locale['u010']."' /><br />\n";
			echo "<input type='checkbox' name='del_avatar' value='y' /> ".$locale['u013']."\n";
			echo "<input type='hidden' name='user_avatar' value='".$user_data['user_avatar']."' /></td>\n";
			echo "</tr>\n";
		}

		$profile_method = "input"; $i = 0; $user_cats = array(); $user_fields = array(); $ob_active = false;
		$result2 = dbquery(
			"SELECT tuf.field_cat, tuf.field_name, tufc.field_cat_name FROM ".DB_USER_FIELDS." tuf
			INNER JOIN ".DB_USER_FIELD_CATS." tufc ON tuf.field_cat = tufc.field_cat_id
			ORDER BY field_cat_order, field_order"
		);
		if (dbrows($result2)) {
			while($data2 = dbarray($result2)) {
				if ($i != $data2['field_cat']) {
					if ($ob_active) {
						$user_fields[$i] = ob_get_contents();
						ob_end_clean();
						$ob_active = false;
					}
					$i = $data2['field_cat'];
					$user_cats[] = array(
						"field_cat_name" => $data2['field_cat_name'],
						"field_cat" => $data2['field_cat']
					);
				}
				if (!$ob_active) {
					ob_start();
					$ob_active = true;
				}
				if (file_exists(LOCALE.LOCALESET."user_fields/".$data2['field_name'].".php")) {
					include_once LOCALE.LOCALESET."user_fields/".$data2['field_name'].".php";
				}elseif(file_exists(LOCALE."English/user_fields/".$data2['field_name'].".php")) { // Pimped
					include_once LOCALE."English/user_fields/".$data2['field_name'].".php";
				}
				if (file_exists(INCLUDES."user_fields/".$data2['field_name']."_include.php")) {
					include INCLUDES."user_fields/".$data2['field_name']."_include.php";
				}
			}
		}
		
		if ($ob_active) {
			$user_fields[$i] = ob_get_contents();
			ob_end_clean();
		}
		
		foreach ($user_cats as $category) {
			if (array_key_exists($category['field_cat'], $user_fields) && $user_fields[$category['field_cat']]) {
				echo "<tr>\n";
				echo "<td colspan='2' class='tbl2'><strong>".$category['field_cat_name']."</strong></td>\n";
				echo "</tr>\n".$user_fields[$category['field_cat']];
			}
		}
		
		echo "<tr>\n<td align='center' colspan='2' class='tbl'><br />\n";
		echo "<input type='hidden' name='user_hash' value='".$user_data['user_password']."' />\n";
		echo "<input type='submit' name='savechanges' value='".$locale['440']."' class='button' /></td>\n";
		echo "</tr>\n</table>\n</form>\n";
		closetable();
	}

// Delete User
// This code needs to be improved regarding the forum
} elseif (isset($_GET['step']) && $_GET['step'] == "delete" && $user_id) {
	$result = dbquery("SELECT user_id FROM ".DB_USERS." WHERE user_id='".$user_id."' AND user_level<'".nSUPERADMIN."'");
	if (dbrows($result)) {
		$result = dbquery("DELETE FROM ".DB_USERS." WHERE user_id='$user_id'");
		$result = dbquery("DELETE FROM ".DB_ARTICLES." WHERE article_name='$user_id'");
		$result = dbquery("DELETE FROM ".DB_COMMENTS." WHERE comment_name='$user_id'");
		$result = dbquery("DELETE FROM ".DB_MESSAGES." WHERE message_to='$user_id' OR message_from='$user_id'");
		$result = dbquery("DELETE FROM ".DB_NEWS." WHERE news_name='$user_id'");
		$result = dbquery("DELETE FROM ".DB_POLL_VOTES." WHERE vote_user='$user_id'");
		$result = dbquery("DELETE FROM ".DB_RATINGS." WHERE rating_user='$user_id'");
		$result = dbquery("DELETE FROM ".DB_SHOUTBOX." WHERE shout_name='$user_id'");
		$result = dbquery("DELETE FROM ".DB_SUSPENDS." WHERE suspended_user='$user_id'");
		$result = dbquery("DELETE FROM ".DB_THREADS." WHERE thread_author='$user_id'");
		$result = dbquery("DELETE FROM ".DB_POSTS." WHERE post_author='$user_id'");
		$result = dbquery("DELETE FROM ".DB_THREAD_NOTIFY." WHERE notify_user='$user_id'");
		redirect(USER_MANAGEMENT_SELF."&status=dok");
	} else {
		redirect(USER_MANAGEMENT_SELF."&status=der");	
	}
// Ban User
} elseif (isset($_GET['action']) && $_GET['action'] == 1 && $user_id) {
	require_once LOCALE.LOCALESET."admin/members_email.php";
	require_once INCLUDES."sendmail_include.php";
	
	$result = dbquery("SELECT user_name, user_email, user_status FROM ".DB_USERS." WHERE user_id='$user_id' AND user_level<'".nSUPERADMIN."'");
	if (dbrows($result)) {
		$udata = dbarray($result);
		if (isset($_POST['ban_user'])) {
			if ($udata['user_status'] == 1) {
				$result = dbquery("UPDATE ".DB_USERS." SET user_status='0', user_actiontime='0' WHERE user_id='$user_id'");
				unsuspend_log($user_id, 1, stripinput($_POST['ban_reason']));
				redirect(USER_MANAGEMENT_SELF."&status=bre");
			} else {
				$result = dbquery("UPDATE ".DB_USERS." SET user_status='1', user_actiontime='0' WHERE user_id='$user_id'");
				suspend_log($user_id, 1, stripinput($_POST['ban_reason']));
				$message = str_replace("[USER_NAME]", $udata['user_name'], $locale['email_ban_message']);
				$message = str_replace("[REASON]", stripinput($_POST['ban_reason']), $message);
				sendemail($udata['user_name'], $udata['user_email'], $settings['siteusername'], $settings['siteemail'], $locale['email_ban_subject'], $message);
				redirect(USER_MANAGEMENT_SELF."&status=bad");
			}
		} else {
			if ($udata['user_status'] == 1) {
				$ban_title = $locale['408']." ".$udata['user_name'];
			} else {
				$ban_title = $locale['409']." ".$udata['user_name'];
			}
			opentable($ban_title);
			echo "<form method='post' action='".stripinput(USER_MANAGEMENT_SELF)."&amp;action=1&amp;user_id=$user_id'>\n";
			echo "<table cellpadding='0' cellspacing='0' width='460' class='center'>\n<tr>\n";
			echo "<td colspan='2' class='tbl'>".$locale['585a'].$udata['user_name'].".</td>\n";
			echo "</tr>\n<tr>\n";
			echo "<td valign='top' width='80' class='tbl'>".$locale['515'].":</td>\n";
			echo "<td class='tbl'><textarea name='ban_reason' cols='60' rows='2' class='textbox' style='width:380px;'></textarea></td>\n";
			echo "</tr>\n<tr>\n";
			echo "<td colspan='2' align='center'><input type='submit' name='cancel' value='".$locale['418']."' class='button' />\n";
			echo "<input type='submit' name='ban_user' value='".$ban_title."' class='button' /></td>\n";
			echo "</tr>\n</table>\n</form>\n";
			closetable();
			display_suspend_log($user_id, 1, $rowstart, 10);
		}
	} else {
		redirect(USER_MANAGEMENT_SELF."&status=ber");
	}
// Activate User
} elseif (isset($_GET['action']) && $_GET['action'] == 2 && $user_id) {
	require_once LOCALE.LOCALESET."admin/members_email.php";
	require_once INCLUDES."sendmail_include.php";

	$result = dbquery("SELECT user_name, user_email FROM ".DB_USERS." WHERE user_id='$user_id' LIMIT 1");
	if (dbrows($result)) {
		$udata = dbarray($result);
		$result = dbquery("UPDATE ".DB_USERS." SET user_status='0', user_actiontime='0' WHERE user_id='$user_id'");
		suspend_log($user_id, 2);
		$subject = $locale['email_activate_subject'].$settings['sitename'];
		$message = str_replace("[USER_NAME]", $udata['user_name'], $locale['email_activate_message']);
		sendemail($udata['user_name'], $udata['user_email'], $settings['siteusername'], $settings['siteemail'], $subject, $message);
		redirect(USER_MANAGEMENT_SELF."&status=aok");
	} else {
		redirect(USER_MANAGEMENT_SELF."&status=aer");
	}
// Suspend User
} elseif (isset($_GET['action']) && $_GET['action'] == 3 && $user_id) {
	include LOCALE.LOCALESET."admin/members_email.php";
	require_once INCLUDES."sendmail_include.php";
	
	$result = dbquery("SELECT user_name, user_email, user_status FROM ".DB_USERS." WHERE user_id='$user_id' AND user_level<'".nSUPERADMIN."'");
	if (dbrows($result)) {
		$udata = dbarray($result);
		if (isset($_POST['suspend_user'])) {
			if ($udata['user_status'] == 3) {
				$result = dbquery("UPDATE ".DB_USERS." SET user_status='0', user_actiontime='0' WHERE user_id='$user_id'");
				unsuspend_log($user_id, 3, stripinput($_POST['suspend_reason']));
				redirect(USER_MANAGEMENT_SELF."&status=sre");
			} else {
				$actiontime = (isset($_POST['suspend_duration']) && isnum($_POST['suspend_duration']) ? $_POST['suspend_duration'] * 86400 : 864000) + time();
				$result = dbquery("UPDATE ".DB_USERS." SET user_status='3', user_actiontime='$actiontime' WHERE user_id='$user_id'");
				suspend_log($user_id, 3, stripinput($_POST['suspend_reason']));
				$message = str_replace("[USER_NAME]", $udata['user_name'], $locale['email_suspend_message']);
				$message = str_replace("[DATE]", showdate('longdate', $actiontime), $message);
				$message = str_replace("[REASON]", stripinput($_POST['suspend_reason']), $message);
				sendemail($udata['user_name'], $udata['user_email'], $settings['siteusername'], $settings['siteemail'], $locale['email_suspend_subject'], $message);
				redirect(USER_MANAGEMENT_SELF."&status=sad");
			}
		} else {
			if ($udata['user_status'] == 3) {
				$suspend_title = $locale['591']." ".$udata['user_name'];
				$action = $locale['593'];
			} else {
				$suspend_title = $locale['590']." ".$udata['user_name'];
				$action = $locale['592'];
			}
			opentable($suspend_title);
			echo "<form method='post' action='".stripinput(USER_MANAGEMENT_SELF)."&amp;action=3&amp;user_id=$user_id'>\n";
			echo "<table cellpadding='0' cellspacing='0' width='460' class='center'>\n<tr>\n";
			echo "<td colspan='2' class='tbl'>".$locale['594'].$action.$locale['595'].$udata['user_name'].".</td>\n";
			if ($udata['user_status'] != 3) {
				echo "</tr>\n<tr>\n";
				echo "<td valign='top' width='80' class='tbl'>".$locale['596']."</td>\n";
				echo "<td class='tbl'><input type='text' name='suspend_duration' class='textbox' style='width:60px;' /> <span class='small'>(".$locale['551'].")</span></td>\n";
			}
			echo "</tr>\n<tr>\n";
			echo "<td valign='top' width='80' class='tbl'>".$locale['552']."</td>\n";
			echo "<td class='tbl'><textarea name='suspend_reason' cols='60' rows='2' class='textbox' style='width:380px;'></textarea></td>\n";
			echo "</tr>\n<tr>\n";
			echo "<td colspan='2' align='center'><input type='submit' name='cancel' value='".$locale['418']."' class='button' />\n";
			echo "<input type='submit' name='suspend_user' value='".$suspend_title."' class='button' /></td>\n";
			echo "</tr>\n</table>\n</form>\n";
			closetable();
			display_suspend_log($user_id, 3, 10, 10);
		}
	} else {
		redirect(USER_MANAGEMENT_SELF."&status=ser");
	}
// Security Ban User
} elseif (isset($_GET['action']) && $_GET['action'] == 4 && $user_id) {
	require_once INCLUDES."sendmail_include.php";
	
	$result = dbquery("SELECT user_name, user_email, user_status FROM ".DB_USERS." WHERE user_id='$user_id' AND user_level<'".nSUPERADMIN."'");
	if (dbrows($result)) {
		$udata = dbarray($result);
		if (isset($_POST['sban_user'])) {
			if ($udata['user_status'] == 4) {
				$result = dbquery("UPDATE ".DB_USERS." SET user_status='0', user_actiontime='0' WHERE user_id='$user_id'");
				unsuspend_log($user_id, 4, stripinput($_POST['sban_reason']));
				redirect(USER_MANAGEMENT_SELF."&status=sbre");
			} else {
				$result = dbquery("UPDATE ".DB_USERS." SET user_status='4', user_actiontime='0' WHERE user_id='$user_id'");
				suspend_log($user_id, 4, stripinput($_POST['sban_reason']));
				$message = str_replace("[USER_NAME]", $udata['user_name'], $locale['email_secban_message']);
				sendemail($udata['user_name'], $data['user_email'], $settings['siteusername'], $settings['siteemail'], $locale['email_secban_subject'], $message);
				redirect(USER_MANAGEMENT_SELF."&status=sbad");
			}
		} else {
			if ($udata['user_status'] == 4) {
				$ban_title = $locale['602'].$udata['user_name'];
				$action = $locale['603'];
			} else {
				$ban_title = $locale['600']." ".$udata['user_name'];
				$action = $locale['601'];
			}
			opentable($ban_title);
			echo "<form method='post' action='".stripinput(USER_MANAGEMENT_SELF)."&amp;action=4&amp;user_id=$user_id'>\n";
			echo "<table cellpadding='0' cellspacing='0' width='460' class='center'>\n<tr>\n";
			echo "<td colspan='2' class='tbl'>".$locale['594'].$action.$locale['595'].$udata['user_name'].".</td>\n";
			echo "</tr>\n<tr>\n";
			echo "<td valign='top' width='80' class='tbl'>".$locale['604']."</td>\n";
			echo "<td class='tbl'><textarea name='sban_reason' cols='60' rows='2' class='textbox' style='width:380px;'></textarea></td>\n";
			echo "</tr>\n<tr>\n";
			echo "<td colspan='2' align='center'><input type='submit' name='cancel' value='".$locale['418']."' class='button' />\n";
			echo "<input type='submit' name='sban_user' value='".$ban_title."' class='button' /></td>\n";
			echo "</tr>\n</table>\n</form>\n";
			closetable();
			display_suspend_log($user_id, 4, 10, 10);
		}
	} else {
		redirect(USER_MANAGEMENT_SELF."&status=sber");
	}
// Cancel User
} elseif (isset($_GET['action']) && $_GET['action'] == 5 && $user_id) {
	$result = dbquery("SELECT user_status FROM ".DB_USERS." WHERE user_id='$user_id' AND user_level<'".nSUPERADMIN."'");
	if (dbrows($result)) {
		$udata = dbarray($result);
		if ($udata['user_status'] == 5) {
			$result = dbquery("UPDATE ".DB_USERS." SET user_status='0', user_actiontime='0' WHERE user_id='$user_id'");
			unsuspend_log($user_id, 5);
		} else {
			$result = dbquery("UPDATE ".DB_USERS." SET user_status='5', user_actiontime='".$response_required."' WHERE user_id='$user_id'");
			suspend_log($user_id, 5);
		}
		redirect(USER_MANAGEMENT_SELF);
	} else {
		redirect(USER_MANAGEMENT_SELF);
	}
// Annonymise User
} elseif (isset($_GET['action']) && $_GET['action'] == 6 && $user_id) {
	$result = dbquery("SELECT user_status FROM ".DB_USERS." WHERE user_id='$user_id' AND user_level<'".nSUPERADMIN."'");
	if (dbrows($result)) {
		$udata = dbarray($result);
		if ($udata['user_status'] == 6) {
			$result = dbquery("UPDATE ".DB_USERS." SET user_status='0', user_actiontime='0' WHERE user_id='$user_id'");
			unsuspend_log($user_id, 6);
		} else {
			$result = dbquery("UPDATE ".DB_USERS." SET user_status='6', user_actiontime='0' WHERE user_id='$user_id'");
			suspend_log($user_id, 6);
		}
		redirect(USER_MANAGEMENT_SELF);
	} else {
		redirect(USER_MANAGEMENT_SELF);
	}
// Deactivate User
} elseif (isset($_GET['action']) && $_GET['action'] == 7 && $user_id) {
	$result = dbquery("SELECT user_status FROM ".DB_USERS." WHERE user_id='$user_id' AND user_level<'".nSUPERADMIN."'");
	if (dbrows($result)) {
		$udata = dbarray($result);
		if ($udata['user_status'] == 7) {
			$result = dbquery("UPDATE ".DB_USERS." SET user_status='0', user_actiontime='0' WHERE user_id='$user_id'");
			unsuspend_log($user_id, 7);
		} else {
			require_once LOCALE.LOCALESET."admin/members_email.php";
			require_once INCLUDES."sendmail_include.php";
			$code = md5($response_required.$data['user_password']);
			$message = str_replace("[CODE]", $code, $locale['email_deactivate_message']);
			$message = str_replace("[USER_NAME]", $data['user_name'], $message);
			$message = str_replace("[USER_ID]", $data['user_id'], $message);
			if (sendemail($data['user_name'], $data['user_email'], $settings['siteusername'], $settings['siteemail'], $locale['email_deactivate_subject'], $message)) {
				$result = dbquery("UPDATE ".DB_USERS." SET user_status='7', user_actiontime='".$response_required."' WHERE user_id='".$user_id."'");
				suspend_log($user_id, 7);
			}
		}
		redirect(USER_MANAGEMENT_SELF);
	} else {
		redirect(USER_MANAGEMENT_SELF);
	}

} else {
	opentable($locale['400']);
	if (isset($_GET['search_text']) && preg_check("/^[-0-9A-Z_@\s]+$/i", $_GET['search_text'])) {
		$user_name = " user_name LIKE '".stripinput($_GET['search_text'])."%' AND";
		$list_link = "search_text=".stripinput($_GET['search_text']);
	} elseif (isset($_GET['sortby']) && preg_check("/^[0-9A-Z]$/", $_GET['sortby'])) { 
		$user_name = ($_GET['sortby'] == "all" ? "" : " user_name LIKE '".stripinput($_GET['sortby'])."%' AND");
		$list_link = "sortby=".stripinput($_GET['sortby']);
	} else {
		$user_name = "";
		$list_link = "sortby=all";
		$_GET['sortby'] = "all";
	}
	
	$usr_mysql_status = $status;

	if ($status == 0 && $settings['enable_deactivation'] == 1) {
		$usr_mysql_status = "0' AND user_lastvisit>'".$time_overdue."' AND user_actiontime='0";
	} elseif ($status == 8 && $settings['enable_deactivation'] == 1) {
		$usr_mysql_status = "0' AND user_lastvisit<'".$time_overdue."' AND user_actiontime='0";
	}

	$rows = dbcount("(user_id)", DB_USERS, "$user_name user_status='$usr_mysql_status' AND user_level<'".nSUPERADMIN."'");
	$result = dbquery(
		"SELECT user_id, user_name, user_level FROM ".DB_USERS." 
		WHERE $user_name user_status='$usr_mysql_status' AND user_level<'".nSUPERADMIN."' 
		ORDER BY user_level DESC, user_name 
		LIMIT $rowstart,20"
	);
	echo "<table cellpadding='0' cellspacing='1' width='80%' class='tbl-border center'>\n<tr>\n<td class='tbl1'>\n";
	echo "<form name='viewstatus' method='get' action='".FUSION_SELF."'>\n";
	echo "<input type='hidden' name='aid' value='".iAUTH."' />\n";
	echo "<input type='hidden' name='sortby' value='".$sortby."' />\n";
	echo $locale['405']." <select name='status' class='textbox' onchange='submit()'>\n";
	for ($i = 0; $i < 9; $i++) {
		if ($i < 8 || $settings['enable_deactivation'] == 1) {
			echo "<option value='$i'".($status == $i ? " selected='selected'" : "").">".getsuspension($i)."</option>\n";
		}
	}
	echo "</select>\n";
	echo "<input type='hidden' name='page' value='".$page."' />\n</form>\n"; // Pimped
	echo "</td>\n<td class='tbl1' align='right'>\n";
	echo "<a href='".FUSION_SELF.$aidlink."&amp;step=add'>".$locale['402']."</a>\n";
	if ($settings['enable_deactivation'] == 1) {
		if (dbcount("(user_id)", DB_USERS, "user_status='0' AND user_level<'".nSUPERADMIN."' AND user_lastvisit<'$time_overdue' AND user_actiontime='0'")) {
			echo " | <a href='".FUSION_SELF.$aidlink."&amp;step=inactive'>".$locale['580']."</a>\n";
		}
	}
	echo "</td>\n</tr>\n</table>\n";
	echo "<div style='text-align:center;margin-bottom:10px;'></div>\n";
	

	if ($rows) {
		$i = 0;
		echo "<table cellpadding='0' cellspacing='1' width='80%' class='tbl-border center'>\n<tr>\n";
		echo "<td class='tbl2'><strong>".$locale['401']."</strong></td>\n";
		echo "<td align='center' width='1%' class='tbl2' style='white-space:nowrap'><strong>".$locale['403']."</strong></td>\n";
		echo "<td align='center' width='1%' class='tbl2' style='white-space:nowrap'><strong>".$locale['404']."</strong></td>\n";
		echo "</tr>\n";
		while ($data = dbarray($result)) {
			$cell_color = ($i % 2 == 0 ? "tbl1" : "tbl2"); $title = "";
			echo "<tr>\n<td class='$cell_color'><a href='".FUSION_SELF.$aidlink."&amp;step=view&amp;user_id=".$data['user_id']."'>".$data['user_name']."</a></td>\n";
			echo "<td align='center' width='1%' class='$cell_color' style='white-space:nowrap'>".getuserlevel($data['user_level'])."</td>\n";
			echo "<td align='center' width='1%' class='$cell_color' style='white-space:nowrap'>";
			echo "<a href='".FUSION_SELF.$aidlink."&amp;step=edit&amp;user_id=".$data['user_id']."'>".$locale['406']."</a>\n";
			if ($status == 0) { echo "- <a href='".stripinput(USER_MANAGEMENT_SELF."&action=3&user_id=".$data['user_id'])."'>".$locale['553']."</a>\n";
			 } elseif ($status == 2) { $title = $locale['407'];
			} elseif ($status != 8) { $title = $locale['419']; }
			if ($title) { echo "- <a href='".stripinput(USER_MANAGEMENT_SELF."&action=$status&user_id=".$data['user_id'])."'>$title</a>\n"; }
			echo "- <a href='".stripinput(USER_MANAGEMENT_SELF."&step=delete&user_id=".$data['user_id'])."' onclick='return DeleteMember();'>".$locale['410']."</a>\n"; 
			if ($status == 0) {
				echo "<form name='editstatus_".$data['user_id']."' method='get' action='".FUSION_SELF."'>\n";
				echo "<input type='hidden' name='aid' value='".iAUTH."' />\n";
				echo "<input type='hidden' name='sortby' value='".$sortby."' />\n";
				echo "<input type='hidden' name='status' value='".$status."' />\n";
				echo "<input type='hidden' name='page' value='".$page."' />\n"; // Pimped
				echo "<select name='action' class='textbox' onchange='submit()'>\n";
				echo "<option value='' selected='selected'>-- ".$locale['417']." --</option>\n";
				for ($ii = 1; $ii < 8; $ii++) {
					if ($ii != 2 && $ii != 4) { echo "<option value='$ii'>".getsuspension($ii, true)."</option>\n"; }
				}
				echo "</select>\n";
				echo "<input type='hidden' name='user_id' value='".$data['user_id']."' />\n";
				echo "</form>\n";
			}
			echo "</td>\n</tr>\n"; $i++;
		}
		echo "</table>\n";
	} else {
		if (isset($_GET['search_text']) && preg_check("/^[-0-9A-Z_@\s]+$/i", $_GET['search_text'])) {
			echo "<div style='text-align:center'><br />".sprintf($locale['411'], ($status == 0 ? "" : getsuspension($status))).$locale['413']."'".stripinput($_GET['search_text'])."'<br /><br />\n</div>\n";
		} else {
			echo "<div style='text-align:center'><br />".sprintf($locale['411'], ($status == 0 ? "" : getsuspension($status))).($_GET['sortby'] == "all" ? "" : $locale['412'].$_GET['sortby']).".<br /><br />\n</div>\n";
		}
	}
	$alphanum = array(
		"A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R",
		"S","T","U","V","W","X","Y","Z","0","1","2","3","4","5","6","7","8","9"
	);
	echo "<div style='margin-top:10px;'></div>\n";
	echo "<table cellpadding='0' cellspacing='1' width='450' class='tbl-border center'>\n<tr>\n";
	echo "<td rowspan='2' class='tbl2'><a href='".FUSION_SELF.$aidlink."&amp;status=".$status."'>".$locale['414']."</a></td>";
	for ($i = 0; $i < 36; $i++) {
		echo "<td align='center' class='tbl1'><div class='small'><a href='".FUSION_SELF.$aidlink."&amp;sortby=".$alphanum[$i]."&amp;status=$status'>".$alphanum[$i]."</a></div></td>";
		echo ($i == 17 ? "<td rowspan='2' class='tbl2'><a href='".FUSION_SELF.$aidlink."&amp;status=".$status."'>".$locale['414']."</a></td>\n</tr>\n<tr>\n" : "\n");
	}
	echo "</tr>\n</table>\n";
	echo "<hr />\n<form name='searchform' method='get' action='".FUSION_SELF."'>\n";
	echo "<div style='text-align:center'>\n";
	echo "<input type='hidden' name='aid' value='".iAUTH."' />\n";
	echo "<input type='hidden' name='status' value='$status' />\n";
	echo $locale['415']." <input type='text' name='search_text' class='textbox' style='width:150px'/>\n";
	echo "<input type='submit' name='search' value='".$locale['416']."' class='button' />\n";
	echo "</div>\n</form>\n";
	closetable();
	if ($rows > 20) { echo "<div align='center' style='margin-top:5px;'>\n".pagination(true,$rowstart,20,$rows,3,FUSION_SELF.$aidlink."&amp;sortby=".$sortby."&amp;status=".$status."&amp;")."\n</div>\n"; }
	echo "<script type='text/javascript'>"."\n"."function DeleteMember(username) {\n";
	echo "return confirm('".$locale['423']."');\n}\n</script>\n";
}

require_once TEMPLATES."footer.php";
?>