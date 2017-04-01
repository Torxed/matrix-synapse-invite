<?php
	class query {
		public $q;
		public $result;

		private $conHandle = null;
		private $dbhost = '127.0.0.1';
		private $dbuser = 'synapse';
		private $dbpass = '<SomeRandomPassword>';
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
				pg_query($this->conHandle, "CREATE TABLE IF NOT EXISTS invites (id SERIAL PRIMARY KEY, key VARCHAR(128), owner VARCHAR(120), regged TIMESTAMP WITH TIME ZONE default now(), used BOOL DEFAULT FALSE, UNIQUE(key));");
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

	$invite_server_location = 'chat.example.com';
	$temporary_dummy_owner = '@user:chat.example.com';

	$newKey = hash('sha256', random_bytes(64));
	$q = new query("INSERT INTO invites (key, owner) VALUES('".$newKey."', '$temporary_dummy_owner');");
	$q->execute();
	print "https://$invite_server_location/invite.php?K=".$newKey;
?>
