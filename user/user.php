<?php
class User {
    public $name;
    public $city;
    function __construct($name, $city) {
        $this->name = $name;
        $this->city = $city;
    }
}
?>