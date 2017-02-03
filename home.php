<?php
    session_start();
    if (!isset($_SESSION['uuid'])) {
        header('Location: index.php');
    }
	
	//Didn't write this yet, need to add some user-important stat tracking before I can do this (ex: Total kills, total deaths, etc.)