<?php

/**
 * View to manage the user's personal data.
 * @author matti
 *
 */
class KontaktdatenView extends CrudRefLocationView
{

    public function __construct($ctrl)
    {
        $this->setController($ctrl);
    }

    public function start()
    {
        // personal data
        $contact = $this->getData()->getContactForUser($_SESSION["user"]);
        if ($contact <= 0) {
            Writing::p(Lang::txt("KontaktdatenView_start.message"));
            return;
        }
        $cid = $contact["id"];

        $form = new Form(Lang::txt("KontaktdatenView_start.Form"), $this->modePrefix() . "savePD");
        $form->autoAddElements($this->getData()->getFields(), $this->getData()->getTable(), $cid, array("company"));
        $form->removeElement("id");
        $form->removeElement("notes");
        $form->removeElement("address");
        $form->removeElement("status");
        $form->removeElement("is_conductor");
        if ($form->getElement("instrument") == null) {
            $form->addElement("instrument", new Dropdown("instrument"));
        }
        $form->setForeign("instrument", "instrument", "id", "name", $contact["instrument"]);

        $address = $this->getData()->getAddress($contact["address"]);
        $this->addAddressFieldsToForm($form, $address);

        // custom data
        $this->appendCustomFieldsToForm($form, 'c', $contact, true);

        $form->write();
    }

    public function startOptions()
    {
        $chPw = new Link($this->modePrefix() . "changePassword", Lang::txt("KontaktdatenView_startOptions.changePassword"));
        $chPw->addIcon("key");
        $chPw->write();
        $this->buttonSpace();

        $settings = new Link($this->modePrefix() . "settings", Lang::txt("KontaktdatenView_startOptions.settings"));
        $settings->addIcon("settings");
        $settings->write();
    }

    public function savePD()
    {
        $this->getData()->update($_SESSION["user"], $_POST);
        new Message(Lang::txt("KontaktdatenView_savePD.Message_1"), Lang::txt("KontaktdatenView_savePD.Message_2"));
    }

    public function changePassword()
    {
        // change password
        $pwNote = Lang::txt("KontaktdatenView_changePassword.Message");

        $form2 = new Form(Lang::txt("KontaktdatenView_changePassword.Form"), "<p style=\"font-weight: normal;\">$pwNote</p>", $this->modePrefix() . "password");
        $form2->addElement(Lang::txt("KontaktdatenView_changePassword.New"), new Field("pw1", "", FieldType::PASSWORD));
        $form2->addElement(Lang::txt("KontaktdatenView_changePassword.Repeat"), new Field("pw2", "", FieldType::PASSWORD));
        $form2->write();
    }

    public function password()
    {
        $this->getData()->updatePassword();
        new Message(Lang::txt("KontaktdatenView_password.Message_1"), Lang::txt("KontaktdatenView_password.Message_2"));
    }

    public function settings()
    {
        $form = new Form(Lang::txt("KontaktdatenView_settings.saveSettings"), $this->modePrefix() . "saveSettings");

        // E-Mail Notification
        $default = $this->getData()->getSysdata()->userEmailNotificationOn() ? "1" : "0";
        $form->addElement(Lang::txt("KontaktdatenView_settings.email_notification"), new Field("email_notification", $default, FieldType::BOOLEAN));

        $form->write();
    }

    public function saveSettings()
    {
        $this->getData()->saveSettings($_SESSION["user"]);

        new Message(Lang::txt("KontaktdatenView_saveSettings.Message_1"), Lang::txt("KontaktdatenView_saveSettings.Message_2"));
    }
}
