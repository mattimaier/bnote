<?php

/**
 * Login views.
 * @author matti
 *
 */
class LoginView extends AbstractView
{

    public function __construct($ctrl)
    {
        $this->setController($ctrl);
    }

    public function start()
    {
        include $GLOBALS["DIR_PRESENTATION"] . "logo.php";
    }

    public function showOptions()
    {

        // Login
        $mod = "login";
        if ($_GET["mod"] != $mod) {
            $login = new Link("?mod=$mod", Lang::txt("LoginView_showOptions.login"));
            $login->write();
            $this->buttonSpace();
        }

        // PW
        $mod = "forgotPassword";
        if ($_GET["mod"] != $mod) {
            $pwForgot = new Link("?mod=$mod", Lang::txt("LoginView_showOptions.forgotPassword"));
            $pwForgot->write();
            $this->buttonSpace();
        }

        // Registration
        $mod = "registration";
        /* check if user registration is on */
        $user_reg = $this->getData()->getSysdata()->getDynamicConfigParameter("user_registration");
        if ($user_reg == 1 && $_GET["mod"] != $mod) {
            $reg = new Link("?mod=$mod", Lang::txt("LoginView_showOptions.registration"));
            $reg->write();
            $this->buttonSpace();
        }

        // Terms
        $mod = "terms";
        if ($_GET["mod"] != $mod) {
            $terms = new Link("?mod=$mod", Lang::txt("LoginView_showOptions.terms"));
            $terms->write();
            $this->buttonSpace();
        }

        // Impressum
        $mod = "impressum";
        if ($_GET["mod"] != $mod) {
            $imp = new Link("?mod=$mod", Lang::txt("LoginView_showOptions.impressum"));
            $imp->write();
        }
    }

    public function login()
    {

        Writing::p(Lang::txt("LoginView_login.message_1"));

        Writing::p(Lang::txt("LoginView_login.message_2"));

        // login form
        $form = new Form(Lang::txt("LoginView_login.Form"), $this->modePrefix() . "login");
        $form->addElement(Lang::txt("LoginView_login.login"), new Field("login", "", FieldType::CHAR));
        $form->addElement(Lang::txt("LoginView_login.password"), new Field("password", "", FieldType::PASSWORD));
        $form->write();
    }

    public function forgotPassword()
    {
        Writing::h1(Lang::txt("LoginView_forgotPassword.title"));
        Writing::p(Lang::txt("LoginView_forgotPassword.message"));

        // forgotten password form
        $form = new Form("", $this->modePrefix() . "password");
        $form->addElement(Lang::txt("LoginView_forgotPassword.email"), new Field("email", "", FieldType::EMAIL));
        $form->write();
    }

