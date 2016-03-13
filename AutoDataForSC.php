<!DOCTYPE html>
<!--
This script generates an email with the current rota 
starting today and running for n days, - n is defined in the .ini file
The Rota is embedded in an email and sent to a mail list also in the .ini file
The script is designed to be run by cron daily at 16:59

160314  MT  New version following OLRS upgrade and migration to nbbutils
            Uploaded to GitHub as Part of NBBUtils

-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
        <style>
            td {text-align:center}
        </style>
}
</style>
    </head>
    <body>
         <?php
        //  Get the required libraries
        require_once './common/phpmailer/class.phpmailer.php';
        require_once './common/phpmailer/class.smtp.php';
        require_once './common/mrbs/mrbs_functions.inc';     // define useful functions
        
        //  Get the required external files
        require_once '../nbbrota/config.nbbperiods.inc';     // sets period names
        require_once '../nbbrota/config.nbbshifttypes.inc';    // sets shift types
        require_once '../nbbcontxt/mrbs_dbconnect.inc';        // set dbconnect strings
        require_once '../nbbcontxt/quadrapps.inc';             // set environment strings
        //
        //  Get the configuration for this routine
        require_once './AutoDataForSC.ini';                 // default params & constants
        
/*
        
         Real code starts here

*/
        // get midnight today and midnight in x days as seconds   
        $msgtxt ="";
        $yr=date("Y"); 
        $mo=date("n");
        $da=date("j");
        $dow=date("w");
        $Endday = $da+NUMOFDAYS;
        
        $TodaySecs=mktime(0, 0, 0, $mo, $da, $yr);
        $EndSecs=mktime(0, 0, 0, $mo, $Endday, $yr);
 
        $msgtxt = $msgtxt   . "<strong>NBB Rota Report for dates: " 
                            . date("d/m/y",$TodaySecs)
                            ." until " .date("l d/m/y",$EndSecs)
                            . "<br><br></strong>";
        $msgtxt = $msgtxt   . "Note: 'GNAAS BoB' and 'Hexham' shifts are scheduled runs. <br>" 
                            . "Members on these shifts are not normally available"
                            . " for standard jobs. <br>All other members on shift are "
                            . "available for any jobs"
                            . "<br><br>";
       
        $conn = new mysqli($DBServer, $DBUser, $DBPass, $DBName);
 
        // check connection
        if ($conn->connect_error) {
            trigger_error('Database connection failed: '  . $conn->connect_error, E_USER_ERROR);
        }
        // Get record set
        $sql='SELECT start_time, name, type, description FROM mrbs_entry '
                . 'WHERE (start_time >= '.$TodaySecs. ' AND start_time <' . $EndSecs .') '
                . 'ORDER BY start_time';
 
        $rs=$conn->query($sql);
 
        if($rs === false) {
            trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $conn->error, E_USER_ERROR);
        } else {
            $rows_returned = $rs->num_rows;
        }
        #echo 'Num of Rows '.$rows_returned.'<br>';
        #iterate over record set after setting up table definition
        $msgtxt = $msgtxt . '<table style=width:"95%">';
        $msgtxt = $msgtxt . '<col width="200">'
                          . '<col width="250">'
                          . '<col width="150">'
                          . '<col width="200">';
        $msgtxt = $msgtxt . '<th>Date</th>'
                          . '<th>Rota and Shift</th>'
                          . '<th>Member</th>'
                          . '<th>Mobile</th>';
        $rs->data_seek(0);
        while($row = $rs->fetch_assoc()){
            $shifttype=GetShiftType($row['type']);
         
            if (strpos(ROTAEXCLUDETYPES,$shifttype) == 0) {
                $msgtxt = $msgtxt . "<tr>";
                $msgtxt = $msgtxt . "<td>" . date("l d M Y",$row['start_time'])."</td>".
                    "<td>".GetShiftName(GetShiftNum($TodaySecs,$row['start_time']))."</td>".
                        "<td>".$row['name'] ."</td>".
                            "<td>". GetMobileFromDescription($row['description']) ."</td>".
                                '</tr>';
            }
        }
        $msgtxt = $msgtxt . "</table>";
        echo $msgtxt. '<hr>';
        
        /* 
         * $msgtxt has the info
         * Now Create and Send Email
         */
 

        $mail = new PHPMailer();                // defaults to using php "mail()"

        require_once './common/mrbs/mrbs_smtpconnect.inc';    // set defaults for googlemail
                                                // other CONSTANTS from ini file
        $mail->addAddress(MAILTO);              // Add a recipient
        $mail->addBCC(MAILBCC);                 // Add hidden recipient
        $mail->addBCC(MAILBCC2);                // Add hidden recipient if defined
        $mail->addBCC(MAILBCC3);                // Add hidden recipient if defined
        
        
        $mail->Subject = MAILSUBJECT;           // Add subject
        $mail->Body    = $msgtxt;

        if(!$mail->Send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
            echo "Message sent!";
        }
        ?>
    </body>
</html>
