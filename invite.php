<?php
	session_start();
	require_once('invite_helpers.php');

	if(isset($_GET['logout'])) {
		session_unset();
		session_destroy();
		header('Location: ?login=true');
		die();
	}

	/*// Design
		helpers.<config>
		helpers.dbquery()
		helpers.validate_input()

		if $_GET['K']
			if not <login credentials>
				show_create-user_form()
			else
				register_new_user
		else
			if not <login credentials>
				show_login_form()
			else
				show_generated_invite-key()
		
	*/

	if(isset($_GET['K'])) {
		// If we got a key, that means we're trying to register a new user
		// and we're not reaching this site to administrate new keys.

		$OTK = $_GET['K'];

		$dict = array();
		$dictRaw = new query("SELECT owner, key FROM invites WHERE key='" . $_GET['K'] . "' AND used=false;");
		if ($dictRaw) {
			foreach ($dictRaw->get() as $row) {
				foreach($row as $key => $val)
					$dict[$key] = $val;
			}
		}

		if (!isset($dict['owner'])) {
			header("Location: $ERROR_redirect");
			die();
		} else {
			$q = new query("UPDATE invites SET used=true WHERE key='$OTK';");
			$q->execute();
		}

		/*
			If we've gotten this far, it means that hopefully the userinput isn't malicious.
			And the key supplied should be valid, so it's time to check if we've supplied
			a username/password combo.. If we haven't the key will be set as used and a new
			key is generated for the next session.
		*/

		if(!isset($_POST['username']) || !isset($_SESSION['password'])) {
			//
			$newKey = hash('sha256', random_bytes(64));
			$q = new query("INSERT INTO invites (key, owner) VALUES('".$newKey."', '".$dict['owner']."');");
			$q->execute();
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
							<form method="POST" action="./invite.php?K=<?php print $newKey; ?>">
								<input type="text" name="username" placeholder="Username">
								<?php
									function generatePassword($length = 8) {
										$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!#$-._';
										$count = mb_strlen($chars);

										for ($i = 0, $result = ''; $i < $length; $i++) {
											$index = rand(0, $count - 1);
											$result .= mb_substr($chars, $index, 1);
										}

										return $result;
									}

									session_unset();
									session_destroy();

									session_start();
									$_SESSION['password'] = generatePassword();
									print '<input type="text" id="password" value="'.$_SESSION['password'].'" DISABLED>';
								?>
								<?php //<input type="password" name="password" placeholder="Password"> ?>
								<input type="submit" value="Register">
								<br>
								<font color="#c2c2c2">
									<ul>
										<li><b>Remember the password</b> - and change it later under settings</li>
										<li><b>Usernames are case sensitive</b></li>
									</ul>
								</font>
							</form>
						</div>
					</div>
				</body>
				</html>
			<?php
		} else {
			// We've got a valid key and a supplied username,
			// and the password if fetched from the previous session
			$user = $_POST['username'];
			$pass = $_SESSION['password'];

			$q = new query("UPDATE invites SET regged_as='$user' WHERE key='$OTK';");
			$q->execute();

			$mac = hash_hmac('sha1', $user . "\0" . $pass . "\0" . "notadmin", $SharedSecret);
			//mac = mac.hexdigest() /Default/

			$data = array(
				"user" => $user,
				"password" => $pass,
				"mac" => $mac,
				"type" => "org.matrix.login.shared_secret",
				"admin" => false
			);

			$server_location = "https://$HomeServer:8448";
			$payload = json_encode($data);

			print $payload;

			// http://php.net/manual/en/function.curl-setopt.php
			$curl = curl_init($server_location."/_matrix/client/api/v1/register");
			curl_setopt($curl, CURLOPT_POSTFIELDS, $payload );
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($payload)));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
			// DEBUG: Because self signed cert, remove on production!
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);

			$result = curl_exec($curl);
			print curl_error($curl);
			curl_close($curl);

			header("Location: $CHAT_redirect");
			die();
		}
	} else {
		if(isset($_POST['username']) && isset($_POST['password'])) {
			// Were logging in to create a invite link.
			// Logging in to generate new 
			$user = $_POST['username'];
			$pass = $_POST['password'];
			
			// Build a dictionary of matching users that looks like:
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
							<form method="POST" action="./invite.php">
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
			$user = $_SESSION['username'];

			$newKey = hash('sha256', random_bytes(64));
			$q = new query("INSERT INTO invites (key, owner) VALUES('".$newKey."', '" . $_SESSION['username'] . "');");
			$q->execute();
			print "$INVITE_server/invite.php?K=".$newKey;
			print "<br><br>Valid for: <b>24h</b><br><a href=\"?logout=true\">Login as another user</a>";

			print'<br>People you\'ve invited:<br>';
			print '<ul>';
			$dictRaw = new query("SELECT regged_as FROM invites WHERE owner='$user' AND regged_as is not NULL;");
			if ($dictRaw) {
				foreach ($dictRaw->get() as $row) {
					print '<li>@' . $row['regged_as'] . ":$HomeServer_domain</li>";
				}
			}
			print '</ul>';
		}
	}

?>