    public function registration()
    {
        /* check if user registration is on */
        $user_reg = $this->getData()->getSysdata()->getDynamicConfigParameter("user_registration");
        if ($user_reg == 0) {
            new BNoteError(Lang::txt("LoginView_registration.registration_deactivated"));
        }

        Writing::h1(Lang::txt("LoginView_registration.title"));

        ?>
<form method="POST" action="<?php echo $this->modePrefix(); ?>register">

<script>
	  <?php echo $this->getData()->getJSValidationFunctions(); ?>
</script>

<p class="login"><?php echo Lang::txt("LoginView_registration.logintext"); ?></p>

<table class="login">
	<TR>
		<TD class="login"><?php echo Lang::txt("LoginView_registration.first_name"); ?></TD>
		<TD class="loginInput"><input name="name" type="text" size="25"
			onChange="validateInput(this, 'name');" /></TD>
	</TR>
	<TR>
		<TD class="login"><?php echo Lang::txt("LoginView_registration.surname"); ?></TD>
		<TD class="loginInput"><input name="surname" type="text" size="25"
			onChange="validateInput(this, 'name');" /></TD>
	</TR>
	<TR>
		<TD class="login"><?php echo Lang::txt("LoginView_registration.phone"); ?></TD>
		<TD class="loginInput"><input name="phone" type="text" size="25"
			onChange="validateInputOptional(this, 'phone');" /></TD>
	</TR>
	<TR>
		<TD class="login"><?php echo Lang::txt("LoginView_registration.email"); ?></TD>
		<TD class="loginInput"><input name="email" type="text" size="25"
			onChange="validateInput(this, 'email');" /></TD>
	</TR>
	<TR>
		<TD class="login"><?php echo Lang::txt("LoginView_registration.street"); ?></TD>
		<TD class="loginInput"><input name="street" type="text" size="25"
			onChange="validateInput(this, 'street');" /></TD>
	</TR>
	<TR>
		<TD class="login"><?php echo Lang::txt("LoginView_registration.zip"); ?></TD>
		<TD class="loginInput"><input name="zip" type="text" size="25"
			onChange="validateInput(this, 'zip');" /></TD>
	</TR>
	<TR>
		<TD class="login"><?php echo Lang::txt("LoginView_registration.city"); ?></TD>
		<TD class="loginInput"><input name="city" type="text" size="25"
			onChange="validateInput(this, 'city');" /></TD>
	</TR>
	<TR>
		<TD class="login"><?php echo Lang::txt("LoginView_registration.country"); ?> *</TD>
		<td class="loginInput">
			<?php
$dd = $this->buildCountryDropdown("");
        echo $dd->write();
        ?>
		</td>
	</TR>
	<TR>
		<TD class="login"><?php echo Lang::txt("LoginView_registration.instrument"); ?></TD>
		<TD class="loginInput"><SELECT name="instrument">
				<?php
$instruments = $this->getData()->getInstruments();
        global $system_data;
        $cats = $system_data->getInstrumentCategories();
        for ($i = 1; $i < count($instruments); $i++) {
            // filter instruments of categories
            if (!in_array($instruments[$i]["cat"], $cats)) {
                continue;
            }

            echo '<OPTION value="' . $instruments[$i]["id"] . '">';
            echo $instruments[$i]["category"] . ": " . $instruments[$i]["instrument"] . "</OPTION>\n";
        }
        ?>
		</SELECT>
		</TD>
	</TR>
	<TR>
		<TD class="login"><?php echo Lang::txt("LoginView_registration.login"); ?></TD>
		<TD class="loginInput"><input name="login" type="text" size="25"
			onChange="validateInput(this, 'login');" /><br /> <span
			style="font-size: 10px;"><?php echo Lang::txt("LoginView_registration.login_text"); ?></span>
		</TD>
	</TR>
	<TR>
		<TD class="login"><?php echo Lang::txt("LoginView_registration.pw1"); ?></TD>
		<TD class="loginInput"><input name="pw1" type="password" size="25"
			onChange="validateInput(this, 'password');" /><br /> <span
			style="font-size: 10px;"><?php echo Lang::txt("LoginView_registration.password_text"); ?></span>
		</TD>
	</TR>
	<TR>
		<TD class="login"><?php echo Lang::txt("LoginView_registration.pw2"); ?></TD>
		<TD class="loginInput"><input name="pw2" type="password" size="25"
			onChange="validateInput(this, 'password');" /></TD>
	</TR>
	<TR>
		<TD class="login"><?php echo Lang::txt("LoginView_registration.terms_1"); ?><a href="?mod=terms" style="text-decoration: underline;" target="_blank"><?php echo Lang::txt("LoginView_registration.terms_2"); ?></a><?php echo Lang::txt("LoginView_registration.terms_3"); ?>
		</TD>
		<TD class="loginInput"><input type="checkbox" name="terms" /></TD>
	</TR>
	<TR>
		<TD class="login" colspan="2"
			style="font-size: 10pt; padding-bottom: 15px; width: 100%;"><?php echo Lang::txt("LoginView_registration.message"); ?></TD>
	</TR>
	<TR>
		<TD class="login" colspan="2"><input name="register" type="submit"
			value=<?php echo Lang::txt("LoginView_registration.register"); ?>></TD>
	</TR>
</table>
</form>
<?php
}

    public function impressum()
    {
        include "data/impressum.html";
    }

    public function terms()
    {
        include "data/terms.html";
    }

    public function gdpr()
    {
        include "data/gdpr.php";
    }

    public function extGdpr()
    {
        ?>
		<style> #content_insets { margin-left: 1%; } </style>
		<?php
Writing::h2(Lang::txt("LoginView_extGdpr.title"));

        // validate code
        if (!isset($_GET["code"])) {
            new BNoteError(Lang::txt("LoginView_extGdpr.error"));
        }
        $code = $_GET["code"];

        // process approval
        if (isset($_GET["sub"]) && $_GET["sub"] == "ok") {
            $this->getData()->gdprOk($code);
            new Message(Lang::txt("LoginView_extGdpr.message_1"), Lang::txt("LoginView_extGdpr.message_2"));
            return;
        }

        // show acceptance
        $contact = $this->getData()->findContactByCode($code);
        if ($contact == null) {
            new BNoteError(Lang::txt("LoginView_extGdpr.codeerror"));
        }

        Writing::p(Lang::txt("LoginView_extGdpr.codemessage"));
        $dv = new Dataview();
        $dv->addElement(Lang::txt("LoginView_extGdpr.name"), $contact["name"] . " " . $contact["surname"]);
        $dv->addElement(Lang::txt("LoginView_extGdpr.nickname"), $contact["nickname"]);
        $dv->addElement(Lang::txt("LoginView_extGdpr.phone"), $contact["phone"]);
        $dv->addElement(Lang::txt("LoginView_extGdpr.fax"), $contact["fax"]);
        $dv->addElement(Lang::txt("LoginView_extGdpr.mobile"), $contact["mobile"]);
        $dv->addElement(Lang::txt("LoginView_extGdpr.business"), $contact["business"]);
        $dv->addElement(Lang::txt("LoginView_extGdpr.email"), $contact["email"]);
        $dv->addElement(Lang::txt("LoginView_extGdpr.web"), $contact["web"]);
        $dv->addElement(Lang::txt("LoginView_extGdpr.street"), $contact["street"] . ", " . $contact["zip"] . " " . $contact["city"]);
        $dv->addElement(Lang::txt("LoginView_extGdpr.birthday"), $contact["birthday"]);
        $dv->write();

        $ok = new Link("?mod=extGdpr&sub=ok&code=$code", Lang::txt("LoginView_extGdpr.link"));
        $ok->write();
    }
}

?>