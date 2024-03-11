<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

require __DIR__ .'/' .'lib.php';
require __DIR__ .'/' .'libdb.php';
require __DIR__ .'/' .'libdatadisplay.php';

?>
<!DOCTYPE html>
<html>
<head>
<style><?= Hcdata(file_get_contents(__DIR__ .'/' .'style.css')) ?></style>
</head>
<body>
<?php

$HC = new HCtx();

if ($_GET['db']??null) {
	$In = new SQLiteInstance(SQLiteInstance::filePathnameSafety($_GET['db']));
	$HC = $HC->with('db', $In); }
else
	$In = null;

if ($In && ($_GET['query']??null)) {
	$Qr = new SQLiteQuery($In, $_POST['sql']);
	$HC = $HC->with('query', $Qr);
}

if ($In && ($_GET['table']??null)) {
	$Tb = new SQLiteTable($In, $_GET['table']);
	$HC = $HC->with('table', $Tb); }
else
	$Tb = null;
if ($Tb && ($_GET['rowid']??null)) {
	$Rw = new SQLiteRow($Tb, $_GET['rowid']);
	$HC = $HC->with('rowid', $Rw);
}

if ($HC->has('rowid')) {
	$DB = $In->DB();

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$DB->beginTransaction();
		foreach ($_POST['field'] as $field => $value) {
			$orig = $_POST['orig_field'][$field];
			$a = $DB->queryFetchAll('SELECT rowid FROM ' .$DB->e($Tb->name()) .' WHERE ' .$DB->e($field) .' = ? AND rowid = ?', [$orig, $Rw->rowid()]);
			if (empty($a))
				throw new \Exception(sprintf('cannot save changes, field "%s" changed in the meantime', $field));
			$DB->queryFetchAll('UPDATE ' .$DB->e($Tb->name()) .' SET ' .$DB->e($field) .' = ? WHERE rowid = ?', [$value, $Rw->rowid()]); }
		$DB->commit();
		header('Location: ?' .$HC->without('rowid')->toQueryString());
		http_response_code(303);
	}

	echo '<h1><a href="' .$HC->without('rowid') .'">&lt;--</a> Editing record <a href="#">' .H($Rw->namePretty()) .'</a> of table <a href="' .$HC->without('rowid') .'">' .H($Tb->namePretty()) .'</a> in <a href="' .$HC->without('rowid', 'table') .'">' .H($In->namePretty()) .'</a></h1>';

	$Rnd = new DataRowEditorRenderer();
	$Rnd->setHC($HC);
	$Rnd->setRow($Rw);
	$Rnd->setRecord($a = $DB->queryFetchOne('SELECT rowid, * FROM ' .$DB->e($Tb->name()). ' WHERE rowid = ?', [$Rw->rowid()]));
	echo $Rnd->H();
}
else if ($HC->has('table')) {
	$DB = $In->DB();

	echo '<h1><a href="' .$HC->without('table') .'">&lt;--</a> Browsing table <a href="' .$HC .'">' .H($Tb->namePretty()) .'</a> in <a href="' .$HC->without('table') .'">' .H($In->namePretty()) .'</a></h1>';

	$Rnd = new DataTableRender();
	$Rnd->setHC($HC);
	$Rnd->setTable($Tb);
	$Rnd->setRecords($a = $DB->queryFetchAll('SELECT rowid, * FROM ' .$DB->e($Tb->name())));
	echo $Rnd->H();
}
else if ($HC->has('query')) {
	echo '<h1>Editing freehand query in <a href="' .$HC->without('query') .'">' .H($In->namePretty()) .'</a></h1>';

	echo '<form method="post" action="' .$HC->with('query', 'freehand') .'">';
		$sql = $Qr->sql();
		$DB = $In->DB();

		$rows = max(5, count(explode("\n", $sql)));
		echo '<textarea name="sql" style="width: 100%" rows="' .H($rows) .'">' .H($sql) .'</textarea>';
		echo '<button type="submit" name="action" value="execute-sql" class="action-button-main">execute SQL</button>';
	echo '</form>';

	$Rnd = new DataTableRender();
	$Rnd->setRecords($DB->queryFetchAll($sql));
	$Rnd->setHC($HC);
	echo $Rnd->H();
}
else if ($HC->has('db')) {
	$DB = $In->DB();

	echo '<h1><a href="' .$HC->without('db') .'">&lt;--</a> Browsing <a href="' .$HC .'">' .H($In->namePretty()) .'</a></h1>';

	$a = $DB->queryFetchAll('SELECT * FROM sqlite_schema ORDER BY name');
	if (empty($a))
		echo '<p>No tables found in database "<code>' .H($In->namePretty()) .'</code>".</p>';

	echo '<table class="records-listing"><tbody>';
	foreach ($a as $rcd) {
		if ($rcd['type'] === 'index')
			continue;
		$rcd['num_rows'] = $DB->queryFetchOne('SELECT COUNT(*) AS count FROM ' .$DB->e($rcd['name']))['count'];
		echo '<tr>
			<td>' .H($rcd['type']) .':</td>
			<td><a href="' .$HC($rcd['type'], $rcd['name']) .'">' .H($rcd['name']) .'</a></td>
			<td class="numeric">' .H($rcd['num_rows']) .'</td>
		</tr>'; }
	echo '</tbody></table>';

	echo '<hr>';

	echo '<table class="records-listing"><tbody>';
	foreach ($a as $rcd) {
		if ($rcd['type'] !== 'index')
			continue;
		echo '<tr>
			<td>' .H($rcd['type']) .':</td>
			<td><a href="' .$HC('table', $rcd['name']) .'">' .H($rcd['name']) .'</a></td>
		</tr>'; }
	echo '</tbody></table>';

	echo '<hr>';

	echo '<form method="post" action="' .$HC->with('query', 'freehand') .'">';
		$sql = $_POST['sql'] ?? '';
		$rows = max(5, count(explode("\n", $sql)));
		echo '<textarea name="sql" style="width: 100%" rows="' .H($rows) .'">' .H($sql) .'</textarea>';
		echo '<button type="submit" name="action" value="execute-sql" class="action-button-main">execute SQL</button>';
	echo '</form>';
}
else {
	echo '<h1>Welcome to SunBurst</h1>';
	$a = glob($pattern='*.sqlite', GLOB_MARK);
	if (empty($a))
		echo '<p>No database files found through pattern "<code>' .H($pattern) .'</code>".</p>';
	echo '<table class="records-listing"><tbody>';
	foreach ($a as $fn)
		echo '<tr>
			<td>db:</a></td>
			<td><a href="' .$HC('db', $fn) .'">' .H($fn) .'</a></td>
			<td class="numeric">' .H(round(filesize($fn)/1024./1024, 2)) .' MB</td>
		</tr>';
	echo '</tbody></table>';
}
