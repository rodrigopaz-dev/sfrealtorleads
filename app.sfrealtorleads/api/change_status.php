<?php
   require_once __DIR__ . "\\..\\config\\Connection.php";


   //Get session
   session_start();
   
   //Verify if session is active
   if(!isset($_SESSION["user"])) {
      header("location: ../");
   }


   // Get connection
   $db = Database::getInstance();
   $db = $db->getConnection();

   if(!isset($_POST["submit"])) {
      $id = $_GET["id"];
      $name = $_GET["name"];
   } else {
      $id = $_POST["lead_id"];
      $status = $_POST["status"];
      
      $sql = "UPDATE LEADS SET STATUS = :status WHERE ID = :id;";
      $stmt = $db->prepare($sql);
      $ok = $stmt->execute(array("status" => $status, "id" => $id));

      $sql = "UPDATE FOLLOWUPS SET AUTO_REPLAY = false, FOLLOW_24H = false, FOLLOW_3D = false WHERE LEAD_ID = :id";
      $stmt = $db->prepare($sql);
      $ok = $stmt->execute(array("id"=>$id));
      if($ok) { 
         ?>
            <script>
               alert("Status changed successfuly!");
               location.href = "../app/leads.php";
            </script>
         <?php
      }
   }
?>

<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="../app/assets/css/change_status.css">
      <title>Realtor Leads System</title>
   </head>
   <body>
      <header>
         <p>User: <span><?php echo $_SESSION["realtor_name"]; ?></span></p>
         <p><a href="closeSession.php">Log out</a></p>
      </header>

      <section class="info">
         <div><h1>Changing Lead Status</h1></div>
         <div class="menu"><ul><li><a href="../app/leads.php">Back to Leads</a></li></ul></div>
      </section>

      <section class="content">
         <div>Changing status for <b><span><?php echo $name; ?></span></b></div>
         <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST">
            <label for="status">Select status: </label>
            <select name="status" id="status">
               <option value="" disabled selected>-- Select one --</option>
               <option value="contacted">Contacted</option>
               <option value="scheduled">Scheduled</option>
               <option value="closed">Closed</option>
               <option value="lost">Lost</option>
            </select>
            <input type="hidden" name="lead_id" id="lead_id" value="<?php echo $id; ?>">
            <input type="submit" name="submit" value="Change Status">
         </form>
      </section>
   </body>
</html>