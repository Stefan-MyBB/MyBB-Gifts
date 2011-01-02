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
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function gifts_time($string)
{
		if(!preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)\/([0-9]+)\.([0-9]+)$#', $string, $matches))
		{
			return false;
		}
		return @mktime($matches[4], $matches[5], 0, $matches[2], $matches[1], $matches[3]);
}

$page->add_breadcrumb_item($lang->gifts, 'index.php?module=tools/gifts');

$sub_tabs['gifts_home'] = array(
	'title' => $lang->gifts,
	'link' => 'index.php?module=tools/gifts',
	'description' => $lang->gifts_admin_desc
);
$sub_tabs['gifts_add'] = array(
	'title' => $lang->gifts_admin_add,
	'link' => 'index.php?module=tools/gifts&amp;action=add',
	'description' => $lang->gifts_admin_add_desc
);
$sub_tabs['gifts_log'] = array(
	'title' => $lang->gifts_admin_log,
	'link' => 'index.php?module=tools/gifts&amp;action=log',
	'description' => $lang->gifts_admin_log_desc
);

if($mybb->input['action'] == 'log_delete')
{
	if($mybb->input['no'])
	{
		admin_redirect('index.php?module=tools/gifts&amp;action=log');
	}
	if($mybb->request_method == 'post')
	{
		$query = $db->simple_select('gifts_sent', '*', 'gsid='.intval($mybb->input['gsid']));
		$gift = $db->fetch_array($query);
		if(!$gift)
		{
			flash_message($lang->gifts_list_no, 'error');
			admin_redirect('index.php?module=tools/gifts&action=log');
		}
		$db->update_query('gifts_sent', array('deleted' => 1), 'gsid='.intval($gift['gsid']));
		log_admin_action($gift['gsid']);
		flash_message($lang->gifts_deleted, 'success');
		admin_redirect('index.php?module=tools/gifts&action=log');
	}
	$page->output_confirm_action('index.php?module=tools/gifts&amp;action=log_delete&amp;gsid='.$mybb->input['gsid'], $lang->gifts_confirm_delete);
}

if($mybb->input['action'] == 'log_delete2')
{
	if($mybb->input['no'])
	{
		admin_redirect('index.php?module=tools/gifts&amp;action=log');
	}
	if($mybb->request_method == 'post')
	{
		$query = $db->simple_select('gifts_sent', '*', 'gsid='.intval($mybb->input['gsid']));
		$gift = $db->fetch_array($query);
		if(!$gift)
		{
			flash_message($lang->gifts_list_no, 'error');
			admin_redirect('index.php?module=tools/gifts&action=log');
		}
		$db->delete_query('gifts_sent', 'gsid='.intval($gift['gsid']));
		log_admin_action($gift['gsid']);
		flash_message($lang->gifts_deleted, 'success');
		admin_redirect('index.php?module=tools/gifts&action=log');
	}
	$page->output_confirm_action('index.php?module=tools/gifts&amp;action=log_delete2&amp;gsid='.$mybb->input['gsid'], $lang->gifts_confirm_delete);
}

