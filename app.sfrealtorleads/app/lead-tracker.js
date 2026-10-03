document.addEventListener("DOMContentLoaded", function () {
   
   const config = document.currentScript.dataset;
   const forms = document.querySelectorAll("form");
   forms.forEach(form => {
      form.addEventListener("submit", function () {
         const formData = new FormData(form);
         const data = {};

         formData.forEach((value, key) => {
            data[key] = value;
         });

         //Extra info útil
         data.page_url = window.location.href;
         data.timestamp = new Date().toISOString();
         data.realtor_id = config.realtorid;

         //Enviar a tu servidor
         fetch("https://tudominio.com/save_lead.php", {
            method: "POST",
            headers: {
               "Content-Type": "application/json"
            },
            body: JSON.stringify(data)
         });
      });
   });
});