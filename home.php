<?php
    session_start();
    include 'db_info.php';
    include 'utilities.php';

    if (!isset($_SESSION['uuid'])) {
        header('Location: index.php');
    }

    $dbconnect = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (mysqli_connect_errno()) {
        die("Connection error: " . mysqli_connect_error());
    }

    $playerInfo = getPlayerInfo($_SESSION['uuid'], $dbconnect);
    $formattedRank = formatRank($playerInfo['rank']);
?>
<html>
    <head>
        <title>UserCP :: MCAdministration</title>
        <style>
            a:visited {
                color: blue;
            }
        </style>
    </head>
    <body>
        <h1 id="welcome">Welcome, <?php echo $playerInfo['last_name']; ?>!</h1>
        <h2 id="rank">Rank: <span style="color: <?php echo $formattedRank['color']; ?>; font-weight: <?php echo $formattedRank['font-weight']; ?>; text-shadow: <?php echo $formattedRank['text-shadow']; ?>"><?php echo $playerInfo['rank']; ?></span></h2>
        <h3 id="lastseen">Last Seen: <?php echo date("F j, Y, g:i a", $playerInfo['lastseen']); ?></h3>
        <h3 id="playtime">Total Playtime: <?php echo round($playerInfo['playtime'] / 3600, 2);  ?> hours</h3>
        <h3 id="kills">Total Kills: <?php echo $playerInfo['kills']; ?></h3>
        <h3 id="deaths">Total Deaths: <?php echo $playerInfo['deaths']; ?></h3>
        <br />

        <?php echo (isStaffMember($_SESSION['uuid'], $dbconnect) ? "<font size='2'><a href='admincp.php'>AdminCP</a> - </font>" : ""); ?>
        <font size="2"><a href="logout.php">Log Out</a></font>
    </body>
</html>
