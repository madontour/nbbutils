<?php

/* 
 * The copyright to this code belongs to Northumbria Blood Bikes
 * This code package will have an included LICENSE that you should view
 *  .
 * Index.php         21/3/2016     MT     First version
 */
function PrintTableHeader($numcols)
 {
    echo '<div style="overflow-x:auto;">'; 
    echo '<table>';
     
    if($numcols>0){
        $cw = 1280 / $numcols;
        $str = "";
        for ($l=1; $l<=$numcols; $l++ ){
            $str = $str . '<col width="'. $cw .'">';
        }
        echo $str;
     }
 
 }
 function PrintTableHeaderBespoke($numcols,$fsizes)
 {
    echo '<div style="overflow-x:auto;">'; 
    echo '<table>';
     
    if($numcols>0){
        PrintBlankRow($numcols);
        $cw = 1280 / $numcols;
        $str = "";
        foreach($fsizes as $var){
          $str = $str . '<col width="'. $var . '">';
        }
        echo $str;
        PrintBlankRow($numcols);
        
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
     echo "</table></div><hr>";
}  
function PrintBlankRow($numcols)       
 {
     $str = "";
     $str = $str . "<tr>" ;
    for ($l=1; $l<=$numcols; $l++ ){
            $str = $str . "<td>" . ' ' . "</td>";
        }
     $str = $str ."</tr>";
     echo $str;
}
function PrintDividerRow($numcols)       
 {
     $str = "";
     $str = $str . "<tr>" ;
    for ($l=1; $l<=$numcols; $l++ ){
            $str = $str . "<td>" . '---' . "</td>";
        }
     $str = $str ."</tr>";
     echo $str;
}