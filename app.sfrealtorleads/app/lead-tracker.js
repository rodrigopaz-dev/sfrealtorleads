const script = document.currentScript;
document.addEventListener("DOMContentLoaded", function () {
   
   const forms = document.querySelectorAll("form");
   forms.forEach(form => {
      form.addEventListener("submit", function (e) {
         e.preventDefault();
         const formData = new FormData(form);
         const data = {};

         formData.forEach((value, key) => {
            data[key] = value;
         });

         //Extra info
         const config = script.dataset;
         data.page_url = window.location.href;
         data.timestamp = new Date().toISOString();
         data.realtor_id = config.realtorid;
         console.log(JSON.stringify(data));

         //Send to app
         fetch("https://app.sfleads.rodrigopazz.com/api/save_lead.php", {
            method: "POST",
            headers: {
               "Content-Type": "application/json"
            },
            body: JSON.stringify(data)
         })
         .then(response => response.text())
         .then(data => {
            console.log("Server respond:", data);
            alert(`Your form has been sent successfuly.\nNow you are going to redirect to SF Realtor Leads App.You can log in with the user demo:\n\nusername: demo_user\npassword: 1234`);
            location.href = "https://app.sfleads.rodrigopazz.com";
         })
         .catch(error => console.error("Error:", error));
      });
   });
});