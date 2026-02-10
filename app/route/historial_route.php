<?php
	use App\Lib\Response;
 
	$app->group('/historial/', function () {
		$this->get('', function ($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de historial');
		});

		// Agregar historial
		$this->post('add/', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$historial = $this->model->historial->add($parsedBody);
			if($historial->response){
				$historial_id = $historial->result;
				$seg_log = $this->model->seg_log->add('Registro nuevo historial', $historial_id, 'historial_nutricion'); 
				if(!$seg_log->response){
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
                        return $response->withJson($seg_log);
				}
			}else{
				$historial->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($historial); 
			}
			$historial->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($historial);
		});

		// Obtener historial por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->historial->get($arguments['id']));
		});

		// Obtener historial por integrante
		$this->get('getByInte/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->historial->getByInte($arguments['id']));
		});

		// Editar historial
		$this->put('edit/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$historial_id = $arguments['id'];
			$historial = $this->model->historial->edit($parsedBody, $historial_id); 
			if($historial->response) {
				$seg_log = $this->model->seg_log->add('Actualización información historial', $historial_id, 'historial_nutricion', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			}else{
				$historial->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($historial); 
			}
			$historial->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($historial);
		});

		// Eliminar historial
		$this->put('del/{id}', function ($req, $res, $args) {
			$this->response = new Response();
			$this->model->transaction->iniciaTransaccion();
			$resultado = $this->model->historial->del($args['id']);
			if($resultado->response){
				$add = $this->model->seg_log->add('Elimina historial', $args['id'], 'historial_nutricion');
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
						->write(json_encode($this->model->historial->findBy($args['f'], $args['v'])));			
		});
	});
?>