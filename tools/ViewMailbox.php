<?php

	set_time_limit(3000);
	
	//set_error_handler("errorPipe");
	//define("LOGPATH","logs/");
	//define("MAILTOUSERONERROR","mail@example.com");
	
	
$ihostname = '{smtp.example.com:993/imap/ssl}INBOX';
$iusername = 'ftp@example.com';
$ipassword = 'password';
	

//all script errors ie, db connetion
function errorPipe($errno, $errstr) {
	$fullErrorResults = "[". time() ."] Error:". $errno ."-". $errstr ."\r\n";
	//mail(MAILTOUSERONERROR,"result error",$fullErrorResults);
	file_put_contents(LOGPATH."errorLog.txt",$fullErrorResults, FILE_APPEND | LOCK_EX);
	die();
 }

 //function- custom error info and exit.
function errorCustomPipe($emailTitle, $MessageToSend){
	//mail(MAILTOUSERONERROR,$emailTitle,$MessageToSend);
	//save to log file- ie sender-date.txt
	die();
}

//here we must check the imap folder for the email with the unique subject name- then read the attachment.	


/* try to connect */
$inbox = imap_open($ihostname,$iusername,$ipassword);

//takes headerSubject to find the other email
$emails = imap_search($inbox, 'UNANSWERED');

//var_dump($emails);

if ($emails == False){	
	//errorCustomPipe("Error: no imap emails with that subject found", $stdinData);
}

//if (count($emails) > 1){
	//errorCustomPipe("Error: multiple imap emails found #".count($emails)." with same subject.", $stdinData);
//}

//var_dump($emails);

//echo count($emails); 

        $attachments = array();
 
	/* for every email... */
	foreach($emails as $email_number) {
		
		/* get information specific to this email */
		$overview = imap_fetch_overview($inbox,$email_number,0);
		$message = imap_fetchbody($inbox,$email_number,2);
		
		/* output the email header information */
		//$output.= '<div>';
		$output.= '<span class="subject">'.$overview[0]->subject.'</span> ';
		$output.= '<span class="from">'.$overview[0]->from.'</span>';
		$output.= '<span class="date">on '.$overview[0]->date.'</span>';
		$output.= '</div>';
		
		/* output the email body */
		$output.= '<div class="body">'.$message.'</div><hr>';
	}
	
	echo $output;

	
/* close the connection */
imap_close($inbox); 
	
?>