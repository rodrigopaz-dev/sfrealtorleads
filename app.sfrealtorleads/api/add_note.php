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

   if(isset($_POST["submit"])) {
      $leadId = $_POST["lead_id"];
      $note = $_POST["note"];
      

      $sql = "INSERT INTO LEAD_NOTES(LEAD_ID,NOTE,CREATED_AT) VALUES(:leadId, :note, :createdAt);";
      $stmt = $db->prepare($sql);
      $ok = $stmt->execute(array("leadId" => $leadId, "note" => $note, "createdAt" => date('Y/m/d')));
      if($ok) { 
         ?>
            <script>
               alert("Note added successfuly!");
               location.href = "../app/leads.php";
            </script>
         <?php
      }
   } else {
      $id = $_GET["id"];
      $sql = "SELECT * FROM LEADS WHERE ID = $id;";
      $stmt = $db->prepare($sql);
      $stmt->execute();
      $row = $stmt->fetch();
   }
?>

<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="../app/assets/css/add_note.css">
      <title>Realtor Leads System</title>
   </head>
   <body>
      <header>
         <p>User: <span><?php echo $_SESSION["realtor_name"]; ?></span></p>
         <p><a href="closeSession.php">Log out</a></p>
      </header>

      <section class="info">
         <div><h1>Adding Notes</h1></div>
         <div class="menu"><ul><li><a href="../app/leads.php">Back to Leads</a></li></ul></div>
      </section>

      <section class="content">
         <div>Creating new note for <b><span><?php echo $row["full_name"]; ?></span></b></div>
         <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST">
            <label for="note">Note: </label>
            <textarea name="note" id="note"></textarea>
            <input type="hidden" name="lead_id" id="lead_id" value="<?php echo $id; ?>">
            <input type="submit" name="submit" value="Save Note">
         </form>
      </section>
   </body>
</html>