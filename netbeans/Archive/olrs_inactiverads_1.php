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
      olrs_inactiverads_1.php

    This script checks the entries in the mrbs_entries table checking for riders
    and drivers (rads) who are still active (ie they exist in the users table)
    but have no recorded shifts.

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
        $sql="SELECT u.name, u.registers, u.mobile, u.email FROM mrbs_users u " . 
             " WHERE u.name NOT IN " .
             " (SELECT e.name from mrbs_entry e " . 
             " WHERE e.type IN (" . $myShiftTypes. ")) " . 
             " AND (LOCATE('R',u.registers)>0 OR LOCATE('D',u.registers)>0)" .
             " ORDER BY u.name";
                
        $rs=$conn->query($sql);                                 // create record set
 
        if($rs === false) {
            trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $conn->error, E_USER_ERROR);
        } else {
            $rows_returned = $rs->num_rows;
        }
        // echo 'Num of Rows '.$rows_returned.'<br>';
        if ($rows_returned > 0){
            echo "<strong>Report of NBB Riders and Drivers with no recorded shifts <br>" .
                  "</strong> <br><br>";
                  PrintTableHeader();
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
                                                                            //strpos returns num or false - but position
                                                                            // will be zero so check for boolean
            if (is_bool(strpos($myUserName,'~')))  {  
                PrintTableRow(ucwords($myUserName), $row['mobile'], 
                        strtolower($row['email']));
                $NumInactiveRads +=1;   
            }    
              
        }
        PrintTableFooter();
        echo "<br><hr><br> Number of members listed ~ ".$NumInactiveRads;
        die("<br> all done - script ended");
         
 /* ==================================================================================
    
    Functions start here
  
    ==================================================================================
  */    
 function PrintTableHeader()
 {
     echo '<table style=width:"95%">';
     echo '<col width="200">' .
          '<col width="200">' .
          '<col width="350">';
     PrintTableRow("<strong>Member</strong>",
                   "<strong>Mobile</strong>", 
                   "<strong>Email</strong>");
 }
 function PrintTableRow($mem, $mob, $ema)       
 {
     echo "<tr>" .
          "<td>" . $mem . "</td>".
          "<td>" . $mob . "</td>".
          "<td>" . $ema  ."</td>".
          "</tr>";
}
function PrintTableFooter()
{
     echo "</table>";
}
    
?>
    </body>
</html>
