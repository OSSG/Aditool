<?php
# aditool - Apache Digest Tool
# Simple web-inteface for managing data in htdigest2 file
#
# Copyright (C) 2010 Fedor A. Fetisov <faf@oits.ru>, OITS Co. Ltd.
# All Rights Reserved.
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

## Configuration #####################################################################
# Digest file to operate with
define('DIGEST_FILE', '/tmp/htdigest');

# Default auth realm
define('REALM', 'admin');

# Interface language
define('LANG', 'ru');

######################################################################################
# Version
define('VERSION', '1.0.1');

# Trivial localization engine
function get_string($string) {

    $str = array(
	'en' => array(), // Don't need to localize strings for english language
	'ru' => array(
	    'Actions' => 'Операции',
	    'Add user' => 'Добавить пользователя',

	    'Can\'t close digest file: ' => 'Невозможно закрыть файл паролей: ',
	    'Can\'t create new empty digest file! See error log file for details.' => 'Невозможно создать новый пустой файл паролей! Подробности в системном журнале. ',
	    'Can\'t open digest file for reading: ' => 'Невозможно открыть файл паролей на чтение: ',
	    'Can\'t open digest file for writing! See error log file for details.' => 'Невозможно открыть файл паролей на запись! Подробности в системном журнале.',
	    'Can\'t write to digest file! See error log file for details.' => 'Невозможно записать в файл паролей! Подробности в системном журнале.',
	    'Change password' => 'Изменить пароль',

	    'Digest file doesn\'t exists and will be created on first user addition.' => 'Файл паролей не существует и будет создан при добавлении первого пользователя.',
	    'Digest file is not readable!' => 'Файл паролей не может быть прочитан!',
	    'Delete user' => 'Удалить пользователя',
	    'Digest file content:' => 'Содержимое файла паролей:',

	    'Existing users' => 'Существующие пользователи',

	    'File: ' => 'Файл: ',

	    'Generate password' => 'Придумать пароль',

	    'htdigest2 file management utility' => 'утилита управления файлом htdigest2',

	    'New empty digest file created.' => 'Создан новый пустой файл паролей.',
	    'New user' => 'Новый пользователь',

	    'Password' => 'Пароль',
	    'Password changed.' => 'Пароль изменён.',

	    'Realm' => 'Зона',
	    'Reset form' => 'Очистить форму',

	    'Such user doesn\'t exists!' => 'Такого пользователя не существует!',

	    'Unknown action requested!' => 'Запрошено неизвестное действие!',
	    'User added.' => 'Пользователь добавлен.',
	    'User deleted.' => 'Пользователь удалён.',
	    'User with such name and realm already exists!' => 'Пользователь с такими именем и зоной уже существует!',
	    'Username' => 'Имя пользователя',
	    'Username and realm can\'t be empty!' => 'Имя пользователя и зона не могут быть пустыми!',

	    'Warning! JavaScript disabled. Some secondary functions will not work.' => 'Внимание! JavaScript отключён. Некоторые второстепенные функции не работают.'
	)
    );

    return array_key_exists($string, $str[LANG]) ? $str[LANG][$string] : $string;
}


# Initialize messages and flags
$error = 0;
$error_message = '';

$warning = 0;
$message = '';

# Initialize users array with data from the given digest file
$users = array();

if (file_exists(DIGEST_FILE)) {

    if (is_file(DIGEST_FILE) && is_readable(DIGEST_FILE)) {
	if ($fh = fopen(DIGEST_FILE, 'r')) {

	    while (!feof($fh)) {
		$str = rtrim(fgets($fh));
# Skip all bad strings and parse all good ones
		if (preg_match('/^\S+:\S+:\S+$/', $str)) {
		    list($user, $realm, $digest) = explode(':', $str);
		    if ($user && $realm && $digest) {
			$users[$user . ':' . $realm] = array( 'username' => $user, 'realm' => $realm, 'digest' => $digest );
		    }
		}
	    }

	    if (!fclose($fh)) {
		$error = 1;
		$error_message = get_string('Can\'t close digest file! See error log file for details.');
	    }
	}
	else {
	    $error = 1;
	    $error_message = get_string('Can\'t open digest file for reading! See error log file for details.');
	}
    }
    else {
	$error = 1;
	$error_message = get_string('Digest file is not readable!');
    }

}
else {

    $message = get_string('Digest file doesn\'t exists and will be created on first user addition.');
    $warning = 1;

}

