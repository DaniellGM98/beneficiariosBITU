<?php
	use App\Lib\Response,
		App\Lib\MiddlewareToken,
		PHPMailer\PHPMailer\PHPMailer,
		PHPMailer\PHPMailer\Exception,
		Slim\Http\UploadedFile;
		require_once './core/defines.php';

	$app->group('/ficha_tecnica/', function() use ($app) {
		$this->get('', function($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Ruta de ficha_tecnica');
		});

        // Agregar ficha_tecnica
		$this->post('add/', function($request, $response, $arguments) {
			require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$ficha_tecnica = $this->model->ficha_tecnica->add($parsedBody);
			if($ficha_tecnica->response){
				$ficha_tecnica_id = $ficha_tecnica->result;
				$seg_log = $this->model->seg_log->add('Registro nueva ficha_tecnica', $ficha_tecnica_id, 'access_user'); 
				if(!$seg_log->response){
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			}else{
				$ficha_tecnica->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($ficha_tecnica); 
			}
			$ficha_tecnica->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($ficha_tecnica);
		});

        // Obtener ficha_tecnica por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->ficha_tecnica->get($arguments['id']));
		});

        // Obtener ficha_tecnica por titular
		$this->get('getByTit/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->ficha_tecnica->getByTit($arguments['id']));
		});

        // Editar ficha_tecnica
		$this->put('edit/{id}', function($request, $response, $arguments) {
			require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$ficha_tecnica_id = $arguments['id'];
			$ficha_tecnica = $this->model->ficha_tecnica->edit($parsedBody, $ficha_tecnica_id); 
			if($ficha_tecnica->response) {
				$seg_log = $this->model->seg_log->add('Actualización información ficha_tecnica', $ficha_tecnica_id, 'ficha_tecnica', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			}else{
				$ficha_tecnica->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($ficha_tecnica); 
			}
			$ficha_tecnica->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($ficha_tecnica);
		});

		// Eliminar ficha_tecnica
		$this->put('del/{id}', function ($req, $res, $args) {
			$this->response = new Response();
			$this->model->transaction->iniciaTransaccion();
			$resultado = $this->model->ficha_tecnica->del($args['id']);
			if($resultado->response){
				$add = $this->model->seg_log->add('Elimina Ficha_tecnica', $args['id'], 'ficha_tecnica');
				if(!$add->response){
					$add->state = $this->model->transaction->regresaTransaccion();
					return $this->response->withJson($add); 
				}
			}else{
				$resultado->state = $this->model->transaction->regresaTransaccion(); 
				return $this->withJson($resultado); 
			}
			$resultado->state = $this->model->transaction->confirmaTransaccion();
	        return $res->withHeader('Content-type', 'application/json')
	                   ->write(json_encode($resultado));
	    });

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
						->write(json_encode($this->model->ficha_tecnica->findBy($args['f'], $args['v'])));			
		});
	});	
?>