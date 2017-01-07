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
        require_once './olrs_invalidshifttypes.ini';                 // default params & constants
        
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
       
        $conn = new mysqli($DBServer, $DBUser, $DBPass, $DBName);
 
        // check connection
        if ($conn->connect_error) {
            trigger_error('Database connection failed: '  . $conn->connect_error, E_USER_ERROR);
        }
        // Get record set
        $sql='SELECT start_time, name, type, description FROM mrbs_entry '
                . 'WHERE (start_time >= '.$TodaySecs. ' AND start_time <' . $EndSecs
                . ' AND type = "' . ROTAINCLUDETYPES . '") '
                . 'ORDER BY start_time';
 
        $rs=$conn->query($sql);
 
        if($rs === false) {
            trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $conn->error, E_USER_ERROR);
        } else {
            $rows_returned = $rs->num_rows;
        }
        
// prepare a mail object for later
        $mail = new PHPMailer();                                // defaults to using php "mail()"
        require_once './common/mrbs/mrbs_smtpconnect.inc';      // set defaults for googlemail     
        
// now iterate over records        
        $rs->data_seek(0);
        while($row = $rs->fetch_assoc()){
        
            $name = $row['name'];
            $emailad = GetEmailFromName($name);
            $salutation = GetSalutationFromName($name);
            $msgtxt = "";    
            $msgtxt = $msgtxt . "Hello ". $salutation . ",<br><br>";
            $msgtxt = $msgtxt . "Your shift booked for " . date('D jS M Y',$row['start_time']) 
                  . " requires attention as the shift type may not be correct. <br><br> ";
            $msgtxt = $msgtxt . "Regards, <br><br>";
            $msgtxt = $msgtxt . "Steve";
            echo $msgtxt. '<hr>';
        
        /* 
         * $msgtxt has the info
         * Now Create and Send Email
         */
 
         
       // $mail->addAddress($emailad);              // Add a recipient - ie the shift owner
       // $mail->addBCC(MAILBCC);                 // Add hidden recipient
       // $mail->addCC(MAILBCC2);                // Add hidden recipient if defined
       // $mail->addBCC(MAILBCC3);                // Add hidden recipient if defined
 
        $mail->addAddress("michael.thompson@northumbriabloodbikes.org.uk");
        $mail->Subject = MAILSUBJECT;           // Add subject
        $mail->msgHTML($msgtxt);

        if(!$mail->Send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
            echo "Message sent!  <br>";
            //sleep(10);
            $mail->ClearAllRecipients(); 
        }
        }
        ?>
    </body>
</html>
