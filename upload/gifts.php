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

define("IN_MYBB", 1);
define("NO_ONLINE", 1);
define('THIS_SCRIPT', 'gifts.php');

$templatelist = 'gifts,gifts_row';

// Load MyBB core files
require_once "./global.php";

$lang->load('gifts');

add_breadcrumb($lang->gifts, 'gifts.php');

if($mybb->user['uid'] == 0)
{
	error_no_permission();
}

if(isset($mybb->input['action']))
{
	$query = $db->simple_select('gifts_sent', '*', 'deleted = 0 AND gsid='.intval($mybb->input['gsid']));
	$gift = $db->fetch_array($query);
	if($gift && (is_moderator() || $mybb->user['uid'] == $gift['toid']))
	{
		$db->update_query('gifts_sent', array('deleted' => 1), 'gsid='.intval($gift['gsid']));
		redirect('gifts.php', $lang->gifts_deleted);
	}
	else
	{
		error_no_permision();
	}
}

if(isset($mybb->input['from']))
{
	$action = 'from';
	$other = 'to';
}
elseif(isset($mybb->input['to']))
{
	$action = 'to';
	$other = 'from';
}
if(isset($action))
{
	$max = intval($mybb->settings['gifts_max']);
	$query = $db->simple_select('gifts_sent', 'COUNT(gsid) as count', $action.'id='.intval($mybb->input[$action]).' AND deleted=0');
	$count = $db->fetch_field($query, 'count');
	if(intval($mybb->input['page']) > 0)
	{
		$page = $mybb->input['page'];
		$start = ($page-1) * $max;
		$pages = $count / $max;
		$pages = ceil($pages);
		if($page > $pages)
		{
			$start = 0;
			$page = 1;
		}
	}
	else
	{
		$start = 0;
		$page = 1;
	}
	if($count > 0)
	{
		$multipage = multipage($count, $max, $page, 'gifts.php?'.$action.'='.intval($mybb->input[$action]));
	}
	else
	{
		$multipage = '';
	}

	if($mybb->input[$action] == $mybb->user['uid'])
	{
		$user = $mybb->user;
	}
	else
	{
		$query = $db->simple_select('users', '*', 'uid='.intval($mybb->input[$action]));
		$user = $db->fetch_array($query);
	}
	$title = 'gifts_'.$action.'title';
	$title = $lang->$title;
	$usertitle = 'gifts_'.$other.'user';
	$usertitle = $lang->$usertitle;
	$query = $db->query('SELECT g.*, s.gsid, s.dateline, s.comment, u.uid, u.username, u.usergroup, u.displaygroup
		FROM '.TABLE_PREFIX.'gifts_sent s
		LEFT JOIN '.TABLE_PREFIX.'gifts g ON s.gid=g.gid
		LEFT JOIN '.TABLE_PREFIX.'users u ON s.'.$other.'id=u.uid
		WHERE s.'.$action.'id='.intval($mybb->input[$action]).' AND s.deleted=0
		ORDER BY s.dateline DESC
		LIMIT '.$start.','.$max);
	if($db->num_rows($query) == 0)
	{
		$rows = '<tr><td class="trow1" colspan="5">'.$lang->gifts_list_no.'</td></tr>';
	}
	else
	{
		$rows = '';
		while($gift = $db->fetch_array($query))
		{
			$trow = alt_trow($trow);
			$gift['picture'] = htmlspecialchars_uni($gift['picture']);
			$gift['title'] = htmlspecialchars_uni($gift['title']);
			$gift['description'] = htmlspecialchars_uni($gift['description']);
			$userlink = build_profile_link(format_name($gift['username'], $gift['usergroup'], $gift['displaygroup']), $gift['uid']);
			$gift['dateline'] = my_date($mybb->settings['dateformat'], $gift['dateline']).', '.my_date($mybb->settings['timeformat'], $gift['dateline']);
			if($mybb->input[$action] == $mybb->user['uid'] || is_moderator())
			{
				$userlink .= ' ('.htmlspecialchars_uni($gift['comment']).')';
			}
			if($mybb->input['to'] == $mybb->user['uid'] || is_moderator())
			{
				$userlink .= ' [<a href="gifts.php?action=delete&gsid='.$gift['gsid'].'" onclick="return confirm(\''.addslashes($lang->gifts_confirm_delete).'\');">'.$lang->gifts_delete.'</a>]';
			}
			eval("\$rows .= \"".$templates->get('gifts_list_row')."\";");
		}
	}

	if(isset($mybb->input['uid']))
	{
		$query = $db->simple_select('users', 'username', 'uid='.intval($mybb->input['uid']));
		$username = $db->fetch_field($query, 'username');
	}

	eval("\$gifts = \"".$templates->get('gifts_list')."\";");
	output_page($gifts);
	exit;
}

if($mybb->request_method == 'post')
{
	$query = $db->simple_select('gifts', '*', 'startdate <= '.TIME_NOW.' AND (enddate >= '.TIME_NOW.' OR enddate=0) AND amount!=0 AND gid='.intval($mybb->input['gift']));
	$gift = $db->fetch_array($query);
	if(!$gift || $gift['costs'] > $mybb->user['myps'])
	{
		error($lang->gifts_gid_invalid);
	}
	if(isset($mybb->input['uid']))
	{
		$query = $db->simple_select('users', '*', 'uid='.intval($mybb->input['uid']));
		$user = $db->fetch_array($query);
		if(!$user)
		{
			error($lang->gifts_username_invalid);
		}

		$query = $db->simple_select('gifts_sent', '*', 'gid='.intval($mybb->input['gift']).' AND fromid='.intval($mybb->user['uid']).' AND toid='.intval($user['uid']));
		$check = $db->fetch_array($query);
		if($check)
		{
			error($lang->gifts_gid_sent);
		}

		$insert = array(
			'fromid' => intval($mybb->user['uid']),
			'toid' => intval($user['uid']),
			'gid' => intval($gift['gid']),
			'comment' => $db->escape_string($mybb->input['comment']),
			'dateline' => TIME_NOW
			);
		$db->insert_query('gifts_sent', $insert);

		$db->write_query('UPDATE '.TABLE_PREFIX.'users SET myps=myps-'.intval($gift['cost']).' WHERE uid='.intval($mybb->user['uid']));

		require_once MYBB_ROOT."inc/datahandlers/pm.php";
		$pmhandler = new PMDataHandler();
		$pm = array(
			'subject' => $mybb->settings['gifts_title'],
			'message' => str_replace(array('{recipient}', '{recipient_uid}', '{sender}', '{sender_uid}', '{gift}', '{comment}'), array($user['username'], $user['uid'], $mybb->user['username'], $mybb->user['uid'], $gift['title'], $mybb->input['comment']), $mybb->settings['gifts_message']),
			'icon' => 0,
			'fromid' => $mybb->settings['gifts_uid'],
			'do' => '',
			'pmid' => '',
			'to' => array($user['username']),
			'options' => array('savecopy' => 0)
			);
		$pmhandler->set_data($pm);
		if($pmhandler->validate_pm())
		{
			$pmhandler->insert_pm();
		}
		redirect('gifts.php', $lang->gifts_sent);
	}
	$query = $db->simple_select('users', '*', 'username=\''.$db->escape_string($mybb->input['username']).'\'');
	$user = $db->fetch_array($query);
	if(!$user)
	{
		error($lang->gifts_username_invalid);
	}

	$query = $db->simple_select('gifts_sent', '*', 'gid='.intval($mybb->input['gift']).' AND fromid='.intval($mybb->user['uid']).' AND toid='.intval($user['uid']));
	$check = $db->fetch_array($query);
	if($check)
	{
		error($lang->gifts_gid_sent);
	}

	add_breadcrumb($lang->gifts_send);
	$gift['picture'] = htmlspecialchars_uni($gift['picture']);
	$gift['title'] = htmlspecialchars_uni($gift['title']);
	$gift['description'] = htmlspecialchars_uni($gift['description']);
	$username = build_profile_link(format_name($user['username'], $user['usergroup'], $user['displaygroup']), $user['uid']);
	eval("\$gifts = \"".$templates->get('gifts_send')."\";");
	output_page($gifts);
	exit;
}

$query = $db->simple_select('gifts', '*', 'startdate <= '.TIME_NOW.' AND (enddate >= '.TIME_NOW.' OR enddate=0) AND amount!=0', array('order_by' => 'title'));
if($db->num_rows($query) == 0)
{
	$rows = '<tr><td class="trow1" colspan="5">'.$lang->gifts_no.'</td></tr>';
}
else
{
	$rows = '';
	while($gift = $db->fetch_array($query))
	{
		$trow = alt_trow($trow);
		$gift['picture'] = htmlspecialchars_uni($gift['picture']);
		$gift['title'] = htmlspecialchars_uni($gift['title']);
		$gift['description'] = htmlspecialchars_uni($gift['description']);
		$gift['startdate'] = my_date($mybb->settings['dateformat'], $gift['startdate']).', '.my_date($mybb->settings['timeformat'], $gift['startdate']);
		if($gift['amount'] == -1)
		{
			$gift['amount'] = $lang->gifts_no_limit;
		}
		if($gift['enddate'] == 0)
		{
			$gift['enddate'] = $lang->gifts_no_limit;
		}
		else
		{
			$gift['enddate'] = my_date($mybb->settings['dateformat'], $gift['enddate']).', '.my_date($mybb->settings['timeformat'], $gift['enddate']);
		}
		if($gift['costs'] <= $mybb->user['myps'])
		{
			$checkbox = '<input type="radio" name="gift" value="'.$gift['gid'].'" />';
		}
		else
		{
			$checkbox = '';
		}
		eval("\$rows .= \"".$templates->get('gifts_row')."\";");
	}
}

if(isset($mybb->input['uid']))
{
	$query = $db->simple_select('users', 'username', 'uid='.intval($mybb->input['uid']));
	$username = $db->fetch_field($query, 'username');
}

eval("\$gifts = \"".$templates->get('gifts')."\";");
output_page($gifts);
?>