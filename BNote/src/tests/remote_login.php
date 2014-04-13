<?php

/**
 * This file tests whether a login attempt can be processed when sent from another host.
 */

// Target System
$bn_host = "http://test.bnote.info";

// Login-Action
$bn_action = $bn_host . "/login.php";

?>

<html>
	<head>
		<title>Remote Login Test</title>
	</head>
	<body>
	
	<form action="<?php echo $bn_action; ?>" method="POST">
		<label for="login">Benutzername</label>
		<input type="text" name="login" />
		
		<label for="password">Passwort</label>
		<input type="password" name="password" />
		
		<input type="submit" value="Anmelden" />
	</form>
	
	</body>
</html>


<?php

// DOES NOT WORK -> local links!

?>