<?php
   // 1. Si la cookie ya existe, usamos esa zona horaria
   if (isset($_COOKIE['user_timezone'])) {
      $userTimezone = $_COOKIE['user_timezone'];
      
      // Validamos que sea una zona horaria válida antes de establecerla
      if (in_array($userTimezone, timezone_identifiers_list())) {
         date_default_timezone_set($userTimezone);
      }
   } else {
      // Zona horaria por defecto si aún no se ha detectado
      date_default_timezone_set('UTC'); 
   }

   echo "Zona horaria establecida: " . date_default_timezone_get() . "<br>";
   echo "Hora actual: " . date('Y-m-d H:i:s');
?>

<!-- 2. Código JavaScript para detectar la zona horaria y guardarla en una cookie -->
<script>
    // Obtiene el identificador (ej. "America/Mexico_City", "Europe/Madrid")
    const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    
    // Guarda la zona horaria en una cookie que PHP leerá en la siguiente recarga
    document.cookie = "user_timezone=" + userTimezone + "; path=/; max-age=31536000; SameSite=Lax";
    
    // Si es la primera vez que entra, recarga para que PHP aplique el cambio de inmediato
    if (!document.cookie.includes('user_timezone')) {
        location.reload();
    }
</script>

<?php
   set_exception_handler(function (Throwable $exception) {

      $zonaUsuario = $_COOKIE['user_timezone'] ?? 'UTC';
      try {
         $zona = new DateTimeZone($zonaUsuario);
      } catch (Exception $e) {
         $zona = new DateTimeZone('UTC');
      }
      // Registrer error in error_log file
      
      $local = new DateTimeImmutable('now', $zona);
      error_log("[Local " . $local->format('d-M-Y H:i:s T') . "] " .  "[" . get_class($exception) . "] " . $exception->getMessage() . " in " . $exception->getFile() . " line " . $exception->getLine());

      // Show standard message to the client
      http_response_code(500);
      echo "Sorry, there was an internal error in server.";
      exit;
   });

   require_once __DIR__ . "/config/Connection.php";

   // Get connection
   $db = Database::getInstance();
   $db = $db->getConnection();

   if (isset($_GET["wrong"])) {
      $error = true;
   } elseif (isset($_POST["submit"])) {
      $user = htmlentities(addslashes($_POST["user"]));
      $password = htmlentities(addslashes($_POST["password"]));

      $stmt = $db->prepare("SELECT * FROM USERS WHERE USER = :user AND PASSWORD = :password;");
      $stmt->execute(array("user"=>$user, "password"=>$password));

      //Get records
      $totalRows = $stmt->rowCount();

      if($totalRows !=0) {
         //Iniciate session
         session_start();
         $_SESSION["user"] = $user;

         header("location:app/dashboard.php");
      } else {
         //Redirect to login page
         header("location:index.php?wrong");
      }
   }
?>

<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="./app/assets/css/index.css">
      <title>Realtor Leads System</title>
   </head>
   <body>
      <div class="login-page">
         <h1>Realtor Leads System</h1>
         <div class="form">
            <form class="login-form" action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST">
               <input type="text" id="user" name="user" placeholder="username"/>
               <input type="password" id="password" name="password" placeholder="password"/>
               <input type="submit" name="submit" id="submit" value="login">
               <?php if (isset($error)) { ?>
                  <p class="message">Incorrect user or password<br>Try again</p>
               <?php } ?>
            </form>
         </div>
      </div>
   </body>
</html>