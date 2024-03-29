<?php

class TabularNavigator
{
	public int $limit;
	public int $page;
	public ?string $query;
	public array $sel;
	public array $params;

	function __construct(int $limit = 10, int $page = 0, string $query=null, array $sel=[])
	{
		$this->limit = $limit;
		$this->page = $page;
		$this->query = $query;
		$this->sel = $sel;
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

	protected
	function textEditorShortH(string $k) : string {
		return '<p><label>' .H($k) .': <input name="field[' .H($k) .']" value="' .H($this->rcd[$k]) .'" size="52"/></label></p>';
	}

	protected
	function textEditorLongH(string $k) : string {
		$v = $this->rcd[$k];
		$nrows = max(3, count(explode("\n", $v))+1);
		$nrows = min(10, $nrows);
		return '<div><label>' .H($k) .':<br>
			<textarea name="field[' .H($k) .']" style="width: 100%" rows="' .H($nrows) .'">' .H($v) .'</textarea></label></div>';
	}

	function H() : string {
		$ret = '';

		$ret .= '<form method="post" action="' .$this->HC .'">';
			foreach ($this->rcd as $k => $v) {
				if (is_int($k))
					;
				else if ($k === 'rowid')
					$ret .= '<p><label>rowid: <code>' .H($this->rcd[$k]) .'</code></label></p>';
				else {
					$ret .= '
						<input type="hidden" name="orig_field[' .H($k) .']" value="' .H($this->rcd[$k]) .'"/>';
					if ((strlen($this->rcd[$k]) > 48) || (count(explode("\n", $this->rcd[$k]))>1))
						$ret .= $this->textEditorLongH($k);
					else
						$ret .=$this->textEditorShortH($k); }
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
		else if (is_string($rcd[$key])) {
			$orig = $rcd[$key];
			$s = substr($orig, 0, 128);
			$s = implode("\n", array_slice(explode("\n", $s), 0, 3));
			if ($s !== $orig)
				$s .= '...';
			$H = H($s); }
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
	protected ?array $opsRecords = null;
	protected $Nav;
	protected $OpsNav;

	function setTable(SQLiteTable $Tb) { $this->Tb = $Tb; }
	function setRecords(array $a) { $this->a = $a; }
	function setOpsRecords(array $a) { $this->opsRecords = $a; }
	function setNav(TabularNavigator $Nav) { $this->Nav = $Nav; }
	function setOpsNav(TabularNavigator $OpsNav) { $this->OpsNav = $OpsNav; }

	protected
	function navH() : string {
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
		return $ret;
	}

	protected
	function opsH() : string {
		$ret = '';
		$HC = $this->HC;

		$ret .= '<fieldset>';

		$ret .= '<button name="dialog" value="insert">Insert...</button>';
		$ret .= ' | ';
		$ret .= '<button name="ops" value="delete">Delete...</button>';

		if ($this->OpsNav) {
			$ret .= '<div>';
			$nrows = max(3, count(explode("\n", $this->OpsNav->query))+1);
			$ret .= '<label>Ops:<br>
				<textarea style="width: 100%" rows="' .H($nrows) .'" name="opsquery">' .H($this->OpsNav->query) .'</textarea></label>';
			if ($this->opsRecords === null)
				$ret .= '<button name="ops" value="exec" type="submit" class="action-button-main action-button-dangerous"><span>Perform!</span></button>';
			else {
				$ret .= '<table class="records-listing"><thead>';
				foreach ($this->opsRecords as $rcd) {
					$ret .= '<tr>';
					foreach ($rcd as $k => $v)
						if (is_int($k))
							;
						else
							$ret .= '<th>' .H($k) .'</th>';
					$ret .= '</tr></thead><tbody>';
					break; }
				foreach ($this->opsRecords as $rcd) {
					$ret .= '<tr>';
					foreach ($rcd as $k => $v)
						if (is_int($k))
							;
						else
							$ret .= '<td>' .H($v) .'</td>';
					$ret .= '</tr>';
				}
				$ret .= '</tbody></table>'; }
			$ret .= '</div>';
		}

		$ret .= '</fieldset>';
		return $ret;
	}

	function H() : string {
		$ret = '';
		$HC = $this->HC;

		$ret .= $this->navH();

		$ret .= '<form method="post" action="' .$HC .'">';

		$ret .= '<table class="records-listing">';
		$ret .= '<thead>' .$this->headerRowH() .'</thead>';
		$ret .= '<tbody>';

		foreach ($this->a as $rcd) {
			if ($rcd['_checked']??null)
				$cH = ' checked="checked"';
			else
				$cH = '';
			$ret .= '<tr>';
			if (array_key_exists('rowid', $rcd))
				$ret .= '<th><a href="' .$HC('rowid', $rcd['rowid']) .'">edit...</a></th>';
			if (array_key_exists('rowid', $rcd))
				$ret .= '<th><label><input name="sel[rowid][]" value="' .H($rcd['rowid']) .'" type="checkbox" ' .$cH .'/> ' .H($rcd['rowid']) .'</label></th>';
			foreach ($rcd as $k => $v)
				$ret .= $this->fieldH($rcd, $k);
			$ret .= '</tr>';
		}
		$ret .= '</tbody></table>';

		$ret .= $this->opsH();
		$ret .= '</form>';

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
		else if (is_string($rcd[$key])) {
			$orig = $rcd[$key];
			$s = substr($orig, 0, 128);
			$s = implode("\n", array_slice(explode("\n", $s), 0, 3));
			if ($s !== $orig)
				$s .= '...';
			$H = H($s); }
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
			if (array_key_exists('rowid', $rcd))
				$ret .= '<th>[ ]</th>';
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
