<?php

/**
 * Password Forgotten Page
 */

include "dirs.php";
include $DIR_DATA . "database.php";
include $DIR_DATA . "regex.php";
$sysconfig = new XmlData("config/config.xml", "Software");
$swname = $sysconfig->getParameter("Name");
$regex = new Regex();
date_default_timezone_set("Europe/Berlin");
?>

<html>
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title><?php echo $swname; ?> | Passwort vergessen</title>
  <link href="<?php echo $GLOBALS["DIR_CSS"]; ?>login.css" rel="StyleSheet" type="text/css" /> 
 </head>

<body>
	<div id="topline">
	<?php echo $swname; ?>
	</div>
	<form method="POST" action="pwforgotten.php">

		<div id="login">
			Passwort vergessen
			<?php
			if(isset($_POST["login"]) && $_POST["login"] != "") {
				// validate input for attack prevention
				$regex->isLogin($_POST["login"]);
				
				// get email address
				$db = new Database();
				$cid = $db->getCell("user", "contact", "login = \"". $_POST["login"] ."\"");
				if($cid < 1) {
					echo '<p class="login">Der Benutzername wurde nicht gefunden.</p>';
					unset($_POST["login"]);
					echo '<a href="index.php">Zur&uuml;ck</a>';
					exit();
				}
				$email = $db->getCell("contact", "email", "id = $cid");
				
				// generate new password
				$chars = "abcdefghijkmnpqrstuvwxyz123456789";
				srand((double)microtime()*1000000);
				$i = 0;
				$pass = '' ;
				while ($i <= 6) {
					$num = rand() % 33;
					$tmp = substr($chars, $num, 1);
					$pass = $pass . $tmp;
					$i++;
				}
				$password = $pass;
				
				// send email
				$subject = "Neues Passwort";
				$body = "Dein neues Passwort lautet: $password .";
				if(!mail($email, $subject, $body)) {
					// talk to leader
					echo '<p class="login">Leider konnte die E-Mail an dich nicht versandt werden.<br />
							Bitte wende dich an deinen Bandleiter.</p>';
				}
				else {					
					// Change password in system only if mail has been sent.
					$pwenc = crypt($password, CRYPT_BLOWFISH);
					$query = "UPDATE user SET password = '$pwenc' WHERE login = '" . $_POST["login"] . "'";
					$db->execute($query);
					
					// success message
					echo '<p class="login">Das Passwort wurde dir soeben zugeschickt.</p>';
				}
			}
			else {
			?>
			<p class="login">Bitte gebe deinen Benutzernamen ein und das System wird dir 
							 ein neues Passwort per E-Mail zuschicken.<br/><br/>

				<span style="font-size: 14px">Benutzername</span> &nbsp; <input name="login" type="text" size="25" /><br/><br/>
				<input type="submit" value="Neues Passwort zuschicken">
			</p>
			<?php
			} 
			?>
		</div>

	</form>

	<div id="lowline">by Matti Maier Internet Solutions</div>

</body>
</html>