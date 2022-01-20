#!/usr/local/bin/php -q
<?php

$email_content = "";

//saves email info
$fd = fopen("php://stdin", "r");

while (!feof($fd)) {
$email_content .= fread($fd, 1024);
}
fclose($fd); 


mail("mail@example.com","Results",$email_content);

 

?>