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

$app->group('/v1.0', function() {

    $this->post('/download', function ($request, $response, $args) {
        return \doc2pdf($request->getParsedBody());
    });

    $this->post('/send', function ($request, $response, $args) {
        $body = $request->getParsedBody();
        $data = \send_email("GDF Documentos",$body['email'],'documento',$body, \doc2pdf($body,'S'));

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data));
    });
});

$app->get('/{slug:.*}', function ($request, $response, $args) {
    return $this->view->render($response, 'index.html');
});  
