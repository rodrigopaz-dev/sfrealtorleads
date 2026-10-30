<?php
   require_once __DIR__ . "/../config/Connection.php";

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
   $email        = getField($data, ["email", "email_address"]);
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
   $stmt->execute(array("realtorId" => $realtorId, "fullName" => $fullName, "type" => $type, "email" => $email, "phone" => $phone, "budget" => $budget, "interestArea" => $interestArea, "status" => $status, "createdAt" => $createdAt, "rawData" => $rawData));

   
   //INSERT IN FOLLOWUPS TABLE
   $lastId = $db->lastInsertId();

   $sql = "INSERT INTO FOLLOWUPS (LEAD_ID, AUTO_REPLAY, FOLLOW_24H, FOLLOW_3D)
   VALUES (:leadId, :autoReplay, :follow24h, :follow3d);";
   
   $stmt = $db->prepare($sql);
   $stmt->execute(array("leadId" => $lastId, "autoReplay" => true, "follow24h" => true, "follow3d" => true));


   /********** CODE FOR SEND EMAIL TO REALTOR **********/
   $headers = "MIME-Version: 1.0\r\n";
   $headers.= "Content-type: text/html; charset=iso-8859-1\r\n";
   $headers.= "From: Realtor Leads System < realtorleadsystem@gmail.com >\r\n";


   //FOR TESTING ONLY
   $email = "rodrigo1305paz@gmail.com";

   $subject = "New Buyer Lead - Downtown SF - $850k";
   $message = "
      Name: $fullName\r\n
      Phone: $phone\r\n
      Email: $email\r\n
      Budget: $budget\r\n
      Area: $interestArea\r\n
   ";

   //$success = mail($email, $subject, $message, $headers);
   
   
   /********** CODE FOR SEND EMAIL TO CLIENT **********/
   $headers = "";
   $headers = "MIME-Version: 1.0\r\n";
   $headers.= "Content-type: text/html; charset=iso-8859-1\r\n";
   $headers.= "From: Realtor's Website < realtorwebsite@gmail.com >\r\n";


   //FOR TESTING ONLY
   $emailClient = "rodrigo.paz.socialmedia@gmail.com";

   $subject = "Thanks for reaching out!";
   $message = "Hi John,\r\nThanks for your interest in buying in San Francisco.\r\nI’ll contact you shortly to schedule a consultation.";

   //$successClient = mail($emailClient, $subject, $message, $headers);
   
   //if($success && $successClient) {
   if(true) {
      echo "Your email was sended to $email and $emailClient.";
   } else {
      echo "There was an error trying to send your email";
   }
?>