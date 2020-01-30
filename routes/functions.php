<?php 

error_reporting(0);

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Slim\Views\Twig;
use Intervention\Image\ImageManager;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;
use Twig\Extension\StringLoaderExtension;
use Tuupola\Base62;
use App\User;
use App\Email;
use App\Machine;
use App\ProductColorFormula;
use App\Invoice;
use App\InvoiceItemFormula;
use App\InvoiceItem;

function convert_machine($ml,$oz,$pulse,$fraction){
    $y1 = (float) $ml / $oz;
    $y = (int) $y1;
    $p1  = $y1 - $y;
    $p2 = $p1 * $pulse;
    $p = (int) $p2;
    $f1 = (float) $p2 - $p;
    $f3 = floor($f1/$fraction)*$fraction;
    return (object)[
        'y' => $y,
        'p' => $p,
        'f' => $f3
    ];
}

function convert_g($ml,$p1,$density,$oz,$pulse,$fraction){
    $y = (float) $ml * $oz;
    $p2 = (float) $ml / $p1; 
    $p = (float) $p2 * $pulse;
    $f = (float) $p2 * $fraction;
    return (float) ($y + $p + $f) * $density;
}

function get_price_rounded($price){
    switch(getenv('APP_PRICE_ROUND')){
        case 'up':
            return ceil($price);
        case 'down':
            return floor($price);
        default:
            return round((float) $price,2);
    }
}

function stringInsert($str,$pos,$insertstr){
    if (!is_array($pos))
        $pos=array($pos);

    $offset=-1;
    foreach($pos as $p){
        $offset++;
        $str = substr($str, 0, $p+$offset) . $insertstr . substr($str, $p+$offset);
    }
    return $str;
}

function generate_uuid($id){
    $str = md5(uniqid($id, true));
    $str = stringInsert($str,8,'-');
    $str = stringInsert($str,13,'-');
    $str = stringInsert($str,17,'-');
    return $str;
}

function send_quote_pdf($email,$guid,$subject="Untitled",$html="Text is unset",$user=NULL,$template="template",$debug=0){

    global $container; 
    
    $view = new \Slim\Views\Twig( __DIR__ . '/../' . getenv('APP_PUBLIC') . 'templates', [
        'cache' => false
    ]);

    $view->addExtension(new StringLoaderExtension());
    $html = $view->fetch("emails/{$template}.html",[
        'html' => $html,
        'app_url' => getenv('APP_URL'),
        'api_url' => $container->request->getUri()->getScheme() . '://' . $container->request->getUri()->getHost(),
        'user' => $user,    
        'guid' => $guid
    ]);

    //if($_SERVER['REMOTE_ADDR'] == '127.0.0.1') return ['status' => 'success'];

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

    return $data;
}

/* sends email to an account.. */

function send_email($subject,$recipient,$template,$data,$debug = 0){

    global $container; 

    $view = new \Slim\Views\Twig( __DIR__ . '/../' . getenv('APP_PUBLIC') . 'templates', [
        'cache' => false
    ]);

    $code = strtolower(Base62::encode(random_bytes(16)));

    while($container["spot"]->mapper("App\Email")->first(["code" => $code])){
        $code = strtolower(Base62::encode(random_bytes(16)));
    }

    $data['code'] = $code;
    $data['recipient'] = $recipient;
    $data['app_url'] = getenv('APP_URL');
    $data['api_url'] = $container->request->getUri()->getScheme() . '://' . $container->request->getUri()->getHost();

    $html = $view->fetch("emails/{$template}",$data);
    $full_name = $recipient->first_name . ' ' . $recipient->last_name;

    if( strpos($subject,getenv('APP_TITLE')) === false) {
        $subject = getenv('APP_TITLE') . " " . $subject;
    }

    $body = [
        'code' => $code,
        'subject' => $subject,
        'user_id' => $recipient->id,
        'email' => $recipient->email,
        'full_name' => $full_name,
        'content' => $html
    ];

    $email = new Email($body);
    $container["spot"]->mapper("App\Email")->save($email);


    //if($_SERVER['REMOTE_ADDR'] == '127.0.0.1') return ['status' => 'success'];

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
    $mail->AltBody = \html2text($html);
    $mail->addAddress($recipient->email, $full_name);

    //$mail->addAttachment('images/phpmailer_mini.png');
    $data = [];


    //send the message, check for errors
    if ( ! $mail->send()) {
        $data['status'] =  "error";
        $data['message'] = $mail->ErrorInfo;
    } else {
        $data['status'] = "success";
    }

    return $data;
}

