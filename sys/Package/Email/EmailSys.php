<?php

declare(strict_types=1);

namespace Abiesoft\System\Package\Email;

use Abiesoft\App\Shared\Helpers\ApiResult;
use Abiesoft\System\Package\Email\Template\Otp;
use Abiesoft\System\Package\Email\Template\Test;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class EmailSys
{

    use Otp, Test, ApiResult;

    protected function emailConfig($opsi) {
        return match($opsi){
            'pass' => $_ENV['EMAIL_PASSWORD'],
            'akun' => $_ENV['EMAIL_AKUN'],
            'user' => $_ENV['EMAIL_USER'],
            'port' => $_ENV['EMAIL_PORT'],
            'auth' => $_ENV['EMAIL_SMTP_AUTH'],
            'host' => $_ENV['EMAIL_HOST'],
            default => ""
        };
    }
    
    public function kirim($emailpenerima = "", $namapenerima = "", $data = "", $template = "otp" )
    {

        $mail = new PHPMailer(true);
        
        $emailpenerima = urldecode($emailpenerima);

        try {
            
            $mail->isSMTP();                        
            $mail->Host       = $this->emailConfig('host');   
            $mail->SMTPAuth   = $this->emailConfig('auth');                
            $mail->Username   = $this->emailConfig('akun');      
            $mail->Password   = $this->emailConfig('pass'); 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
            $mail->Port       = $this->emailConfig('port');                            

            
            $mail->setFrom($this->emailConfig('akun'), $this->emailConfig('user'));
            $mail->addAddress($emailpenerima, $namapenerima); 

            if($template == "otp"){
                $kodeOTP = $data;
                $this->templateOtp($mail, $namapenerima, $kodeOTP);
                $mail->send();
                return $kodeOTP;
            }

            if($template == "test"){
                $this->templateTest($mail, $namapenerima);
                $mail->send();
                return "Sukses";
            }

        } catch (Exception $e) {
            return "Pesan gagal dikirim. Error: {$mail->ErrorInfo}";
        }
    }

    
}