<?php
 /**
 * This file is part of Gifts for MyBB.
 * Copyright (C) 2009-2011 StefanT (http://www.mybbcoder.info)
 * https://github.com/Stefan-ST/MyBB-Gifts
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$plugins->add_hook('admin_tools_menu', 'gifts_admin_menu');
$plugins->add_hook('admin_tools_action_handler', 'gifts_admin_handler');
$plugins->add_hook('admin_tools_permissions', 'gifts_admin_permissions');
$plugins->add_hook('member_profile_end', 'gifts_profile');

function gifts_info()
{
	return array(
		'name'					=> 'Gifts',
		'description'		=> '',
		'website'				=> 'http://www.mybbcoder.info',
		'author'				=> 'StefanT',
		'authorsite'		=> 'http://www.mybbcoder.info',
		'version'				=> '1.0',
		'compatibility'	=> '14*'
		);
}

function gifts_admin_menu($sub_menu)
{
	global $lang;
	$lang->load('../gifts');
	$sub_menu[101] = array('id' => 'gifts', 'title' => $lang->gifts, 'link' => 'index.php?module=tools/gifts');
}

function gifts_admin_handler(&$actions)
{
	$actions['gifts'] = array('active' => 'gifts', 'file' => 'gifts.php');
}


function gifts_admin_permissions(&$admin_permissions)
{
	global $db, $mybb, $lang;
	$admin_permissions['gifts'] = $lang->gifts_can;
}

function gifts_is_installed()
{
	global $db;

	if($db->table_exists('gifts'))
	{
		return true;
	}

	return false;
}

function gifts_install()
{
	global $db, $mybb;

	$db->query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."gifts` (
		`gid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`picture` varchar(250) NOT NULL,
		`title` varchar(100) NOT NULL,
		`description` text NOT NULL,
		`startdate` bigint(30) NOT NULL,
		`enddate` bigint(30) NOT NULL,
		`costs` int(5) unsigned NOT NULL,
		`amount` int(5) NOT NULL,
		PRIMARY KEY (`gid`)
		) ENGINE=MyISAM;");

	$db->query("CREATE TABLE IF NOT EXISTS `mybb_gifts_sent` (
		`gsid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`fromid` int(10) unsigned NOT NULL,
		`toid` int(10) unsigned NOT NULL,
		`gid` int(19) unsigned NOT NULL,
		`comment` text NOT NULL,
		`dateline` bigint(30) NOT NULL,
		`deleted` tinyint(1) NOT NULL DEFAULT '0',
		PRIMARY KEY (`gsid`)
		) ENGINE=MyISAM");

	$group = array(
		'name' => 'gifts',
		'title' => 'Geschenke',
		'description' => '',
		'disporder' => 26,
		'isdefault' => 0,
		);
	$gid = $db->insert_query('settinggroups', $group);

	$insert = array(
		'name' => 'gifts_uid',
		'title' => 'UID für PN-Nachrichten',
		'description' => '',
		'optionscode' => 'text',
		'value' => 1,
		'disporder' => 1,
		'gid' => $gid
		);
	$db->insert_query('settings', $insert);

	$insert = array(
		'name' => 'gifts_title',
		'title' => 'Titel für PN',
		'description' => '',
		'optionscode' => 'text',
		'value' => 'Du hast ein Geschenk bekommen!',
		'disporder' => 2,
		'gid' => $gid
		);
	$db->insert_query('settings', $insert);

	$insert = array(
		'name' => 'gifts_message',
		'title' => 'Nachricht für PNs',
		'description' => 'Du kannst {recipient}, {recipient_uid}, {sender}, {sender_uid}, {gift} und {comment} verwenden.',
		'optionscode' => 'textarea',
		'value' => 'Hallo {recipient},

{sender} hat dir das Geschenk "{gift}" geschenkt. Dazu hat es folgendes geschrieben:

{comment}',
		'disporder' => 3,
		'gid' => $gid
		);
	$db->insert_query('settings', $insert);

	$insert = array(
		'name' => 'gifts_max',
		'title' => 'Geschenke pro Seite',
		'description' => '',
		'optionscode' => 'text',
		'value' => 20,
		'disporder' => 4,
		'gid' => $gid
		);
	$db->insert_query('settings', $insert);

	rebuild_settings();
}

function gifts_activate()
{
	global $db;

	$insert = array(
		'title' => 'gifts',
		'template' => '<html>
<head>
<title>{$mybb->settings[\\\'bbname\\\']} - {$lang->gifts}</title>
{$headerinclude}
</head>
<body>
{$header}
<form action="gifts.php" method="post">
<table border="0" cellspacing="{$theme[\\\'borderwidth\\\']}" cellpadding="{$theme[\\\'tablespace\\\']}" class="tborder">
<tr>
<td class="thead" colspan="5"><strong>{$lang->gifts}</strong></td>
</tr>
<tr>
<td class="tcat" align="center"><strong>{$lang->gifts_picture}</strong></td>
<td class="tcat"><strong>{$lang->gifts_title}<br />{$lang->gifts_description}</strong></td>
<td class="tcat"><strong>{$lang->gifts_costs}<br />{$lang->gifts_amount}</strong></td>
<td class="tcat"><strong>{$lang->gifts_available}</strong></td>
<td class="tcat" align="center"><strong>{$lang->gifts_select}</strong></td>
</tr>
{$rows}
<tr>
<td class="tfoot" colspan="5">{$lang->username} <input type="text" name="username" id="username" value="{$username}" /> <input type="submit" value="{$lang->gifts_send}" /></td>
</tr>
</table>
</form>
<script type="text/javascript" src="jscripts/autocomplete.js?ver=1400"></script>
<script type="text/javascript">
<!--
	if(use_xmlhttprequest == "1")
	{
		new autoComplete("username", "xmlhttp.php?action=get_users", {valueSpan: "username"});
	}
// -->
</script>
{$footer}
</body>
</html>',
		'sid' => '-1',
		'version' => '1400',
		'dateline' => TIME_NOW
	);
	$db->insert_query('templates', $insert);

	$insert = array(
		'title' => 'gifts_row',
		'template' => '<tr>
<td class="{$trow}" width="5%" align="center"><img src="{$gift[\\\'picture\\\']}" alt="{$gift[\\\'title\\\']}" /></td>
<td class="{$trow}" width="55%"><strong>{$gift[\\\'title\\\']}</strong><br />{$gift[\\\'description\\\']}</td>
<td class="{$trow}" width="15%">{$gift[\\\'costs\\\']} {$mybb->settings[\\\'myps_name\\\']}<br />{$gift[\\\'amount\\\']} {$lang->gifts_piece}</td>
<td class="{$trow}" width="15%">{$lang->gifts_from} {$gift[\\\'startdate\\\']}<br />{$lang->gifts_to} {$gift[\\\'enddate\\\']}</td>
<td class="{$trow}" width="10%" align="center">{$checkbox}</td>
</tr>',
		'sid' => '-1',
		'version' => '1400',
		'dateline' => TIME_NOW
	);
	$db->insert_query('templates', $insert);

	$insert = array(
		'title' => 'gifts_send',
		'template' => '<html>
<head>
<title>{$mybb->settings[\\\'bbname\\\']} - {$lang->gifts_send}</title>
{$headerinclude}
</head>
<body>
{$header}
<form action="gifts.php" method="post">
<input type="hidden" name="uid" value="{$user[\\\'uid\\\']}" />
<input type="hidden" name="gift" value="{$gift[\\\'gid\\\']}" />
<table border="0" cellspacing="{$theme[\\\'borderwidth\\\']}" cellpadding="{$theme[\\\'tablespace\\\']}" class="tborder">
<tr>
<td class="thead" ><strong>{$lang->gifts_send}</strong></td>
</tr>
<tr>
<td class="trow1"><img src="{$gift[\\\'picture\\\']}" alt="{$gift[\\\'title\\\']}" /><br /><strong>{$gift[\\\'title\\\']}</strong><br />{$gift[\\\'description\\\']}</td>
</tr>
<tr>
<td class="trow2">{$lang->gifts_costs}: {$gift[\\\'costs\\\']} ({$lang->gifts_limit} {$mybb->user[\\\'myps\\\']} {$mybb->settings[\\\'myps_name\\\']})</td>
</tr>
<tr>
<td class="trow1">{$lang->username} {$username}</td>
</tr>
<tr>
<td class="trow2">{$lang->gifts_comment}:<br /><textarea name="comment" row="5" cols="50"></textarea></td>
</tr>
<tr>
<td class="tfoot"><input type="submit" value="{$lang->gifts_send}" /></td>
</tr>
</table>
</form>
{$footer}
</body>
</html>',
		'sid' => '-1',
		'version' => '1400',
		'dateline' => TIME_NOW
	);
	$db->insert_query('templates', $insert);

	$insert = array(
		'title' => 'gifts_profile',
		'template' => '<br />
<table border="0" cellspacing="{$theme[borderwidth]}" cellpadding="{$theme[tablespace]}" class="tborder">
<tr>
<td colspan="2" class="thead"><strong>{$lang->gifts}</strong></td>
</tr>
<tr>
<td class="trow1" width="40%"><strong>{$lang->gifts_got}</strong></td>
<td class="trow1" width="60%">{$got} [<a href="gifts.php?to={$memprofile[\\\'uid\\\']}">{$lang->gifts_details}</a>]</td>
</tr>
<tr>
<td class="trow2" width="40%"><strong>{$lang->gifts_given}</strong></td>
<td class="trow2" width="60%">{$given} [<a href="gifts.php?from={$memprofile[\\\'uid\\\']}">{$lang->gifts_details}</a>]</td>
</tr>
</table>',
		'sid' => '-1',
		'version' => '1400',
		'dateline' => TIME_NOW
	);
	$db->insert_query('templates', $insert);

	$insert = array(
		'title' => 'gifts_list',
		'template' => '<html>
<head>
<title>{$mybb->settings[\\\'bbname\\\']} - {$lang->gifts} {$title} {$user[\\\'username\\\']}</title>
{$headerinclude}
</head>
<body>
{$header}
{$multipage}
<table border="0" cellspacing="{$theme[\\\'borderwidth\\\']}" cellpadding="{$theme[\\\'tablespace\\\']}" class="tborder">
<tr>
<td class="thead" colspan="5"><strong>{$lang->gifts} {$title} {$user[\\\'username\\\']}</strong></td>
</tr>
<tr>
<td class="tcat" align="center"><strong>{$lang->gifts_picture}</strong></td>
<td class="tcat"><strong>{$lang->gifts_title}<br />{$lang->gifts_description}</strong></td>
<td class="tcat"><strong>{$usertitle}</strong></td>
<td class="tcat"><strong>{$lang->gifts_date}</strong></td>
</tr>
{$rows}
</table>
{$multipage}
{$footer}
</body>
</html>',
		'sid' => '-1',
		'version' => '1400',
		'dateline' => TIME_NOW
	);
	$db->insert_query('templates', $insert);

	$insert = array(
		'title' => 'gifts_list_row',
		'template' => '<tr>
<td class="{$trow}" width="5%" align="center"><img src="{$gift[\\\'picture\\\']}" alt="{$gift[\\\'title\\\']}" /></td>
<td class="{$trow}" width="55%"><strong>{$gift[\\\'title\\\']}</strong><br />{$gift[\\\'description\\\']}</td>
<td class="{$trow}" width="20%">{$userlink}</td>
<td class="{$trow}" width="20%">{$gift[\\\'dateline\\\']}</td>
</tr>',
		'sid' => '-1',
		'version' => '1400',
		'dateline' => TIME_NOW
	);
	$db->insert_query('templates', $insert);

	require '../inc/adminfunctions_templates.php';
	find_replace_templatesets('header', '#{\$lang->toplinks_help}</a></li>#', "{\$lang->toplinks_help}</a></li><li><a href=\"{\$mybb->settings['bburl']}/gifts.php\">Geschenke</a></li>");
	find_replace_templatesets('member_profile', '#{\$mypsfields}#', "{\$mypsfields}{\$gifts}");

	change_admin_permission('tools','gifts', 1);
}

function gifts_uninstall()
{
	global $db;
	$db->drop_table('gifts');
	$db->drop_table('gifts_sent');
}

function gifts_deactivate()
{
	global $db;

	require '../inc/adminfunctions_templates.php';
	find_replace_templatesets('header', '#<li><a href=\"{\$mybb->settings[\'bburl\']}/gifts.php\">Geschenke</a></li>#', "");
	find_replace_templatesets('member_profile', '#{\$gifts}#', "");

	$db->query("DELETE FROM ".TABLE_PREFIX."templates WHERE title LIKE 'gifts%'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name = 'gifts'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name LIKE 'gifts%'");

	rebuild_settings();

	change_admin_permission('tools','gifts', -1);
}

function gifts_profile()
{
	global $mybb, $db, $lang, $theme, $templates, $gifts, $memprofile;
	$lang->load('gifts');
	$query = $db->simple_select('gifts_sent', 'COUNT(gsid) AS count', 'fromid='.intval($memprofile['uid']));
	$given = $db->fetch_field($query, 'count');
	$query = $db->simple_select('gifts_sent', 'COUNT(gsid) AS count', 'toid='.intval($memprofile['uid']));
	$got = $db->fetch_field($query, 'count');
	eval("\$gifts = \"".$templates->get('gifts_profile')."\";");
}

?>