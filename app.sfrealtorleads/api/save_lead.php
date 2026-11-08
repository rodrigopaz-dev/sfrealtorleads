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


   /******* EMAIL FOR REALTOR *******/

   // Gett realtor data
   $sql = "SELECT * FROM REALTORS WHERE ID = $realtorId;";
   $stmt = $db->prepare($sql);
   $stmt->execute();
   $row = $stmt->fetch();
   $realtorEmail = $row["email"];
   $realtorName = $row["name"];

   // Create an instance of the PHPMailer class
   $mail = new PHPMailer(true);
   
   try {
      // --- Server configuration ---
      $mail->SMTPDebug = 0;                      
      $mail->isSMTP();                                            
      $mail->Host       = 'smtp.resend.com'; // SMTP Server
      $mail->SMTPAuth   = true;                                   
      $mail->Username   = 'resend';  // email address
      $mail->Password   = getenv("MAIL_PASSWORD"); // password app
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // TLS (port 587) o ENCRYPTION_SMTPS for SSL (port 465)
      $mail->Port       = 587;                                    
      $mail->CharSet    = 'UTF-8';                 // Allow to use 'ñ' and acent without problems

      // --- Addressee ---
      $mail->setFrom('onboarding@resend.dev', 'SF Realtor Leads');
      $mail->addAddress($realtorEmail, $realtorName);     

      // --- Content ---
      $mail->isHTML(true);                                  
      $mail->Subject = 'New Lead for You!';
      $mail->Body    = `
         <h1>¡Hello $realtorName!</h1>
         <p>Someone is trying to meet with you.</p><br>
         <p>Name: $fullName</p>
         <p>Phone: $phone</p>
         <p>Email: $emailClient</p>
         <p>Budget: $budget</p>
         <p>Area: $interestArea</p><br>
         <p>Don't lose this lead. Response quickly at $emailClient to follow-up</p>
      `;
      $mail->AltBody = 'Este es el texto alternativo en formato plano si el cliente no soporta HTML.';

      // Send email
      $mail->send();
      echo 'The email has been sent successfully.';
   } catch (Exception $e) {
      // En producción, registra el error internamente. No le muestres $e->getMessage() al cliente final.
      error_log("Error al enviar correo: {$mail->ErrorInfo}");
      echo 'Sorry. There was a problem to proccess your request for realtor email.';
      echo $mail->ErrorInfo;
   }
   
   
   /******* EMAIL FOR CLIENT *******/
   
   // Create an instance of the PHPMailer class
   $mail = new PHPMailer(true);
   
   try {
      // --- Server configuration ---
      $mail->SMTPDebug = 0;                      
      $mail->isSMTP();                                            
      $mail->Host       = 'smtp.resend.com'; // SMTP Server
      $mail->SMTPAuth   = true;                                   
      $mail->Username   = 'resend';  // email address
      $mail->Password   = getenv("MAIL_PASSWORD"); // password app
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // TLS (port 587) o ENCRYPTION_SMTPS for SSL (port 465)
      $mail->Port       = 587;
      $mail->CharSet    = 'UTF-8';                 // Allow to use 'ñ' and acent without problems

      // --- Addressee ---
      $mail->setFrom('onboarding@resend.dev', 'SF Realtor Leads');
      $mail->addAddress($emailClient, $fullName);     

      // --- Content ---
      $mail->isHTML(true);                                  
      $mail->Subject = 'You almost get!';
      $mail->Body    = `
         <h1>¡Hello $fullName!</h1>
         <p>Thanks for reaching out!.</p><br>
         <p>Thanks for your interest in buying in San Francisco.I'll contact you shortly to schedule a meeting. In the meantime, if you have any question, you can send me a email at $realtorEmail</p><br>
         <p>Best regards,</p>
         <p>$realtorName</p>
      `;
      $mail->AltBody = 'Este es el texto alternativo en formato plano si el cliente no soporta HTML.';

      // Send email
      $mail->send();
      echo 'The email has been sent successfully.';
   } catch (Exception $e) {
      // En producción, registra el error internamente. No le muestres $e->getMessage() al cliente final.
      error_log("Error al enviar correo: {$mail->ErrorInfo}");
      echo 'Sorry. There was a problem to proccess your request for client email.';
      echo $mail->ErrorInfo;
   }   
?>