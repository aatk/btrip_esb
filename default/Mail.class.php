<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 09.06.2018
 * Time: 16:04
 */

class Mail extends ex_class
{
    private $connectionInfo;
    private $ApMailerConfig;

    public function CreateDB()
    {
        $info["mail_inbox"] = [
            "id" => ['type' => 'int(15)', 'null' => 'NOT NULL', 'inc' => true],
            "user" => ['type' => 'varchar(255)', 'null' => 'NOT NULL'],

            "uid" => ['type' => 'varchar(35)'],
            "subject" => ['type' => 'varchar(255)'],
            "emailfrom" => ['type' => 'varchar(255)'],
            "emailto" => ['type' => 'varchar(255)'],
            "date" => ['type' => 'varchar(255)'],
            "message_id" => ['type' => 'varchar(255)'],
            "size" => ['type' => 'int(15)'],
            "msgno" => ['type' => 'int(15)'],
            "recent" => ['type' => 'int(15)'],
            "flagged" => ['type' => 'int(15)'],
            "answered" => ['type' => 'int(15)'],
            "deleted" => ['type' => 'int(15)'],
            "seen" => ['type' => 'int(15)'],
            "draft" => ['type' => 'int(15)'],
            "udate" => ['type' => 'datetime'],

            "attach" => ['type' => 'bool', 'null' => 'NOT NULL']
        ];

        $info["mail_outbox"] = [
            "id" => ['type' => 'int(15)', 'null' => 'NOT NULL', 'inc' => true],
            "uid" => ['type' => 'int(15)', 'null' => 'NOT NULL'],
            "subject" => ['type' => 'varchar(255)', 'null' => 'NOT NULL'],
        ];

        $info["mail_attach"] = [
            "id" => ['type' => 'int(15)', 'null' => 'NOT NULL', 'inc' => true],
            "boxid" => ['type' => 'int(15)', 'null' => 'NOT NULL'],
            "gkey" => ['type' => 'varchar(25)', 'null' => 'NOT NULL'],
            "info" => ['type' => 'text'],
            "inarhive" => ['type' => 'bool', 'null' => 'NOT NULL'],
            "arhivemd5" => ['type' => 'varchar(25)'],
            "content" => ['type' => 'longblob', 'null' => 'NOT NULL'],
        ];

        $this->create($this->connectionInfo['database_type'], $info);
    }

    public function __construct($metod = "")
    {
        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"];
        $this->ApMailerConfig = $_SESSION["i4b"]['MailerConfig'];

        parent::__construct($this->connectionInfo);   //на тот случай если мы будем наследовать от класса

    }

    public function SendMail($emails, $subject, $text, $Attachment = null)
    {

        $ApMailerConfig = $this->ApMailerConfig;

        $message = '<html>
                    <head>
                        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                        <title>' . $subject . '</title>
                    </head>' . $text . '</html>';

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

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
            foreach ($emails as $key => $emailsar) {
                if ($key == "Address") {
                    foreach ($emailsar as $email) {
                        $mail->addAddress($email, '<' . $email . '>');     // Add a recipient
                    }
                } elseif ($key == "CC") {
                    foreach ($emailsar as $email) {
                        $mail->addCC($email, '<' . $email . '>');     // Add a recipient
                    }
                }
            }

            //$content = ""; $filename = "";
            //$mail->addStringAttachment($content, $filename)
            //$mail->addAttachment($path);

            //Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = $message;

            $mail->send();
            $result = ["result" => true, "message" => 'Message has been sent'];
        } catch (Exception $e) {
            $result = ["result" => false, "message" => 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo];
        }

