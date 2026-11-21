<?php
   require_once __DIR__ . "/../config/Connection.php";
   require_once __DIR__ . '/../vendor/autoload.php';

   $dotenv = Dotenv\Dotenv::createImmutable('/home/u279829464/domains/sfleads.rodrigopazz.com');
   $dotenv->load();

   // Classes from PHPMailer
   use PHPMailer\PHPMailer\PHPMailer;
   use PHPMailer\PHPMailer\Exception;

   // Get connection
   $db = Database::getInstance();
   $db = $db->getConnection();
   

   try {
      // ==========================================
      // HOSTING CONFIGURATION
      // ==========================================
      $smtpHost     = $_ENV['SMTP_HOST'];       // Hosting's SMPT Server
      $smtpUsername = $_ENV['SMTP_USERNAME'];   // Hosting email for notifications
      $smtpPassword = $_ENV['SMTP_PASSWORD'];   // Email password
      $smtpPort     = $_ENV['SMTP_PORT'];       // Port SSL
      $smtpCharSet  = $_ENV['SMTP_CHARSET'];    // Charset


      /******* 1. EMAIL FOR CLIENT AFTER 24 HOURS *******/
      $sql = "SELECT l.*, r.NAME FROM LEADS l INNER JOIN REALTORS r ON l.REALTOR_ID = r.ID WHERE l.STATUS = 'new' AND l.CREATED_AT = (SELECT DATE_SUB(CURDATE(), INTERVAL 1 DAY));";
      $stmt = $db->prepare($sql);
      $stmt->execute();

      $counter24h = 0;
      $clients = [];
      while($result = $stmt->fetch()) {
         $clientName = $result["full_name"];
         $clientEmail = $result["email"];
         $realtorName = $result["name"];
         
         $client = [];
         $client["name"] = $clientName;
         $client["email"] = $clientEmail;
         $client["realtorName"] = $realtorName;

         $clients[] = $client;
         $counter24h += 1;
      }

      $mail = new PHPMailer(true);
      $mail->isSMTP();                                            
      $mail->Host       = $smtpHost;
      $mail->SMTPAuth   = true;
      $mail->Username   = $smtpUsername;
      $mail->Password   = $smtpPassword;
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
      $mail->Port       = $smtpPort;
      $mail->CharSet    = $smtpCharSet;

      $mail->SMTPKeepAlive = true;  // Keep connection open with SMTP Server
      $mail->setFrom($smtpUsername, 'SF Realtor Leads');
      
      $mail->isHTML(true);                                  
      $mail->Subject = 'Your enquiry from yesterday about your next House';
      
      foreach($clients as $client) {
         $mail->addAddress($client["email"], $client["name"]);
         $mail->Body    = "
            Hello {$client['name']}, I am {$client['realtorNname']}. I saw your request yesterday about a propertie and I want to make sure you can get your goals. I had a full day of visits and I was unable to get in touch with you. Are you still looking for options and help?
         ";
         
         $mail->send();
         $mail->clearAddresses();
      }
      
      $mail->smtpClose();


      /*******21. EMAIL FOR CLIENT AFTER 3 DAYS *******/
      $sql = "SELECT l.*, r.NAME, r.EMAIL AS realtor_email FROM LEADS l INNER JOIN REALTORS r ON l.REALTOR_ID = r.ID WHERE l.STATUS = 'new' AND l.CREATED_AT = (SELECT DATE_SUB(CURDATE(), INTERVAL 3 DAY));";
      $stmt = $db->prepare($sql);
      $stmt->execute();

      $counter3d = 0;
      $clients = [];
      while($result = $stmt->fetch()) {
         $clientName = $result["full_name"];
         $clientEmail = $result["email"];
         $realtorName = $result["name"];
         $realtorEmail = $result["realtor_email"];
         
         $client = [];
         $client["name"] = $clientName;
         $client["email"] = $clientEmail;
         $client["realtorName"] = $realtorName;
         $client["realtorEmail"] = $realtorEmail;

         $clients[] = $client;
         $counter3d += 1;
      }

      $mail = new PHPMailer(true);
      $mail->isSMTP();                                            
      $mail->Host       = $smtpHost;
      $mail->SMTPAuth   = true;
      $mail->Username   = $smtpUsername;
      $mail->Password   = $smtpPassword;
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
      $mail->Port       = $smtpPort;
      $mail->CharSet    = $smtpCharSet;

      $mail->SMTPKeepAlive = true;  // Keep connection open with SMTP Server
      $mail->setFrom($smtpUsername, 'SF Realtor Leads');
      
      $mail->isHTML(true);                                  
      $mail->Subject = 'Did you stop your search?';
      
      foreach($clients as $client) {
         $mail->addAddress($client["email"], $client["name"]);
         $mail->Body    = "
            Hello {$client['name']} this is {$client['realtorName']}. I am writing to you for the last time this week so as not to overwelhm you. Since I didn't get a respond to my last message, I assume you stop you search for help with a propertie or you are too bussy. If you are still interest and need some help you can send me an email at {$client['realtorEmail']}
         ";
         
         $mail->send();
         $mail->clearAddresses();
      }
      
      $mail->smtpClose();

      echo "<script>console.log('$counter24h emails sended after 24 hours')</script>";
      echo "<script>console.log('$counter3d emails sended after 3 days')</script>";

   }catch(Exception $e) {
      // Captura errores de PHPMailer o excepciones personalizadas
      error_log("Error en el proceso: " . $e->getMessage());
      http_response_code(500);
      echo json_encode([
         "status" => "error", 
         "message" => "There was a problem processing your request.",
         "debug" => $e->getMessage() // Quitar en producción
      ]);
   } catch (\PDOException $e) {
      // Captura errores de Base de Datos
      error_log("Error de BD: " . $e->getMessage());
      http_response_code(500);
      echo json_encode([
         "status" => "error", 
         "message" => "Database storage failed."
      ]);
   }
?>