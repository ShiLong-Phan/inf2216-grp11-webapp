<?php
include "utils/session.php";

$_SESSION = array();
if (ini_get('session.cookie_lifetime')) {
    setcookie(session_name(), '', 100);
    session_unset();
    session_destroy();

}

header("Location: ../index.php");

?>