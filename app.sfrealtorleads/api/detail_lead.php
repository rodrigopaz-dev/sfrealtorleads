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
      $id = $_POST["lead-id"];
      $autoReplay = isset($_POST["auto-replay"]) ? true : false;
      $follow24h = isset($_POST["follow-24h"]) ? true : false;
      $follow3d = isset($_POST["follow-3d"]) ? true : false;

      $sql = "UPDATE FOLLOWUPS SET AUTO_REPLAY = :autoReplay, FOLLOW_24H = :follow24h, FOLLOW_3D = :follow3d
      WHERE LEAD_ID = :leadId;";
      $stmt = $db->prepare($sql);
      $update = $stmt->execute(array("autoReplay" => true, "follow24h" => $follow24h, "follow3d" => $follow3d, "leadId" => $id));

      if($update) 
         ?> <script>alert("Automations update successfuly!")</script> <?php
   } else {
      $id = $_GET["id"];
   }

   $sql = "SELECT * FROM LEADS WHERE ID = $id;";
   $stmt = $db->prepare($sql);
   $stmt->execute();
   $row = $stmt->fetch();

   $stmt = null;
   $sql = "SELECT * FROM LEAD_NOTES WHERE LEAD_ID = $id";
   $stmt = $db->prepare($sql);
   $stmt->execute();

   $sql = "SELECT * FROM FOLLOWUPS WHERE LEAD_ID = $id";
   $stmtFollow = $db->prepare($sql);
   $stmtFollow->execute();
   $follow = $stmtFollow->fetch();
?>

<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="../app/assets/css/detail_lead.css">
      <title>Realtor Leads System</title>
   </head>
   <body>
      <header>
         <p>User: <span><?php echo $_SESSION["realtor_name"]; ?></span></p>
         <p><a href="closeSession.php">Log out</a></p>
      </header>

      <section class="info">
         <div>
            <h1>Lead Detail</h1>
         </div>
         <div class="menu">
            <ul>
               <li><a href="../app/leads.php">Return to Leads</a></li>
            </ul>
         </div>
      </section>

      <section class="info-lead-detail">
         <table>
            <tbody>
               <tr>
                  <td>Client</td>
                  <td><?php echo $row["full_name"]; ?></td>
               </tr>
               <tr>
                  <td>type</td>
                  <td><?php echo $row["type"]; ?></td>
               </tr>
               <tr>
                  <td>Email</td>
                  <td><?php echo $row["email"]; ?></td>
               </tr>
               <tr>
                  <td>Phone</td>
                  <td><?php echo $row["phone"]; ?></td>
               </tr>
               <tr>
                  <td>Budget</td>
                  <td><?php echo $row["budget"]; ?></td>
               </tr>
               <tr>
                  <td>Interested Area</td>
                  <td><?php echo $row["interest_area"]; ?></td>
               </tr>
               <tr>
                  <td>Status</td>
                  <td><?php echo $row["status"]; ?></td>
               </tr>
               <tr>
                  <td>Created at</td>
                  <td><?php echo $row["created_at"]; ?></td>
               </tr>
               
               <tr>
                  <td colspan="2" class="automations-td">AUTOMATIONS</td>
               </tr>
               <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST">
                  <tr>
                     <td>Send auto-replay to leads</td>
                     <td>
                        <input type="checkbox" id="auto-replay" name="auto-replay" <?php if($follow["auto_replay"]) echo "checked" ?>>
                        <span id="auto-replay-span"> 
                           <?php 
                              if($follow["auto_replay"]) echo "Enabled";
                              else echo "Disabled [" . $row["status"] . "]"; 
                           ?>
                        </span>
                     </td>
                  </tr>
                  <tr>
                     <td>Send follow-up after 24h</td>
                     <td>
                        <input type="checkbox" id="follow-24h" name="follow-24h" <?php if($follow["follow_24h"]) echo "checked" ?>>
                        <span id="follow-24h-span"> 
                           <?php 
                              if($follow["follow_24h"]) echo "Enabled";
                              else echo "Disabled [" . $row["status"] . "]"; 
                           ?>
                        </span>
                     </td>
                  </tr>
                  <tr>
                     <td>Send follow-up<br>after 3 days</td>
                     <td>
                        <input type="checkbox" id="follow-3d" name="follow-3d" <?php if($follow["follow_3d"]) echo "checked" ?>>
                        <span id="follow-3d-span"> 
                           <?php 
                              if($follow["follow_3d"]) echo "Enabled";
                              else echo "Disabled [" . $row["status"] . "]"; 
                           ?>
                        </span>
                     </td>
                  </tr>
                  <tr>
                     <td colspan="2" class="btn-save-changes">
                        <input type="hidden" id="lead-id" name="lead-id" value="<?php echo $id; ?>">
                        <input type="submit" id="submit" name="submit" value="Save Changes">
                     </td>
                  </tr>
               </form>
            </tbody>
         </table>
      </section>

      <section class="notes">
         <h2>Notes</h2>
         <div class="notes-container">
            <?php if ($stmt->rowCount() == 0) { ?>
               <div class="card-note">
                  <p>Data Not Found</p>
               </div>
            <?php } ?>
            <?php while($note = $stmt->fetch()) { ?>
               <div class="card-note">
                  <p>Created at: <b><?php echo $note["created_at"]; ?></b></p>
                  <p><br><?php echo $note["note"]; ?></p>
               </div>
            <?php } ?>
         </div>
      </section>

      <script>
         const autoReplayCheck = document.getElementById("auto-replay");
         const follow24hCheck = document.getElementById("follow-24h");
         const follow3dCheck = document.getElementById("follow-3d");
         
         const autoReplaySpan = document.getElementById("auto-replay-span");
         const follow24hSpan = document.getElementById("follow-24h-span");
         const follow3dSpan = document.getElementById("follow-3d-span");
         

         autoReplayCheck.addEventListener("change", e => {
            if(e.target.checked) {
               autoReplaySpan.textContent = "Enabled";
            } else {
               autoReplaySpan.textContent = "Disabled";
            }
         });

         follow24hCheck.addEventListener("change", e => {
            if(e.target.checked) {
               follow24hSpan.textContent = "Enabled";
            } else {
               follow24hSpan.textContent = "Disabled";
            }
         });

         follow3dCheck.addEventListener("change", e => {
            if(e.target.checked) {
               follow3dSpan.textContent = "Enabled";
            } else {
               follow3dSpan.textContent = "Disabled";
            }
         });
      </script>
   </body>
</html>