# If some action were requested - try to react
if (!$error && isset($_REQUEST['action'])) {
    $old_users = $users;

# Get requested data and test submitted username and realm
    $data = array();
    foreach (array('action', 'username', 'realm', 'passwd') as $param) {
	if (isset($_REQUEST[$param])) {
	    $data[$param] = $_REQUEST[$param];
	}
    }

    if (!preg_match('/^\s*$/', $data['username']) && !preg_match('/^\s*$/', $data['realm'])) {

# Adding new user (pair of username and realm should be unique)
	if ($data['action'] == 'add') {
	    if (!array_key_exists($data['username'] . ':' . $data['realm'], $users)) {
		$users[$data['username'] . ':' . $data['realm']] = array(  'username' => $data['username'],
						    'realm' => $data['realm'],
						    'digest' => md5( $data['username'] . ':' . $data['realm'] . ':' . $data['passwd'] ) );
		$message = get_string('User added.');
		$warning = 0;
	    }
	    else {
		$error = 1;
		$error_message = get_string('User with such name and realm already exists!');
	    }
	}
# Updating digest for an existing user (pair of username and realm should exist)
	elseif ($data['action'] == 'update') {
	    if (array_key_exists($data['username'] . ':' . $data['realm'], $users)) {
		$users[$data['username'] . ':' . $data['realm']]['username'] = $data['username'];
		$users[$data['username'] . ':' . $data['realm']]['realm'] = $data['realm'];
		$users[$data['username'] . ':' . $data['realm']]['digest'] = md5( $data['username'] . ':' . $data['realm'] . ':' . $data['passwd'] );
		$message = get_string('Password changed.');
	    }
	    else {
		$error = 1;
		$error_message = get_string('Such user doesn\'t exists!');
	    }
	}
# Delete an existing user (pair of username and realm should exist)
	elseif ($data['action'] == 'delete') {
	    if (array_key_exists($data['username'] . ':' . $data['realm'], $users)) {
		unset($users[$data['username'] . ':' . $data['realm']]);
		$message = get_string('User deleted.');
	    }
	    else {
		$error = 1;
		$error_message = get_string('Such user doesn\'t exists!');
	    }
	}
# Unknown action requested - something wrong
	else {
	    $error = 1;
	    $error_message = get_string('Unknown action requested!');
	}
    }
    else {
	$error = 1;
	$error_message = get_string('Username and realm can\'t be empty!');
    }

# Try to write new data to the digest file if everyting is fine
    if (!$error) {

	if ($fh = fopen(DIGEST_FILE, 'w')) {

	    $output = '';
	    foreach ($users as $user => $data) {
		$output .= $data['username'] . ':' . $data['realm'] . ':' . $data['digest'] . "\n";
	    }

	    if (fwrite($fh, $output) === FALSE) {
	    	$error = 1;
		$error_message = get_string('Can\'t write to digest file! See error log file for details. ');
	    }

	    if (!fclose($fh)) {
		$error = 1;
		$error_message = get_string('Can\'t close digest file! See error log file for details.');
	    }

	    if ($error) {
		$error_message .= '<br />' . get_string('Digest file content:') . '<br /><pre>' . htmlspecialchars($output) . '</pre>';
	    }

	}
	else {
	    $error = 1;
	    $error_message = get_string('Can\'t open digest file for writing! See error log file for details.');
	}
    }

    if ($error) {
	$users = $old_users;
    }
}

# Sort users list for output
asort($users);

