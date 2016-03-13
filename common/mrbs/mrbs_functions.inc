<?php

/* 
 * 
 * This file contains useful functions
 * for manipulating records retreived from the mrbs database
 * *
 * GetShiftNum  returns a shift number when passed a time and midnight for a given day 
 * GetShiftName returns the name of the shift when passed a shift number
 * GetShiftType($fshiftltr) get full name of a shift from reference num
 * Dlookup - copy of MSAccess dlookup function.
 * GetAvailableShifts($ftype,$fdate)  returns shifts on that date
 * DayShiftRequired($fdate) returns true fdate is if weekend or Bank Holiday
 * GenerateEmailRecipients($fcat) get list of email addresses for C or D or R
 * isBankHoliday(fdate)
 * 
 */
 // ==============  Functions =================================================
//=============================================================================
        function GetShiftNum($fstartsecs,$fstarttime) {
            $remainingsecs = $fstarttime - $fstartsecs;
            $numofhours = intval($remainingsecs / 3600);
            $shiftnum = ($remainingsecs - ($numofhours * 3600))/60;
            #echo "<br> Shift $shiftnum <br>";
            return $shiftnum;
        }
//=============================================================================
        function GetShiftName($fshiftnum){
            global $periods;
            return $periods[$fshiftnum];
        }
//==============================================================================
        function GetShiftType($fshiftltr){
            switch ($fshiftltr) {
                case "I":
                    $fshifttype = 'Mixed';
                    break;
                case "A":
                    $fshifttype = 'Car';
                    break;
                case "E":
                    $fshifttype = 'Bike';
                    break;
                default:
                    $fshifttype = 'Coordinator';
            }
         return $fshifttype;
        }
//==============================================================================
        function GetMobNum($fstr, $fld, $tab, $whr){
              $retval = DLookup($fld, $tab, $whr);
              if ($retval === "Not Found"):
                $needle ='Mobile Num';
                $fpos=strpos($fstr,$needle);
                $fpos2 =strpos($fstr,'Locality');
                if ($fpos2 !==false):
                  $fstrlen = $fpos2 - $fpos - 13;
                else:
                  $fstrlen = 14;
                endif;
                if ($fpos !== false):
                  $retval= substr($fstr,$fpos+12,$fstrlen);
                endif;    
              endif;
            
            return $retval;
        }
        
//==============================================================================
        function DLookup($fld, $tab, $whr)
        {
	  global $conn;
          $retval = "Not Found";
          $q = "Select $fld from $tab where $whr";
          
          if ($result=mysqli_query($conn,$q))
          {
            while ($row=mysqli_fetch_row($result))    // Fetch one and one row
            {
                $retval = $row[0];
            }
            //mysqli_free_result($result);            // Free result set
          }
	  return $retval;
          }
//===============================================================================
        function GetAvailableShifts($ftype,$fdate) {
            //Takes in a string for type of shift D R C
            //         a date
            // and returns the required shifts for that date of that type
            
            $ftype = substr(strtoupper($ftype),0,1);
            unset($fshifts);
            if ($ftype==="R"):                              //Riders
                if (DayShiftRequired($fdate)==1):
                    $fshifts = array("2"=>GetShiftName(2),"3"=>GetShiftName(3),
                        "4"=>GetShiftName(4),"5"=>GetShiftName(5),"6"=>GetShiftName(6));
                else:
                    $fshifts = array("2"=>GetShiftName(2),"4"=>GetShiftName(4),
                                        "6"=>GetShiftName(6));
                endif;
            elseif ($ftype === "D"):                        //Drivers
                if (DayShiftRequired($fdate)==1):
                    $fshifts = array("0"=>GetShiftName(0),"1"=>GetShiftName(1),
                                        "2"=>GetShiftName(2));
                else:
                    $fshifts = array("1"=>GetShiftName(1),"2"=>GetShiftName(2));
                endif;
            endif;
            return $fshifts;
        }
