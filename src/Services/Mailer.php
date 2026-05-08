<?php

namespace Kodan\Services;

class Mailer {
    public static function sendTokenRotationAlert(string $to, string $appName, string $newToken) {
        $subject = "⚠️ KODAN-HUB: ALERTA DE SEGURIDAD - Rotación de Token";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: sans-serif; color: #001F3F; }
                .container { padding: 20px; border: 1px solid #D4AF37; border-radius: 10px; }
                .header { background: #001F3F; padding: 10px; text-align: center; color: white; }
                .token { background: #f4f4f4; padding: 15px; font-family: monospace; border-radius: 5px; margin: 20px 0; }
                .footer { font-size: 10px; opacity: 0.5; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>KODAN-HUB</h1>
                </div>
                <h2>Notificación de Seguridad</h2>
                <p>Se ha detectado un posible compromiso o se ha solicitado una rotación de seguridad para la aplicación: <strong>$appName</strong>.</p>
                <p>El token anterior ha sido invalidado. Debes actualizar tu aplicación con la siguiente credencial inmediatamente:</p>
                
                <div class='token'>
                    X-KODAN-TOKEN: $newToken
                </div>
                
                <p>Si no solicitaste este cambio, contacta al administrador del HUB.</p>
                
                <div class='footer'>
                    &copy; 2026 KODAN-HUB ENGINEERING | Centralized AI Gateway
                </div>
            </div>
        </body>
        </html>
        ";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: KODAN-HUB <noreply@kodan.software>" . "\r\n";

        // In a real environment, mail() must be configured or use PHPMailer
        // @mail($to, $subject, $message, $headers);
        
        // Log the "sending" for simulation if needed
        file_put_contents(__DIR__ . '/../../logs/mail.log', "[".date('Y-m-d H:i:s')."] Email sent to $to regarding $appName rotation.\n", FILE_APPEND);
    }
}
