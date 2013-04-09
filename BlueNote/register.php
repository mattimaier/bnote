<?php

/**
 * Registration Form
 */
include "src/presentation/widgets/error.php";
$db = $sd->dbcon;
$regex = $sd->regex;
?>
	<form method="POST" action="?show=register">
	  <script>
		  <?php echo $regex->getJSValidationFunctions(); ?>
	  </script> 
	  <div id="registration">
			<h1>Registrierung</h1>
			
			<?php 
			if(isset($_POST["register"])) {
				// check agreement to terms
				if(!isset($_POST["terms"])) {
					new Error("Bitte stimme den Nutzungsbedingungen zu.");
				}
				
				// validate data
				$regex->isName($_POST["name"]);
				$regex->isName($_POST["surname"]);
				if(isset($_POST["phone"]) && $_POST["phone"] != "") $regex->isPhone($_POST["phone"]);
				$regex->isEmail($_POST["email"]);
				$regex->isStreet($_POST["street"]);
				$regex->isZip($_POST["zip"]);
				$regex->isCity($_POST["city"]);
				$regex->isPositiveAmount($_POST["instrument"]);
				$regex->isLogin($_POST["login"]);
				$regex->isPassword($_POST["pw1"]);
				$regex->isPassword($_POST["pw2"]);
				
				// check for duplicate login
				$login = $_POST["login"];
				$ct = $db->getCell("user", "count(id)", "login = '$login'");
				if($ct > 0) {
					new Error("Der Benutzername wird bereits verwendet.");
				}
				
				// check passwords and encrypt it
				if($_POST["pw1"] != $_POST["pw2"]) {
					new Error("Bitte &uuml;berpr&uuml;fe dein Kennwort.");
				}
				$password = crypt($_POST["pw1"], CRYPT_BLOWFISH);
				
				// create address
				$query = "INSERT INTO address (street, city, zip) VALUES (";
				$query .= '"' . $_POST["street"] . '", "' . $_POST["city"] . '", "' . $_POST["zip"] . '"';
				$query .= ")";
				$aid = $db->execute($query); // address id
				
				// create contact
				$query = "INSERT INTO contact (surname, name, phone, email, address, status, instrument)";
				$query .= " VALUES (";
				$query .= '"' . $_POST["surname"] . '", ';
				$query .= '"' . $_POST["name"] . '", ';
				$query .= '"' . $_POST["phone"] . '", ';
				$query .= '"' . $_POST["email"] . '", ';
				$query .= "$aid, ";
				$query .= '"MEMBER", ';
				$query .= $_POST["instrument"];
				$query .= ")";
				$cid = $db->execute($query); // contact id
				
				// create inactive user
				$query = "INSERT INTO user (login, password, isActive, contact)";
				$query .= " VALUES (";
				$query .= '"' . $login . '", ';
				$query .= '"' . $password . '", ';
				$query .= "0, $cid";
				$query .= ")";
				$uid = $db->execute($query);
				
				// create default rights
				$privQuery = "INSERT INTO privilege (user, module) VALUES ";
				foreach($sd->getDefaultUserCreatePermissions() as $i => $mod) {
					$privQuery .= "($uid, $mod), ";
				}
				$privQuery = substr($privQuery, 0, strlen($privQuery)-2);
				$db->execute($privQuery);
				
				// write success
				?>
				<p class="login"><strong>Registrierung abgeschlossen</strong><br />
				Du hast dich erfolgreich registriert.
				<?php 
				
				if($sd->autoUserActivation()) {
					// create link for activation
					$linkurl = $sd->getSystemURL() . "/src/export/useractivation.php?uid=$uid&email=" . $_POST["email"];
					$subject = "BlueNote Aktivierung";
					$message = "Bitte klicke auf folgenden Link zur Aktivierung deines Benutzerkontos:\n$linkurl";
					
					// send email to activate account and write message
					if(!mail($_POST["email"], $subject, $message)) {
						echo "Leider trat bei der Aktivierung ein <b>Fehler</b> auf. Wende dich zur Freischaltung bitte an deinen Bandleader.<br/>";
					}
					else {
						echo 'Bitte prüfe deine E-Mails. Klicke auf den Aktivierungslink um dein Konto zu bestätigen. Dann kannst du dich anmelden.<br/>';
					}
				}
				else {
					echo 'Bitte wende dich an deinen Bandleader und warte bis dein Konto freigeschalten ist.<br/>';
				}
				
				?>
				</p>
				<?php
			}
			else {
			?>
			<p class="login">Bitte f&uuml;lle dieses Formular aus um dich als Mitglied zu registrieren.
					Die angegebenen Daten werden vertraulich behandelt und nicht an Dritte weitergegeben.</p>

			<table class="login">
				<TR>
					<TD class="login">Vorname *</TD>
					<TD class="loginInput"><input name="name" type="text" size="25" onChange="validateInput(this, 'name');" /></TD>
				</TR>
				<TR>
					<TD class="login">Name *</TD>
					<TD class="loginInput"><input name="surname" type="text" size="25" onChange="validateInput(this, 'name');" /></TD>
				</TR>
				<TR>
					<TD class="login">Telefon</TD>
					<TD class="loginInput"><input name="phone" type="text" size="25" onChange="validateInputOptional(this, 'phone');" /></TD>
				</TR>
				<TR>
					<TD class="login">E-Mail *</TD>
					<TD class="loginInput"><input name="email" type="text" size="25" onChange="validateInput(this, 'email');" /></TD>
				</TR>
				<TR>
					<TD class="login">Stra&szlig;e *</TD>
					<TD class="loginInput"><input name="street" type="text" size="25" onChange="validateInput(this, 'street');" /></TD>
				</TR>
				<TR>
					<TD class="login">PLZ *</TD>
					<TD class="loginInput"><input name="zip" type="text" size="25" onChange="validateInput(this, 'zip');" /></TD>
				</TR>
				<TR>
					<TD class="login">Stadt *</TD>
					<TD class="loginInput"><input name="city" type="text" size="25" onChange="validateInput(this, 'city');" /></TD>
				</TR>
				<TR>
					<TD class="login">Instrument</TD>
					<TD class="loginInput">
						<SELECT name="instrument">
						<?php
						$query = "SELECT id,name FROM instrument";
						$instruments = $db->getSelection($query);
						for($i = 1; $i < count($instruments); $i++) {
							echo '<OPTION value="' . $instruments[$i]["id"] . '">';
							echo $instruments[$i]["name"] . "</OPTION>\n";
						}
						?>
						</SELECT>
					</TD>
				</TR>
				<TR>
					<TD class="login">Login *</TD>
					<TD class="loginInput">
						<input name="login" type="text" size="25" onChange="validateInput(this, 'login');" /><br/>
						<span style="font-size: 10px;">Erlaubte Zeichen: Buchstaben, Zahlen, Punkt, Bindestrich, Unterstrich</span>
					</TD>
				</TR>
				<TR>
					<TD class="login">Passwort *</TD>
					<TD class="loginInput"><input name="pw1" type="password" size="25" onChange="validateInput(this, 'password');" /></TD>
				</TR>
				<TR>
					<TD class="login">Passwort Wdh. *</TD>
					<TD class="loginInput"><input name="pw2" type="password" size="25" onChange="validateInput(this, 'password');" /></TD>
				</TR>
				<TR>
					<TD class="login">Ich stimme den <a href="?show=terms" target="_blank">Nutzungsbedingungen</a> zu.*</TD>
					<TD class="loginInput"></a><input type="checkbox" name="terms" /></TD>
				</TR>
				<TR>
					<TD class="login" colspan="2" style="font-size: 10px; padding-bottom: 15px; width: 100%;">* Die mit Stern gekennzeichneten Felder sind auszuf&uuml;llen.</TD>
				</TR>
				<TR>
					<TD class="login" colspan="2"><input name="register" type="submit" value="Registrieren"></TD>
				</TR>
			</table>
			<?php
			} 
			?>
		</div>
	</form>