        return $result;
    }


    public function connect()
    {
        $host = '{' . $this->ApMailerConfig["imap_host"] . ':' . $this->ApMailerConfig["imap_port"] . '/ssl/novalidate-cert}INBOX';
        $email = $this->ApMailerConfig["imap_username"];
        $pass = $this->ApMailerConfig["imap_password"];
        $connect = imap_open($host, $email, $pass) or die("can't connect: " . imap_last_error());

        return $connect;
    }

    private function GetAttach($content, $nowpart)
    {

        if (strpos($content, "<html") !== false) {
            //utf8_encode
            $content = trim(utf8_encode(quoted_printable_decode($content)));
        } else if ($nowpart->encoding == 3) {
            //$content = $content;
            //$message2 = imap_base64($message2);
        } else if ($nowpart->encoding == 2) {
            $content = imap_binary($content);
        } else if ($nowpart->encoding == 1) {
            //$content = $content;//imap_8bit($content);
        } else {
            //utf8_encode
            $content = trim((quoted_printable_decode(imap_qprint($content))));
        }

        return $content;
    }

    private function GetContent($connect, $msgno, $mailtext, $jsonparts, $ingkey, &$attach)
    {
        $newattach = [];
        $newattach["gkey"] = $ingkey;
        if (isset($jsonparts["parts"])) {
            $this->GetContents($connect, $msgno, $mailtext->parts, $jsonparts["parts"], $ingkey, $attach);
        } else {
            $content = imap_fetchbody($connect, $msgno, $ingkey);
            $content = $this->GetAttach($content, $mailtext);
            $newattach["info"] = $jsonparts;
            $newattach["content"] = $content;
            $attach[] = $newattach;
        }
    }

    private function GetContents($connect, $msgno, $mailtext, $jsonparts, $ingkey, &$attach)
    {
        foreach ($jsonparts as $key => $value) {
            $gkey = $ingkey . "." . ($key + 1);
            $parts = $mailtext[$key + 1];
            $this->GetContent($connect, $msgno, $parts, $value, $gkey, $attach);
        }
    }

    public function ReadMail($connect)
    {
        $result = ["result" => false];

        $new_mails = imap_search($connect, 'UNSEEN'); //Получим ID непрочитанных писем , 'UNSEEN' 'ALL'
        $new_mails = implode(",", $new_mails); //Соберём все ID в строчку через запятую

        $overview = imap_fetch_overview($connect, $new_mails, 0); //Получаем инфу из заголовков сообщений

        foreach ($overview as $ow) { //пробегаем по полученному массиву. Каждый элемент массива - новое письмо

            $uid = $ow->uid;
            $structure = imap_fetchstructure($connect, $uid, FT_UID);
            $jsonstructure = $this->object2array($structure);

            $jow = $this->object2array($ow);
            //print_r($jow);

            $subject = iconv_mime_decode($ow->subject, 0, "UTF-8"); //Получаем тему письма и сразу декодируем её
            //echo "Subject: $subject <br />\r\n"; //Выведем тему письма

            $mail = $jow;
            $mail["user"] = $this->ApMailerConfig["imap_username"];
            $mail["subject"] = $subject;
            $mail["emailfrom"] = iconv_mime_decode($mail["from"], 0, "UTF-8");
            $mail["emailto"] = iconv_mime_decode($mail["to"], 0, "UTF-8");

            $mail["udate"] = date("Y-m-d H:i:s", (int) $mail["udate"]);

            unset($mail["from"]);
            unset($mail["to"]);

            //0 - Message header
            //1 - MULTIPART/ALTERNATIVE
            //1.1 - TEXT/PLAIN
            //1.2 - TEXT/HTML
            //2 - file.ext

            $attach = [];

            $key = 0;
            if ($key == 0) {
                $newattach = [];
                $newattach["gkey"] = $key;
                $message = imap_fetchbody($connect, $ow->msgno, "$key");
                $newattach["content"] = $message;

                $attach[] = $newattach;
            }

            foreach ($structure->parts as $key => $nowpart) {
                if ($key > 0) {
                    $newattach = [];
                    $newattach["gkey"] = $key + 1;

                    $message = imap_fetchbody($connect, $ow->msgno, $newattach["key"]);
                    $message = $this->GetAttach($message, $nowpart);
                    $newattach["info"] = $jsonstructure["parts"][$key];
                    $newattach["content"] = $message;

                    $attach[] = $newattach;
                } else {
                    $jsonparts = $jsonstructure["parts"][0];
                    $ingkey = "1";
                    $this->GetContent($connect, $ow->msgno, $nowpart, $jsonparts, $ingkey, $attach);
                }
            }

            $mail["attach"] = count($attach) > 2 ? true : false;

            if (!$this->has("mail_inbox", ["uid" => $uid])) {
                $idmail = $this->insert("mail_inbox", $mail);

                if ($idmail > 0) {
                    foreach ($attach as $value) {
                        $value["boxid"] = $idmail;
                        $value["inarhive"] = false;
                        $value["info"] = json_encode($value["info"], JSON_UNESCAPED_UNICODE);
                        $value["arhivemd5"] = "";

                        $this->insert("mail_attach", $value);
                    }
                }

                $result["ids"][] = $idmail;
            }

            $result["result"] = true;
        }

        imap_close($connect); //Не забываем закрыть коннект

        return $result;
    }



    public function GetMailList($where = [])
    {
        $res = $this->select("mail_inbox", "*", $where);

        foreach ($res as $value) {
            $attachs = $this->select("mail_attach", "*", ["AND" => ["boxid" => $value["id"], "gkey[>]" => "0", "gkey[<]" => "2"] ]);
            foreach ($attachs as $attach) {

            }
        }

    }

}