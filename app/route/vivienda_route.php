<?php
	use App\Lib\Response;
 
	$app->group('/vivienda/', function () {
		$this->get('', function ($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de vivienda');
		});

        // Agregar vivienda
		$this->post('add/', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			unset($parsedBody['id']);
			$vivienda = $this->model->vivienda->add($parsedBody);
			if($vivienda->response){
				$vivienda_id = $vivienda->result;
				$seg_log = $this->model->seg_log->add('Registro nueva vivienda', $vivienda_id, 'vivienda_titular'); 
				if(!$seg_log->response){
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
                        return $response->withJson($seg_log);
				}
			}else{
				$vivienda->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($vivienda); 
			}
			$vivienda->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($vivienda);
		});

        // Obtener vivienda por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->vivienda->get($arguments['id']));
		});

        // Obtener vivienda por titular
		$this->get('getByTit/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->vivienda->getByTit($arguments['id']));
		});

        // Editar vivienda
		$this->put('edit/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$vivienda_id = $arguments['id'];
			unset($parsedBody['id']);
			$vivienda = $this->model->vivienda->edit($parsedBody, $vivienda_id); 
			if($vivienda->response) {
				$seg_log = $this->model->seg_log->add('Actualización información vivienda', $vivienda_id, 'vivienda_titular', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			}else{
				$vivienda->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($vivienda); 
			}
			$vivienda->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($vivienda);
		});

        // Eliminar vivienda
		$this->put('del/{id}', function ($req, $res, $args) {
			$this->response = new Response();
			$this->model->transaction->iniciaTransaccion();
			$resultado = $this->model->vivienda->del($args['id']);
			if($resultado->response){
				$add = $this->model->seg_log->add('Elimina vivienda', $args['id'], 'vivienda_titular');
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
						->write(json_encode($this->model->vivienda->findBy($args['f'], $args['v'])));			
		});
	});
?>