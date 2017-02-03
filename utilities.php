<?php
    //List all ranks here
    define("STAFF_RANKS", array(
        "Moderator",
        "Administrator",
        "Superadmin",
        "Owner",
    ));

    //All allowed ban lengths, don't change this
    define("BAN_LENGTHS", array(
        "5m", //5 minutes
        "30m", //30 minutes
        "1h", //1 hour
        "12h", //12 hours
        "1d",  //1 day
        "2d", //2 days
        "4d", //4 days
        "1w", //1 week
        "2w", //2 weeks
        "3w", //3 weeks
        "1m", //1 month
    ));

    function isStaffMember($uuid, $dbconnect) {
        $getUserInfoSQL = sprintf("SELECT rank FROM players WHERE uuid = '%s';",
            mysqli_real_escape_string($dbconnect, $uuid));
        $getUserInfoQuery = mysqli_query($dbconnect, $getUserInfoSQL);
        if (mysqli_num_rows($getUserInfoQuery) == 0) return false;
        $userInfo = mysqli_fetch_assoc($getUserInfoQuery);

        return (in_array($userInfo['rank'], STAFF_RANKS));
    }

    function isPlayerBanned($uuid, $dbconnect) {
        $getBanSQL = sprintf("SELECT * FROM bans WHERE target = '%s';",
            mysqli_real_escape_string($dbconnect, $uuid));
        $getBanQuery = mysqli_query($dbconnect, $getBanSQL);
        if (mysqli_num_rows($getBanQuery) == 0) return false;
        $ban = mysqli_fetch_assoc($getBanQuery); //Player has existing ban, now to check to see if it expired

        $getBanInfoSQL = sprintf("SELECT length, timestamp FROM actions WHERE target = '%s' AND aid = %d;",
            mysqli_real_escape_string($dbconnect, $uuid),
            mysqli_real_escape_string($dbconnect, $ban['aid']));
        $getBanInfoQuery = mysqli_query($dbconnect, $getBanInfoSQL);
        $banInfo = mysqli_fetch_assoc($getBanInfoQuery); //Assume query returned results b/c bans table and actions table are in sync
        if ($banInfo['length'] == 0) return true;
        if (time() < ($banInfo['length'] + $banInfo['timestamp'])) {
            return true;
        } else { //Ban exists but expired, return false but remove ban from bans table first
            $removeBanSQL = sprintf("DELETE FROM bans WHERE bid = %d;",
                mysqli_real_escape_string($dbconnect, $ban['bid']));
            mysqli_query($dbconnect, $removeBanSQL);
            return false;
        }
    }

    function playerCanUnban($staff, $target, $dbconnect) {
        $getBanSQL = sprintf("SELECT aid FROM bans WHERE target = '%s';",
            mysqli_real_escape_string($dbconnect, $target));
        $getBanQuery = mysqli_query($dbconnect, $getBanSQL);
        if (mysqli_num_rows($getBanQuery) != 0) {
            $getBanInfoSQL = sprintf("SELECT staff FROM actions WHERE aid = %d;",
                mysqli_real_escape_string($dbconnect, $staff));
            $getBanInfoQuery = mysqli_query($dbconnect, $getBanInfoSQL);
            $banInfo = mysqli_fetch_assoc($getBanInfoQuery);
            if ($banInfo['staff'] == $staff) return true;
            return playerHasPermission($staff, "can_unban_all", $dbconnect);
        }
        return false;
    }

    function logAction($uuid, $action, $staff, $reason, $length, $timestamp, $dbconnect) {
        $logActionSQL = sprintf("INSERT INTO actions VALUES (%d, '%s', '%s', '%s', " . ($reason == null ? "%s" : "'%s'") . ", %d, %d);",
            mysqli_real_escape_string($dbconnect, 0),
            mysqli_real_escape_string($dbconnect, $action),
            mysqli_real_escape_string($dbconnect, $uuid),
            mysqli_real_escape_string($dbconnect, $staff),
            mysqli_real_escape_string($dbconnect, ($reason == null ? "NULL" : $reason)),
            mysqli_real_escape_string($dbconnect, $length),
            mysqli_real_escape_string($dbconnect, $timestamp));
        mysqli_query($dbconnect, $logActionSQL);

        if (strtolower($action) == "ban") {
            $createBanSQL = sprintf("INSERT INTO bans VALUES (%d, %d, '%s');",
                mysqli_real_escape_string($dbconnect, 0),
                mysqli_real_escape_string($dbconnect, mysqli_insert_id($dbconnect)),
                mysqli_real_escape_string($dbconnect, $uuid));
            mysqli_query($dbconnect, $createBanSQL);
        } else if (strtolower($action) == "unban") {
            $removeBanSQL = sprintf("DELETE FROM bans WHERE target = '%s';",
                mysqli_real_escape_string($dbconnect, $uuid));
            mysqli_query($dbconnect, $removeBanSQL);
        }
    }

    function setRank($uuid, $rank, $dbconnect) {
        $setRankSQL = sprintf("UPDATE players SET rank = '%s' WHERE uuid = '%s';",
            mysqli_real_escape_string($dbconnect, $rank),
            mysqli_real_escape_string($dbconnect, $uuid));
        mysqli_query($dbconnect, $setRankSQL);
    }

    function getPlayerInfo($uuid, $dbconnect) {
        $getPlayerInfoSQL = sprintf("SELECT * FROM players WHERE uuid = '%s';",
            mysqli_real_escape_string($dbconnect, $uuid));
        $getPlayerInfoQuery = mysqli_query($dbconnect, $getPlayerInfoSQL);
        if (mysqli_num_rows($getPlayerInfoQuery) == 0) {
            return false;
        }
        return mysqli_fetch_assoc($getPlayerInfoQuery);
    }

    function playerHasPermission($uuid, $action, $dbconnect) {
        $playerInfo = getPlayerInfo($uuid, $dbconnect);
        if ($playerInfo === false) return false;

        $getRankInfoSQL = sprintf("SELECT %s FROM ranks WHERE rank = '%s';",
            mysqli_real_escape_string($dbconnect, $action),
            mysqli_real_escape_string($dbconnect, $playerInfo['rank']));
        $getRankInfoQuery = mysqli_query($dbconnect, $getRankInfoSQL);
        if (mysqli_num_rows($getRankInfoQuery) == 0) { //User's set rank does not exist in ranks database or invalid action supplied
            return false;
        }
        $rankInfo = mysqli_fetch_assoc($getRankInfoQuery);
        return $rankInfo[$action] == 1;
    }

    function getAllRanks($dbconnect) {
        $ranks = array();
        $getAllRanksSQL = sprintf("SELECT rank FROM ranks");
        $getAllRanksQuery = mysqli_query($dbconnect, $getAllRanksSQL);
        if (mysqli_num_rows($getAllRanksQuery) == 0) { //No ranks saved in db
            return null;
        }
        while ($rank = mysqli_fetch_assoc($getAllRanksQuery)) {
            array_push($ranks, $rank['rank']);
        }
        return $ranks;
    }


    function lengthStrToNum($length)
    {
        switch ($length) {
            case "5m": //5 minutes
                return (5 * 60);
            case "30m": //30 minutes
                return (30 * 60);
            case "1h": //1 hour
                return (60 * 60);
            case "12h": //12 hours
                return (12 * (60 * 60));
            case "1d": //1 day
                return (24 * (60 * 60));
            case "2d": //2 days
                return (2 * (24 * (60 * 60)));
            case "4d": //4 days
                return (4 * (24 * (60 * 60)));
            case "1w": //1 week
                return (7 * (24 * (60 * 60)));
            case "2w": //2 weeks
                return (14 * (24 * (60 * 60)));
            case "3w": //3 weeks
                return (21 * (24 * (60 * 60)));
            case "1m": //1 month
                return (31 * (24 * (60 * 60)));
            default:
                return 0;
        }
    }

    function lengthNumToStr($length, $action) {
        switch ($length) {
            case 300:
                return "5 Minutes";
            case 1800:
                return "30 Minutes";
            case 3600:
                return "1 Hour";
            case 43200:
                return "12 Hours";
            case 86400:
                return "1 Day";
            case 172800:
                return "2 Days";
            case 345600:
                return "4 Days";
            case 604800:
                return "1 Week";
            case 1209600:
                return "2 Weeks";
            case 1814400:
                return "3 Weeks";
            case 2678400:
                return "1 Month";
            case 0:
                if (strpos(strtolower($action), "ban") !== false) {
                    return "Permanent";
                } else return "N/A";
            default:
                return "N/A";
        }
    }

    function actionToColorCode($action) {
        $color = array();
        switch($action) {
            case "Warn":
                $color['background'] = "#F6FF00";
                $color['foreground'] = "#000000";

                break;
            case "Ban":
                $color['background'] = "#D40000";
                $color['foreground'] = "#FFFFFF";

                break;
            case "Unban":
                $color['background'] = "#00D420";
                $color['foreground'] = "#FFFFFF";

                break;
            case "Kick":
                $color['background'] = "#DE6A6A";
                $color['foreground'] = "#FFFFFF";

                break;
            case "Slay":
                $color['background'] = "#969696";
                $color['foreground'] = "#FFFFFF";

                break;
            default:
                $color['background'] = "#FFFFFF";
                $color['foreground'] = "#000000";

                break;
        }

        return $color;
    }