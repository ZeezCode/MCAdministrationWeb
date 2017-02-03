<?php
    session_start();
    include 'db_info.php';

    function returnWithError($errorNum) {
        header('Location: index.php?error=' . $errorNum);
    }

    $dbconnect = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (mysqli_connect_errno()) {
        die("Connection error: " . mysqli_connect_error());
    }

    if (isset($_POST['uuid']) && isset($_POST['password'])) {
        $uuid = trim($_POST['uuid']);
        $pass = trim($_POST['password']);
        if ($uuid == "" || $pass == "") {
            returnWithError(2);
        }

        $getUserInfoSQL = sprintf("SELECT * FROM players WHERE uuid = '%s';",
            mysqli_real_escape_string($dbconnect, $uuid));
        $getUserInfoQuery = mysqli_query($dbconnect, $getUserInfoSQL);
        if (mysqli_num_rows($getUserInfoQuery) == 0) {
            returnWithError(3);
        }

        $userInfo = mysqli_fetch_assoc($getUserInfoQuery);
        $hashedPass = hash('sha256', (hash('sha256', $pass) . hash('sha256', $userInfo['salt']))); //This looks gross, I'm sorry

        if ($userInfo['password'] == $hashedPass) {
            $_SESSION['uuid'] = $uuid;
            header('Location: home.php');
        } else {
            returnWithError(4);
        }
    } else {
        returnWithError(1);
    }