//==============================================================================
        function DayShiftRequired($fdate) {
            //takes in a timestamp ad checks if weekend or bank holiday
            $dsr = FALSE;
            if ((date("w",$fdate))==6) {$dsr=TRUE;}                     //Saturday
            if ((date("w",$fdate))==0) {$dsr=TRUE;}                     //Sunday
            if (isBankHoliday(date("m-n-Y",$fdate))==1) {$dsr=TRUE;}    //Bank Holiday
            return $dsr;
        }
        
// =============================================================================
        function GenerateEmailRecipients($fcat){
            // takes text paramater fcat can be D or R or C
            // returns email adresses for Drivers Riders or Controllers
        
            require_once '../contxt/madonapps.inc';                // sets environment Variables
            require_once '../contxt/mrbs_dbconnect.inc';           // set dbconnect strings
            
            global $DBServer, $DBUser, $DBPass, $DBName;
            unset($adreses);
            $fcat = substr(strtoupper($fcat),0,1);
            $conn = new mysqli($DBServer, $DBUser, $DBPass, $DBName);

            // check connection
            if ($conn->connect_error) {
                trigger_error('Database connection failed: '  . $conn->connect_error, E_USER_ERROR);
            }
    
            // Get record set
            $sql="SELECT name, email, registers FROM mrbs_users "
                    . "WHERE (registers LIKE '%$fcat%') ";
            $rs=$conn->query($sql);

            if($rs === false) {
                trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $conn->error, E_USER_ERROR);
            } else {
                $rows_returned = $rs->num_rows;
            }
            // iterate over record set
            $rs->data_seek(0);
            while($row = $rs->fetch_assoc()){
                $adreses[]=$row['email'];
            }
            return $adreses;
        }

//======================================================================
         
        function isBankHoliday($fdate) {
        // takes in a string formatted dd-mm-yyyy
        // and returns true if thatdate is a bank holiday
            $isBH = FALSE;
            
        //----------------------------------------------------------
        //  Now do actual checks     
        //-----------------------------------------------------------
            if (isNewYearsDay($fdate)===TRUE) {$isBH = TRUE;}
            if (isGoodFriday($fdate)===TRUE) {$isBH = TRUE;}
            if (isEasterMonday($fdate)===TRUE) {$isBH = TRUE;}
            if (isEarlyMonday($fdate)===TRUE) {$isBH = TRUE;}
            if (isLateMonday($fdate,"05")===TRUE) {$isBH = TRUE;}
            if (isLateMonday($fdate,"08")===TRUE) {$isBH = TRUE;}
            if (isChristmasDay($fdate)===TRUE) {$isBH = TRUE;}
            if (isBoxingDay($fdate)===TRUE) {$isBH = TRUE;}
            return $isBH;
       }

//=============================================================================
       function isBoxingDay($fdate) {
        // takes in a string formatted dd-mm-yyyy
        // and returns true if that date is boxing day bank holiday
            $isBH = FALSE;
            $bits = explode('-',$fdate);    // day - month - year
            $fyear = $bits[2];
        //  Now do actual checks            
        //  Boxing Day  - 26th Dec unless that falls on a weekend
            $dayfalls = date("w",strtotime("26-12-$fyear"));
            if ($dayfalls ==0):
                $tbh = "28-12-$fyear";
            elseif ($dayfalls == 6):
                $tbh = "28-12-$fyear";
            else:
                $tbh = "26-12-$fyear";
            endif; 
            if (strtotime($fdate) == strtotime($tbh)) {$isBH = TRUE;}
            return $isBH;
       }
       
//=============================================================================
       function isNewYearsDay($fdate) {
        // takes in a string formatted dd-mm-yyyy
        // and returns true if that date is new years day bank holiday
            $isBH = FALSE;
            $bits = explode('-',$fdate);    // day - month - year
            $fyear = $bits[2];
            $dayfalls = date("w",strtotime("01-01-$fyear"));
            if ($dayfalls ==0):
                $tbh = "02-01-$fyear";
            elseif ($dayfalls == 6):
                $tbh = "03-12-$fyear";
            else:
                $tbh = "01-01-$fyear";
            endif; 
            if (strtotime($fdate) == strtotime($tbh)) {$isBH = TRUE;}
            return $isBH;
       }
       
