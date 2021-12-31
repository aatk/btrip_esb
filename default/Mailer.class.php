<?php

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 23.07.2018
 * Time: 11:00
 */
class Mailer extends ex_class
{

    private $mailconfig;
    private $metod;
    private $connectionInfo;

    public function __construct($metod = "")
    {
        parent::__construct($_SESSION["i4b"]["connectionInfo"]);   //на тот случай если мы будем наследовать от класса

        $this->mailconfig = $_SESSION["i4b"]["MailerConfig"];
        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем настройки подключения к БД
        $this->metod = $metod;
    }


    private function temporaryFile($name, $content)
    {
        $file = DIRECTORY_SEPARATOR .
            trim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) .
            DIRECTORY_SEPARATOR .
            ltrim($name, DIRECTORY_SEPARATOR);

        file_put_contents($file, $content);

        return $file;
    }

    public function SendHTMLMailBase64($allemails, $subject, $message, $Attachments = null) {

        if (isset($message["body"]) && (isset($message["vars"]))) {
            $newbody = $message["body"];
            foreach ($message["vars"] as $key => $var) {
                $newbody = str_replace("%".$key."%", $var, $newbody);
            }
            $message["body"] = $newbody;
        }

        $newAtt = [];
        if (isset($Attachments)) {
            foreach ($Attachments as $Attachment) {
                $newF = [];
                $newF['filename'] = $this->temporaryFile($Attachment["name"], base64_decode($Attachment["content"]));
                $newF['name'] = $Attachment["name"];
                $newAtt[] = $newF;
            }
        }

        $result = $this->SendMail($allemails, $subject, $message["body"], $newAtt);

        foreach ($newAtt as $newf) {
            unlink($newf["filename"]);
        }

        return $result;
    }



    public function SendMail($allemails, $subject, $message, $Attachments = null)
    {
        $ApMailerConfig = $this->mailconfig;

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = 0;                                 // Enable verbose debug output
            $mail->isSMTP();                                      // Set mailer to use SMTP
            $mail->Host = $ApMailerConfig['smtp_host'];  // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = $ApMailerConfig['smtp_username'];                 // SMTP username
            $mail->Password = $ApMailerConfig['smtp_password'];                           // SMTP password
            $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
            $mail->Port = $ApMailerConfig['smtp_port'];                                    // TCP port to connect to
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = '8bit';


            //Recipients
            $mail->setFrom($ApMailerConfig['smtp_username'], $ApMailerConfig['smtp_username']);

            foreach ($allemails as $key => $emails) {
                foreach ($emails as $email) {
                    if ($key == "Address") {
                        $mail->addAddress($email, '<'.$email.'>');     // Add a recipient
                    } elseif ($key == "ReplyTo") {
                        $mail->addReplyTo($email, '<'.$email.'>');     // Add a recipient
                    } elseif ($key == "CC") {
                        $mail->addCC($email, '<'.$email.'>');     // Add a recipient
                    } elseif ($key == "BCC") {
                        $mail->addBCC($email, '<'.$email.'>');     // Add a recipient
                    }
                }
            }

            //Attachments
            if (isset($Attachments)) {
                foreach ($Attachments as $Attachment) {
                    $mail->addAttachment($Attachment['filename'], $Attachment['name']);    // Optional name
                }
            }

            //Content
            $mail->isHTML(true);   // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = $message;

            $mail->send();
            $result = ["result" => true, "message" => 'Message has been sent'];
        } catch (Exception $e) {
            $result = ["result" => false, "message" => 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo];
        }

        return $result;
    }


}