if($mybb->input['action'] == 'add')
{
	if($mybb->request_method == 'post')
	{
		$errors = array();
		if(!trim($mybb->input['picture']))
		{
			$errors[] = $lang->gifts_picture_missing;
		}

		if(!trim($mybb->input['title']))
		{
			$errors[] = $lang->gifts_title_missing;
		}

		if(!trim($mybb->input['description']))
		{
			$errors[] = $lang->gifts_description_missing;
		}

		if(intval($mybb->input['costs']) <= 0)
		{
			$errors[] = $lang->gifts_costs_missing;
		}

		if(intval($mybb->input['amount']) < -1)
		{
			$errors[] = $lang->gifts_amount_missing;
		}

		$startdate = gifts_time($mybb->input['startdate']);
		$enddate = $mybb->input['enddate'];
		if($enddate != 0)
		{
			$enddate = gifts_time($enddate);
		}

		if(!$startdate)
		{
			$errors[] = $lang->gifts_startdate_missing;
		}

		if($enddate === false)
		{
			$errors[] = $lang->gifts_enddate_missing;
		}

		if(empty($errors))
		{
			$insert = array(
				'picture' => $db->escape_string($mybb->input['picture']),
				'title' => $db->escape_string($mybb->input['title']),
				'description' => $db->escape_string($mybb->input['description']),
				'costs' => intval($mybb->input['costs']),
				'amount' => intval($mybb->input['amount']),
				'startdate' => intval($startdate),
				'enddate' => intval($enddate)
			);
			$gid = $db->insert_query('gifts', $insert);
			log_admin_action($gid);
			flash_message($lang->gifts_added, 'success');
			admin_redirect('index.php?module=tools/gifts');
		}
	}
	$page->add_breadcrumb_item($lang->gifts_admin_add);
	$page->output_header($lang->gifts_admin_add);
	$page->output_nav_tabs($sub_tabs, 'gifts_add');
	$form = new Form('index.php?module=tools/gifts&amp;action=add', 'post', 'add', 1);
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$mybb->input['startdate'] = date('d.m.Y/H.i');
	}
	$form_container = new FormContainer($lang->gifts_admin_add);
	$form_container->output_row($lang->gifts_picture.' <em>*</em>', $lang->gifts_picture_desc, $form->generate_text_box('picture', $mybb->input['picture']), 'picture');
	$form_container->output_row($lang->gifts_title.' <em>*</em>', '', $form->generate_text_box('title', $mybb->input['title']), 'title');
	$form_container->output_row($lang->gifts_description.' <em>*</em>', '', $form->generate_text_area('description', $mybb->input['description']), 'description');
	$form_container->output_row($lang->gifts_costs.' <em>*</em>', $mybb->settings['myps_name'], $form->generate_text_box('costs', intval($mybb->input['costs'])), 'costs');
	$form_container->output_row($lang->gifts_amount.' <em>*</em>', $lang->gifts_amount_desc, $form->generate_text_box('amount', intval($mybb->input['amount'])), 'amount');
	$form_container->output_row($lang->gifts_startdate.' <em>*</em>', $lang->gifts_startdate_desc, $form->generate_text_box('startdate', $mybb->input['startdate']), 'startdate');
 	$form_container->output_row($lang->gifts_enddate.' <em>*</em>', $lang->gifts_enddate_desc, $form->generate_text_box('enddate', $mybb->input['enddate']), 'enddate');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->gifts_save);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == 'delete')
{
	if($mybb->input['no'])
	{
		admin_redirect('index.php?module=tools/gifts');
	}
	if($mybb->request_method == 'post')
	{
		$query = $db->simple_select('gifts', '*', 'gid='.intval($mybb->input['gid']));
		$gift = $db->fetch_array($query);
		if(!$gift)
		{
			flash_message($lang->gifts_list_no, 'error');
			admin_redirect('index.php?module=tools/gift');
		}
		$db->delete_query('gifts', 'gid='.intval($gift['gid']));
		$db->delete_query('gifts_sent', 'gid='.intval($gift['gid']));
		log_admin_action($gift['gid']);
		flash_message($lang->gifts_deleted, 'success');
		admin_redirect('index.php?module=tools/gifts');
	}
	$page->output_confirm_action('index.php?module=tools/gifts&amp;action=delete&amp;gid='.$mybb->input['gid'], $lang->gifts_delete_notice);
}

