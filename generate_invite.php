<?php
	session_start();

	if(isset($_GET['logout'])) {
		session_unset();
		session_destroy();
		header('Location: ?login=true');
		die();
	}

	$ERROR_redirect = 'https://domain.com/generate_invite.php=error=1';
	$CHAT_redirect = 'https://chat.domain.com';
	$INVITE_server = $CHAT_redirect;
	$HomeServer = 'homeserver.domain.com';
	$HomeServer_domain = 'chat.domain.com'; // used to struct @user:chat.domain.com

	class query {
		public $q;
		public $result;
		private $conHandle = null;
		private $dbhost = 'localhost';
		private $dbuser = 'synapse';
		private $dbpass = '<some secure db-password>';
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

	if(isset($_POST['username']) && isset($_POST['password'])) {
		$user = $_POST['username'];
		$pass = $_POST['password'];
		
		// Build a dictionary that looks like:
		/*
			$dict = {'username' : {
						'username' : sql.name,
						'password_hash' : sql.password_hash,
						'admin' : sql.admin
						},
					 'username2' : { ... }
					}
		*/
		$dict = array();
		$dictRaw = new query("SELECT name, password_hash, admin FROM users WHERE name='@$user:$HomeServer_domain' AND is_guest=0 AND appservice_id is NULL;");
		if ($dictRaw) {
			foreach ($dictRaw->get() as $row) {
				foreach($row as $key => $val) {
					if(!isset($dict[$row['name']]))
						$dict[$row['name']] = array();
					$dict[$row['name']][$key] = $val;
				}
			}
		}

		if (isset($dict["@$user:$HomeServer_domain"]) && password_verify($pass, $dict["@$user:$HomeServer_domain"]['password_hash'])) {
			$_SESSION['username'] = "@$user:$HomeServer_domain";
		} else {
			header("Location: $ERROR_redirect");
			die();
		}
	}

	if(!isset($_SESSION['username'])) {
		?>
		<html>
			<head>
				<style type="text/css">
						body {
							background-color: #2d2d2d;
							overflow: hidden;
						}

						#content {
							position: absolute;
							width: 100%;
							height: 100%;
							overflow: hidden;
						}

						.logo {
							position: absolute;
							width: 200px;
							height: 200px;
							left: 50%;
							top: 50%;
							margin-left: -100px;
							margin-top: -150px;
							background-image: url('./logo.png');
						}

						.fields {
							position: absolute;
							left: 50%;
							top: 50%;
							width: 200px;
							margin-left: -100px;
							margin-top: 75px;
						}

						.fields input {
							width: 200px;
						}

						.fields input[type=submit] {
							background-color: #00d1b2;
							border-color: transparent;
							color: #fff;
						}

						.fields input {
							align-items: center;
							border-radius: 3px;
							display: inline-flex;
							font-size: 1rem;
							height: 2.285em;
							justify-content: flex-start;
							line-height: 1.5;
							padding-left: .75em;
							padding-right: .75em;
							background-color: #fff;
							border: 1px solid #dbdbdb;
							color: #363636;
							box-shadow: inset 0 1px 2px rgba(10,10,10,.1);
							width: 100%;
							margin-top: 2px;
						}

				</style>
			</head>
			<body>
				<div id="content">
					<div class="logo"></div>
					<div class="fields">
						<form method="POST" action="./generate_invite.php">
							<input type="text" name="username" placeholder="Username">
							<input type="password" name="password" placeholder="Password">
							<input type="submit" value="Login">
						</form>
					</div>
				</div>
			</body>
		</html>
		<?php
	} else {
		$newKey = hash('sha256', random_bytes(64));
		$q = new query("INSERT INTO invites (key, owner) VALUES('".$newKey."', '" . $_SESSION['username'] . "');");
		$q->execute();
		print "$INVITE_server/invite.php?K=".$newKey;
		print "<br><br><a href=\"?logout=true\">Login as another user</a>";
	}
?>