//=============================================================================
       function isChristmasDay($fdate) {
        // takes in a string formatted dd-mm-yyyy
        // and returns true if that date is xmas day bank holiday
            $isBH = FALSE;
            $bits = explode('-',$fdate);    // day - month - year
            $fyear = $bits[2];
            $dayfalls = date("w",strtotime("25-12-$fyear"));
            if ($dayfalls ==0):
                $tbh = "27-12-$fyear";
            elseif ($dayfalls == 6):
                $tbh = "27-12-$fyear";
            else:
                $tbh = "25-12-$fyear";
            endif; 
            if (strtotime($fdate) == strtotime($tbh)) {$isBH = TRUE;}
            return $isBH;
       }
       
//=============================================================================
       function isGoodFriday($fdate){
        // takes in a string formatted dd-mm-yyyy
        // and returns true if that date is good friday bank holiday
            $isBH = FALSE;
            $dayfalls = date("w",strtotime($fdate));
            if ($dayfalls == 5):                // is the date passed a friday
                $bits = explode('-',$fdate);    // day - month - year
                $fyear = $bits[2];                     
                $StartSecs=mktime(0, 0, 0, $bits[1], $bits[0], $bits[2]);      // for date passed          

                $yr=date("Y",easter_date($fyear)); 
                $mo=date("n",easter_date($fyear));
                $da=date("j",easter_date($fyear));
                $BHSecs=mktime(0, 0, 0, $mo, $da-2, $yr);      // for good friday
                if ($StartSecs === $BHSecs) {$isBH = TRUE;}
            endif;
            return $isBH;            
       }

//=============================================================================
       function isEasterMonday($fdate){
        // takes in a string formatted dd-mm-yyyy
        // and returns true if that date is easter monday bank holiday
            $isBH = FALSE;
            $dayfalls = date("w",strtotime($fdate));
            if ($dayfalls == 1):                // is the date passed a friday
                $bits = explode('-',$fdate);    // day - month - year
                $fyear = $bits[2];                     
                $StartSecs=mktime(0, 0, 0, $bits[1], $bits[0], $bits[2]);      // for date passed          

                $yr=date("Y",easter_date($fyear)); 
                $mo=date("n",easter_date($fyear));
                $da=date("j",easter_date($fyear));
                $BHSecs=mktime(0, 0, 0, $mo, $da+1, $yr);      // for good friday
                if ($StartSecs === $BHSecs) {$isBH = TRUE;}
            endif;
            return $isBH;
       }

//=============================================================================
       function isEarlyMonday($fdate){
        // takes in a string formatted dd-mm-yyyy 
        // and returns true if that date is early may bank holiday
            $isBH = FALSE;
            $bits = explode('-',$fdate);    // day - month - year
            $fyear = $bits[2];                     
            $StartSecs=mktime(0, 0, 0, $bits[1], $bits[0], $bits[2]);
            $dayfalls = date("w",strtotime("01-05-$fyear"));
            if ($dayfalls > 1):                
                $dbhm = 9-$dayfalls;
            elseif ($dayfalls == 0):
                $dbhm = 2;
            else:
                $dbhm = 1;
            endif;
            $BHSecs=mktime(0, 0, 0, 05, $dbhm, $fyear);      // for bh monday
            if ($StartSecs === $BHSecs) {$isBH = TRUE;}
            return $isBH;
       }
       
//=============================================================================
       function isLateMonday($fdate,$fmo){
        // takes in a string formatted dd-mm-yyyy and month 05 or 08
        // and returns true if that date is late monday bank holiday
            $isBH = FALSE;
            $bits = explode('-',$fdate);    // day - month - year
            $fyear = $bits[2];                     
            $StartSecs=mktime(0, 0, 0, $bits[1], $bits[0], $bits[2]);
            $dayfalls = date("w",strtotime("31-$fmo-$fyear"));
            if ($dayfalls == 1):                // is the date passed a friday
                $dbhm = 31;
            elseif ($dayfalls == 0):
                $dbhm = 31 - 6;
            else:
                $dbhm = 31 - $dayfalls + 1;
            endif;
            $BHSecs=mktime(0, 0, 0, $fmo, $dbhm, $fyear);      // for bh monday
            if ($StartSecs === $BHSecs) {$isBH = TRUE;}
            return $isBH;
       }