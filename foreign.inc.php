<?php
if ($_POST && !$error && !$_POST["add"] && !$_POST["change"] && !$_POST["change-js"]) {
	if (strlen($_GET["name"])) {
		if ($mysql->query("ALTER TABLE " . idf_escape($_GET["foreign"]) . " DROP FOREIGN KEY " . idf_escape($_GET["name"])) && $_POST["drop"]) {
			redirect($SELF . "table=" . urlencode($_GET["foreign"]), lang('Foreign key has been dropped.'));
		}
	}
	$source = array_filter($_POST["source"], 'strlen');
	ksort($source);
	$target = array();
	foreach ($source as $key => $val) {
		$target[$key] = $_POST["target"][$key];
	}
	if ($mysql->query("
		ALTER TABLE " . idf_escape($_GET["foreign"]) . " ADD FOREIGN KEY
		(" . implode(", ", array_map('idf_escape', $source)) . ")
		REFERENCES " . idf_escape($_POST["table"]) . "
		(" . implode(", ", array_map('idf_escape', $target)) . ")
		" . (in_array($_POST["on_delete"], $on_actions) ? "ON DELETE $_POST[on_delete]" : "") . "
		" . (in_array($_POST["on_update"], $on_actions) ? "ON UPDATE $_POST[on_update]" : "") . "
	")) {
		redirect($SELF . "table=" . urlencode($_GET["foreign"]), (strlen($_GET["name"]) ? lang('Foreign key has been altered.') : lang('Foreign key has been created.')));
	}
	$error = $mysql->error;
}

page_header(lang('Foreign key') . ": " . htmlspecialchars($_GET["foreign"]));

if ($_POST) {
	$row = $_POST;
	ksort($row["source"]);
	if ($_POST["add"]) {
		$row["source"][] = "";
	} elseif ($_POST["change"] || $_POST["change-js"]) {
		$row["target"] = array();
	} else {
		echo "<p class='error'>" . lang('Unable to operate foreign keys') . ": " . htmlspecialchars($error) . "</p>\n";
	}
} elseif (strlen($_GET["name"])) {
	$foreign_keys = foreign_keys($_GET["foreign"]);
	$row = $foreign_keys[$_GET["name"]];
} else {
	$row = array("table" => $_GET["foreign"], "source" => array(""));
}
?>
<form action="" method="post">
<p>
<?php echo lang('Target table'); ?>:
<select name="table" onchange="this.form['change-js'].value = '1'; this.form.submit();"><?php echo optionlist(get_vals("SHOW TABLES"), $row["table"]); ?></select>
<input type="hidden" name="change-js" value="" />
</p>
<noscript><p><input type="submit" name="change" value="<?php echo lang('Change'); ?>" /></p></noscript>
<table border="0" cellspacing="0" cellpadding="2">
<thead><tr><th><?php echo lang('Source'); ?></th><th><?php echo lang('Target'); ?></th></tr></thead>
<?php
$source = get_vals("SHOW COLUMNS FROM " . idf_escape($_GET["foreign"]));
$target = ($_GET["foreign"] === $row["table"] ? $source : get_vals("SHOW COLUMNS FROM " . idf_escape($row["table"])));
foreach ($row["source"] as $key => $val) {
	echo "<tr>";
	echo "<td><select name='source[" . intval($key) . "]'><option></option>" . optionlist($source, $val) . "</select></td>";
	echo "<td><select name='target[" . intval($key) . "]'>" . optionlist($target, $row["target"][$key]) . "</select></td>";
	echo "</tr>\n";
}
?>
<tr><th><?php echo lang('ON DELETE'); ?></th><td><select name="on_delete"><option></option><?php echo optionlist($on_actions, $row["on_delete"]); ?></select></td></tr>
<tr><th><?php echo lang('ON UPDATE'); ?></th><td><select name="on_update"><option></option><?php echo optionlist($on_actions, $row["on_update"]); ?></select></td></tr>
</table>
<p>
<input type="hidden" name="token" value="<?php echo $token; ?>" />
<input type="submit" value="<?php echo lang('Save'); ?>" />
<input type="submit" name="add" value="<?php echo lang('Add column'); ?>" />
<?php if (strlen($_GET["name"])) { ?><input type="submit" name="drop" value="<?php echo lang('Drop'); ?>" /><?php } ?>
</p>
</form>
