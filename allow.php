<?PHP

$start = microtime(true);

class Allow {
	private $who;
	private $whotype;

	private static $Defaults = array();

	private static $Rules = array();
	public static function AddRule($whotype, $object_type, $callback) {
		if (!isset(self::$Rules[$whotype])) self::$Rules[$whotype] = array();
		if (!isset(self::$Rules[$whotype][$object_type])) self::$Rules[$whotype][$object_type] = array();
		self::$Rules[$whotype][$object_type][] = $callback;
	}

	private function __construct($whotype, $who) {
		$this->whotype = strtolower($whotype);
		$this->who = $who;
	}

	public static function __callStatic($whotype, $arguments) {
		if (!isset($arguments[0])) $arguments = array(self::$Defaults[$whotype]);
		return new self($whotype, $arguments[0]);
	}

	public function __call($action, $arguments) {
		if (count($arguments) < 1) $arguments[] = '';
		if (count($arguments) < 2) $arguments[] = null;
		if (count($arguments) < 3) $arguments[] = true;
		if (count($arguments) < 4) $arguments[] = false;
		if (count($arguments) < 5) $arguments[] = $arguments[3];

		list($object_type, $object, $return_on_allowed, $return_on_forbidden, $return_on_undecided) = $arguments;

		if (empty(self::$Rules[$this->whotype])) return $return_on_undecided;
		if (empty(self::$Rules[$this->whotype][$object_type])) return $return_on_undecided;

		foreach(self::$Rules[$this->whotype][$object_type] as $callback) {
			$res = call_user_func($callback, $this->who, strtolower($action), $object, $this);
			if ($res === true) return $return_on_allowed;
			if ($res === false) return $return_on_forbidden;
		}

		return $return_on_undecided;
	}

	public function getWho() {
		return $this->who;
	}

	public static setDefault($whotype, &$default) {
		self::$Defaults[$whotype] = &$default;
	}
}

// Rule 1
Allow::AddRule('user', 'var', function($who, $action, $what) {
	if ($action == 'read') {
		if (is_numeric($what)) return true;
	}
});

// Rule 2
Allow::AddRule('user', 'var', function($who, $action, $what) {
	if ($action == 'read') {
		if ($what == 'ZZZ') return true;
	}
});

// Rule 3
Allow::AddRule('user', 'var', function($who, $action, $what) {
	if ($action == 'create') {
		if ($who === 123) return true;
	}
});

// Rule 4
Allow::AddRule('role', 'var', function($who, $action, $what) {
	if ($action == 'create') {
		if ($who === 'admin') return true;
	}
});

echo '<hr>', var_export(Allow::User(123)->Read('var', '123'), true), ' // Should be true';   // Rule 1 - Action == Read, Type == var, What = numeric
echo '<hr>', var_export(Allow::User(123)->Read('var', 'ABC'), true), ' // Should be false';  // No Rule
echo '<hr>', var_export(Allow::User(123)->Read('var', 'ZZZ'), true), ' // Should be true';   // Rule 2 - Action == Read, Type == var, What == ZZZ
echo '<hr>', var_export(Allow::User(123)->Read('var', 'AAA'), true), ' // Should be false';  // No Rule
echo '<hr>', var_export(Allow::User(123)->Create('var'), true),      ' // Should be true';   // Rule 3 - Action == Create, Type == var, Who == 123

$x = Allow::User(234)->Create('var', null, true, false, null);
echo '<hr>User: ', var_export($x, true);						// No Rule - Rule 3 will fail because Who != 123
if ($x === null) $x = Allow::Role('admin')->Create('var');
echo ' - Role: ', var_export($x, true), ' // Should be true';   // Rule 4 - Action == Create, Type == var, Role == 'admin'

echo '<hr>', microtime(true) - $start;
