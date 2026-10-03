<?php
    if(isset($_POST)) {
        error_reporting(0);

        //Get variables from form
        $name = $_POST["name"];
        $email = $_POST["email"];
        $phone = $_POST["phone"];
        $typeOf = $_POST["type-of"];
        $message = $_POST["message"];

        //Get the domain from data was send
        $domain = $_SERVER["HTTP_HOST"];

        //Set parameters for email
        $to = "hello@sfrealtorleads.com";
        $subject = "Contacto desde el formulario del sitio $domain";
        $body = "
            <p>Datos enviados desde el sitio: $domain</p>
            <ul>
                <li>Name: <b>$name</b></li>
                <li>Email: <b>$email</b></li>
                <li>Phone: $phone</li>
                <li>Type of client: $typeOf</li>
                <li>Message: $message</li>
            </ul>
        ";
        $headers = "MIME-Version: 1.0\r\n"."Content-Type: text/html; charset=UTF-8\r\n"."From: Envio Automatico. No Responder <no-reply@$domain>";

        //Send email
        $send_email = mail($to,$subject,$body,$headers);

        //Send response to client
        if($send_email) {
            $response = [
                "error" => false,
                "message" => "Tu mensaje ha sido enviado con exito"
            ];
        } else {
            $response = [
                "error" => true,
                "message" => "Tu mensaje NO fue enviado. Favor intenta de nuevo"
            ];
        }

        //Set response header
        header("Cotent-Type: application/json");
        header("Access-Control-Allow-Origin: *"); //Permitimos el envio de datos cruzados desde el servidor
        echo json_encode($response);
        exit;
    }