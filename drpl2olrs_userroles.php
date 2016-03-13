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
    drpl2olrs_userroles.php

    This script resets the entries in the mrbs_users table according to the
    data retrieved from the drupal database.  A users register entries will be 
    updated where necessary (including set to none for members removed from the
    registers

    07/03/2016  MT  First Version Prepared

        To add : capture users who should have an OLRS account but do not.
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
        require_once '../contxt/madonapps.inc';                // sets environment Variables
        
        
         
        // now set up lbraries - should be common for all machines and environments
        require_once './common/phpmailer/class.phpmailer.php';
        require_once './common/phpmailer/class.smtp.php';
        
        require_once './common/mrbs/mrbs_periodnames.inc';    // sets period names
        require_once './common/mrbs/mrbs_functions.inc';      // define useful functions
       

        //require_once './drpl2olrs_userroles.ini';                       // default params & constants
/*
  -------------------------------------------------------------------------------------        
         Real code starts here
  --------------------------------------------------------------------------------------
*/
        // get current time as seconds   
        $msgtxt ="";
        $myTime=date_format(date_create(),"U");
      
    // connect to the database

        require '../contxt/drpl_dbconnect.inc';                     // set dbconnect strings for drupal
        $conn = new mysqli($DBServer, $DBUser, $DBPass, $DBName);
 
    // check connection
        if ($conn->connect_error) {
            trigger_error('Database connection failed: '  . $conn->connect_error, E_USER_ERROR);
        }
    // Get record set
        $sql='SELECT users_roles.rid, users_roles.uid, users.status
                FROM users_roles
                INNER JOIN users
                ON users_roles.uid=users.uid 
                WHERE users.status=TRUE and users_roles.rid IN (5,11,12,15)
                ORDER BY users_roles.uid';
        $rs=$conn->query($sql);                                 // create record set
 
        if($rs === false) {
            trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $conn->error, E_USER_ERROR);
        } else {
            $rows_returned = $rs->num_rows;
        }
        // echo 'Num of Rows '.$rows_returned.'<br>';
        
 /* ==================================================================================
    
    Rows retrieved from Drupal
    Now connect to OLRS and prepare the update statement
  
    ==================================================================================
  */
        require '../contxt/mrbs_dbconnect.inc';                     // set dbconnect strings for olrs/mrbs
        $conn2 = new mysqli($DBServer, $DBUser, $DBPass, $DBName);
 
    // check connection
        if ($conn2->connect_error) {
            trigger_error('Database connection failed: '  . $conn2->connect_error, E_USER_ERROR);
        }
       
    //  now process the drupal recordset
        
        $rs->data_seek(0);                                                  // go to start of record set
        $uid = 0;
        unset($drupaluids);
        unset($drupalroles);
        $registers = "";
        while($row = $rs->fetch_assoc()){                                   // iterate over record set
            $myuid=$row['uid'];
            $myrid=$row['rid'];
            $drupaluids[]=$myuid;
            if ($uid == $myuid)  {                                          //   this uid is same as previous uid
                    $registers = $registers . role2char($myrid);            //   so convert number to Char and append it
            } else {
                    if ($uid<>0) {                                          //  new uid so update the record
                        $sqlu="UPDATE mrbs_users " .
                                "SET registers = '" . $registers .
                                "', lastupdate = " . $myTime .
                                 " WHERE uid = " . $uid ;    
                        $drupalroles[$uid]=$registers;
                        if ($conn2->query($sqlu) === TRUE) {
                            // echo "Record $uid updated successfully <br>";
                        } else {
                            echo "Error updating record $uid: " . $conn2->error . "<br>";
                        }       
                    }
                    $uid = $myuid;                                          //  reset uid
                    $registers = role2char($myrid)  ;                       //  reset registers text    
            }    
            if ($uid<>0) {                                          //  process the last record
                $sqlu="UPDATE mrbs_users " .
                        "SET registers = '" . $registers .
                        "', lastupdate = " . $myTime .
                         " WHERE uid = " . $uid ;    
                $drupalroles[$uid]=$registers;
                if ($conn2->query($sqlu) === TRUE) {
                    // echo "Record $uid updated successfully <br>";
                } else {
                    echo "Error updating record $uid: " . $conn2->error . "<br>";
                }       
            }  
        }
        echo "all register updates done <br>";
  /* ==================================================================================
    
    All active users updated
    Now check the users not currently active in Drupal and reset OLRS
  
    ==================================================================================
  */       
        $sqlu = "UPDATE mrbs_users " .
                    "SET registers = '" . "----" .
                                "', password = '" . md5("n0t@ctive") .
                                 "' WHERE lastupdate <> " . $myTime . " AND uid > 0" ;     
                        if ($conn2->query($sqlu) === TRUE) {
                            echo "Disused Records updated successfully <br>";
                        } else {
                            echo "Error updating record disused records: " . $conn2->error . "<br>";
                        }
 
 /* ==================================================================================
    
   Now check for drupal users who don't exist in OLRS
  
    ==================================================================================
  */  
       $sql2='SELECT uid FROM mrbs_users
                WHERE uid > 0 ORDER BY uid';
        $rs2=$conn2->query($sql2);                                 // create record set
 
        if($rs2 === false) {
            trigger_error('Wrong SQL: ' . $sql2 . ' Error: ' . $conn->error, E_USER_ERROR);
        } else {
            $rows_returned = $rs->num_rows;
        }                 
                  
        $rs2->data_seek(0);                                                  // go to start of record set
        unset($olrsuids);
        while($row = $rs2->fetch_assoc()){                                   // iterate over record set
            $myuid=$row['uid'];
            $olrsuids[]=$myuid;
        }
        
        $newusers = array_diff(array_unique($drupaluids), $olrsuids);
        echo "<br><strong> New users required</strong><br>";
        foreach($newusers as $newuid) {
            $knownexceptions = array(12,56);
            if (!in_array($newuid,$knownexceptions)){
                $sql3 = "Select name FROM users WHERE uid = ". $newuid;
                $rs3=$conn->query($sql3);                                 // create record set
                if($rs3 === false) {
                    trigger_error('Wrong SQL: ' . $sql3 . ' Error: ' . $conn->error, E_USER_ERROR);
                } else {
                    $rows_returned = $rs->num_rows;
                }                 
                $rs3->data_seek(0);                                                  // go to start of record set
                $row = $rs3->fetch_assoc();
                $newname = $row['name'];
                $myindex=$newuid;
                echo "No OLRS record for $newuid , $newname roles are $drupalroles[$myindex] <br>";
            }
        }
        die("all done - script end");
         
 /* ==================================================================================
    
    Functions start here
  
    ==================================================================================
  */    
        
function role2char($rolenum){
    switch ($rolenum) {
        case 5:
            $role2char = '#';
            break;
        case 11:
            $role2char = 'R';
            break;
        case 15:
            $role2char = 'D';
            break;
        case 12:
            $role2char = 'C';
            break;
        default:
            $role2char = '?';
            break; 
    }
    
    return $role2char;
}
    
?>
    </body>
</html>
