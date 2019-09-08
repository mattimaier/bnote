<?php
/**
 * A class to make links consistent
 **/

class Link implements iWriteable
{

    public $href;
    public $label;
    public $target;
    public $icon;
    public $jsClick;

    /**
     * Creates a link
     * @param $href String to where the field links
     * @param $label Label of the link field
     */
    public function __construct($href, $label)
    {
        $this->href = $href;
        $this->label = $label;
        $this->jsClick = null;
    }

    /**
     * Sets the links target.
     * @param String $target HTML target value.
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function setJsClick($jsClick)
    {
        $this->jsClick = $jsClick;
    }

    public function write()
    {
        echo $this->generate();
    }

    /**
     * Returns a string with the elements HTML code
     */
    public function toString()
    {
        return $this->generate();
    }

    private function generate()
    {
        if (isset($this->target) && $this->target != "") {
            $target = 'target="' . $this->target . '"';
        } else {
            $target = "";
        }

        if (isset($this->icon) && $this->icon != "") {
            $icon = "<img src=\"" . $GLOBALS["DIR_ICONS"] . $this->icon . ".png\""
            . " height=\"15px\" class=\"linkIcon\" alt=\"" . $this->icon . "\" border=\"0\" />&nbsp;";
        } else {
            $icon = "";
        }

        $options = "";
        if ($this->jsClick != null) {
            $options .= ' onclick="' . $this->jsClick . '"';
        }

        return '<a class="linkbox" ' . $target . 'href="' . $this->href . '"' . $options . '>'
        . '<div class="linkbox">' . $icon . $this->label . '</div></a>';
    }

    /**
     * To add an icon in front of the caption, execute this function with a
     * name of the icon from the icons folder.
     * @param String $icon_id Name of the icon file in the icon folder.
     */
    public function addIcon($icon_id)
    {
        $this->icon = $icon_id;
    }

}

class NavLink extends Link
{
    private function generate()
    {
        if (isset($this->target) && $this->target != "") {
            $target = 'target="' . $this->target . '"';
        } else {
            $target = "";
        }

        if (isset($this->icon) && $this->icon != "") {
            $icon = "<img src=\"" . $GLOBALS["DIR_ICONS"] . $this->icon . ".png\""
            . " height=\"15px\" class=\"linkIcon\" alt=\"" . $this->icon . "\" border=\"0\" />&nbsp;";
        } else {
            $icon = "";
        }

        $options = "";
        if ($this->jsClick != null) {
            $options .= ' onclick="' . $this->jsClick . '"';
        }

        return '<a  class="dropdown-item" ' . $target . 'href="' . $this->href . '"' . $options . '>'
        . '<div class="linkbox">' . $icon . $this->label . '</div></a>';
    }

    public function write()
    {
        echo $this->generate();
    }

}
