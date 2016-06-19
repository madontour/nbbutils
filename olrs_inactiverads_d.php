
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
    // Get record set 1
    //<editor-fold> 
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
            unset($vars);
            $vars[] = array('Member', 
                            'Mobile Num',
                            'Email Address', 
                            'Date of Last Shift');
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
                    $vars[] = array(ucwords($myUserName), FormatMobileNum($row['mobile']),
                         strtolower($row['email']), date("j F, Y ",$myStartTime));
                    $NumInactiveRads +=1; 
                }
            }    
              
        }
    //</editor-fold>    
    // Get and process record set 2  - never booked a shift
    //<editor-fold> Record set 2
        
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
        
    //Rows retrieved from MRBS Now process the records 
        
        $rs->data_seek(0);                                                  // go to start of record set
        while($row = $rs->fetch_assoc()){                                   // iterate over record set
            $myUserName=$row['name'];
                                                                            //strpos returns num or false - but position
                                                                            // will be zero so check for boolean
            if (is_bool(strpos($myUserName,'~')))  {  
                $vars[] = array(ucwords($myUserName), 
                                FormatMobileNum($row['mobile']),
                                strtolower($row['email']), 
                                'Never');
                $NumInactiveRads +=1;   
            }         
        }
        
    //</editor-fold> End record set 2    
        
    //  Now send the file to download    
        
    array_to_csv_download($vars);
        
         
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
 
 function PrintTableRow($numcols, $fvars)       
 {
     $str = "";
     $str = $str . "<tr>" ;
     foreach($fvars as $var){
          $str = $str . "<td>" . $var . "</td>";
     }
     $str = $str ."</tr>";
     echo $str;
}
function FormatMobileNum($num){
     if(strlen($num)==11){
       return (substr($num,0,5). " " . substr($num,6)); 
     } else {
        return $num;
     }
     
}  
function array_to_csv_download($array, $filename = "OLRS_export.csv", $delimiter=",") {
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'";');

    // open the "output" stream
    // see http://www.php.net/manual/en/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-descriptioq
    $f = fopen('php://output', 'w');

    foreach ($array as $line) {
        fputcsv($f, $line, $delimiter);
    }
}   
?>

