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

            table {
                border-collapse: collapse;
                border-spacing: 0;
                border: solid black 1px;
                text-align: center;
            }

            table td {
                border: solid black 1px;
                min-width: 150px;
                padding: 5px;
            }

            table tr {
                border: solid black 1px;
            }
        </style>
    </head>
    <body>
        <h1 id="welcome">Welcome, <?php echo $playerInfo['last_name']; ?>!</h1>
        <h3 id="rank">Rank: <span style="color: <?php echo $formattedRank['color']; ?>; font-weight: <?php echo $formattedRank['font-weight']; ?>; text-shadow: <?php echo $formattedRank['text-shadow']; ?>"><?php echo $playerInfo['rank']; ?></span></h3>
        <h3 id="lastseen">Last Seen: <?php echo date("F j, Y, g:i a", $playerInfo['lastseen']); ?></h3>
        <h3 id="playtime">Total Playtime: <?php echo round($playerInfo['playtime'] / 3600, 2);  ?> hours</h3>
        <h3 id="kills">Total Kills: <?php echo $playerInfo['kills']; ?></h3>
        <h3 id="deaths">Total Deaths: <?php echo $playerInfo['deaths']; ?></h3>
        <br />

        <?php echo (isStaffMember($_SESSION['uuid'], $dbconnect) ? "<font size='2'><a href='admincp.php'>AdminCP</a> - </font>" : ""); ?>
        <font size="2"><a href="logout.php">Log Out</a></font>

        <h3>Admin Record</h3>
        <table id="adminrecord">
            <tr>
                <td>DATE</td>
                <td>ACTION</td>
                <td>STAFF</td>
                <td>REASON</td>
                <td>LENGTH</td>
            </tr>
            <?php
            $getAllPunishmentsSQL = sprintf("SELECT * FROM actions WHERE target = '%s' ORDER BY aid ASC;",
                mysqli_real_escape_string($dbconnect, $_SESSION['uuid']));
            $getAllPunishmentsQuery = mysqli_query($dbconnect, $getAllPunishmentsSQL);
            if (!mysqli_num_rows($getAllPunishmentsQuery) == 0) {
                while ($punishment = mysqli_fetch_assoc($getAllPunishmentsQuery)) {
                    $color = actionToColorCode($punishment['action']);
                    ?>
                    <tr style="background-color: <?php echo $color['background']; ?>; color: <?php echo $color['foreground']; ?>;">
                        <td><?php echo date("F j, Y, g:i a", $punishment['timestamp']); ?></td>
                        <td><?php echo $punishment['action']; ?></td>
                        <td><?php echo $punishment['staff']; ?></td>
                        <td><?php echo ($punishment['reason'] == null ? "" : $punishment['reason']); ?></td>
                        <td><?php echo lengthNumToStr($punishment['length'], $punishment['action']); ?></td>
                    </tr>
                    <?php
                }
            }
            ?>
        </table>
    </body>
</html>
