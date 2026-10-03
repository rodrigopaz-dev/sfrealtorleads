<?php
   require_once __DIR__ . "\\..\\config\\Connection.php";

   // Get connection
   $db = Database::getInstance();
   $db = $db->getConnection();


   $headers = "";
   $headers = "MIME-Version: 1.0\r\n";
   $headers.= "Content-type: text/html; charset=iso-8859-1\r\n";
   $headers.= "From: Realtor's Website < realtorwebsite@gmail.com >\r\n";
   
   
   /********** CODE FOR SEND EMAIL TO CLIENT AFTER 24 HOURS FROM CREATE LEAD **********/
   //FOR TESTING ONLY
   $emailClient = "rodrigo.paz.socialmedia@gmail.com";

   $subject = "Reminder after 24 hours!";
   $message = "Hi John,\r\nI am reminding you I am the Realtor";
   
   $sql = "SELECT * FROM LEADS WHERE STATUS = 'new' AND CREATED_AT = (SELECT DATE_SUB(CURDATE(), INTERVAL 1 DAY));";
   $stmt = $db->prepare($sql);
   $stmt->execute();

   $counter24h = 0;

   while($result = $stmt->fetch()) {
      $email = $result["email"];
      echo $email;
      //echo $result["Ayer"];
      //echo $result["full_name"] . " - " . $result["status"] . " - " . $result["created_at"];
      mail($emailClient, $subject, $message, $headers);
      $counter24h += 1;
   }


   /********** CODE FOR SEND EMAIL TO CLIENT AFTER 24 HOURS FROM CREATE LEAD **********/
   //FOR TESTING ONLY
   $emailClient = "rodrigo.paz.socialmedia@gmail.com";

   $subject = "Reminder after 3 DAYS!";
   $message = "Hi John,\r\nI am reminding again after 3 days maldita!";
   
   $sql = "SELECT * FROM LEADS WHERE STATUS = 'new' AND CREATED_AT = (SELECT DATE_SUB(CURDATE(), INTERVAL 3 DAY));";
   $stmt = $db->prepare($sql);
   $stmt->execute();

   $counter3d = 0;

   while($result = $stmt->fetch()) {
      $email = $result["email"];
      echo $email;
      //echo $result["Ayer"];
      //echo $result["full_name"] . " - " . $result["status"] . " - " . $result["created_at"];
      mail($emailClient, $subject, $message, $headers);
      $counter3d += 1;
   }

   echo "$counter24h emails sended after 24 hours<br>";
   echo "$counter3d emails sended after 3 days";
?>