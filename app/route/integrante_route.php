<?php
	use App\Lib\Response;
 
	$app->group('/integrante/', function () {
		$this->get('', function ($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de integrante');
		});

        // Agregar integrante
		$this->post('add/', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$integrante = $this->model->integrante->add($parsedBody);
			if($integrante->response){
				$integrante_id = $integrante->result;
				$seg_log = $this->model->seg_log->add('Registro nuevo integrante', $integrante_id, 'cat_integrante'); 
				if(!$seg_log->response){
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
                        return $response->withJson($seg_log);
				}
			}else{
				$integrante->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($integrante); 
			}
			$integrante->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($integrante);
		});

        // Obtener integrante por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->integrante->get($arguments['id']));
		});

        // Obtener integrante por titular
		$this->get('getByTit/{id}', function($request, $response, $arguments) {
			$resultado = $this->model->integrante->getByTit($arguments['id']);
			foreach ($resultado->result as $item) {
				$item->edad = $this->model->integrante->getEdad($item->fecha_nacimiento);
				$item->ingreso = $item->ingreso != null ? number_format($item->ingreso,2) : '';
				$item->peso = $this->model->historial->getIMC($item->id_integrante);
			}
			return $response->withJson($resultado);
		});

        // Editar integrante
		$this->put('edit/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$integrante_id = $arguments['id'];
			$integrante = $this->model->integrante->edit($parsedBody, $integrante_id); 
			if($integrante->response) {
				$seg_log = $this->model->seg_log->add('Actualización información integrante', $integrante_id, 'cat_integrante', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			}else{
				$integrante->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($integrante); 
			}
			$integrante->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($integrante);
		});

        // Eliminar integrante
		$this->put('del/{id}', function ($req, $res, $args) {
			$this->response = new Response();
			$this->model->transaction->iniciaTransaccion();
			$resultado = $this->model->integrante->del($args['id']);
			if($resultado->response){
				$add = $this->model->seg_log->add('Elimina integrante', $args['id'], 'cat_integrante');
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

		// Ruta para buscar por nombre
		$this->get('findByNombre/{nombre}/{paterno}/{materno}', function($request, $response, $arguments) {
			$findByNombre = $this->model->integrante->findByNombre($arguments['nombre'], $arguments['paterno'], $arguments['materno']);
			$findByNombre->result = "";
			return $response->withJson($findByNombre);
		});

		// Ruta para buscar por curp
		$this->get('findByCURP/{curp}', function($request, $response, $arguments) {
			$fk_titular = $this->model->integrante->getByCURP($arguments['curp']);
			if($fk_titular->response){
				$Tit = $this->model->titular->getByID($fk_titular->result->fk_titular);
				$StatusTit = $Tit->result->estatus;
				if($StatusTit == 1){
					$Tit->result = "";
					return $response->withJson($Tit);
				}else{
					$this->response = new Response();
					return $response->withJson($this->response->SetResponse(false, "No existe el registro"));
				}
			}else{
				$fk_titular->result = "";
				return $response->withJson($fk_titular);
			}
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
						->write(json_encode($this->model->integrante->findBy($args['f'], $args['v'])));			
		});
	});
?>