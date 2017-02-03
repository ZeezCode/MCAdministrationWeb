<?php
    session_start();
?>
<html>
    <head>
        <title>Login :: UserCP :: MCAdministration</title>
    </head>
    <body>
        <form action="process_login.php" method="post">
            <table>
                <tr>
                    <td><strong>UUID: </strong></td>
                    <td><input type="text" name="uuid" required /></td>
                </tr>
                <tr>
                    <td><strong>Password: </strong></td>
                    <td><input type="password" name="password" required /></td>
                </tr>
                <tr>
                    <td><input type="submit" /></td>
                </tr>
            </table>
        </form>
    </body>
</html>