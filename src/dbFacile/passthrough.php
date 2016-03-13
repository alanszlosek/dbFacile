<?php
namespace dbFacile;

class passthrough {
	private $value;
	public function __construct($value) {
		$this->value = $value;
	}
	public function __toString() {
		if (is_array($this->value)) {
			return '(' . implode(',', $this->value) . ')';
		}
		return ''.$this->value;
	}
}