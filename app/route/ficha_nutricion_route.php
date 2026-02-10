<?php
	use App\Lib\Response;
 
	$app->group('/ficha_nutricion/', function () {
		$this->get('', function ($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de ficha_nutricion');
		});

        // Agregar ficha_nutricion
		$this->post('add/', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$ficha_nutricion = $this->model->ficha_nutricion->add($parsedBody['ficha']);
			if($ficha_nutricion->response){
				$ficha_nutricion_id = $ficha_nutricion->result;
				$seg_log = $this->model->seg_log->add('Registro nuevo ficha_nutricion', $ficha_nutricion_id, 'ficha_nutricion'); 
				if(!$seg_log->response){
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
                        return $response->withJson($seg_log);
				}

				$dataHist = $parsedBody['historial'];
				$dataNutri = $parsedBody['ficha'];

				$valores = $this->model->historial->calcularIMC(intval($dataHist['edad'])*12, $dataHist['sexo'], $dataNutri['peso'], $dataNutri['talla']);

				$dataHist['imc'] = $valores[0];
				$dataHist['talla_edad'] = $valores[1];
				$dataHist['peso_talla'] = $valores[2];
				$dataHist['peso_edad'] = $valores[3];

				unset($dataHist['edad']);
				unset($dataHist['sexo']);

				$hist = $this->model->historial->add($dataHist);

				if($hist->response){
					$historial_id = $hist->result;
					$seg_log = $this->model->seg_log->add('Registro nuevo historial_nutricion', $historial_id, 'historial_nutricion'); 
					if(!$seg_log->response){
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
					}
				}else{
					$hist->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($hist); 
				}
			}else{
				$ficha_nutricion->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($ficha_nutricion); 
			}
			$ficha_nutricion->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($ficha_nutricion);
		});


        // Obtener ficha_nutricion por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->ficha_nutricion->get($arguments['id']));
		});

        // Obtener ficha_nutricion por titular
		$this->get('getByTit/{id}', function($request, $response, $arguments) {
			$ficha_nutricion = $this->model->ficha_nutricion->getByTit($arguments['id']);
			$historial = $this->model->historial->getByInte($arguments['id'])->result;
			if($historial!=null){
				$ficha_nutricion->result->glucosa = $historial[0]->glucosa;
				$ficha_nutricion->result->trigliceridos = $historial[0]->trigliceridos;
				$ficha_nutricion->result->colesterol = $historial[0]->colesterol;
				$ficha_nutricion->result->presion = $historial[0]->presion;
				$dataInte = $this->model->integrante->get($arguments['id'])->result;
				$edadd = $this->model->integrante->getEdad($dataInte->fecha_nacimiento);
				$valores = $this->model->historial->calcularIMC(intval($edadd)*12, ($dataInte->sexo)=='M' ?'Masculino' :'Femenino', $ficha_nutricion->result->peso, $ficha_nutricion->result->talla);
				$ficha_nutricion->result->imc = $valores[0];
			}
			return $response->withJson($ficha_nutricion);
		});

        // Editar ficha_nutricion
		$this->put('edit/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$id = $arguments['id'];
			$dataHist = $parsedBody['historial'];
			$dataNutri = $parsedBody['ficha'];
			unset($dataNutri['fk_integrante']);
			unset($dataHist['fk_integrante']);
			$ficha_nutricion = $this->model->ficha_nutricion->edit($parsedBody['ficha'], $id); 
			if($ficha_nutricion->response) {
				$seg_log = $this->model->seg_log->add('Actualización información ficha_nutricion', $id, 'ficha_nutricion', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}

				$valores = $this->model->historial->calcularIMC(intval($dataHist['edad'])*12, $dataHist['sexo'], $dataNutri['peso'], $dataNutri['talla']);

				$dataHist['imc'] = $valores[0];
				$dataHist['talla_edad'] = $valores[1];
				$dataHist['peso_talla'] = $valores[2];
				$dataHist['peso_edad'] = $valores[3];

				unset($dataHist['edad']);
				unset($dataHist['sexo']);

				$hist = $this->model->historial->edit($dataHist, $id);

				if($hist->response){
					$historial_id = $hist->result;
					$seg_log = $this->model->seg_log->add('Actualización información historial_nutricion', $historial_id, 'historial_nutricion'); 
					if(!$seg_log->response){
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
					}
				}else{
					$hist->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($hist); 
				}

			}else{
				$ficha_nutricion->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($ficha_nutricion); 
			}
			$ficha_nutricion->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($ficha_nutricion);
		});

        // Eliminar ficha_nutricion
		$this->put('del/{id}', function ($req, $res, $args) {
			$this->response = new Response();
			$this->model->transaction->iniciaTransaccion();
			$resultado = $this->model->ficha_nutricion->del($args['id']);
			if($resultado->response){
				$add = $this->model->seg_log->add('Elimina ficha_nutricion', $args['id'], 'ficha_nutricion');
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
						->write(json_encode($this->model->ficha_nutricion->findBy($args['f'], $args['v'])));			
		});
	});
?>