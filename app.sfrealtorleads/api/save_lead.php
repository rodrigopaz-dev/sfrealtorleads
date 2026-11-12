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
      function enviarCorreoBrevo($destinatarioEmail, $destinatarioNombre, $asunto, $contenidoHtml) {
         $apiKey = getenv('MAIL_PASSWORD');
         
         // IMPORTANTE: Usa exactamente el mismo correo que verificaste en el Paso 1
         $remitente = [
            'name'  => 'SF Realtor Leads',
            'email' => 'rodrigopaz.dev@gmail.com' 
         ];

         $datos = [
            'sender'      => $remitente,
            'to'          => [
               [
                     'email' => $destinatarioEmail,
                     'name'  => $destinatarioNombre
               ]
            ],
            'subject'     => $asunto,
            'htmlContent' => $contenidoHtml
         ];

         $ch = curl_init('https://brevo.com');
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
         curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json'
         ]);

         $respuesta = curl_exec($ch);
         $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         $errorCurl = curl_error($ch);
         curl_close($ch);

         echo "terminando curl";
         return [
            'exito' => ($codigoHttp >= 200 && $codigoHttp < 300),
            'codigo' => $codigoHttp,
            'respuesta' => json_decode($respuesta, true),
            'error' => $errorCurl
         ];
      }

      // EMAIL FOR REALTOR
      $htmlRealtor = "
         <h1>¡Hello {$realtorName}!</h1>
         <p>Someone is trying to meet with you.</p><br>
         <p>Name: {$fullName}</p>
         <p>Phone: {$phone}</p>
         <p>Email: {$emailClient}</p>
         <p>Budget: {$budget}</p>
         <p>Area: {$interestArea}</p><br>
         <p>Don't lose this lead. Respond quickly at {$emailClient} to follow-up</p>
      ";
      $envioRealtor = enviarCorreoBrevo(
         $realtorEmail, 
         $realtorName, 
         "New Lead for You!", 
         $htmlRealtor
      );

      // EMAIL FOR CLIENT
      $htmlClient = "
         <h1>¡Hello {$fullName}!</h1>
         <p>Thanks for reaching out!</p><br>
         <p>Thanks for your interest in buying in San Francisco. I'll contact you shortly to schedule a meeting. In the meantime, if you have any questions, you can send me an email at {$realtorEmail}</p><br>
         <p>Best regards,</p>
         <p>{$realtorName}</p>
      ";
      $envioClient = enviarCorreoBrevo(
         $emailClient, 
         $fullName, 
         "You almost get!", 
         $htmlClient
      );


      if ($envioRealtor['exito'] && $envioClient['exito']) {
         echo json_encode([
            'status' => 'success', 
            'message' => 'Ambos correos fueron enviados correctamente'
         ]);
      } else {
         http_response_code(500);
         echo json_encode([
            'status' => 'error',
            'message' => 'Hubo un problema al enviar uno o ambos correos',
            'detalle_admin' => $envioRealtor,
            'detalle_usuario' => $envioClient
         ]);
      }

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