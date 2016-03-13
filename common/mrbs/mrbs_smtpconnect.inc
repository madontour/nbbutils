<?php

/* 
 * Details for Google Mail when Sending Messages from OLRS
 * 
 */
$mail->IsSMTP();
$mail->Mailer = 'smtp';
$mail->SMTPAuth = true;
$mail->Host = 'smtp.gmail.com'; 
$mail->SMTPSecure = "tls";
$mail->Port = 587;
$mail->Username = 'robot@northumbriabloodbikes.org.uk';
$mail->Password = 'mtROBOTpwd';
$mail->isHTML(true);                        // Set email format to HTML
$mail->From = 'robot@northumbriabloodbikes.org.uk';
$mail->FromName = 'NBB Rota Manager';