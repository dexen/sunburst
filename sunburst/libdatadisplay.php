<?php

class DataTableRender
{
	protected $Tb;
	protected $a;

	function setTable(SQLiteTable $Tb) { $this->Tb = $Tb; }
	function setRecords(array $a) { $this->a = $a; }

	function H() : string {
		$ret = '';

		$ret .= '<table class="records-listing">';
		$ret .= '<thead>' .$this->headerRowH() .'</thead>';
		$ret .= '<tbody>';

		foreach ($this->a as $rcd) {
			$ret .= '<tr>';
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
			foreach ($rcd as $k => $v)
				if (is_int($k))
					;
				else
					$ret .= '<th>' .H($k) .'</th>';
			break;
		}
		$ret .= '</tr>';
		return $ret;
	}
}
