<?php

/* 
 * The copyright to this code belongs to Northumbria Blood Bikes
 * This code package will have an included LICENSE that you should view
 *  .
 * Index.php         21/3/2016     MT     First version
 */
function PrintTableHeader($numcols)
 {
     echo '<table style=width:"99%">';
     if($numcols>0){
        $cw = 100 / $numcols;
        $str = "";
        for ($l=1; $l<5; $l++ ){
            $str = $str . '<col width="'. $cw . '%">';
        }
        echo $str;
     }
 }
 function PrintTableRow1($numcols, $fvars)       
 {
     $str = "";
     $str = $str . "<tr>" ;
     foreach($fvars as $var){
          $str = $str . "<td><strong>" . $var . "</strong></td>";
     }
     $str = $str ."</tr>";
     echo $str;    
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
function PrintTableFooter()
{
     echo "</table><hr>";
}  