?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo LANG; ?>" lang="<?php echo LANG; ?>">
    <head>
	<title>Aditool v.<?php echo htmlspecialchars(VERSION); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>

	<style type="text/css">

	    body { font-family: tahoma; font-size: 10pt; margin: 0; padding: 0; color: #111; }

	    h1 { font-family: verdana; font-size: 150%; color: #000; padding: 10px; }
	    h2 { font-family: verdana; font-size: 115%; color: #000; padding: 10px; margin: 0; }

	    pre { font-family: monospace; font-size: 10pt; padding: 5px; }

	    table { border-collapse: collapse; margin-top: 10px; margin-left: 5px; }
	    table.main_table { width: 100%; }
	    th { padding: 5px 10px; text-align: left; font-family: verdana; font-size: 10pt; font-weight: bold; }
	    th, td { vertical-align: top; }
	    th.fixed_width { width: 140px; }
	    th.action { padding-left: 115px; }
	    td { text-align: left; padding: 5px 10px; }

	    .even { background-color: #e0e0e0; }
	    .work { padding: 10px; border-top: 1px solid #d0d0d0; border-bottom: 1px solid #d0d0d0; }

	    div.table { border-bottom: 1px solid #d0d0d0; padding: 10px; }

	    div.warning { color: #aa0; background-color: #ffd; border: 1px solid #ff0; margin: 10px; padding: 0px 3px; }
	    div.error { color: #a00; background-color: #fbb; border: 1px solid #f00; margin: 10px; padding: 0px 3px; }
	    div.message { color: #080; background-color: #dfd; border: 1px solid #0f0; margin: 10px; padding: 0px 3px; }

	    div.copyright { color: #444; background-color: #eee; border-top: 1px solid #333; padding: 3px; font-size: 80%; margin-top: 25px; position: fixed; bottom: 0px; left: 0px; width: 100%; }
	    div.copyright a, div.copyright a:visited { text-decoration: none; color: #444; }
            div.copyright a:hover, div.copyright a:active { text-decoration: underline; }

	    div.password_head { position: relative; float: left; clear: left; top: 0; left: -105px; }

	    div.password { position: relative; float: left; clear: left; left: 0px; top: 0; width: 140px; }
	    div.buttons { padding-left: 160px; }

	    form { display: inline; }

	    input.field { padding: 1px; font-size: 90%; width: 130px; }
	    input.button { vertical-align: top; background-color: #dadada; font-size: 90%; }

	    span.file { font-style: italic; font-size: 85%; margin-left: 10px; }

	</style>

    </head>
    <body>

<script type="text/javascript" language="javascript">
//<![CDATA[

    function generate_passwd(form) {
	str = '';
	for (i = 0; i < 6; i++) {
	    var n= Math.floor(Math.random()*62);
	    if (n<10) { str += n; }
	    else if (n<36) { str += String.fromCharCode(n+55); }
	    else { str += String.fromCharCode(n+61); }
	}
	form.passwd.value = str;
    }

//]]>
</script>

<h1>Aditool - <?php echo get_string('htdigest2 file management utility'); ?></h1>
<span class="file"><?php echo get_string('File: ') . htmlspecialchars(DIGEST_FILE); ?></span>

<noscript>
<div class="warning"><?php echo get_string('Warning! JavaScript disabled. Some secondary functions will not work.'); ?></div>
</noscript>

<?php if ($error) { ?>
<div class="error"><?php echo $error_message; ?></div>
<?php }
elseif ($message) { ?>
<div class="<?php if ($warning) { ?>warning<?php } else { ?>message<?php } ?>"><?php echo $message; ?></div>
<?php } ?>

<div class="work">

<h2><?php echo get_string('New user'); ?></h2>

<form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" id="creation_form" name="creation_form" method="post" enctype="multipart/form-data">

<table>
    <tr>
	<th class="fixed_width"><?php echo get_string('Username'); ?></th>
	<th class="fixed_width"><?php echo get_string('Realm'); ?></th>
	<th class="fixed_width"><?php echo get_string('Password'); ?></th>
	<th><?php echo get_string('Actions'); ?></th>
    </tr>
    <tr>
	<td><input type="text" name="username" class="field" value="" size="16" title="<?php echo get_string('Username'); ?>" /></td>
	<td><input type="text" name="realm" class="field" value="<?php echo htmlspecialchars(REALM); ?>" size="16" title="<?php echo get_string('Realm'); ?>" /></td>
	<td><input type="text" name="passwd" class="field" value="" size="16" title="<?php echo get_string('Password'); ?>" /></td>
	<td>
    <input type="submit" value="<?php echo get_string('Add user'); ?>" class="button" />
    <input type="button" value="<?php echo get_string('Generate password'); ?>" class="button" onclick="generate_passwd(this.form)" />
    <input type="reset" value="<?php echo get_string('Reset form'); ?>" class="button" />
	</td>
    </tr>
</table>

    <input type="hidden" name="action" value="add" />
</form>

</div>

<?php if (count($users)) { ?>

<div class="table">

<h2><?php echo get_string('Existing users'); ?></h2>

<?php $counter = 0; ?>

<table class="main_table">
    <tr>
	<th class="fixed_width"><?php echo get_string('Username'); ?></th>
	<th class="fixed_width"><?php echo get_string('Realm'); ?></th>
	<th colspan="2" class="action"> <?php echo get_string('Actions'); ?>
			    <div class="password_head"><?php echo get_string('Password'); ?></div>
	</th>
    </tr>

<?php foreach ($users as $user => $data) { ?>

<tr class="work<?php if (!($counter % 2)) {?> even<?php } ?>">

    <td><?php echo htmlspecialchars($data['username']); ?></td>
    <td><?php echo htmlspecialchars($data['realm']); ?></td>

    <td>

<form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" id="update_form_<?php echo $counter; ?>" name="update_form_<?php echo $counter; ?>" method="post" enctype="multipart/form-data">

<div class="password">
    <input type="text" name="passwd" class="field" value="" size="16" title="<?php echo get_string('Password'); ?>" />
</div>

<div class="buttons">
    <input type="submit" value="<?php echo get_string('Change password'); ?>" class="button" />
    <input type="button" value="<?php echo get_string('Generate password'); ?>" class="button" onclick="generate_passwd(this.form)" />
    <input type="reset" value="<?php echo get_string('Reset form'); ?>" class="button" />
</div>

    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="username" value="<?php echo htmlspecialchars($data['username']); ?>" />
    <input type="hidden" name="realm" value="<?php echo htmlspecialchars($data['realm']); ?>" />
</form>

    </td>

    <td>
<form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" id="delete_form_<?php echo $counter; ?>" name="delete_form_<?php echo $counter; ?>" method="post" enctype="multipart/form-data">
    <input type="submit" value="<?php echo get_string('Delete user'); ?>" class="button" />
    <input type="hidden" name="action" value="delete" />
    <input type="hidden" name="username" value="<?php echo htmlspecialchars($data['username']); ?>" />
    <input type="hidden" name="realm" value="<?php echo htmlspecialchars($data['realm']); ?>" />
</form>
    </td>

</tr>

<?php $counter++; ?>

<?php } ?>

</table>

</div>

<?php } ?>

<div class="copyright">&copy; 2010 <a href="http://www.oits.ru/">OITS Co. Ltd.</a></div>

    </body>
</html>
