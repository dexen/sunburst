<?php

class SQLiteIndex
	implements HCtxProvider
{
	protected $In;
	protected $name;

	function __construct(SQLiteInstance $In, string $name)
	{
		$this->In = $In;
		$this->name = $name;
	}

	function namePretty() { return $this->name; }

	function name() { return $this->name; }

	function hctxSelector() : string { return $this->name; }
}

class SQLiteQuery
	implements HCtxProvider
{
	protected $In;
	protected $sql;

	function __construct(SQLiteInstance $In, string $sql)
	{
		$this->In = $In;
		$this->sql = $sql;
	}

	function sql() : string { return $this->sql; }

	function hctxSelector() : string { return 'freehand'; }
}

class SQLiteRow
	implements HCtxProvider
{
	protected $Tb;
	protected $rowid;

	function __construct(SQLiteTable $Tb, int $rowid)
	{
		$this->Tb = $Tb;
		$this->rowid = $rowid;
	}
	function hctxSelector() : string { return $this->rowid; }

	function namePretty() { return '#' .$this->rowid; }

	function rowid() { return $this->rowid; }
}

class SQLiteView
	implements HCtxProvider
{
	protected $In;
	protected $name;

	function __construct(SQLiteInstance $In, string $name)
	{
		$this->In = $In;
		$this->name = $name;
	}

	function namePretty() { return $this->name; }

	function name() { return $this->name; }

	function hctxSelector() : string { return $this->name; }
}

class SQLiteTable
	implements HCtxProvider
{
	protected $In;
	protected $name;

	function __construct(SQLiteInstance $In, string $name)
	{
		$this->In = $In;
		$this->name = $name;
	}

	function namePretty() { return $this->name; }

	function name() { return $this->name; }

	function hctxSelector() : string { return $this->name; }
}

class SQLiteInstance
	implements HCtxProvider
{
	protected $pn;
	protected $DB;

	function __construct(string $pn)
	{
		$this->pn = $pn;
		$this->DB = new SQLiteDB($pn);
	}

	static
	function filePathnameSafety(string $unsafe_pathname) : string
	{
		$filename = basename($unsafe_pathname);
		if ($filename !== $unsafe_pathname)
			throw new \Exception('path components not supported at present');

		$expected = '.sqlite';
		if (strncmp(strrev($filename), strrev($expected), strlen($expected)) !== 0)
			throw new \Exception(sprintf('expected "%s" extension not found', $expected));

		return $filename;
	}

	function namePretty() { return basename($this->pn); }

	function pathname() { return $this->pn; }

	function DB() { return $this->DB; }

	function hctxSelector() : string { return $this->pathname(); }
}

class SQLiteDB extends DB {
	function __construct(string $pn)
	{
		parent::__construct($dsn=sprintf('sqlite:%s', $pn), null, null, $options=[
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		]);
	}

	function e(string $unsafe_name) : string {
		if (strpos($unsafe_name, "\x00") !== false)
			throw new \Exception('unsupported: NUL');
		$washed_name = str_replace('"', '""', $unsafe_name);
		return '"' .$washed_name .'"';
	}
}

class DB extends PDO {
	function queryFetchAll(string $sql, array $params=[])
	{
		if ($params === [])
			$St = $this->query($sql);
		else {
			$St = $this->prepare($sql);
			$St->execute($params); }
		return $St->fetchAll();
	}

	function queryFetchOne(string $sql, array $params=[])
	{
		$a = $this->queryFetchAll($sql, $params);
		switch (count($a)) {
		case 1:
			return array_shift($a);
		case 0:
			throw new \Exception('no matching records found, expected exactly least one');
		default:
			throw new \Exception('multiple matching records found, expected exactly least one'); }
	}

	function sqlParametersPlaceholders(array $a) : string
	{
		$SAFETY = function(string $param_name) : string {
			if (preg_match('^[a-zA-Z]+$', $param_names))
				return $param_name;
			else
				throw new \Exception(sprintf('unsupported parameter name format: "%s"', $param_name));
		};

		$ret = [];
		foreach ($a as $k => $v)
			if (is_int($k))
				$ret[] = '?';
			else
				$ret[] = ':' .$SAFETY($k);
		return implode(', ', $ret);
	}
}
