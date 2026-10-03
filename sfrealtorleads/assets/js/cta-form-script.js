
//Get elements DOM
const $ctaButtonFinal = document.getElementById('cta-button-final');
const $formSection = document.getElementById('form-section');

const $form = document.getElementById('form');
const $name = document.getElementById('name');
const $errorMessageName = document.getElementById('error-message-name');
const $email = document.getElementById('email');
const $errorMessageEmail = document.getElementById('error-message-email');
const $phone = document.getElementById('phone');
const $errorMessagePhone = document.getElementById('error-message-phone');
const $typeOf = document.getElementById('type-of');
const $message = document.getElementById('message');
const $errorMessageSubmit = document.getElementById('error-message-submit');


//Set click function for button CTA
$ctaButtonFinal.addEventListener('click', () => {
   $formSection.scrollIntoView();
   $name.focus({preventScroll:true});
});


//Define variables to verify form sending
let validName,validEmail,validPhone;


//Name validation
$name.addEventListener('input', e => {
    e.target.value = e.target.value.replace(/[^a-zA-Záéíóúüñ'ÁÉÍÓÚÑÜ\s]/g, '');
});

$name.addEventListener('blur', e => {
   const element = e.target;
   if(element.value.length == 0 || element.value.length < 3) {
      element.classList.add('invalid-input');
      $errorMessageName.innerHTML = 'The name must have at least 3 letters';
      validName = false;
   } else {
      element.classList.remove('invalid-input');
      $errorMessageName.innerHTML = '';
      $errorMessageSubmit.innerHTML = '';
      validName = true;
   }
});


//Email validation
$email.addEventListener('blur', e => {
   const element = e.target;
   const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
   
   if (!regex.test(element.value)) {
      element.classList.add('invalid-input');
      $errorMessageEmail.innerHTML = 'Email format not valid';
      validEmail = false;
   } else {
      element.classList.remove('invalid-input');
      $errorMessageEmail.innerHTML = '';
      $errorMessageSubmit.innerHTML = '';
      validEmail = true;
   }
});


//Phone validation
$phone.addEventListener('input', e => {
   let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
   e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
});

$phone.addEventListener('blur', e => {
   const element = e.target;
   if(element.value.length !== 14 && element.value.length > 0) {
      element.classList.add('invalid-input');
      $errorMessagePhone.innerHTML = 'Phone number not valid';
      validPhone = false;
   } else {
      element.classList.remove('invalid-input');
      $errorMessagePhone.innerHTML = '';
      $errorMessageSubmit.innerHTML = '';
      validPhone = true;
   }
});

$form.addEventListener('submit', e => {
   e.preventDefault();
   $errorMessageSubmit.innerHTML = '';

   if(!validName) {
      $errorMessageSubmit.innerHTML += 'verify name entered ';
      return;
   }

   if(!validEmail) {
      $errorMessageSubmit.innerHTML += 'verify email entered ';
      return;
   }

   if(!validPhone) {
      $errorMessageSubmit.innerHTML += 'verify phone entered ';
      return;
   }


   console.log('sending');


   fetch("https://sfrealtorleads.com/assets/php/send_mail.php", {
         method : 'POST',
         body : new FormData($form),
         mode : 'cors' //Permitimos el envio de datos cruzados desde el cliente
   })
   .then(response => response.ok ? response.json() : Promise.reject(response))
   .then(json => {
         console.log(json);
         $errorMessageSubmit.innerHTML = json;
   })
   .catch(error => {
         console.log(error);
         $errorMessageSubmit.innerHTML = error;
   })
   .finally(() =>{
         $form.reset();
   })
});


function enviarFormulario(e) {
   //e.target.parentNode.parentNode
   const form = document.querySelector('form');
   const message = document.querySelector('p');
   const loader = document.querySelector('.loader');

   loader.classList.remove('hide');
   loader.classList.add('show');

   fetch("send-mail.php", {
         method : 'POST',
         body : new FormData(form),
         mode : 'cors' //Permitimos el envio de datos cruzados desde el cliente
   })
   .then(response => response.ok ? response.json() : Promise.reject(response))
   .then(json => {
         console.log(json);
         message.innerHTML = json.message;
   })
   .catch(error => {
         console.log(error);
         message.innerHTML = error;
   })
   .finally(() =>{
         form.reset();
         loader.classList.remove('show');
         loader.classList.add('hide');
   })
}