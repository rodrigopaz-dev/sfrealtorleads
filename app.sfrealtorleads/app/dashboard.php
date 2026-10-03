<?php
   require_once __DIR__ . "\\..\\config\\Connection.php";


   //Get session
   session_start();
   
   //Verify if session is active
   if(!isset($_SESSION["user"])) {
      header("location:../");
   }

   // Get connection
   $db = Database::getInstance();
   $db = $db->getConnection();

   if(isset($_POST["change-status"])) {
      $id = $_POST["lead-id"];
      $action = $_POST["change-status"];

      if($action == "Contacted") {
         $sql = "UPDATE LEADS SET STATUS = 'contacted' WHERE ID = $id;";
         $stmt = $db->prepare($sql);
      } else if($action == "Scheduled") {
         $sql = "UPDATE LEADS SET STATUS = 'scheduled' WHERE ID = $id;";
         $stmt = $db->prepare($sql);
      } else if($action == "Closed") {
         $sql = "UPDATE LEADS SET STATUS = 'closed' WHERE ID = $id;";
         $stmt = $db->prepare($sql);   
      }
      $ok = $stmt->execute();

      $sql = "UPDATE FOLLOWUPS SET AUTO_REPLAY = false, FOLLOW_24H = false, FOLLOW_3D = false WHERE LEAD_ID = $id";
      $stmt = $db->prepare($sql);
      $ok = $stmt->execute();

      if($ok) ?> <script>alert("Status change successfuly!");</script> <?php
   }

   //Get data from Realtors Table
   $sql = "SELECT * FROM REALTORS WHERE ID = 1;";
   $stmt = $db->prepare($sql);
   $stmt->execute();
   $realtor = $stmt->fetch();

   $_SESSION["realtor_name"] = $realtor["name"] . " " . $realtor["last_name"];

   //Get total leads
   $result = $db->query("SELECT COUNT(*) FROM LEADS WHERE REALTOR_ID = 1;");
   $totalLeads = $result->fetchColumn();
   
   //Get total new leads
   $result = $db->query("SELECT COUNT(*) FROM LEADS WHERE STATUS = 'NEW' AND REALTOR_ID = 1;");
   $totalNewLeads = $result->fetchColumn();

   //Get total contacted leads
   $result = $db->query("SELECT COUNT(*) FROM LEADS WHERE STATUS = 'CONTACTED' AND REALTOR_ID = 1;");
   $totalContactedLeads = $result->fetchColumn();

   //Get total scheduled leads
   $result = $db->query("SELECT COUNT(*) FROM LEADS WHERE STATUS = 'SCHEDULED' AND REALTOR_ID = 1;");
   $totalScheduledLeads = $result->fetchColumn();

   //Get total closed leads
   $result = $db->query("SELECT COUNT(*) FROM LEADS WHERE STATUS = 'CLOSED' AND REALTOR_ID = 1;");
   $totalClosedLeads = $result->fetchColumn();

   //Get total lost leads
   $result = $db->query("SELECT COUNT(*) FROM LEADS WHERE STATUS = 'LOST' AND REALTOR_ID = 1;");
   $totalLostLeads = $result->fetchColumn();


   $typeof = "";
   $tableTitle = "";
   if(isset($_GET["typeof"])) {
      switch($_GET["typeof"]) {
         case "new":
            //Get new leads
            $sql = "SELECT * FROM LEADS WHERE STATUS = 'NEW' AND REALTOR_ID = 1;";
            $typeof = "new";
            $tableTitle = "New Leads";
            break;
         case "contacted":
            //Get contacted leads
            $sql = "SELECT * FROM LEADS WHERE STATUS = 'CONTACTED' AND REALTOR_ID = 1;";
            $typeof = "contacted";
            $tableTitle = "Contacted Leads";
            break;
         case "scheduled":
            //Get scheduled leads
            $sql = "SELECT * FROM LEADS WHERE STATUS = 'SCHEDULED' AND REALTOR_ID = 1;";
            $typeof = "scheduled";
            $tableTitle = "Scheduled Leads";
            break;
         case "closed":
            //Get closed leads
            $sql = "SELECT * FROM LEADS WHERE STATUS = 'CLOSED' AND REALTOR_ID = 1;";
            $typeof = "closed";
            $tableTitle = "Closed Leads";
            break;
      }
   } else {
      $sql = "SELECT * FROM LEADS WHERE STATUS = 'NEW' AND REALTOR_ID = 1;";
      $typeof = "new";
      $tableTitle = "New Leads";
   }

   $stmt = $db->prepare($sql);
   $stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="./assets/css/dashboard.css">
      <title>Realtor Leads System</title>
   </head>
   <body>
      <header>
         <p>User: <span><?php echo $_SESSION["realtor_name"]; ?></span></p>
         <p><a href="../api/closeSession.php">Log out</a></p>
      </header>

      <section class="info">
         <div>
            <h1>Dashboard</h1>
            <p>Total Leads: <span><?php echo $totalLeads; ?></span>  -  ( Lost: <?php echo $totalLostLeads; ?> )</p>
         </div>
         <div class="menu">
            <ul>
               <li><a href="leads.php">Leads Table</a></li>
            </ul>
         </div>
      </section>

      <section class="card-container">
	      <a href="<?php echo $_SERVER["PHP_SELF"]; ?>?typeof=new">
            <div class="card new-leads">
               <h1>New Leads</h1>
               <p><?php echo $totalNewLeads; ?></p>
	         </div>
         </a>
         <a href="<?php echo $_SERVER["PHP_SELF"]; ?>?typeof=contacted">
            <div class="card contacted-leads">
               <h1>Contacted Leads</h1>
               <p><?php echo $totalContactedLeads; ?></p>
            </div>
         </a>
         <a href="<?php echo $_SERVER["PHP_SELF"]; ?>?typeof=scheduled">
            <div class="card scheduled-leads">
               <h1>Scheduled Leads</h1>
               <p><?php echo $totalScheduledLeads; ?></p>
            </div>
         </a>
         <a href="<?php echo $_SERVER["PHP_SELF"]; ?>?typeof=closed">
            <div class="card closed-leads">
               <h1>Closed Leads</h1>
               <p><?php echo $totalClosedLeads; ?></p>
            </div>
         </a>
      </section>

      <section class="table">
         <table>
            <caption><?php echo $tableTitle; ?></caption>
            <?php if($stmt->rowCount() == 0) { ?>
               <tr>
                  <td><b>Data Not Found</b></td>
               </tr>
            <?php } else { ?>
               <thead>
                  <tr>
                     <th scope="col">Name</th>
                     <th scope="col">Phone</th>
                     <th scope="col">Created at</th>
                     <th scope="col">Mark as</th>
                  </tr>
               </thead>
               <tbody>
                  <?php while($lead = $stmt->fetch()) { ?>
                     <tr>
                        <td data-label="Name" scope="row"><?php echo $lead["full_name"]; ?></td>
                        <td data-label="Phone" scope="row"><?php echo $lead["phone"]; ?></td>
                        <td data-label="Crated at" scope="row"><?php echo $lead["created_at"]; ?></td>
                        <td data-label="Mark as" scope="row">
                           <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST">
                              <input type="hidden" id="lead-id" name="lead-id" value="<?php echo $lead["id"]; ?>">
                              <?php if ($typeof == "new") { ?>
                                 <input type="submit" id="change-to-contacted" name="change-status" class="actions btn-contacted" value="Contacted">
                                 <input type="submit" id="change-to-scheduled" name="change-status" class="actions btn-scheduled" value="Scheduled">
                                 <input type="submit" id="change-to-closed" name="change-status" class="actions btn-closed" value="Closed">
                              <?php } elseif ($typeof == "contacted") { ?>
                                 <input type="submit" id="change-to-scheduled" name="change-status" class="actions btn-scheduled" value="Scheduled">
                                 <input type="submit" id="change-to-closed" name="change-status" class="actions btn-closed" value="Closed">
                              <?php } elseif ($typeof == "scheduled") { ?>
                                 <input type="submit" id="change-to-closed" name="change-status" class="actions btn-closed" value="Closed">
                              <?php } else { echo "---"; } ?>
                           </form>
                        </td>
                     </tr>
                  <?php } ?>
               </tbody>
            <?php } ?>
         </table>
      </section>
   </body>
</html>