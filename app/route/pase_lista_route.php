<?php
	use App\Lib\Response;
 
	$app->group('/pase_lista/', function () {
		$this->get('', function ($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de pase_lista');
		});

        //Cerrar lista
        $this->post('cerrarLista[/{fecha}]', function($request, $response, $arguments) {
            if(isset($_SESSION['usuario'])){
                $this->model->transaction->iniciaTransaccion();
                $fecha = isset($arguments['fecha']) ? $arguments['fecha'] : date('Y-m-d');

				if(is_object($this->model->pase_lista->getByFecha($arguments['fecha']))){
					$this->model->transaction->regresaTransaccion(); 
					return $response->withJson(array('message' => "Ya se cerró la lista antes el día de hoy"));
				}

                $user = $_SESSION['usuario']->id_usuario;
                $userName = $_SESSION['usuario']->nombre;
                $res = $this->model->pase_lista->getFaltas($arguments['fecha']);
                $numFaltas = count($res);
                $numBajas = 0;

                foreach($res as $data){
                    $faltas = intval($data->faltas);
                    $faltas++;
                    $baja = "";
					$esbaja = "0";
                    if($faltas >= 4){
                        $baja = ", estatus = 2, bajas = (bajas + 1), fecha_baja = '$fecha ".date('H:i:s')."' ";
						$esbaja = "1";
                        $numBajas++;
						$seg_log = $this->model->seg_log->add('Baja beneficiario', $data->id_titular, 'cat_titular'); 
                        if(!$seg_log->response){
							$seg_log->state = $this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
                        }
                    }
                    $editFaltas = $this->model->pase_lista->editFaltas($data->id_titular, $faltas, $baja, $fecha);
                    if($editFaltas=="0"){
						$this->model->transaction->regresaTransaccion(); 
                        return $response->withJson("Error transaccion editFaltas ".$editFaltas);
					}
					$addFalta = $this->model->asistencia->addFalta($data->id_titular, $faltas, $esbaja);
					if(!$addFalta->response){
						$addFalta->state = $this->model->transaction->regresaTransaccion(); 
                        return $response->withJson($addFalta);
					}
                }
                $arrRes="";
                $addPaseLista = $this->model->pase_lista->addPaseLista($user, $userName, $numFaltas, $numBajas, $fecha);
                    if($addPaseLista=="0"){
						$this->model->transaction->regresaTransaccion(); 
                        return $response->withJson("Error transaccion addPaseLista ".$addPaseLista);
					}else{
                        $seg_log = $this->model->seg_log->add('Registro cerrar lista', $addPaseLista, 'pase_lista'); 
                        if(!$seg_log->response){
								$seg_log->state = $this->model->transaction->regresaTransaccion(); 
                                return $response->withJson($seg_log);
                        }
                        $arrRes = array('success' => true, 'id' => $addPaseLista, 'faltas' => $numFaltas, 'bajas' => $numBajas);
                    }
                $this->model->transaction->confirmaTransaccion();
                return $response->withJson($arrRes);
            }else{
                echo "Acceso denegado";
            }
		});

        // Agregar pase_lista
		$this->post('add/', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$pase_lista = $this->model->pase_lista->add($parsedBody);
			if($pase_lista->response){
				$seg_log = $this->model->seg_log->add('Registro nuevo pase_lista', $pase_lista->result, 'pase_lista'); 
				if(!$seg_log->response){
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
                        return $response->withJson($seg_log);
				}
			}else{
				$pase_lista->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($pase_lista); 
			}
			$pase_lista->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($pase_lista);
		});

        // Obtener pase_lista por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->pase_lista->get($arguments['id']));
		});

        // Editar pase_lista
		$this->put('edit/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$pase_lista_id = $arguments['id'];
			$pase_lista = $this->model->pase_lista->edit($parsedBody, $pase_lista_id); 
			if($pase_lista->response) {
				$seg_log = $this->model->seg_log->add('Actualización información pase_lista', $pase_lista_id, 'pase_lista', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			}else{
				$pase_lista->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($pase_lista); 
			}
			$pase_lista->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($pase_lista);
		});

        // Eliminar pase_lista
		$this->put('del/{id}', function ($req, $res, $args) {
			$this->response = new Response();
			$this->model->transaction->iniciaTransaccion();
			$resultado = $this->model->pase_lista->del($args['id']);
			if($resultado->response){
				$add = $this->model->seg_log->add('Elimina pase_lista', $args['id'], 'pase_lista');
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
						->write(json_encode($this->model->pase_lista->findBy($args['f'], $args['v'])));			
		});
	});
?>