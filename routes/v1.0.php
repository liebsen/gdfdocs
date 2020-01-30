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

define('EURO',chr(128));

$app->group('/v1.0', function() {

    $this->post("/ecmalog", function($request, $response, $arguments){
        if(getenv('ECMALOG_LEVEL')){
            $body = $request->getParsedBody();
            $data = $this->spot->mapper("App\Ecmalog")->save(new App\Ecmalog($body));

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode([$data]));
        }
    });

    $this->put("/testp", function ($request, $response, $arguments) {
            $body = $request->getParsedBody();
            extract($body);
            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($body));                
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

    $this->post('/download', function ($request, $response, $args) {
        $body = $request->getParsedBody();
        extract($body);

        $pdf = new Fpdi();
        $pages_count = $pdf->setSourceFile(__DIR__ . '/../public/static/documents/' . $pdf_name . '.pdf');

        for($i = 1; $i <= $pages_count; $i++)
        {
            $pdf->AddPage(); 
            $tplIdx = $pdf->importPage($i);
            $pdf->useTemplate($tplIdx,['adjustPageSize' => true]); 

            if(!empty($values[$i])){
                foreach($values[$i] as $item){

                    $value = utf8_decode(str_replace("€", "EUR",$item['value']));
                    $pdf->SetFont('Arial','B'); 
                    $pdf->SetFontSize(15);
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

    $this->post('/print', function ($request, $response, $args) {

        $qit = sprintf("%'.08d", $invoice->id);
        $datenow = date('j-n-y',time());
        $datevalid = date('j-n-y',strtotime(getenv('APP_QUOTE_TIMESPAN')));
        $imgpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . 'img/';
        $uploadpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . 'uploads/';
        $logo = '';

        if($user->logo_url && file_exists($uploadpath . '200x200' . basename($user->logo_url))) {
            $logo = '../public_html/uploads/200x200' . basename($user->logo_url);
        }

        // create pdf canvas
        $pdf = new FPDF();
        $pdf->AddFont('Weber-Regular','','Weber-Regular.php');
        $pdf->SetFont('Weber-Regular','',40);

        $pdf->AddPage();

        if(strlen($logo)){
            $pdf->Image($logo,10);
        }

        $pdf->setXY(0,20);
        $pdf->SetTextColor(140,140,140);
        $pdf->Cell(198,6,'Presupuesto',0,0,'R');

        $pdf->SetFont('Weber-Regular','',16);
        $pdf->SetTextColor(0,0,0);
        $pdf->setXY(10,50);
        $pdf->Cell(190,6,'Fecha: ' . $datenow,0,0,'R');
        $pdf->Ln();
        $pdf->Cell(190,6,'Fecha de validez: ' . $datevalid,0,0,'R');
        $pdf->Ln();

        /*
        if($invoice->user->code){
            $pdf->Cell(20,6,'Cód. Cliente: '.utf8_decode($invoice->user->code),0,0,'L');
            $pdf->Ln();
        }*/

        $pdf->Cell(190,6,utf8_decode($invoice->customer),0,0,'R');
        $pdf->Ln();

        if(strlen($invoice->email)){
            $pdf->Cell(190,6,$invoice->email,0,0,'R');
            $pdf->Ln();    
        }

        if(strlen($invoice->phone)){
            $pdf->Cell(190,6,$invoice->phone,0,0,'R');
            $pdf->Ln();    
        }

        $subtotal = $invoice->subtotal;
        if(!empty($invoice->subtotal_discount) AND $invoice->subtotal_discount > 0){
            $subtotal = $invoice->subtotal_discount;
        }

        $pdf->SetFont('Weber-Regular','',12);
        $pdf->SetTextColor(80,80,80);
        // Column widths
        $w = array(25, 100, 35, 30, 30);
        // Header
        $pdf->Ln();  
        $header = array('Cantidad', 'Descripción', 'Precio unitario', 'Total de línea');
        for($i=0;$i<count($header);$i++)
            $pdf->Cell($w[$i],7,utf8_decode($header[$i]),1,0,'C');
        $pdf->Ln();
        // Data
        $pdf->SetTextColor(0,0,0);
        $discount = 0;
        foreach($invoice->items as $item)
        {
            $discount+= !empty($invoice->discount) && $invoice->discount > 0 ? number_format($item->amount * ($invoice->discount / 100),2, ',', '.') : '';
            $pdf->Cell($w[0],7,(int) $item->quantity,'BL',0,'R');
            $pdf->Cell($w[1],7,utf8_decode($item->title),'BL');
            $pdf->Cell($w[2],7,number_format($item->unit_price,2, ',', '.'),'BL',0,'R');
            $pdf->Cell($w[3],7,number_format($item->amount,2, ',', '.'),'BLR',0,'R');
            $pdf->Ln();
            //$pdf->Cell($w[3],7,$discount,'BLR',0,'R');    
        }


        $pdf->Cell($w[0],7,'','',0,'R');
        $pdf->Cell($w[1],7,'','',0,'R');
        $pdf->Cell($w[2],7,'','',0,'R');
        $pdf->Cell($w[3],7,'','',0,'R');
        $pdf->Ln();


        $pdf->Cell($w[0],7,'','',0,'R');
        $pdf->Cell($w[1],7,'','',0,'R');
        //$pdf->Cell($w[2],7,'','B',0,'R');
        $pdf->SetTextColor(80,80,80);
        $pdf->Cell($w[2],7,utf8_decode('Subtotal'),'',0,'R');
        $pdf->SetTextColor(0,0,0);
        $pdf->Cell($w[3],7,number_format($invoice->subtotal,2, ',', '.'),'TBLR',0,'R');   
        $pdf->Ln();    

        if(!empty($invoice->discount) AND $invoice->discount > 0){
            $pdf->Cell($w[0],7,'','',0,'R');
            $pdf->Cell($w[1],7,'','',0,'R');
            $pdf->SetTextColor(80,80,80);
            $pdf->Cell($w[2],7,utf8_decode('Descuento'),'',0,'R');
            $pdf->SetTextColor(0,0,0);
            $pdf->Cell($w[3],7,number_format($invoice->subtotal - $invoice->subtotal_discount,2, ',', '.'),'BLR',0,'R');   
            //$pdf->Cell($w[4],7,'','BLR',0,'R');  
            $pdf->Ln();

            $pdf->Cell($w[0],7,'','',0,'R');
            $pdf->Cell($w[1],7,'','',0,'R');
            $pdf->SetTextColor(80,80,80);
            $pdf->Cell($w[2],7,utf8_decode('Subtotal con descuento'),'',0,'R');
            $pdf->SetTextColor(0,0,0);
            $pdf->Cell($w[3],7,number_format($invoice->subtotal_discount,2, ',', '.'),'BLR',0,'R');   
            //$pdf->Cell($w[4],7,'','BLR',0,'R');  
            $pdf->Ln();

        }

        $pdf->Cell($w[0],7,'','',0,'R');
        $pdf->Cell($w[1],7,'','',0,'R');
        //$pdf->Cell($w[2],7,'','B',0,'R');
        $pdf->SetTextColor(80,80,80);
        $pdf->Cell($w[2],7,utf8_decode($user->iva . '% IVA'),'',0,'R');
        $pdf->SetTextColor(0,0,0);
        $pdf->Cell($w[3],7,number_format(($subtotal * $user->iva / 100),2, ',', '.'),'BLR',0,'R');   
        $pdf->Ln();    

        $pdf->Cell($w[0],7,'','',0,'R');
        $pdf->Cell($w[1],7,'','',0,'R');
        $pdf->Cell($w[2],7,'','',0,'R');
        $pdf->Cell($w[3],7,'','',0,'R');
        $pdf->Ln();

        $pdf->Cell($w[0],7,'','',0,'R');
        $pdf->Cell($w[1],7,'','',0,'R');
        //$pdf->Cell($w[2],7,'','B',0,'R');
        $pdf->SetTextColor(80,80,80);
        $pdf->Cell($w[2],7,utf8_decode('Total'),'TBLR',0,'R');
        $pdf->SetTextColor(0,0,0);
        $pdf->Cell($w[3],7,number_format($invoice->total,2, ',', '.'),'TBLR',0,'R');   
        $pdf->Ln();

        if(strlen($user->disclaimer)) {
            $pdf->Ln();
            $pdf->MultiCell(190,6,utf8_decode($user->disclaimer),1);
        }

        $pdf->setXY(0,270);
        $pdf->Cell(210,6, implode(' ',[$user->company,$user->formatted_address,$user->phone,$user->email]),0,0,'C');

        // Closing line
        //$pdf->Cell(array_sum($w),0,'','T');

        return $pdf->Output($output, $name);
    });
});

$app->get('/{slug:.*}', function ($request, $response, $args) {
    return $this->view->render($response, 'index.html');
});  
