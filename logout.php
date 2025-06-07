<?php
require_once 'include/functions.php';

session_start();
session_destroy();
redirect('login.php');
?>
