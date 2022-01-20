<?php

	set_time_limit(3000);
	set_error_handler("errorPipe");
	//define("LOGPATH","smftp/logs/");
	define("MAILTOUSERONERROR","mail@example.com");
	define("IMAPHOSTNAME","smtp.example.com");

//all script errors ie, db connetion
function errorPipe($errno, $errstr) {
	$fullErrorResults = "[". time() ."] Error:". $errno ."-". $errstr ."\r\n";
	mail(MAILTOUSERONERROR,"result error",$fullErrorResults);
	file_put_contents(LOGPATH."errorLog.txt",$fullErrorResults, FILE_APPEND | LOCK_EX);
	die();
 }

//function- custom error info and exit.
function errorCustomPipe($emailTitle, $MessageToSend){
	mail(MAILTOUSERONERROR,$emailTitle,$MessageToSend);
	//save to log file- ie sender-date.txt
	die();
}

//-=-=-=-=-=-=-=-=- PROCESS EMAIL

// fetch data from stdin
$stdinData = file_get_contents("php://stdin");

// explode on new line
$data = explode("\n", $stdinData);

// define a variable map of known headers
$patterns = array(
  'Return-Path',
  'X-Original-To',
  'Delivered-To',
  'Received',
  'To',
  'Message-Id',
  'Date',
  'From',
  'Subject',
  'SMFTP-AUTH',
  'SMFTP-USERID',
);

// define a variable to hold parsed headers
$headers = array();

// loop through data
foreach ($data as $data_line) {

  // for each line, assume a match does not exist yet
  $pattern_match_exists = false;

  // check for lines that start with white space
  // NOTE: if a line starts with a white space, it signifies a continuation of the previous header
  if ((substr($data_line,0,1)==' ' || substr($data_line,0,1)=="\t") && $last_match) {

    // append to last header
    $headers[$last_match][] = $data_line;
    continue;

  }

  // loop through patterns
  foreach ($patterns as $key => $pattern) {

    // create preg regex
    $preg_pattern = '/^' . $pattern .': (.*)$/';

    // execute preg
    preg_match($preg_pattern, $data_line, $matches);

    // check if preg matches exist
    if (count($matches)) {

      $headers[$pattern][] = $matches[1];
      $pattern_match_exists = true;
      $last_match = $pattern;

    }

  }

  // check if a pattern did not match for this line
  if (!$pattern_match_exists) {
    $headers['UNMATCHED'][] = $data_line;
  }

}

	if (preg_match("'<(.*?)>'si", $headers['From'][0], $MatchHasBrackets)) {
			$from = $MatchHasBrackets[1];
		}else{
			$from = $headers['From'][0];
		}
//	mail(MAILTOUSERONERROR,"result errorheaders",$headers['SMFTP-USERID'][0]); //this will email full headers
	
//$headers['SMFTP-USERID'];
//$headers['SMFTP-AUTH'];
	
if (!isset($headers['SMFTP-USERID'])){
	errorCustomPipe("Error: NO SMFTP-USERID SPECIFIED", $stdinData);
}
//if (!isset($headers['SMFTP-AUTH'])){
//	errorCustomPipe("Error: NO SMFTP-AUTH SPECIFIED", $stdinData);
//}

//more error checking here
	
//here we need to check if from address is in the allowed list and subject contains usercode:

	//check if from address and subject match db
    require_once 'db.php';
	$authDB = 0;
	$result = mysqli_query($conn, "SELECT * FROM USERDB WHERE uid = '".$headers['SMFTP-USERID'][0]."' AND user = '$from'");
	if (mysqli_num_rows($result) == '1'){
		//here we load other info such as ftp details and security checking
		$authDB = 1;
	}
	mysqli_close($conn);	

	
if ($authDB == 0){
	errorCustomPipe("Error: User not found in DB", $stdinData);
}


//here we have the uid and email to make into 1stkey, then use that key and authcode to make key- key is in db with ftp details
//echo "Blowfish: ".crypt('something','$2a$09$k47UqzWTyGsYC4hGZLgY34$')."\n<br>";

//	mail(MAILTOUSERONERROR,"result errorheaders",$stdinData); //this will email full headers
	
//here we must check the imap folder for the email with the unique subject name- then read the attachment.	
$ihostname = '{'.IMAPHOSTNAME.':993/imap/ssl}INBOX';
$iusername = 'mail@example.com'; # e.g somebody@gmail.com
$ipassword = 'password';

/* try to connect */
$inbox = imap_open($ihostname,$iusername,$ipassword);

//takes headerSubject to find the other email
$emails = imap_search($inbox, 'SUBJECT "'.$headers['Subject'][0].'" FROM "'.$from.'"');

//var_dump($emails);

if ($emails == False){	
	errorCustomPipe("Error: no imap emails with that subject found", $stdinData);
}

