<?php

	$CHAT_redirect = 'https://chat.domain.com';
	$ERROR_redirect = $CHAT_redirect . '/invite.php?error=1&logout=true';
	$INVITE_server = $CHAT_redirect;
	$HomeServer_domain = 'matrix.domain.com';
	$HomeServer = 'matrix.domain.com';
	$SharedSecret = "<the secret from /etc/synapse/homeserver.yaml>";

	class query {
		public $q;
		public $result;
		private $conHandle = null;
		private $dbhost = 'matrix.domain.com';
		private $dbuser = 'synapse';
		private $dbpass = '<db password>';
		private $dbname = 'synapse';

		public function __construct($q) {
			$this->q = $q;
			$this->connect();
		}

		public function error() {
			return pg_last_error($this->conHandle);
		}

		private function connect() {
			// CREATE DATABASE dhsupport OWNER dhsupport
			// ALTER USER dhsupport WITH PASSWORD 'passwd';
			$this->conHandle = pg_connect("host=" . $this->dbhost . " user=" . $this->dbuser . " password=" . $this->dbpass . " dbname=" . $this->dbname);
			if (!$this->conHandle) {
				error_log("Connect failed: %s\n", $this->conHandle->connect_error);
				//exit();
				return false;
			} else {
				pg_query($this->conHandle, "CREATE TABLE IF NOT EXISTS invites (id SERIAL PRIMARY KEY, key VARCHAR(128), owner VARCHAR(120), regged TIMESTAMP WITH TIME ZONE default now(), used BOOL DEFAULT FALSE, regged_as VARCHAR(255), UNIQUE(key));");
				pg_query($this->connHandle, "UPDATE invites SET used='t' WHERE age(regged) > INTERVAL '1 day';");
				return true;
			}
		}

		public function execute() {
			$this->result = pg_query($this->conHandle, $this->q);

			if (!$this->result) {
				error_log($this->q);
				die("Error: %s\n" . $this->conHandle->error);
				$this->close();
			}
			$this->close();
		}

		public function get() {
			$this->result = pg_query($this->conHandle, $this->q);

			if ($this->result && $this->result !== TRUE) {
				while ($row = pg_fetch_assoc($this->result))
					yield $row;
			}
			$this->close();
		}

		public function close() {
			//if($this->result)
			//      $this->result->free();
			pg_close($this->conHandle);
		}
	}

	function validate_input($obj) {
		global $error_redirect_path;
		$valid_chars = "/[^A-Za-z0-9\!\#\$\-\.\_]/";
		foreach($obj as $key => $val) {
			if (preg_match($valid_chars, $key) || preg_match($valid_chars, $val)) {
				header('Location: '. $error_redirect_path);
				die();
			}
		}
	}
	// TODO:
	// Super daft way of checking user input, but works for testing purposes.
	validate_input($_GET);
	validate_input($_POST);

?>