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

    if (!isStaffMember($_SESSION['uuid'], $dbconnect)) {
        header('Location: home.php');
    }
?>
<html>
    <head>
        <title>AdminCP :: MCAdministration</title>
        <style>
            a:visited {
                color: blue;
            }
            form, p {
                margin: 0;
                padding: 0;
                border: 0;
                vertical-align: baseline;
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

            input[type="text"] {
                width: 250px;
            }
        </style>
    </head>
    <body>
        <a href="home.php">Back to UserCP</a><br />

        <form action="admincp.php" method="get">
            <label>UUID: </label>
            <input type="text" name="uuid" value="<?php echo (isset($_GET['uuid']) ? $_GET['uuid'] : ""); ?>" />
            <input type="submit" />
        </form>
<?php
    if (isset($_GET['uuid'])) {
        $targetPlayerInfo = getPlayerInfo($_GET['uuid'], $dbconnect);
        if ($targetPlayerInfo != null) {
            ?>
            <p id="name">Name: <?php echo $targetPlayerInfo['last_name']; ?></p>
            <p id="rank">Rank: <?php echo $targetPlayerInfo['rank']; ?></p>
            <p id="lastseen">Last Seen: <?php echo date("F j, Y, g:i a", $targetPlayerInfo['lastseen']); ?></p>
            <p id="playtime">Playtime: <?php echo round($targetPlayerInfo['playtime'] / 3600, 2);  ?> hours</p>
            <?php
        }
        $action = "";
        if (isset($_GET['action'])) {
            $action = strtolower($_GET['action']);
            if (strpos($action, 'form') !== false) { //Action contains "form", show user action form
                switch ($action) {
                    case "banform":
                        ?>
                        <form name="banform" action="admincp.php" method="get">
                            <input type="hidden" name="action" value="ban" />
                            <input type="hidden" name="uuid" value="<?php echo $_GET['uuid'] ?>" />
                            <label>Reason: </label><input type="text" name="reason" /><br />
                            <select name="length">
                                <option value="5m">5 Minutes</option>
                                <option value="30m">30 Minutes</option>
                                <option value="1h">1 Hour</option>
                                <option value="12h">12 Hours</option>
                                <option value="1d">1 Day</option>
                                <option value="2d">2 Days</option>
                                <option value="4d">4 Days</option>
                                <option value="1w">1 Week</option>
                                <option value="2w">2 Weeks</option>
                                <option value="3w">3 Weeks</option>
                                <option value="1m">1 Month</option>
                            </select>
                            <input type="submit" value="Ban">
                        </form>
                        <?php
                        break;
                    case "unbanform":
                        ?>
                        <p>Are you sure that you want to unban <?php echo $_GET['uuid'] ?>?</p>
                        <form name="unbanyes" action="admincp.php" method="get">
                            <input type="hidden" name="action" value="unban" />
                            <input type="hidden" name="uuid" value="<?php echo $_GET['uuid'] ?>" />
                            <input type="submit" value="Yes" />
                        </form>
                        <form name="unbanno" action="admincp.php" method="get">
                            <input type="hidden" name="uuid" value="<?php echo $_GET['uuid'] ?>" />
                            <input type="submit" value="No" />
                        </form>
                        <?php
                        break;
                    case "warnform":
                        ?>
                        <form name="warnform" action="admincp.php" method="get">
                            <input type="hidden" name="action" value="warn" />
                            <input type="hidden" name="uuid" value="<?php echo $_GET['uuid'] ?>" />
                            <label>Reason: </label><input type="text" name="reason" /><br />
                            <input type="submit" value="Warn">
                        </form>
                        <?php
                        break;
                    case "setrankform":
                        ?>
                        <form name="setrankform" action="admincp.php" method="get">
                            <input type="hidden" name="action" value="setrank" />
                            <input type="hidden" name="uuid" value="<?php echo $_GET['uuid'] ?>" />
                            <select name="rank">
                                <?php
                                    foreach (getAllRanks($dbconnect) as $rank) {
                                        ?>
                                            <option value="<?php echo $rank; ?>"><?php echo $rank; ?></option>
                                        <?php
                                    }
                                ?>
                            </select>
                            <input type="submit" value="Set Rank">
                        </form>
                        <?php
                        break;
                }
            } else { //Action does not contain "form", action was confirmed, execute:
                switch ($action) {
                    case "ban":
                        if (playerHasPermission($_SESSION['uuid'], "can_ban", $dbconnect)) {
                            if (isset($_GET['reason']) && empty($_GET['reason'])==false && isset($_GET['length'])) {
                                if (in_array($_GET['length'], BAN_LENGTHS)) {
                                    logAction($_GET['uuid'], "Ban", $_SESSION['uuid'], $_GET['reason'], lengthStrToNum($_GET['length']), time(), $dbconnect);
                                    echo "<p>You have successfully banned " . $_GET['uuid'] . "!</p>";
                                }
                            }
                        }

                        break;
                    case "unban":
                        if (playerHasPermission($_SESSION['uuid'], "can_ban", $dbconnect)) {
                            logAction($_GET['uuid'], "Unban", $_SESSION['uuid'], null, 0, time(), $dbconnect);
                            echo "<p>You have successfully unbanned " . $_GET['uuid'] . "!</p>";
                        }

                        break;
                    case "warn":
                        if (playerHasPermission($_SESSION['uuid'], "can_warn", $dbconnect)) {
                            if (isset($_GET['reason'])) {
                                logAction($_GET['uuid'], "Warn", $_SESSION['uuid'], $_GET['reason'], 0, time(), $dbconnect);
                                echo "<p>You have successfully warned " . $_GET['uuid'] . "!</p>";
                            }
                        }

                        break;
                    case "setrank":
                        if ($targetPlayerInfo!=null && playerHasPermission($_SESSION['uuid'], "can_set_rank", $dbconnect)) {
                            if (isset($_GET['rank'])) {
                                $oldRank = $targetPlayerInfo['rank'];
                                logAction($_GET['uuid'], "Edit Rank", $_SESSION['uuid'], "Changed rank from " . $oldRank . " to " . $_GET['rank'], 0, time(), $dbconnect);
                                setRank($_GET['uuid'], $_GET['rank'], $dbconnect);
                                echo "<p>You have successfully set the rank of " . $_GET['uuid'] . "!</p>";
                            }
                        }

                        break;
                    default:
                        //Ignore

                        break;
                }
            }
        }
        if (isPlayerBanned($_GET['uuid'], $dbconnect) && $action!="unbanform") {
            if (playerHasPermission($_SESSION['uuid'], "can_ban", $dbconnect) && playerCanUnban($_SESSION['uuid'], $_GET['uuid'], $dbconnect)) {
                ?>
                <form name="unban" action="admincp.php" method="get">
                    <input type="hidden" name="action" value="unbanform" />
                    <input type="hidden" name="uuid" value="<?php echo $_GET['uuid']; ?>">
                    <input type="submit" value="Unban">
                </form>
                <?php
            }
        } else {
            if (playerHasPermission($_SESSION['uuid'], "can_ban", $dbconnect) && $action!="banform") {
                ?>
                <form name="ban" action="admincp.php" method="get">
                    <input type="hidden" name="action" value="banform" />
                    <input type="hidden" name="uuid" value="<?php echo $_GET['uuid']; ?>" />
                    <input type="submit" value="Ban" />
                </form>
                <?php
            }
        }

        if (playerHasPermission($_SESSION['uuid'], "can_warn", $dbconnect) && $action!="warnform") {
            ?>
            <form name="warn" action="admincp.php" method="get">
                <input type="hidden" name="action" value="warnform" />
                <input type="hidden" name="uuid" value="<?php echo $_GET['uuid']; ?>" />
                <input type="submit" value="Warn" />
            </form>
            <?php
        }

        if ($targetPlayerInfo!=null && playerHasPermission($_SESSION['uuid'], "can_set_rank", $dbconnect) && $action!="setrankform") {
            ?>
            <form name="setrank" action="admincp.php" method="get">
                <input type="hidden" name="action" value="setrankform" />
                <input type="hidden" name="uuid" value="<?php echo $_GET['uuid']; ?>" />
                <input type="submit" value="Set Rank" />
            </form>
            <?php
        }
        ?>
        <h4>Admin Record</h4>
        <table id="adminrecord">
            <tr>
                <td>DATE</td>
                <td>ACTION</td>
                <td>STAFF</td>
                <td>REASON</td>
                <td>LENGTH</td>
            </tr>
            <?php
                $getAllPunishmentsSQL = sprintf("SELECT * FROM actions WHERE target = '%s' ORDER BY aid DESC;",
                    mysqli_real_escape_string($dbconnect, $_GET['uuid']));
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
        <br />
        <h4>Admin Actions</h4>
        <table id="adminactions">
            <tr>
                <td>DATE</td>
                <td>ACTION</td>
                <td>TARGET</td>
                <td>REASON</td>
                <td>LENGTH</td>
            </tr>
            <?php
                $getAllActionsSQL = sprintf("SELECT * FROM actions WHERE staff = '%s' ORDER BY aid DESC;",
                    mysqli_real_escape_string($dbconnect, $_GET['uuid']));
                $getAllActionsQuery = mysqli_query($dbconnect, $getAllActionsSQL);
                if (!mysqli_num_rows($getAllActionsQuery) == 0) {
                    while ($action = mysqli_fetch_assoc($getAllActionsQuery)) {
                        $color = actionToColorCode($action['action']);
                        ?>
                        <tr style="background-color: <?php echo $color['background']; ?>; color: <?php echo $color['foreground']; ?>;">
                            <td><?php echo date("F j, Y, g:i a", $action['timestamp']); ?></td>
                            <td><?php echo $action['action']; ?></td>
                            <td><?php echo ($action['target'] == null ? "N/A" : $action['target']); ?></td>
                            <td><?php echo ($action['reason'] == null ? "" : $action['reason']); ?></td>
                            <td><?php echo lengthNumToStr($action['length'], $action['action']); ?></td>
                        </tr>
                        <?php
                    }
                }
            ?>
        </table>
        <?php
    }
?>
    </body>
</html>