/* sends email with predefined title and content to an account.. */

function send_email_template($template,$recipient,$data,$debug = 0){

    global $container; 

    $view = new \Slim\Views\Twig( __DIR__ . '/../' . getenv('APP_PUBLIC') . 'templates', [
        'cache' => false
    ]);
    
    $view->addExtension(new StringLoaderExtension());
    $code = strtolower(Base62::encode(random_bytes(16)));

    while($container["spot"]->mapper("App\Email")->first(["code" => $code])){
        $code = strtolower(Base62::encode(random_bytes(16)));
    }

    $subject = getenv($template . '_TITLE');
    $html = getenv($template . '_HTML');
    $data['code'] = $code;
    $data['html'] = $html;
    $data['recipient'] = $recipient;
    $data['app_url'] = getenv('APP_URL');
    $data['api_url'] = $container->request->getUri()->getScheme() . '://' . $container->request->getUri()->getHost();

    $html = $view->fetch('emails/template.html',$data);
    $full_name = $recipient->first_name . ' ' . $recipient->last_name;

    if( strpos($subject,getenv('APP_TITLE')) === false) {
        $subject = getenv('APP_TITLE') . " " . $subject;
    }

    $body = [
        'code' => $code,
        'subject' => $subject,
        'user_id' => $recipient->id,
        'email' => $recipient->email,
        'full_name' => $full_name,
        'content' => $html
    ];

    $email = new Email($body);
    $container["spot"]->mapper("App\Email")->save($email);

    //if($_SERVER['REMOTE_ADDR'] == '127.0.0.1') return ['status' => 'success'];

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
    $mail->AltBody = \html2text($html);
    $mail->addAddress($recipient->email, $full_name);

    //$mail->addAttachment('images/phpmailer_mini.png');
    $data = [];


    //send the message, check for errors
    if ( ! $mail->send()) {
        $data['status'] =  "error";
        $data['message'] = $mail->ErrorInfo;
    } else {
        $data['status'] = "success";
    }

    return $data;
}

function invoice2pdf($uuid,$uid,$output=null,$name=null){

    global $container;    

    $invoice = $container["spot"]->mapper("App\Invoice")->first([
        'uuid' => $uuid
    ]);

    if(!$invoice){
        return false;
    }

    $user = $container["spot"]->mapper("App\User")->first([
        'id' => $uid
    ]);

    if(!defined('FPDF_FONTPATH')){
        define('FPDF_FONTPATH',getenv('FPDF_FONTPATH'));
    }

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
}

