<?php 

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;
use Tuupola\Base62;
use setasign\Fpdi\Fpdi;
use App\Custom_FPDF;

define('EURO',chr(128));

$app->group('/v1.0', function() {

    $this->post('/download', function ($request, $response, $args) {
        $body = $request->getParsedBody();
        extract($body);

        $pdf = new Custom_FPDF();
        $pages_count = $pdf->setSourceFile(__DIR__ . '/../public/static/documents/' . $pdf_name . '.pdf');

        for($i = 1; $i <= $pages_count; $i++)
        {
            $pdf->AddPage(); 
            $tplIdx = $pdf->importPage($i);
            $pdf->useTemplate($tplIdx,['adjustPageSize' => true]); 

            if(!empty($values[$i])){
                foreach($values[$i] as $item){

                    $value = utf8_decode(str_replace("€", "EUR",$item['value']));
                    $size = $item['pdfi']['size']?:15;
                    $spacing = $item['pdfi']['spacing']?:0;
                    $pdf->SetFont('Arial','B'); 
                    $pdf->SetFontSize($size);
                    $pdf->SetFontSpacing($spacing);
                    $pdf->SetTextColor(79,129,189); 
                    $pdf->SetXY((float) $item['pdfi']['x'], (float) $item['pdfi']['y']); 

                    if(strlen($item['pdfi']['align'])) {
                        $pdf->Cell((float) $item['pdfi']['w'],(float) $item['pdfi']['h'], $value,0,1,$item['pdfi']['align']);
                    } else {
                        $pdf->Write(0, $value); 
                    }
                }
            }
        }

        return $pdf->Output($output, $name);
    });
    $this->post("/contact", function ($request, $response, $arguments) {


        $attachment = \refocus2pdf($guid,'S');

        //Create a new PHPMailer instance
        $mail = new \PHPMailer;
        $mail->IsSMTP(); 
        $mail->SMTPDebug = $debug?:getenv('MAIL_SMTP_DEBUG');
        $mail->SMTPAuth = getenv('MAIL_SMTP_AUTH');
        $mail->SMTPSecure = getenv('MAIL_SMTP_SECURE');
        $mail->Host = getenv('MAIL_SMTP_HOST');
        $mail->Port = getenv('MAIL_SMTP_PORT');
        $mail->CharSet = "UTF-8";
        $mail->IsHTML(true);
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );    
        $mail->Username = getenv('MAIL_SMTP_ACCOUNT');
        $mail->Password = getenv('MAIL_SMTP_PASSWORD');
        $mail->setFrom(getenv('MAIL_SMTP_ACCOUNT'), getenv('MAIL_FROM_NAME'));
        $mail->addReplyTo(getenv('MAIL_SMTP_ACCOUNT'), getenv('MAIL_FROM_NAME'));
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = $html;
        $mail->addAddress($email, $subject);

        //$mail->addAttachment('images/phpmailer_mini.png');
        $mail->AddStringAttachment($attachment, "{$guid}.pdf", 'base64', 'application/pdf');// attachment
        $data = [];

        //send the message, check for errors
        if ( ! $mail->send()) {
            $data['success'] = false;
            $data['message'] = $mail->ErrorInfo;
        } else {
            $data['success'] = true;
        }
        
        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data));
    });

});

$app->get('/{slug:.*}', function ($request, $response, $args) {
    return $this->view->render($response, 'index.html');
});  