if (count($emails) > 1){
	errorCustomPipe("Error: multiple imap emails found #".count($emails)." with same subject.", $stdinData);
}

//var_dump($emails);

//echo count($emails); 

        $attachments = array();
 
/* if any emails found, iterate through each email */
if(count($emails) == 1 && $emails == True) {
// echo "yes";
    /* put the newest emails on top */
    //rsort($emails);
 
 
    /* for every email... */
    foreach($emails as $email_number) 
    {
	//echo $emails[0];
        /* get information specific to this email */
        $overview = imap_fetch_overview($inbox,$email_number,0);
 
        /* get mail message, not actually used here. 
           Refer to http://php.net/manual/en/function.imap-fetchbody.php
           for details on the third parameter.
         */
        $message = imap_fetchbody($inbox,$email_number,2);
 
        /* get mail structure */
        $structure = imap_fetchstructure($inbox, $email_number);
 

 
        /* if any attachments found... */
        if(isset($structure->parts) && count($structure->parts)) 
        {
            for($i = 0; $i < count($structure->parts); $i++) 
            {
                $attachments[$i] = array(
                    'is_attachment' => false,
                    'filename' => '',
                    'name' => '',
                    'attachment' => ''
                );
 
                if($structure->parts[$i]->ifdparameters) 
                {
                    foreach($structure->parts[$i]->dparameters as $object) 
                    {
                        if(strtolower($object->attribute) == 'filename') 
                        {
                            $attachments[$i]['is_attachment'] = true;
                            $attachments[$i]['filename'] = $object->value;
                        }
                    }
                }
 
                if($structure->parts[$i]->ifparameters) 
                {
                    foreach($structure->parts[$i]->parameters as $object) 
                    {
                        if(strtolower($object->attribute) == 'name') 
                        {
                            $attachments[$i]['is_attachment'] = true;
                            $attachments[$i]['name'] = $object->value;
                        }
                    }
                }
 
                if($attachments[$i]['is_attachment']) 
                {
                    $attachments[$i]['attachment'] = imap_fetchbody($inbox, $email_number, $i+1);
 
                    /* 3 = BASE64 encoding */
                    if($structure->parts[$i]->encoding == 3) 
                    { 
                        $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                    }
                    /* 4 = QUOTED-PRINTABLE encoding */
                    elseif($structure->parts[$i]->encoding == 4) 
                    { 
                        $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                    }
                }
            }
        }
 
// hhhhh
 

    }
 
} 

/* close the connection */
imap_close($inbox); 

//imap is closed - can process attachments. 
 
//imap portian has been done and now we can do furthe error checking
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=--=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=- 
 
//echo count($attachments); 

if ($attachments){

//here we connect to database and get ftp details.
//we need to do this from the userid

	$authDB = 0;
	$result = mysqli_query($conn, "SELECT * FROM FTPDB WHERE uid = '".$headers['SMFTP-USERID'][0]."'");
	if (mysqli_num_rows($result) == '1'){
		//here we load other info such as ftp details and security checking
		$ftpUser = $row['user'];
		$ftpPass = $row['pass'];
		$ftpHost = $row['host'];
		$authDB = 1;
	}
	mysqli_close($conn);

if ($authDB == 0){
	errorCustomPipe("FTP Details incorrect.", $stdinData);
}

//here we connect to ftp server and send the attachment(s). 

// connect and login to FTP server
$ftp_conn = ftp_connect($ftpHost);
$login = ftp_login($ftp_conn, $ftpUser, $ftpPass);


$tempHandle = fopen('php://temp', 'r+');

 
 
        /* iterate through each attachment and save it */
        foreach($attachments as $attachment)
        {
            if($attachment['is_attachment'] == 1)
            {
                $filename = $attachment['name'];
                if(empty($filename)) $filename = $attachment['filename'];
 
                if(empty($filename)) $filename = time() . ".dat"; //?
 
                /* prefix the email number to the filename in case two emails
                 * have the attachment with the same file name.
                 */
				 
				//echo $attachment['attachment'];
fwrite($tempHandle, $attachment['attachment']);
rewind($tempHandle);
				
// upload file
if (ftp_fput($ftp_conn, $attachment['filename'], $tempHandle, FTP_BINARY))
  {
  echo "Successfully uploaded";
  //check file size is same etc.
  }
else
  {
  echo "Error uploading";
  }
				
				 
				 
                //$fp = fopen("./" . $email_number . "-" . $filename, "w+");
                //fwrite($fp, $attachment['attachment']);
                //fclose($fp);
            }
 
        } 
		
// close this connection and file handler
ftp_close($ftp_conn);		
			
}

if (count($attachments) == 0 && count($emails) == 1 && $emails <> False){
	echo "no attachments found in email sent";
}
	//mail(MAILTOUSERONERROR,"result headers",$headers['Message-Id'][0]);	



?>