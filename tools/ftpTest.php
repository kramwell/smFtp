<?php

//here we connect to ftp server and send the attachment(s).

$ftp_server = "ftp.example.com";
$ftp_username = "ftp@example.com";
$ftp_userpass = "password";

// connect and login to FTP server
$ftp_conn = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");
$login = ftp_login($ftp_conn, $ftp_username, $ftp_userpass);

// open file for reading
$string = "Your content goes here";
$stream = fopen('data://text/plain,' . $string,'r');

// upload file
if (ftp_fput($ftp_conn, "somefile.txt", $stream, FTP_ASCII))
  {
  echo "Successfully uploaded";
  }
else
  {
  echo "Error uploading";
  }

// close this connection and file handler
ftp_close($ftp_conn);

?>