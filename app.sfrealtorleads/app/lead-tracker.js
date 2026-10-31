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

         //Extra info útil
         const config = script.dataset;
         data.page_url = window.location.href;
         data.timestamp = new Date().toISOString();
         data.realtor_id = config.realtorid;
         console.log(JSON.stringify(data));

         //Enviar a tu servidor
         fetch("https://sfrealtorleads.onrender.com/api/save_lead.php", {
            method: "POST",
            headers: {
               "Content-Type": "application/json"
            },
            body: JSON.stringify(data)
         })
         .then(response => response.json())
         .then(data => console.log("Server respond:", data))
         .catch(error => console.error("Error:", error));
      });
   });
});