function color2pdf($uuid,$mid,$uid,$output=null,$name=null){

    global $container;    

    $item = $container["spot"]->mapper("App\InvoiceItem")->first([
        'uuid' => $uuid
    ]);

    if(!$item){
        return false;
    }

    if(!defined('FPDF_FONTPATH')){
        define('FPDF_FONTPATH',getenv('FPDF_FONTPATH'));
    }

    $datenow = date('j-n-y H:i',time());

    $color = $container["spot"]->mapper("App\ProductColor")->first([
        'user_id' => [1,$uid],
        'id' => $item->color_id
    ]);

    $machine = $container["spot"]->mapper("App\Machine")->first([
        'id' => $mid
    ]);     

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($machine, new Machine);
    $mac = $fractal->createData($resource)->toArray()['data'];

    /**/
    $auto = strstr(strtolower($machine->type->title),"auto");
    $manual = strstr(strtolower($machine->type->title),"manual");
    $colorants = [];

    $formulas = $container["spot"]->mapper("App\InvoiceItemFormula")->where([            
        'invoice_item_id' => $item->id
    ]);

    foreach ($formulas as $formula) {

        $ml = (float) $formula->amount / $formula->density;

        if($auto) {
            $colorants[]= [
                'title' => utf8_decode(implode(' ',[$formula->description,$formula->code])),
                'qty' => implode(' ',[number_format($formula->amount * $item->pack->title / $item->product->colorant_unit,2).'g',number_format($ml * $invoice->packs / $invoice->product->colorant_unit,2).'ml'])
            ];
        } elseif ($manual) {
            $values = \convert_machine($ml * $invoice->packs / $invoice->product->colorant_unit,$machine->ounce->ml,$machine->pulse->quantity,$machine->fraction->quantity);
            $colorants[]= [
                'title' => utf8_decode(implode(' ',[$formula->description,$formula->code])),
                'qty' => implode(' ',[$values->y.'Y',$values->p.'P',$values->f.'F'])
            ];
        }
    }


    // create pdf canvas
    $pdf = new FPDF();
    $pdf->AddFont('Weber-Regular','','Weber-Regular.php');
    $pdf->SetFont('Weber-Regular','',16);

    $imgpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . 'img/';
    $uploadpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . 'uploads/';

    $logo  = '';

    if($user->logo_url && file_exists($uploadpath . basename($user->logo_url))) {
        $logo = '../public_html/uploads/600x200' . basename($user->logo_url);
    }

    $pdf->AddPage();
    
    if(strlen($logo)){
        $pdf->Image('../public_html/img/logo-pdf.png',10,null);
    }

    $pdf->Ln(20);
    $pdf->Cell(20,6,$datenow,0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Cliente: '.utf8_decode($invoice->customer),0,0,'L');
    if(strlen($invoice->phone)){
        $pdf->Ln();
        $pdf->Cell(20,6,'Tel.: '.$invoice->phone,0,0,'L');
    }
    if(strlen($invoice->email)){
        $pdf->Ln();    
        $pdf->Cell(20,6,'Email: '.$invoice->email,0,0,'L');
    }
    $pdf->Ln();    
    $pdf->Ln();
    $pdf->Ln();
    $pdf->Cell(20,6,'Color: '.utf8_decode($invoice->colors),0,0,'L');

    if(strlen(trim($invoice->comments))){
        $pdf->Ln();    
        $pdf->Cell(20,6,'Observaciones: '.utf8_decode($invoice->comments),0,0,'L');
    }

    foreach($colorants as $i => $item){
        $pdf->SetXY(140,140 + ($i+1)*6);
        $pdf->Cell(60,6,implode(' ',[$item['title'].' .....',$item['qty']]),0,0,'R');
    }

    return $pdf->Output($output, $name);
}

function color3pdf($uuid,$macid,$uid,$output=null,$name=null){

    global $container;    

    $item = $container["spot"]->mapper("App\InvoiceItem")->first([
        'uuid' => $uuid
    ]);

    if(!$item){
        return false;
    }

    if(!defined('FPDF_FONTPATH')){
        define('FPDF_FONTPATH',getenv('FPDF_FONTPATH'));
    }

    $invoice = $container["spot"]->mapper("App\Invoice")->first([
        'id' => $item->invoice_id
    ]);     

    $machine = $container["spot"]->mapper("App\Machine")->first([
        'id' => $macid
    ]);     

    $user = $container["spot"]->mapper("App\User")->first([
        'id' => $uid
    ]);

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($machine, new Machine);
    $mac = $fractal->createData($resource)->toArray()['data'];

    /**/
    $auto = strstr(strtolower($machine->type->title),"auto");
    $manual = strstr(strtolower($machine->type->title),"manual");
    $ths = $auto ? ['COLORANTES','GRAMOS','MILILITROS'] : ['COLORANTES','Y','PULSOS','FRACCIÓN'];
    $colorants = [];

    $formulas = $container["spot"]->mapper("App\InvoiceItemFormula")->where([            
        'invoice_item_id' => $item->id
    ]);

    foreach ($formulas as $formula) {

        $ml = (float) $formula->amount / $formula->density;

        if($auto) {
            $colorants[]= (object)[
                'code' => $formula->code,
                'amounts' => (object)[
                    'g' => number_format($formula->amount * $item->pack->kg / $item->product->colorant_unit,2),
                    'ml' => number_format($ml * $item->pack->kg / $item->product->colorant_unit,2)
                ]
            ];
        } elseif ($manual) {
            $colorants[]= (object)[
                'code' => $formula->code,
                'amounts' => \convert_machine($ml * $item->pack->kg / $item->product->colorant_unit,$machine->ounce->ml,$machine->pulse->quantity,$machine->fraction->quantity)
            ];
        }
    }

    // clear if necessary

    foreach($colorants as $i => $colorant){
        $hasanyvalue = 0;
        foreach ($colorant->amounts as $amount) {
            if($amount > 0){
                $hasanyvalue = 1;
            }
        }

        if(!$hasanyvalue){
            unset($colorants[$i]);
        }
    }

    $colorants = array_values($colorants);

    // create pdf canvas
    $pdf = new FPDF();
    $pdf->AddFont('Weber-Medium','','Weber-Medium.php');
    $pdf->AddFont('Weber-Regular','','Weber-Regular.php');
    $pdf->SetFont('Weber-Regular','',16);

    $imgpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . 'img/';
    $uploadpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . 'uploads/';
    $datenow = date('j-n-y H:i',time());
    $logo = '';

    if($user->logo_url && file_exists($uploadpath . '200x200' . basename($user->logo_url))) {
        $logo = '../public_html/uploads/200x200' . basename($user->logo_url);
    }

    $pdf->AddPage();
    if(strlen($logo)){
        $pdf->Image($logo,10);
    }
    $pdf->Ln();
    $pdf->Cell(20,6,$datenow,0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Cliente: '.utf8_decode($invoice->customer),0,0,'L');
    if(strlen($invoice->phone)){
        $pdf->Ln();
        $pdf->Cell(20,6,'Tel.: '.$invoice->phone,0,0,'L');
    }
    if(strlen($invoice->email)){
        $pdf->Ln();    
        $pdf->Cell(20,6,'Email: '.$invoice->email,0,0,'L');
    }
    $pdf->Ln();    
    $pdf->Ln();    
    //$pdf->SetFont('Weber-Medium','',16);
    $pdf->Cell(20,6,'Equipo: '.utf8_decode($machine->title),0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Producto: '.utf8_decode($item->product->title),0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Base: '.utf8_decode($item->base->title),0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Color: '.utf8_decode($item->color->title),0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Envase: '.utf8_decode($item->pack->title),0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Baldes: '.utf8_decode($item->quantity),0,0,'L');

    if(strlen(trim($item->comments))){
        $pdf->Ln();    
        $pdf->MultiCell(160,6,'Observaciones: '.utf8_decode($item->comments));
    }   

    $pdf->SetFont('Weber-Medium','',15);
    $cellw = 48;
    $celly = 150;
    foreach($ths as $i => $th){
        $x = 10 + ($i*$cellw);
        $y = $celly;
        $pdf->SetXY($x,$y);
        $pdf->Image('../fpdf/img/TD2.png',$x,null,$cellw);
        $pdf->SetTextColor(50,50,50);
        $pdf->SetXY($x,$y);
        $pdf->Cell($cellw,10,utf8_decode(strtoupper($th)),0,0,'C');
    }

    foreach($colorants as $j => $colorant){

        $x = 10;
        $y = $celly + 10 + ($j*10);
        $pdf->SetXY($x,$y);
        $pdf->Image('../fpdf/img/' . $colorant->code . '.png',$x,null,$cellw);

        if(in_array($colorant->code,['AXX','KX'])){
            $pdf->SetTextColor(0,0,0);    
        } else {
            $pdf->SetTextColor(255,255,255);
        }

        $pdf->SetXY($x,$y);
        $pdf->Cell($cellw,10,$colorant->code,0,0,'C');

        foreach ($colorant->amounts as $k => $value) {
            $x+= ($k+1)*$cellw;
            $pdf->SetXY($x,$y);
            $pdf->Image('../fpdf/img/TD2.png',$x,null,$cellw);
            $pdf->SetTextColor(50,50,50);
            $pdf->SetXY($x,$y);
            $pdf->Cell($cellw,10,$value,0,0,'C');
        }
    }

    return $pdf->Output($output, $name);
}

function formula3pdf($colid,$macid,$uid,$output=null,$name=null){

    global $container;    

    $color = $container["spot"]->mapper("App\ProductColor")->first([
        'user_id' => [1,$uid],
        'id' => $colid
    ]);

    if(!$color){
        return false;
    }

    if(!defined('FPDF_FONTPATH')){
        define('FPDF_FONTPATH',getenv('FPDF_FONTPATH'));
    }

    $datenow = date('j-n-y H:i',time());

    $machine = $container["spot"]->mapper("App\Machine")->first([
        'id' => $macid
    ]);     

    $user = $container["spot"]->mapper("App\User")->first([
        'id' => $uid
    ]);


    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($machine, new Machine);
    $mac = $fractal->createData($resource)->toArray()['data'];

    /**/
    $auto = strstr(strtolower($machine->type->title),"auto");
    $manual = strstr(strtolower($machine->type->title),"manual");
    $ths = $auto ? ['COLORANTES','G','ML'] : ['COLORANTES','Y','PULSOS','FRACCIÓN'];
    $colorants = [];

    $formulas = $container["spot"]->mapper("App\ProductColorFormula")
        ->where(['color_id' => $colid])
        ->where(['unit' => 'g']);

    foreach ($formulas as $formula) {

        $ml = (float) $formula->amount / $formula->colorant->density;
        if($auto) {
            $colorants[]= (object)[
                'code' => $formula->colorant->code,
                'amounts' => (object)[
                    'g' => number_format($formula->amount,2),
                    'ml' => number_format($ml,2)
                ]
            ];
        } elseif ($manual) {
            $colorants[]= (object)[
                'code' => $formula->colorant->code,
                'amounts' => \convert_machine($ml,$machine->ounce->ml,$machine->pulse->quantity,$machine->fraction->quantity)
            ];
        }
    }

    // clear if necessary

    foreach($colorants as $i => $colorant){
        $hasanyvalue = 0;
        foreach ($colorant->amounts as $amount) {
            if($amount > 0){
                $hasanyvalue = 1;
            }
        }

        if(!$hasanyvalue){
            unset($colorants[$i]);
        }
    }

    $colorants = array_values($colorants);

    // create pdf canvas
    $pdf = new FPDF();
    $pdf->AddFont('Weber-Medium','','Weber-Medium.php');
    $pdf->AddFont('Weber-Regular','','Weber-Regular.php');
    $pdf->SetFont('Weber-Regular','',16);

    $imgpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . 'img/';
    $uploadpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . 'uploads/';

    $logo = '';

    if($user->logo_url && file_exists($uploadpath . '200x200' . basename($user->logo_url))) {
        $logo = '../public_html/uploads/200x200' . basename($user->logo_url);
    }

    $pdf->AddPage();
    if(strlen($logo)){
        $pdf->Image($logo,10);
    }
    $pdf->Ln(20);
    $pdf->Cell(20,6,$datenow,0,0,'L');

    $pdf->Ln();
    $pdf->Cell(20,6,utf8_decode('Fórmula propia: ') . utf8_decode($color->title),0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,utf8_decode('Equipo: ') . utf8_decode($machine->title),0,0,'L');

    $pdf->SetFont('Weber-Medium','',15);
    $cellw = 48;
    $celly = 150;

    foreach($ths as $i => $th){
        $x = 10 + ($i*$cellw);
        $y = $celly;
        $pdf->SetXY($x,$y);
        $pdf->Image('../fpdf/img/TD2.png',$x,null,$cellw);
        $pdf->SetTextColor(50,50,50);
        $pdf->SetXY($x,$y);
        $pdf->Cell($cellw,10,utf8_decode(strtoupper($th)),0,0,'C');
    }

    foreach($colorants as $j => $item){

        $x = 10;
        $y = $celly + 10 + ($j*10);
        $pdf->SetXY($x,$y);
        $pdf->Image('../fpdf/img/' . $item->code . '.png',$x,null,$cellw);

        if(in_array($item->code,['AXX','KX'])){
            $pdf->SetTextColor(0,0,0);    
        } else {
            $pdf->SetTextColor(255,255,255);
        }

        $pdf->SetXY($x,$y);
        $pdf->Cell($cellw,10,$item->code,0,0,'C');

        foreach ($item->amounts as $k => $value) {
            $x+= ($k+1)*$cellw;
            $pdf->SetXY($x,$y);
            $pdf->Image('../fpdf/img/TD2.png',$x,null,$cellw);
            $pdf->SetTextColor(50,50,50);
            $pdf->SetXY($x,$y);
            $pdf->Cell($cellw,10,$value,0,0,'C');
        }
    }

    return $pdf->Output($output, $name);
}

function log2file($path, $data, $mode="a"){
   $fh = fopen($path, $mode) or die($path);
   fwrite($fh,$data . "\n");
   fclose($fh);
   chmod($path, 0777);
}

function login_redirect($data){
    \log2file( __DIR__ . "/../logs/ecma-" . date('Y-m-d') . ".log",json_encode($data)); 
    return "<script>location.href = '" . \login_redirect_url($data) . "';</script>";
}

function login_redirect_url($data){
    return getenv('APP_URL') . "/opener?token=" . json_encode($data) . "&url=" . getenv('APP_REDIRECT_AFTER_LOGIN');
}

function strToHex($string){
    $hex = '';
    for ($i=0; $i<strlen($string); $i++){
        $ord = ord($string[$i]);
        $hexCode = dechex($ord);
        $hex .= substr('0'.$hexCode, -2);
    }
    return strToUpper($hex);
}

function hexToStr($hex){
    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2){
        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    return $string;
}

function set_username($intended){

    global $container; 

    if($intended == ""){
        $intended = strtolower(Base62::encode(random_bytes(8)));
    }

    $j=0;
    $username = $intended;

    while($container["spot"]->mapper("App\User")->first(["username" => \slugify($username)])){
        $j++;
        $username = $intended . $j;
    }

    return \slugify($username);
}

function slugify($text){

    // replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);

    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    // trim
    $text = trim($text, '-');

    // remove duplicate -
    $text = preg_replace('~-+~', '-', $text);

    // lowercase
    $text = strtolower($text);

    if (empty($text)) {
        return strtolower(Base62::encode(random_bytes(8)));
    }

    return $text;
}

function set_token($user){

    global $container;

    $now = new DateTime();
    $future = new DateTime("now +" . getenv('APP_JWT_EXPIRATION'));
    $jti = Base62::encode(random_bytes(16));

    $payload = [
        "uid" => $user->id,
        "rid" => $user->role_id,
        "iat" => $now->getTimeStamp(),
        "exp" => $future->getTimeStamp(),
        "jti" => $jti
    ];

    return JWT::encode($payload, getenv("APP_JWT_SECRET"), "HS256");
}

function register_if_not_exists($email){

    global $container;

    if(!strlen($email)) return false;

    $user = $container["spot"]->mapper("App\User")->first([
        "email" => $email
    ]);

    $fakenames = ['Fresh','Hot','Flamming','Bumpy'];
    $fakesurenames = ['Feeling','Splendorous','Jackets'];

    if(!$user){
        $password = strtolower(Base62::encode(random_bytes(10)));
        $emaildata['readable_password'] = $password;
        $emaildata['email_encoded'] = Base62::encode($email);
        $hash = sha1($password . getenv('APP_HASH_SALT'));
        $user = new User([
            "email" => $email,
            "password" => $hash,
            "first_name" => "User"
        ]);

        /*
        \log2file( __DIR__ . "/../logs/password-" . date('Y-m-d') . ".log",json_encode([
            'hash' => $hash,
            'salt' => getenv('APP_HASH_SALT'),
            'password' => $password
        ])); */

        $container["spot"]->mapper("App\User")->save($user);

        \send_email("Welcome to " . getenv('APP_TITLE'),$user,'welcome.html',$emaildata);
    }

    return $user;
}

function html2text($Document) {
    $Rules = array ('@<style[^>]*?>.*?</style>@si',
                    '@<script[^>]*?>.*?</script>@si',
                    '@<[\/\!]*?[^<>]*?>@si',
                    '@([\r\n])[\s]+@',
                    '@&(quot|#34);@i',
                    '@&(amp|#38);@i',
                    '@&(lt|#60);@i',
                    '@&(gt|#62);@i',
                    '@&(nbsp|#160);@i',
                    '@&(iexcl|#161);@i',
                    '@&(cent|#162);@i',
                    '@&(pound|#163);@i',
                    '@&(copy|#169);@i',
                    '@&(reg|#174);@i',
                    '@&#(d+);@e'
             );
    $Replace = array ('',
                      '',
                      '',
                      '',
                      '',
                      '&',
                      '<',
                      '>',
                      ' ',
                      chr(161),
                      chr(162),
                      chr(163),
                      chr(169),
                      chr(174),
                      'chr()'
                );
  return preg_replace($Rules, $Replace, $Document);
}

function human_timespan_short($time){

    $str = "";
    $diff = time() - $time; // to get the time since that moment
    $diff = ($diff<1)? $diff*-1 : $diff;

    $Y = date('Y', $time);
    $n = date('n', $time);
    $w = date('w', $time);
    $wdays = ['dom','lun','mar','mié','jue','sáb'];

    if($diff < 86400){
        $str = date('H:i',$time); 
    } elseif($diff < 604800){
        $str = $wdays[$w];
    } elseif($Y <> date('Y')){
        $str = date('j/n/y',$time);  
    } elseif($n <> date('n')){
        $str = date('j/n',$time); 
    } else {
        $str = date('j',$time);  
    }

    return $str;
}

function human_timespan($time){

    $time = time() - $time; // to get the time since that moment
    $time = ($time<1)? $time*-1 : $time;
    $tokens = array (
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'min',
        1 => 'sec'
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits.' '.$text.($numberOfUnits>1)?'s':'';
    }
}