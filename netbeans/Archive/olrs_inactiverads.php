<!DOCTYPE html>
<!--
Copyright (C) 2016 QUAD Developments - Michael Thompson 

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program - look for LICENSE.txt,  If not, 
see <http://www.gnu.org/licenses/>.
-->
<!--
      olrs_inactiverads.php

    This script checks the entries in the mrbs_entries table checking for riders
    and drivers (rads) who are still active (ie they exist in the users table)
    but have not done a shift in x days (where x is defined in ini file) 

    06/06/2016  MT  First Version Prepared

        To add : capture num of days from URL.

-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
       
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
         <?php
        // set up variables for this machine or environment
        require_once '../nbbcontxt/quadrapps.inc';      // sets environment Variables
        require_once './olrs_inactiverads.ini';         // sets constants
        
         
        // now set up lbraries - should be common for all machines and environments
        // require_once './common/phpmailer/class.phpmailer.php';
        // require_once './common/phpmailer/class.smtp.php';
        
        require_once './common/mrbs/mrbs_periodnames.inc';    // sets period names
        require_once './common/mrbs/mrbs_functions.inc';      // define useful functions
       
/*
  -------------------------------------------------------------------------------------        
         Real code starts here
  --------------------------------------------------------------------------------------
*/
        if (QuadGET('days') <> false){
            $threshold = QuadGET('days');
        } else {
            $threshold = INACTIVEDAYS;
        }
        
    // get current time as seconds   
        $msgtxt ="";
        $yr=date("Y"); 
        $mo=date("n");
        $da=date("j");                
        $StartSecs=(mktime(0, 0, 0, $mo, $da, $yr))-(60*60*24*$threshold);
        $NumInactiveRads=0;
    // connect to the database

        require '../nbbcontxt/mrbs_dbconnect.inc';                     // set dbconnect strings for drupal
        $conn = new mysqli($DBServer, $DBUser, $DBPass, $DBName);
 
    // check connection
        if ($conn->connect_error) {
            trigger_error('Database connection failed: '  . $conn->connect_error, E_USER_ERROR);
        }
    // Get record set
        $myShiftTypes = SHIFTTYPESINCLUDED;
        $sql="SELECT max(start_time),mrbs_entry.name, type, mrbs_users.uid, mrbs_users.mobile, mrbs_users.email
                FROM mrbs_entry 
                JOIN mrbs_users ON mrbs_entry.name = mrbs_users.name
                WHERE type IN(". $myShiftTypes.")
                GROUP BY mrbs_entry.name";
        $rs=$conn->query($sql);                                 // create record set
 
        if($rs === false) {
            trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $conn->error, E_USER_ERROR);
        } else {
            $rows_returned = $rs->num_rows;
        }
        // echo 'Num of Rows '.$rows_returned.'<br>';
        if ($rows_returned > 0){
            echo "<strong>Report of NBB Riders and Drivers with no recorded shifts <br>".
                    "in the last ". $threshold . " days ~ ".
                    "i.e. since " . date("j, F, Y ",$StartSecs). "</strong> <br><br>";
            PrintTableHeader(4);
            PrintTableRow1();
        }
        
 /* ==================================================================================
    
    Rows retrieved from MRBS
    Now prepare the web Page
  
    ==================================================================================
  */

    //  now process the drupal recordset
        
        $rs->data_seek(0);                                                  // go to start of record set
        while($row = $rs->fetch_assoc()){                                   // iterate over record set
            $myUserName=$row['name'];
            $myStartTime=$row['max(start_time)'];
 
            if ($myStartTime<$StartSecs)  {  
                if (is_bool(strpos($myUserName,'~')))  {  
                    PrintTableRow(ucwords($myUserName), $row['mobile'],
                            strtolower($row['email']), date("j, F, Y ",$myStartTime));
                    $NumInactiveRads +=1; 
                }
            }    
              
        }
        PrintTableFooter();
        echo "<br> Number of members listed ~ ".$NumInactiveRads;
        die("<br> all done - script ended");
         
 /* ==================================================================================
    
    Functions start here
  
    ==================================================================================
  */    
   function QuadGET($name=NULL, $value=false, $option="default")
{
    $option=false; // Old version depricated part
    $content=(!empty($_GET[$name]) ? trim($_GET[$name]) : (!empty($value) && !is_array($value) ? trim($value) : false));
    if(is_numeric($content))
        return preg_replace("@([^0-9])@Ui", "", $content);
    else if(is_bool($content))
        return ($content?true:false);
    else if(is_float($content))
        return preg_replace("@([^0-9\,\.\+\-])@Ui", "", $content);
    else if(is_string($content))
    {
        if(filter_var ($content, FILTER_VALIDATE_URL))
            return $content;
        else if(filter_var ($content, FILTER_VALIDATE_EMAIL))
            return $content;
        else if(filter_var ($content, FILTER_VALIDATE_IP))
            return $content;
        else if(filter_var ($content, FILTER_VALIDATE_FLOAT))
            return $content;
        else
            return preg_replace("@([^a-zA-Z0-9\+\-\_\*\@\$\!\;\.\?\#\:\=\%\/\ ]+)@Ui", "", $content);
    }
    else false;
}
  function PrintTableHeader($numcols)
 {
     echo '<table style=width:"95%">';
     if($numcols>0){
        $cw = 100 / $numcols;
        $str = "";
        for ($l=1; $l<5; $l++ ){
            $str = $str . '<col width="'. $cw . '%">';
        }
        echo $str;
     }
 }
 function PrintTableRow1()
 {   
     echo "<tr><td><strong>Member</strong></td>" .
                   "<td><strong>Mobile</strong></td>" .  
                   "<td><strong>Email</strong></td>" . 
                   "<td><strong>Last Shift</strong></td></tr>";
 }
 
 function PrintTableRow($mem, $mob, $ema, $cell4)       
 {
     echo "<tr>" .
          "<td>" . $mem . "</td>".
          "<td>" . $mob . "</td>".
          "<td>" . $ema  ."</td>".
          "<td>" . $cell4 . "</td>" .
          "</tr>";
}
function PrintTableFooter()
{
     echo "</table><hr>";
}  
?>
    </body>
</html>
