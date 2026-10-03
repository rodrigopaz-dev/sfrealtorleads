<?php
   require_once __DIR__ . "\\config\\Connection.php";

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