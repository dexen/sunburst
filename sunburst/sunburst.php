<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

require 'sunburst/lib.php';
require 'sunburst/libdb.php';

?>
<!DOCTYPE html>
<html>
<head>
<style><?= Hcdata(file_get_contents('sunburst/style.css')) ?></style>
</head>
<body>
<?php

$HC = new HCtx();

if ($_GET['db']??null) {
	$In = new SQLiteInstance(SQLiteInstance::filePathnameSafety($_GET['db']));
	$HC = $HC->with('db', $In); }
else
	$In = null;

if ($_GET['table']??null) {
	$Tb = new SQLiteTable($In, $_GET['table']);
}

if ($HC->has('db')) {
	$DB = $In->DB();

	echo '<h1><a href="' .$HC->without('db') .'">&lt;--</a> Browsing <a href="' .$HC .'">' .H($In->namePretty()) .'</a></h1>';

	$a = $DB->queryFetchAll('SELECT * FROM sqlite_schema WHERE type = \'table\'');
	if (empty($a))
		echo '<p>No tables found in database "<code>' .H($In->namePretty()) .'</code>".</p>';

	echo '<table class="records-listing"><tbody>';
	foreach ($a as $rcd) {
		$rcd['num_rows'] = $DB->queryFetchOne('SELECT COUNT(*) AS count FROM ' .$DB->e($rcd['name']))['count'];
		echo '<tr>
			<td>table:</td>
			<td><a href="' .$HC('table', $rcd['name']) .'">' .H($rcd['name']) .'</a></td>
			<td class="numeric">' .H($rcd['num_rows']) .'</td>
		</tr>'; }
	echo '</tbody></table>';
}
if (empty($_GET['db'])) {
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