if($mybb->input['action'] == 'edit')
{
	$query = $db->simple_select('gifts', '*', 'gid='.intval($mybb->input['gid']));
	$gift = $db->fetch_array($query);
	if(!$gift)
	{
		flash_message($lang->gifts_gifts_list_no, 'error');
		admin_redirect('index.php?module=tools/gifts');
	}
	if($mybb->request_method == 'post')
	{
		$errors = array();
		if(!trim($mybb->input['picture']))
		{
			$errors[] = $lang->gifts_picture_missing;
		}

		if(!trim($mybb->input['title']))
		{
			$errors[] = $lang->gifts_title_missing;
		}

		if(!trim($mybb->input['description']))
		{
			$errors[] = $lang->gifts_description_missing;
		}

		if(intval($mybb->input['costs']) <= 0)
		{
			$errors[] = $lang->gifts_costs_missing;
		}

		if(intval($mybb->input['amount']) < -1)
		{
			$errors[] = $lang->gifts_amount_missing;
		}

		$startdate = gifts_time($mybb->input['startdate']);
		$enddate = $mybb->input['enddate'];
		if($enddate != 0)
		{
			$enddate = gifts_time($enddate);
		}

		if(!$startdate)
		{
			$errors[] = $lang->gifts_startdate_missing;
		}

		if($enddate === false)
		{
			$errors[] = $lang->gifts_enddate_missing;
		}

		if(empty($errors))
		{
			$insert = array(
				'picture' => $db->escape_string($mybb->input['picture']),
				'title' => $db->escape_string($mybb->input['title']),
				'description' => $db->escape_string($mybb->input['description']),
				'costs' => intval($mybb->input['costs']),
				'amount' => intval($mybb->input['amount']),
				'startdate' => intval($startdate),
				'enddate' => intval($enddate)
			);
			$db->update_query('gifts', $insert, 'gid='.intval($gift['gid']));
			log_admin_action($gift['gid']);
			flash_message($lang->gifts_added, 'success');
			admin_redirect('index.php?module=tools/gifts');
		}
	}
	$sub_tabs['gifts_edit'] = array(
		'title' => $lang->gifts_admin_edit,
		'link' => 'index.php?module=tools/gifts&amp;action=edit&amp;gid='.$gift['gid'],
		'description' => $lang->gifts_admin_edit_desc
	);
	$page->add_breadcrumb_item($lang->gifts_admin_edit);
	$page->output_header($lang->gifts_admin_edit);
	$page->output_nav_tabs($sub_tabs, 'gifts_edit');
	$form = new Form('index.php?module=tools/gifts&amp;action=edit', 'post', 'edit', 1);
	echo $form->generate_hidden_field('gid', $mybb->input['gid']);
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$mybb->input['picture'] = $gift['picture'];
		$mybb->input['title'] = $gift['title'];
		$mybb->input['description'] = $gift['description'];
		$mybb->input['costs'] = $gift['costs'];
		$mybb->input['amount'] = $gift['amount'];
		$mybb->input['startdate'] = date('d.m.Y/H.i', $gift['startdate']);
		if($gift['enddate'] == 0)
		{
			$mybb->input['enddate'] = 0;
		}
		else
		{
			$mybb->input['enddate'] = date('d.m.Y/H.i', $gift['enddate']);
		}
	}
	$form_container = new FormContainer($lang->gifts_admin_edit);
	$form_container->output_row($lang->gifts_picture.' <em>*</em>', $lang->gifts_picture_desc, $form->generate_text_box('picture', $mybb->input['picture']), 'picture');
	$form_container->output_row($lang->gifts_title.' <em>*</em>', '', $form->generate_text_box('title', $mybb->input['title']), 'title');
	$form_container->output_row($lang->gifts_description.' <em>*</em>', '', $form->generate_text_area('description', $mybb->input['description']), 'description');
	$form_container->output_row($lang->gifts_costs.' <em>*</em>', $mybb->settings['myps_name'], $form->generate_text_box('costs', intval($mybb->input['costs'])), 'costs');
	$form_container->output_row($lang->gifts_amount.' <em>*</em>', $lang->gifts_amount_desc, $form->generate_text_box('amount', intval($mybb->input['amount'])), 'amount');
	$form_container->output_row($lang->gifts_startdate.' <em>*</em>', $lang->gifts_startdate_desc, $form->generate_text_box('startdate', $mybb->input['startdate']), 'startdate');
 	$form_container->output_row($lang->gifts_enddate.' <em>*</em>', $lang->gifts_enddate_desc, $form->generate_text_box('enddate', $mybb->input['enddate']), 'enddate');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->gifts_save);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == 'log')
{
	$page->output_header($lang->gifts_admin_log);

	$page->output_nav_tabs($sub_tabs, 'gifts_log');

	$query = $db->query('SELECT g.*, s.gsid, s.dateline, s.comment, s.deleted, u.uid, u.username, u.usergroup, u.displaygroup, z.uid as touid, z.username as tousername, z.usergroup as tousergroup, z.displaygroup as todsplaygroup
		FROM '.TABLE_PREFIX.'gifts_sent s
		LEFT JOIN '.TABLE_PREFIX.'gifts g ON s.gid=g.gid
		LEFT JOIN '.TABLE_PREFIX.'users u ON s.fromid=u.uid
		LEFT JOIN '.TABLE_PREFIX.'users z ON s.toid=z.uid
		ORDER BY s.dateline DESC');

	$table = new Table;
	$table->construct_header($lang->gifts_picture);
	$table->construct_header($lang->gifts_title.'<br />'.$lang->gifts_description);
	$table->construct_header($lang->gifts_fromuser.'<br />'.$lang->gifts_comment);
	$table->construct_header($lang->gifts_touser);
	$table->construct_header($lang->gifts_action);

	if($db->num_rows($query) == 0)
	{
		$table->construct_cell($lang->gifts_no, array('colspan' => '7'));
		$table->construct_row();
	}
	else
	{
		while($gift = $db->fetch_array($query))
		{
			if($gift['deleted'] == 0)
			{
				$gift['title'] = '<strong>'.htmlspecialchars_uni($gift['title']).'</strong>';
				$delete = '<a href="index.php?module=tools/gifts&amp;action=log_delete&amp;gsid='.$gift['gsid'].'">'.$lang->gifts_delete.'</a> - ';
			}
			else
			{
				$gift['title'] = '<s>'.htmlspecialchars_uni($gift['title']).'</s>';
				$delete = '';
			}
			$table->construct_cell('<img src="../'.htmlspecialchars_uni($gift['picture']).'" alt="'.htmlspecialchars_uni($gift['title']).'" />', array('width' => '10%'));
			$table->construct_cell($gift['title'].'<br />'.htmlspecialchars_uni($gift['description']), array('width' => '40%'));
			$table->construct_cell(build_profile_link(format_name($gift['username'], $gift['usergroup'], $gift['displaygroup']), $gift['uid']).'<br />'.htmlspecialchars_uni($gift['comment']), array('width' => '20%'));
			$table->construct_cell(build_profile_link(format_name($gift['tousername'], $gift['tousergroup'], $gift['todisplaygroup']), $gift['touid']), array('width' => '15%'));
			$table->construct_cell($delete.'<a href="index.php?module=tools/gifts&amp;action=log_delete2&amp;gsid='.$gift['gsid'].'">'.$lang->gifts_delete2.'</a>', array('width' => '15%'));
			$table->construct_row();
		}
	}

	$table->output($lang->gifts);	

	$page->output_footer();
}

if(!$mybb->input['action'])
{
	$page->output_header($lang->gifts_admin);

	$page->output_nav_tabs($sub_tabs, 'gifts_home');

	$query = $db->simple_select('gifts', '*', '', array('order_by' => 'title'));

	$table = new Table;
	$table->construct_header($lang->gifts_picture);
	$table->construct_header($lang->gifts_title.'<br />'.$lang->gifts_description);
	$table->construct_header($lang->gifts_costs.'<br />'.$lang->gifts_amount);
	$table->construct_header($lang->gifts_available);
	$table->construct_header($lang->gifts_action);

	if($db->num_rows($query) == 0)
	{
		$table->construct_cell($lang->gifts_no, array('colspan' => '7'));
		$table->construct_row();
	}
	else
	{
		while($gift = $db->fetch_array($query))
		{
			if($gift['startdate'] <= TIME_NOW && ($gift['enddate'] >= TIME_NOW || $gift['enddate'] == 0))
			{
				$gift['title'] = '<strong>'.htmlspecialchars_uni($gift['title']).'</strong>';
			}
			else
			{
				$gift['title'] = '<s>'.htmlspecialchars_uni($gift['title']).'</s>';
			}
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
			$table->construct_cell('<img src="../'.htmlspecialchars_uni($gift['picture']).'" alt="'.htmlspecialchars_uni($gift['title']).'" />', array('width' => '10%'));
			$table->construct_cell($gift['title'].'<br />'.htmlspecialchars_uni($gift['description']), array('width' => '50%'));
			$table->construct_cell($gift['costs'].' '.$mybb->settings['myps_name'].'<br />'.$gift['amount'].' '.$lang->gifts_piece, array('width' => '10%'));
			$table->construct_cell($lang->gifts_from.' '.my_date($mybb->settings['dateformat'], $gift['startdate']).', '.my_date($mybb->settings['timeformat'], $gift['startdate']).'<br />'.$lang->gifts_to.' '.$gift['enddate'], array('width' => '20%'));
			$table->construct_cell('<a href="index.php?module=tools/gifts&amp;action=edit&amp;gid='.$gift['gid'].'">'.$lang->gifts_edit.'</a> - <a href="index.php?module=tools/gifts&amp;action=delete&amp;gid='.$gift['gid'].'">'.$lang->gifts_delete.'</a>', array('width' => '10%'));
			$table->construct_row();
		}
	}

	$table->output($lang->gifts);	

	$page->output_footer();
}
?>