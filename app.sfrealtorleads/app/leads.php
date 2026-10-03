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

   if(!isset($_POST["search-filter"])) {
      $stmt = $db->prepare("SELECT * FROM LEADS");
      $stmt->execute();
   } else {
      $filter = $_POST["filter-btn"];
      
      if($filter == "TYPE" || $filter == "STATUS") {
         $filterSelected = $_POST["select-filter"];
         $sql = "SELECT * FROM LEADS WHERE $filter = '$filterSelected';";
         $stmt = $db->prepare($sql);
         $stmt->execute();
      } else {
         $dateFrom = $_POST["date-from-filter"];
         $dateTo = $_POST["date-to-filter"];
         echo $dateFrom . " - " . $dateTo . "<br>";
         echo "SELECT * FROM LEADS WHERE $filter BETWEEN '$dateFrom' AND '$dateTo'";
         $stmt = $db->prepare("SELECT * FROM LEADS WHERE $filter BETWEEN '$dateFrom' AND '$dateTo';");
         $stmt->execute();
      }  
   }   
?>

<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="./assets/css/leads.css">
      <title>Realtor Leads System</title>
   </head>
   <body>
      <header>
         <p>User: <span><?php echo $_SESSION["realtor_name"]; ?></span></p>
         <p><a href="../api/closeSession.php">Log out</a></p>
      </header>

      <section class="info">
         <div>
            <h1>Leads</h1>
         </div>
         <div class="menu">
            <ul>
               <li><a href="dashboard.php">Dashboard</a></li>
            </ul>
         </div>
      </section>

      <section class="filter">
         <form id="search-form" action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST">
            <div>
               <label for="">Filter by:&nbsp;</label>
               <input type="radio" id="type-filter" value="TYPE" name="filter-btn"> <label for="filter-btn">Type&nbsp;</label>
               <input type="radio" id="status-filter" value="STATUS" name="filter-btn"> <label for="filter-btn">Status&nbsp;</label>
               <input type="radio" id="date-range-filter" value="CREATED_AT" name="filter-btn"> <label for="filter-btn">Date Range</label>
            </div>
            <div class="filter-inputs">
               <div>
                  <select name="select-filter" id="select-filter">
                  <option value="" disabled selected>-- select one --</option>
                  </select>
               </div>
               <div>
                  <label for="date-from-filter">From: </label>
                  <input type="date" name="date-from-filter" id="date-from-filter">
                  <label for="date-to-filter">To: </label>
                  <input type="date" name="date-to-filter" id="date-to-filter">
               </div>
               <input type="submit" name="search-filter" id="search-filter" value="Search">
            </div>
         </form>
      </section>

      <?php if(isset($_POST["search-filter"])) { ?>
         <div class="filter-cleaning"><a href="<?php echo $_SERVER["PHP_SELF"]; ?>">Clear Filter</a></div>
      <?php } ?>
      
      <section class="table">
         <table>
            <?php if($stmt->rowCount() == 0) { ?>
               <tr>
                  <td><b>Data Not Found</b></td>
               </tr>
            <?php } else { ?>
               <thead>
                  <tr>
                     <th scope="col">Name</th>
                     <th scope="col">Type</th>
                     <th scope="col">Area</th>
                     <th scope="col">Status</th>
                     <th scope="col">Date</th>
                     <th scope="col" colspan="2">Actions</th>
                  </tr>
               </thead>
               <tbody>
                  <?php while($row = $stmt->fetch()) { ?>
                     <tr>
                        <td data-label="Name" scope="row"><?php echo $row["full_name"]; ?></td>
                        <td data-label="Type" scope="row"><?php echo $row["type"]; ?></td>
                        <td data-label="Area" scope="row"><?php echo $row["interest_area"]; ?></td>
                        <td data-label="Status" scope="row"><?php echo $row["status"]; ?></td>
                        <td data-label="Date" scope="row"><?php echo $row["created_at"]; ?></td>
                        <td colspan="2">
                           <a class="actions btn-view" href="../api/detail_lead.php?id=<?php echo $row["id"]; ?>">View</a>
                           <a class="actions btn-change-status" href="../api/change_status.php?id=<?php echo $row["id"]?>&name=<?php echo urlencode($row["full_name"]); ?>">Change Status</a>
                           <a class="actions btn-add-note" href="../api/add_note.php?id=<?php echo $row["id"]; ?>">Add Note</a>
                        </td>
                     </tr>
                  <?php }; ?>
               </tbody>
            <?php } ?>   
         </table>
      </section>


      <script>
         document.addEventListener("DOMContentLoaded", function() {

            document.querySelector('.filter-inputs').children[1].style.display = 'none';
            
            //Set filter options
            let filterType = null;
            const selectBtn = document.getElementById("select-filter");
            const radioBtns = document.getElementsByName("filter-btn");
            radioBtns.forEach(radio => {
               radio.addEventListener('click', function(e) {
                  filterType = e.target.value;
                  selectBtn.options.length = 0;

                  const optDefault = document.createElement('option');
                  optDefault.text = '-- select one --';
                  optDefault.value = '';
                  optDefault.selected = true;
                  optDefault.disabled = true;
                  selectBtn.appendChild(optDefault);

                  if(filterType == 'TYPE') {
                     document.querySelector('.filter-inputs').children[0].style.display = 'initial';
                     document.querySelector('.filter-inputs').children[1].style.display = 'none';

                     
                     const optType1 = document.createElement('option');
                     optType1.text = 'buyer';
                     optType1.value = 'buyer';

                     const optType2 = document.createElement('option');
                     optType2.text = 'seller';
                     optType2.value = 'seller';

                     const optType3 = document.createElement('option');
                     optType3.text = 'investor';
                     optType3.value = 'investor';

                     selectBtn.appendChild(optType1);
                     selectBtn.appendChild(optType2);
                     selectBtn.appendChild(optType3);

                  } else if(filterType == 'STATUS') {
                     document.querySelector('.filter-inputs').children[0].style.display = 'initial';
                     document.querySelector('.filter-inputs').children[1].style.display = 'none';
                     
                     const optStatus1 = document.createElement('option');
                     optStatus1.text = 'new';
                     optStatus1.value = 'new';

                     const optStatus2 = document.createElement('option');
                     optStatus2.text = 'contacted';
                     optStatus2.value = 'contacted';

                     const optStatus3 = document.createElement('option');
                     optStatus3.text = 'scheduled';
                     optStatus3.value = 'scheduled';

                     const optStatus4 = document.createElement('option');
                     optStatus4.text = 'closed';
                     optStatus4.value = 'closed';

                     const optStatus5 = document.createElement('option');
                     optStatus5.text = 'lost';
                     optStatus5.value = 'lost';

                     selectBtn.appendChild(optStatus1);
                     selectBtn.appendChild(optStatus2);
                     selectBtn.appendChild(optStatus3);
                     selectBtn.appendChild(optStatus4);
                     selectBtn.appendChild(optStatus5);

                  } else if(filterType == 'CREATED_AT') {
                     document.querySelector('.filter-inputs').children[0].style.display = 'none';
                     document.querySelector('.filter-inputs').children[1].style.display = 'initial';
                  }
               });
            });   
         
         });
      </script>
   </body>
</html>