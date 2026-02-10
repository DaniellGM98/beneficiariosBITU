<?php
	use App\Lib\Response;
 
	$app->group('/egresos/', function () {
		$this->get('', function ($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de egresos');
		});

        // Agregar egresos
		$this->post('add/', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			unset($parsedBody['id']);
			$egresos = $this->model->egresos->add($parsedBody);
			if($egresos->response){
				$egresos_id = $egresos->result;
				$seg_log = $this->model->seg_log->add('Registro nuevo egresos', $egresos_id, 'egresos_titular'); 
				if(!$seg_log->response){
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
                        return $response->withJson($seg_log);
				}
			}else{
				$egresos->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($egresos); 
			}
			$egresos->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($egresos);
		});

        // Obtener egresos por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->egresos->get($arguments['id']));
		});

        // Obtener egresos por titular
		$this->get('getByTit/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->egresos->getByTit($arguments['id']));
		});

        // Editar egresos
		$this->put('edit/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$egresos_id = $arguments['id'];
			unset($parsedBody['id']);
			$egresos = $this->model->egresos->edit($parsedBody, $egresos_id); 
			if($egresos->response) {
				$seg_log = $this->model->seg_log->add('Actualización información egresos', $egresos_id, 'egresos_titular', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
				$infoEgresos = $this->model->egresos->get($egresos_id)->result;
				$fechaActualizacion = $this->model->titular->editFechaActualizacion($infoEgresos->fk_titular); 
				if(!$fechaActualizacion->response) {
					$egresos->state = $this->model->transaction->regresaTransaccion();
					return $response->withJson($egresos); 
				}
			}else{
				$egresos->state = $this->model->transaction->regresaTransaccion();
				return $response->withJson($egresos); 
			}
			$egresos->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($egresos);
		});

        // Eliminar egresos
		$this->put('del/{id}', function ($req, $res, $args) {
			$this->response = new Response();
			$this->model->transaction->iniciaTransaccion();
			$resultado = $this->model->egresos->del($args['id']);
			if($resultado->response){
				$add = $this->model->seg_log->add('Elimina Egresos', $args['id'], 'egresos_titular');
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
						->write(json_encode($this->model->egresos->findBy($args['f'], $args['v'])));			
		});
	});
?>