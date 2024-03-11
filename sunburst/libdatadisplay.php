<?php

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
					$ret .= '<p><label>' .H($k) .': <input name="field[' .H($k) .']" value="' .H($this->rcd[$k]) .'"/></label></p>';
			}
			$ret .= '<button type="submit" name="action" value="save" class="action-button-main">save</button>';
		$ret .= '</form>';

		return $ret;
	}
}

class DataTableRender extends Renderer
{
	protected $Tb;
	protected $a;

	function setTable(SQLiteTable $Tb) { $this->Tb = $Tb; }
	function setRecords(array $a) { $this->a = $a; }

	function H() : string {
		$ret = '';
		$HC = $this->HC;

		$ret .= '<table class="records-listing">';
		$ret .= '<thead>' .$this->headerRowH() .'</thead>';
		$ret .= '<tbody>';

		foreach ($this->a as $rcd) {
			$ret .= '<tr>';
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
		$ret .= '<th>#</th>';

		foreach ($this->a as $rcd) {
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
