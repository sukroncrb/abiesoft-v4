<?php

declare(strict_types=1);

namespace Abiesoft\System\Package\Email\Template;

trait Otp
{
    
    public function templateOtp($mail, $nama_user, $otp_code)
    {
        $mail->isHTML(true);
        $mail->Subject = $otp_code . ' adalah Kode Verifikasi Abiesoft Anda';

        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; color: #333;'>
                <div style='text-align: center; border-bottom: 1px solid #eeeeee; padding-bottom: 20px; margin-bottom: 20px;'>
                    <h2 style='color: #4285F4; margin: 0;'>Abiesoft System</h2>
                </div>
                
                <p>Halo <strong>{$nama_user}</strong>,</p>
                <p>Gunakan kode OTP di bawah ini untuk memverifikasi akun Anda.</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <span style='font-family: \"Courier New\", Courier, monospace; font-size: 40px; font-weight: bold; letter-spacing: 10px; background-color: #f4f4f4; padding: 10px 20px; border-radius: 5px; color: #202124; border: 1px dashed #ccc;'>
                        {$otp_code}
                    </span>
                </div>
                
                <p style='font-size: 13px; color: #777;'>Jika Anda tidak merasa meminta kode ini, abaikan saja email ini.</p>
                
                <hr style='border: none; border-top: 1px solid #eeeeee; margin-top: 30px;'>
                <p style='font-size: 11px; color: #999; text-align: center;'>
                    &copy; " . date('Y') . " Abiesoft System.
                </p>
            </div>
        ";

        $mail->AltBody = "Kode OTP Anda adalah: {$otp_code}. Kode ini bersifat rahasia, jangan berikan kepada siapapun.";
    }

    
}