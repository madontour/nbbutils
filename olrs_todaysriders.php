<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        //require_once '../nbbrota/config.nbbperiods.inc'; 
        require_once './common/mrbs/mrbs_periodnames2.inc';    // sets period names
        require_once './common/mrbs/mrbs_shifttypes.inc';      // sets shifttypes
        require_once './common/layout_functions.inc';          // common functions for table layouts
        require_once './common/mrbs/mrbs_functions.inc';       // define useful functions
        // get midnight today and midnight tomorrow as seconds
        $yr=date("Y"); 
        $mo=date("n");
        $da=date("j");
        $StartSecs=mktime(0, 0, 0, $mo, $da, $yr);
        $EndSecs=mktime(0, 0, 0, $mo, $da+1, $yr);
        #echo $da." ".$mo." ".$yr." ".$StartSecs." ".$EndSecs;
         
        // connect to the database
        $DBServer = 'localhost'; 
        $DBUser   = 'root';
        $DBPass   = '';
        $DBName   = 'nbb_rota';

        $conn = new mysqli($DBServer, $DBUser, $DBPass, $DBName);
 
        // check connection
        if ($conn->connect_error) {
            trigger_error('Database connection failed: '  . $conn->connect_error, E_USER_ERROR);
        }
        // Get record set
        $sql='SELECT start_time, name, type FROM mrbs_entry '
                . 'WHERE (start_time >= '.$StartSecs. ' AND start_time <' . $EndSecs .') '
                . 'ORDER BY start_time';
        #echo '<br>'.$sql.'<br>';
 
        $rs=$conn->query($sql);
 
        if($rs === false) {
            trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $conn->error, E_USER_ERROR);
        } else {
            $rows_returned = $rs->num_rows;
        }
        // if rows returned then output headers
      
        if($rows_returned > 0) {
            PrintTableHeader(4);
            unset($vars);
                $vars[] = "shift";
                $vars[] = "member";
                $vars[] = "mobile";
                $vars[] = "type";
            PrintTableRow1(4,$vars);
        }
     
        #echo 'Num of Rows '.$rows_returned.'<br>';
        // iterate over record set
        $rs->data_seek(0);
        while($row = $rs->fetch_assoc()){
            $shiftname = GetShiftName(GetShiftNum($StartSecs,$row['start_time']));
            $membername = $row['name'];
            $shifttype = GetShiftType($row['type']);
            $mobnum = GetMobileFromMemberName($membername);
            unset($vars);
                $vars[] = $shiftname;
                $vars[] = $membername;
                $vars[] = $mobnum;
                $vars[] = $shifttype;
            PrintTableRow(4,$vars);
        }
        PrintTableFooter();
        
        
        // ==============  Functions ======================
        Function GetMobileFromMemberName($fmember)
        {
            $whrstr = "name = '".$fmember."'";
            $retval = dlookup("mobile","mrbs_users",$whrstr);
            return $retval;
        }
        ?>
    </body>
</html>
