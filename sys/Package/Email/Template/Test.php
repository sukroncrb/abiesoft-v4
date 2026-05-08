<?php

declare(strict_types=1);

namespace Abiesoft\System\Package\Email\Template;

trait Test
{
    
    public function templateTest($mail, $nama_user)
    {
        $mail->isHTML(true);
        $mail->Subject = 'Abiesoft Test Email';
        $mail->Body    = "<div>Email Test</div>";
        $mail->AltBody = "Ini adalah email Test yang dikirim dari Framework Abiesoft";
    }

    
}