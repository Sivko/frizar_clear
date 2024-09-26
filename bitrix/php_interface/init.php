<?php
function custom_mail($to, $subject, $message, $additionalHeaders = '')
{
  require_once $_SERVER["DOCUMENT_ROOT"] . '/api/v2/vendor/phpmailer/phpmailer/src/PHPMailer.php';
  require_once $_SERVER["DOCUMENT_ROOT"] . '/api/v2/vendor/phpmailer/phpmailer/src/SMTP.php';
  require_once $_SERVER["DOCUMENT_ROOT"] . '/api/v2/vendor/phpmailer/phpmailer/src/Exception.php';
  
  $mail = new PHPMailer\PHPMailer\PHPMailer();
  $mail->isSMTP();                   
  $mail->Host   = 'smtp.beget.com';  
  $mail->SMTPAuth   = true;          
  $mail->Username   = 'abc@frizar.ru';       
  $mail->Password   = '!@#$1234QWERasdf';    
  $mail->SMTPSecure = 'ssl';         
  $mail->Port   = 465;               
  
  $to = str_replace(' ', '', $to);
  $address = explode(',', $to);
  foreach ($address as $addr)
    $mail->addAddress($addr);
  
  $mail->isHTML(true); // Устанавливаем формат HTML
  
  $mail->Subject = $subject;
  $mail->Body    = $message;
  $mail->From    = 'abc@frizar.ru';
  $mail->FromName = 'Your Name'; // Если хотите добавить имя отправителя
  
  $mail->send();
}
