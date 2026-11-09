<?php
   
   // 1. Permitir cualquier origen (o cambia * por tu dominio específico)
   header("Access-Control-Allow-Origin: *");

   // 2. Autorizar los métodos que vas a usar (POST para los datos, OPTIONS para el navegador)
   header("Access-Control-Allow-Methods: POST, OPTIONS");

   // 3. Permitir que se envíe la cabecera Content-Type (necesaria para JSON)
   header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

   // 4. Responder con éxito inmediato a la petición "Preflight" (OPTIONS) del navegador
   if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      http_response_code(200);
      exit();
   }

   // 5. Validar que realmente sea una petición POST antes de procesar
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      echo json_encode(["error" => "Method not permitted. Use POST."]);
      exit();
   }

   // Leer JSON
   $input = file_get_contents("php://input");
   $data = json_decode($input, true);
   echo $data;

   // (Opcional) Responder al cliente para confirmar la recepción
   header("Content-Type: application/json");
   echo json_encode(["status" => "success", "received" => $data]);

   
   require_once __DIR__ . "/../config/Connection.php";
   // Composer autoload
   require_once __DIR__ . '/../vendor/autoload.php';

   // Classes from PHPMailer
   use PHPMailer\PHPMailer\PHPMailer;
   use PHPMailer\PHPMailer\Exception;
   use PHPMailer\PHPMailer\SMTP;

   
   // Get connection
   $db = Database::getInstance();
   $db = $db->getConnection();

   // Función helper
   function getField($data, $possibleNames) {
      foreach($possibleNames as $name) {
         if(isset($data[$name]) && !empty($data[$name])) {
            return $data[$name];
         }
      }
      return null;
   }

   //Intentar mapear campos comunes
   $realtorId    = getField($data, ["realtor_id"]);
   $fullName     = getField($data, ["name", "full_name", "firstName"]);
   $type         = getField($data, ["type","type_of","type-of","kinda","kind_of","kind-of"]);
   $emailClient  = getField($data, ["email", "email_address"]);
   $phone        = getField($data, ["phone", "tel", "mobile"]);
   $budget       = getField($data, ["budget"]);
   $interestArea = getField($data, ["interest_area"]);
   $message      = getField($data, ["message", "comments"]);
   $status       = "new";
   $createdAt    = date('Y/m/d');

   // Guardar TODO el JSON
   $rawData = json_encode($data);

   
   //INSERT IN LEADS TABLE
   $sql = "INSERT INTO LEADS (REALTOR_ID, FULL_NAME, TYPE, EMAIL, PHONE, BUDGET, INTEREST_AREA, STATUS, CREATED_AT, RAW_DATA)
   VALUES (:realtorId, :fullName, :type, :email, :phone, :budget, :interestArea, :status, :createdAt, :rawData);";
   
   $stmt = $db->prepare($sql);
   $stmt->execute(array("realtorId" => $realtorId, "fullName" => $fullName, "type" => $type, "email" => $emailClient, "phone" => $phone, "budget" => $budget, "interestArea" => $interestArea, "status" => $status, "createdAt" => $createdAt, "rawData" => $rawData));

   
   //INSERT IN FOLLOWUPS TABLE
   $lastId = $db->lastInsertId();

   $sql = "INSERT INTO FOLLOWUPS (LEAD_ID, AUTO_REPLAY, FOLLOW_24H, FOLLOW_3D)
   VALUES (:leadId, :autoReplay, :follow24h, :follow3d);";
   
   $stmt = $db->prepare($sql);
   $stmt->execute(array("leadId" => $lastId, "autoReplay" => true, "follow24h" => true, "follow3d" => true));


   // Gett realtor data
   $sql = "SELECT * FROM REALTORS WHERE ID = $realtorId;";
   $stmt = $db->prepare($sql);
   $stmt->execute();
   $row = $stmt->fetch();
   $realtorEmail = $row["email"];
   $realtorName = $row["name"];


   try {
      // ==========================================
      // GMAIL CONFIGURATION (For both emails)
      // ==========================================
      $smtpHost     = 'smtp.gmail.com';               // Gmail's SMPT Server
      $smtpUsername = 'sfrealtorleads@gmail.com';   // Real email of SF Realtor Leads account
      $smtpPassword = 'lgre kvtl iojo ilcq';        // App Password from Gmail account
      $smtpPort     = 2587;                         // Port TLS
      $smtpCharSet  = 'UTF-8';

      /******* 1. EMAIL FOR REALTOR *******/
      $mail = new PHPMailer(true);
      $mail->isSMTP();                                            
      $mail->Host       = $smtpHost;
      $mail->SMTPAuth   = true;                                   
      $mail->Username   = $smtpUsername;
      $mail->Password   = $smtpPassword;
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Gmail requiere TLS obligado
      $mail->Port       = $smtpPort;                                    
      $mail->CharSet    = $smtpCharet;

      // IMPORTANT: Gmail's 'setFrom' MUST be the same gmail account or an alias autorized. Another ways,
      // Gmail will re-write the addressee for safely.
      $mail->setFrom($smtpUsername, 'SF Realtor Leads');
      $mail->addAddress($realtorEmail, $realtorName);     

      $mail->isHTML(true);                                  
      $mail->Subject = 'New Lead for You!';
      $mail->Body    = "
         <h1>¡Hello {$realtorName}!</h1>
         <p>Someone is trying to meet with you.</p><br>
         <p>Name: {$fullName}</p>
         <p>Phone: {$phone}</p>
         <p>Email: {$emailClient}</p>
         <p>Budget: {$budget}</p>
         <p>Area: {$interestArea}</p><br>
         <p>Don't lose this lead. Respond quickly at {$emailClient} to follow-up</p>
      ";
      $mail->AltBody = 'Se ha registrado un nuevo Lead en la plataforma.';
      $mail->send();


      /******* 2. EMAIL FOR CLIENT *******/
      $mailForClient = new PHPMailer(true);
      $mailForClient->isSMTP();                                            
      $mailForClient->Host       = $smtpHost;
      $mailForClient->SMTPAuth   = true;                                   
      $mailForClient->Username   = $smtpUsername;
      $mailForClient->Password   = $smtpPassword;
      $mailForClient->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mailForClient->Port       = $smtpPort;
      $mailForClient->CharSet    = $smtpCharSet;

      $mailForClient->setFrom($smtpUsername, 'SF Realtor Leads');
      $mailForClient->addAddress($emailClient, $fullName);

      $mailForClient->isHTML(true);                                  
      $mailForClient->Subject = 'You almost get!';
      $mailForClient->Body    = "
         <h1>¡Hello {$fullName}!</h1>
         <p>Thanks for reaching out!</p><br>
         <p>Thanks for your interest in buying in San Francisco. I'll contact you shortly to schedule a meeting. In the meantime, if you have any questions, you can send me an email at {$realtorEmail}</p><br>
         <p>Best regards,</p>
         <p>{$realtorName}</p>
      ";
      $mailForClient->AltBody = 'Gracias por contactarnos. Nos comunicaremos contigo a la brevedad.';
      $mailForClient->send();   
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