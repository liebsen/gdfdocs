<?php 

//error_reporting(0);

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
use setasign\Fpdi\Fpdi;
use App\Custom_FPDF;
define('EURO',chr(128));

function generate_uuid($id){
    $str = md5(uniqid($id, true));
    $str = stringInsert($str,8,'-');
    $str = stringInsert($str,13,'-');
    $str = stringInsert($str,17,'-');
    return $str;
}

/* sends email to an account.. */

function send_email($subject,$recipient,$template,$body,$attachment,$debug = 0){

    global $app;

    extract($body);

    $container = $app->getContainer();

    $data = [];

    $view = new \Slim\Views\Twig( __DIR__ . '/../public/templates', [
        'cache' => false
    ]);

    $body['baseurl'] = $container->request->getUri()->getScheme() . '://' . $container->request->getUri()->getHost();

    $html = $view->fetch("email/{$template}.html",$body);
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
    $mail->addAddress($email);

    if(!empty($attachment)){
        //$mail->addAttachment('images/phpmailer_mini.png');
        $mail->AddStringAttachment($attachment, "{$pdf_name}.pdf", 'base64', 'application/pdf');// attachment
    }

    //send the message, check for errors
    if ( ! $mail->send()) {
        $data['status'] =  "error";
        $data['message'] = $mail->ErrorInfo;
    } else {
        $data['status'] = "success";
    }

    return $data;
}

function doc2pdf($data, $output = NULL){
    extract($data);

    $pdf = new Custom_FPDF();
    $pages_count = $pdf->setSourceFile(__DIR__ . '/../public/static/documents/' . $pdf_name . '.pdf');

    for($i = 1; $i <= $pages_count; $i++)
    {
        $pdf->AddPage(); 
        $tplIdx = $pdf->importPage($i);
        $pdf->useTemplate($tplIdx,['adjustPageSize' => true]); 

        if(!empty($values[$i])){
            foreach($values[$i] as $pageno => $item){

                $value = utf8_decode(str_replace("€",utf8_encode(EURO),$item['value']));
                //$value = utf8_decode($item['value']);
                $size = $item['size']?:15;
                $lineheight = $item['lineheight']?:3;
                $spacing = $item['spacing']?:0;
                $x = (float) $pdf->GetPageWidth() * (float) $item['x'] / 100;
                $y = (float) $pdf->GetPageHeight() * (float) $item['y'] / 100;

                if($x=='INF') $x = 1;
                if($y=='INF') $y = 1;


                $pdf->SetFont('Arial','B'); 
                $pdf->SetFontSize($size);
                $pdf->SetFontSpacing($spacing);
                $pdf->SetTextColor(79,129,189); 
                $pdf->SetXY($x,$y);

                if(strlen($item['multiline'])) {
                    $pdf->MultiCell(null,($lineheight * substr_count($value,"\n") + $lineheight) , $value,0,$item['align']);             
                } else if(strlen($item['align'])) {
                    $pdf->Cell((float) $item['w'],(float) $item['h'], $value,0,1,$item['align']);
                } else {
                    $pdf->Write(0, $value); 
                }
            }
        }
    }

    return $pdf->Output($output, $pdf_name);
}

function log2file($data, $mode="a"){
    $path = __DIR__ . '/../logs/data.txt';
    $fh = fopen($path, $mode) or die($path);
    fwrite($fh,$data . "\n");
    fclose($fh);
    chmod($path, 0777);
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