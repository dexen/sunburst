<?php

class TabularNavigator
{
	public int $limit;
	public int $page;
	public ?string $query=null;

	function __construct(int $limit = 3, int $page = 0, string $query=null)
	{
		$this->limit = $limit;
		$this->page = $page;
		$this->query = $query;
	}
}

abstract class Renderer
{
	protected $HC;

	function setHC(HCtx $HC) { $this->HC = $HC; }

	abstract
	function H() : string;
}

class DataRowEditorRenderer extends Renderer
{
	protected $Rw;
	protected $rcd;

	function setRow(SQLiteRow $Rw) { $this->Rw = $Rw; }

	function setRecord(array $rcd) { $this->rcd = $rcd; }

	function H() : string {
		$ret = '';

		$ret .= '<form method="post" action="' .$this->HC .'">';
			foreach ($this->rcd as $k => $v) {
				if (is_int($k))
					;
				else if ($k === 'rowid')
					$ret .= '<p><label>rowid: <code>' .H($this->rcd[$k]) .'</code></label></p>';
				else
					$ret .= '
						<input type="hidden" name="orig_field[' .H($k) .']" value="' .H($this->rcd[$k]) .'"/>
						<p><label>' .H($k) .': <input name="field[' .H($k) .']" value="' .H($this->rcd[$k]) .'"/></label></p>';
			}
			$ret .= '<button type="submit" name="action" value="save" class="action-button-main">save</button>';
		$ret .= '</form>';

		return $ret;
	}
}

class DataROTableRenderer extends Renderer
{
	protected $a;

	function setRecords(array $a) { $this->a = $a; }

	function H() : string {
		$ret = '';
		$HC = $this->HC;

		$ret .= '<table class="records-listing">';
		$ret .= '<thead>' .$this->headerRowH() .'</thead>';
		$ret .= '<tbody>';

		foreach ($this->a as $rcd) {
			$ret .= '<tr>';
			if (array_key_exists('rowid', $rcd))
				$ret .= '<th><a href="' .$HC('rowid', $rcd['rowid']) .'">edit...</a></th>';
			foreach ($rcd as $k => $v)
				$ret .= $this->fieldH($rcd, $k);
			$ret .= '</tr>';
		}
		$ret .= '</tbody></table>';
		return $ret;
	}

	protected
	function fieldH(array $rcd, $key) : string {
		if (is_int($key))
			return '';
		if ($key === 'rowid')
			return '';
		if ($rcd[$key] === null)
			$H = '<em><code>NULL</code></em>';
		else if (is_string($rcd[$key]) && (strlen($rcd[$key]) > 255))
			$H = H(substr($rcd[$key], 0, 255) .'...');
		else
			$H = H($rcd[$key]);
		return '<td>' .$H .'</td>';
	}

	protected
	function headerRowH()
	{
		$ret = '';
		$ret .= '<tr>';

		foreach ($this->a as $rcd) {
			if (array_key_exists('rowid', $rcd))
				$ret .= '<th>#</th>';
			foreach ($rcd as $k => $v)
				if (is_int($k))
					;
				else if ($k === 'rowid')
					;
				else
					$ret .= '<th>' .H($k) .'</th>';
			break;
		}
		$ret .= '</tr>';
		return $ret;
	}
}

class DataTableRender extends Renderer
{
	protected $Tb;
	protected $a;
	protected $Nav;

	function setTable(SQLiteTable $Tb) { $this->Tb = $Tb; }
	function setRecords(array $a) { $this->a = $a; }
	function setNav(TabularNavigator $Nav) { $this->Nav = $Nav; }

	function H() : string {
		$ret = '';
		$HC = $this->HC;

		$ret .= '<form method="get">';
		$ret .= $HC->asHiddenInputsH();
		$ret .= '<fieldset>';
		$ret .= '<textarea readonly style="width: 100%">' .H($this->Nav->query) .'</textarea>';
		$ret .= '<label>Limit: <input name="nav[limit]" value="' .H($this->Nav->limit) .'" size="3"/></label>';
		$ret .= '<label>Page: <input name="nav[page]" value="' .H($this->Nav->page) .'" size="3"/></label>';
		$ret .= '<button type="submit">Navigate</button>';
		$ret .= ' <button type="submit" name="nav[page]" value="' .H($this->Nav->page-1) .'">&lt;--</button> ';
		$ret .= ' <button type="submit" name="nav[page]" value="' .H($this->Nav->page+1) .'">--&gt;</button>';

		$ret .= '</fieldset>';
		$ret .= '</form>';

		$ret .= '<table class="records-listing">';
		$ret .= '<thead>' .$this->headerRowH() .'</thead>';
		$ret .= '<tbody>';

		foreach ($this->a as $rcd) {
			$ret .= '<tr>';
			if (array_key_exists('rowid', $rcd))
				$ret .= '<th><a href="' .$HC('rowid', $rcd['rowid']) .'">edit...</a></th>';
			foreach ($rcd as $k => $v)
				$ret .= $this->fieldH($rcd, $k);
			$ret .= '</tr>';
		}
		$ret .= '</tbody></table>';
		return $ret;
	}

	protected
	function fieldH(array $rcd, $key) : string {
		if (is_int($key))
			return '';
		if ($key === 'rowid')
			return '';
		if ($rcd[$key] === null)
			$H = '<em><code>NULL</code></em>';
		else if (is_string($rcd[$key]) && (strlen($rcd[$key]) > 255))
			$H = H(substr($rcd[$key], 0, 255) .'...');
		else
			$H = H($rcd[$key]);
		return '<td>' .$H .'</td>';
	}

	protected
	function headerRowH()
	{
		$ret = '';
		$ret .= '<tr>';

		foreach ($this->a as $rcd) {
			if (array_key_exists('rowid', $rcd))
				$ret .= '<th>#</th>';
			foreach ($rcd as $k => $v)
				if (is_int($k))
					;
				else if ($k === 'rowid')
					;
				else
					$ret .= '<th>' .H($k) .'</th>';
			break;
		}
		$ret .= '</tr>';
		return $ret;
	}
}
