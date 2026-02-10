<?php
	use App\Lib\Response;
	use Envms\FluentPDO\Literal;
	use function Complex\argument;

	$app->group('/asistencia/', function () {
		$this->get('', function ($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de Asistencia');
		});

        // Pasar Lista
		$this->post('pasarLista/{credencial}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$cred = $arguments['credencial'];
			$arrRes = "";
            if($cred == 'NUEV0915AZULAA'){
                $res = $this->model->asistencia->getAsistTemp();
                if($res->response){
					$editContador = $this->model->asistencia->editContador($res->result->id_temporal);
					if($editContador=="0"){
						$editContador->state = $this->model->transaction->regresaTransaccion(); 
                        return $response->withJson($editContador);
					}
                }else{
                    $addAsistTemp = $this->model->asistencia->addAsistTemp();
					if($addAsistTemp=="0"){
						$addAsistTemp->state =$this->model->transaction->regresaTransaccion(); 
                        return $response->withJson($addAsistTemp);
					}
                }
				$arrRes = array('found' => true, 'error' => true, 'baja' => false, 'msg' => 'Beneficiario TEMPORAL');
            }else{
                $res = $this->model->asistencia->getByCred($cred);
				if($res->response){
					if($res->result->fecha_visita == date('w')){
						if($res->result->visita == date('d/m/Y')){
							$arrRes = array('found' => true, 'error' => true, 'baja' => false, 'msg' => 'Ya pasó el día de hoy');
						}else{
							if($res->result->estatus == 1){
								if($res->result->faltas < 4){
									//return $response->withJson($res);
									$foto = 'data/fotos/'.$res->result->credencial.'.jpg';
									if(!file_exists($foto)) $foto = '';
									if($foto == ''){
										$foto = 'data/fotos/'.$res->result->credencial.'.JPG';
										if(!file_exists($foto)) $foto = '';
									}
									if($foto == ''){
										$foto = 'data/fotos/'.$res->result->credencial.'.png';
										if(!file_exists($foto)) $foto = '';
									}
									// PASAR ASISTENCIA
									$tit = $res->result->id_titular;
									$asistencia = $this->model->asistencia->getAsistencia($tit);
									if(!$asistencia->response){
										$addAsist = $this->model->asistencia->addAsistencia($tit, $res->result->faltas);
										if($addAsist=="0"){
											$addAsist->state = $this->model->transaction->regresaTransaccion(); 
											return $response->withJson($addAsist);
										}
										$editDateTitu = $this->model->asistencia->editUltimaVisita($tit);
										if($editDateTitu=="0"){
											$editDateTitu->state = $this->model->transaction->regresaTransaccion(); 
											return $response->withJson($editDateTitu);
										}
									}
									$arrRes = array('found' => true, 
													'error' => false, 
													'nombre' => utf8_encode($res->result->nomTit), 
													'visita' => $res->result->visita, 
													'falta' => $res->result->falta, 
													'faltas' => intval($res->result->faltas), 
													'obs' => $res->result->observaciones_asist, 
													'foto' => $foto);
								}else{
									$tit = $res->result->id_titular;
									$editEstatusBajas = $this->model->asistencia->editEstatusBajas($tit);
									if($editEstatusBajas=="0"){
										$editEstatusBajas->state = $this->model->transaction->regresaTransaccion(); 
										return $response->withJson($editEstatusBajas);
									}								
									$arrRes = array('found' => true, 
													'error' => true, 
													'baja' => true, 
													'nombre' => utf8_encode($res->result->nomTit), 
													'faltas' => intval($res->result->faltas), 
													'obs' => $res->result->observaciones_asist);
								}
							}else{
								$arrRes = array('found' => true, 
										'error' => true, 
										'baja' => true, 
										'nombre' => utf8_encode($res->result->nomTit), 
										'faltas' => intval($res->result->faltas), 
										'obs' => $res->result->observaciones_asist);
							}
						}
					}else{
						$arrDia = array('','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado');
						$arrRes = array('found' => true, 'error' => true, 'baja' => false, 'msg' => 'Le toca el día '.$arrDia[$res->result->fecha_visita]);
					}
				}else{
					$arrRes = array('found' => false, 'res'=>$res);
				}
            }
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($arrRes);
		});

		// Obtener Lista
		$this->get('getLista/{tipo}[/{fecha}]', function($request, $response, $arguments) {
			$fecha = isset($arguments['fecha']) ? $arguments['fecha'] : date('Y-m-d');
			$getLista = $this->model->asistencia->getLista($arguments['tipo'], $fecha);
			$getBajas = $this->model->asistencia->getBajas($arguments['tipo'], $fecha);

			if(empty($getBajas)){
				$result = $getLista;
			}else{
				$result = array_merge($getBajas, $getLista);

				foreach ($result as $key => $row) {
					$aux[$key] = $row->hora;
				}
				array_multisort($aux, SORT_DESC, $result);
			}

			$cerrado = $this->model->pase_lista->getByFecha($fecha);
			
			// foreach($result as $item){
			// 	if($item->hora==null){
			// 		if(is_object($cerrado)){
			// 			$item->faltas = $this->model->asistencia->getFaltas($item->id_titular, $fecha)->result->num_faltas;
			// 		}else{
			// 			$item->faltas = $this->model->titular->get($item->id_titular)->result->faltas;
			// 		}
			// 	}else{
			// 		$item->faltas = $this->model->asistencia->getAsistenciaByFecha($item->id_titular, $fecha)->result->num_faltas;
			// 	}
			// }

			if($arguments['fecha'] == date('Y-m-d')){
				foreach($result as $item){
					if($item->hora==null){
						if(is_object($cerrado)){
							$item->faltas = $this->model->asistencia->getFaltas($item->id_titular, $fecha)->result->num_faltas;
						}else{
							$item->faltas = $this->model->titular->get($item->id_titular)->result->faltas;
						}
					}else{
						$item->faltas = $this->model->titular->get($item->id_titular)->result->faltas;
					}
				}
			}else{
				foreach($result as $item){
					if($item->hora==null){
						$num_falt =  $this->model->asistencia->getFaltas($item->id_titular, $fecha);
						if(is_object($cerrado)){
							if($num_falt->response){
								$item->faltas = $num_falt->result->num_faltas;
							}else{
								$item->faltas = "";
							}
						}else{
								$item->faltas = "";
						}
					}else{
						$item->faltas = "0";
					}
				}
			}


			$arrDia = array('','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado');
			$arrMes = array('','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
			$strFecha = $arrDia[date('w', strtotime($fecha))].", ".date('d', strtotime($fecha))." de ".$arrMes[date('n', strtotime($fecha))];

			return $response->withJson(array('lista' => $result, 'cerrado' => is_object($cerrado), 'strFecha' => $strFecha));
			// return $response->withJson($result);
		});

		// Obtener lista de asistencia (pdf)
		$this->get('getPDFLista/{tipo}[/{fecha}]', function($request, $response, $arguments) {
			$fecha = isset($arguments['fecha']) ? $arguments['fecha'] : date('Y-m-d');
			$getLista = $this->model->asistencia->getLista($arguments['tipo'], $fecha);
			$getBajas = $this->model->asistencia->getBajas($arguments['tipo'], $fecha);
			if(empty($getBajas)){
				$result = $getLista;
			}else{	
				$result = array_merge($getBajas, $getLista);

				foreach ($result as $key => $row) {
					$aux[$key] = $row->hora;
				}
				array_multisort($aux, SORT_DESC, $result);
			}
			$cerrado = $this->model->pase_lista->getByFecha($fecha);
			// foreach($result as $item){
			// 	if($item->hora==null){
			// 		if(is_object($cerrado)){
			// 			$item->faltas = $this->model->asistencia->getFaltas($item->id_titular, $fecha)->result->num_faltas;
			// 		}else{
			// 			$item->faltas = $this->model->titular->get($item->id_titular)->result->faltas;
			// 		}
			// 	}else{
			// 		$item->faltas = $this->model->asistencia->getAsistenciaByFecha($item->id_titular, $fecha)->result->num_faltas;
			// 	}
			// }

			if($arguments['fecha'] == date('Y-m-d')){
				foreach($result as $item){
					if($item->hora==null){
						if(is_object($cerrado)){
							$item->faltas = $this->model->asistencia->getFaltas($item->id_titular, $fecha)->result->num_faltas;
						}else{
							$item->faltas = $this->model->titular->get($item->id_titular)->result->faltas;
						}
					}else{
						$item->faltas = $this->model->titular->get($item->id_titular)->result->faltas;
					}
				}
			}else{
				foreach($result as $item){
					if($item->hora==null){
						$num_falt =  $this->model->asistencia->getFaltas($item->id_titular, $fecha);
						if(is_object($cerrado)){
							if($num_falt->response){
								$item->faltas = $num_falt->result->num_faltas;
							}else{
								$item->faltas = "";
							}
						}else{
								$item->faltas = "";
						}
					}else{
						$item->faltas = "0";
					}
				}
			}

			if(is_object($cerrado)) {
				$titulo = "Lista de Asistencia (Cerrada)"; 
				$arrMes = array('','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
				$sub2 = "Del ".substr($fecha,8,10)." de ".$arrMes[intval(substr($fecha,5,7))]." de ".substr($fecha,0,4);
			}else{
				$titulo = "Lista de Asistencia (Abierta)";
				$arrMes = array('','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
				$sub2 = "Al ".substr($fecha,8,10)." de ".$arrMes[intval(substr($fecha,5,7))]." de ".substr($fecha,0,4)." ".date('H:i:s');
			}
			$total = count($result);
			$faltan = 0;
			$asistencias = 0;
			foreach($result as $data){if($data->hora==null)$faltan++;}
			$asistencias=$total-$faltan;
			$ex = explode(',',$arguments['tipo']);
			$sub = "";
			if(in_array('1',$ex)) $sub.="Beneficiarios - ";
			if(in_array('2',$ex)) $sub.="Comunidad - ";
			if(in_array('3',$ex)) $sub.="Voluntarios - ";
			if(in_array('4',$ex)) $sub.="COVID-19 - ";
			$params = array('vista' => $titulo);
			$params['sub'] = substr($sub, 0, strlen($sub) - 3);
			$params['sub2'] = $sub2;
			$params['fecha'] = $fecha;
			$params['total'] = $total." titulares en lista. ".$asistencias." asistencias, ".$faltan." restantes";
        	$params['registros'] = $result;
			return $this->view->render($response, 'rptListaAsistencias.php', $params);
		});

		// Obtener lista de asistencia (pdf)
		$this->get('getPDFListaBajas/{tipo}[/{fecha}]', function($request, $response, $arguments) {
			$fecha = isset($arguments['fecha']) ? $arguments['fecha'] : date('Y-m-d');

			$result = $this->model->asistencia->getListaBajas($arguments['tipo'], $fecha);

			foreach ($result as $key => $row) {
				$aux[$key] = $row->credencial;
			}
			array_multisort($aux, SORT_ASC, $result);
			
			$titulo = "Lista de Bajas"; 
			$arrMes = array('','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
			$sub2 = "Del ".substr($fecha,8,10)." de ".$arrMes[intval(substr($fecha,5,7))]." de ".substr($fecha,0,4);
			
			//print_r($result);
			
			$total = count($result);
			$ex = explode(',',$arguments['tipo']);
			$sub = "";
			if(in_array('1',$ex)) $sub.="Beneficiarios - ";
			if(in_array('2',$ex)) $sub.="Comunidad - ";
			if(in_array('3',$ex)) $sub.="Voluntarios - ";
			if(in_array('4',$ex)) $sub.="COVID-19 - ";
			$params = array('vista' => $titulo);
			$params['sub'] = substr($sub, 0, strlen($sub) - 3);
			$params['sub2'] = $sub2;
			$params['fecha'] = $fecha;
			$params['total'] = $total==1 ? $total." baja" : $total." bajas";
        	$params['registros'] = $result;
			return $this->view->render($response, 'rptListaBajas.php', $params);
		});

		// Agregar asistencia
		$this->post('add/', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$asistencia = $this->model->asistencia->add($parsedBody);
			if($asistencia->response){
				$seg_log = $this->model->seg_log->add('Registro nuevo asistencia', $asistencia->result, 'asistencia'); 
				if(!$seg_log->response){
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
                        return $response->withJson($seg_log);
				}
			}else{
				$asistencia->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($asistencia); 
			}
			$asistencia->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($asistencia);
		});

		// Cancelar asistencia
		$this->post('cancelar/{credencial}', function($request, $response, $arguments) {
			date_default_timezone_set('America/Mexico_City');
			$this->model->transaction->iniciaTransaccion();
			$res = $this->model->titular->getByCred($arguments['credencial']);
			if($res->response){
				$id = $res->result->id_titular;

				$info = $this->model->asistencia->getUltimaAsist($id);
				if($info->response){
					$numFaltas = $info->result->num_faltas;
				}else{
					$numFaltas = 0;
				}

				$delete = $this->model->asistencia->delUltima($id);
				if(!$delete->response){
					$delete->state = $this->model->transaction->regresaTransaccion();
					return $response->withJson($delete->setResponse(false, 'Verificar datos'));
				}

				$info = $this->model->asistencia->getUltimaAsist($id);
				if($info->response){
					$ultimaVisita = substr($info->result->fecha,0,10);
				}else{
					$ultimaVisita = null;
				}
				$data = [
					"ultima_visita" => $ultimaVisita,
					"faltas" => $numFaltas,
				];
				$edit = $this->model->titular->edit($data, $id);
				if(!$edit->response){					
					$edit->state = $this->model->transaction->regresaTransaccion();
					return $response->withJson($edit->setResponse(false, 'Verificar datos'));
				}

				$seg_log = $this->model->seg_log->add('Asistencia Cancelada', $id, 'asistencia');
				if(!$seg_log->response){
						$seg_log->state = $this->model->transaction->regresaTransaccion();
						return $response->withJson($seg_log);
				}
				return $response->withJson($edit);
			}else{
				$res->state = $this->model->transaction->regresaTransaccion();
				return $response->withJson($res->setResponse(false, 'Verificar datos'));
			}
		});

		// Obtener asistencia por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->asistencia->get($arguments['id']));
		});

		// Obtener asistencia por titular
		$this->get('getByTit/{id}[/{pagina}[/{limite}]]', function($request, $response, $arguments) {
			$arguments['pagina'] = isset($arguments['pagina'])? $arguments['pagina'] : null;
			$arguments['limite'] = isset($arguments['limite'])? $arguments['limite'] : null;
			return $response->withJson($this->model->asistencia->getByTit($arguments['id'], $arguments['pagina'], $arguments['limite']));
		});

		// Editar asistencia
		$this->put('edit/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$asistencia_id = $arguments['id'];
			$asistencia = $this->model->asistencia->edit($parsedBody, $asistencia_id); 
			if($asistencia->response) {
				$seg_log = $this->model->seg_log->add('Actualización información asistencia', $asistencia_id, 'asistencia', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			}else{
				$asistencia->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($asistencia); 
			}
			$asistencia->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($asistencia);
		});

		// Eliminar asistencia
		$this->put('del/{id}', function ($req, $res, $args) {
			$this->response = new Response();
			$this->model->transaction->iniciaTransaccion();
			$resultado = $this->model->asistencia->del($args['id']);
			if($resultado->response){
				$add = $this->model->seg_log->add('Elimina asistencia', $args['id'], 'asistencia');
				if(!$add->response){
					$add->state = $this->model->transaction->regresaTransaccion();
					return $this->response->withJson($add); 
				}
			}else{
				$resultado->state = $this->model->transaction->regresaTransaccion(); 
				return $this->withJson($resultado); 
			}
			$this->model->transaction->confirmaTransaccion();
	        return $res->withHeader('Content-type', 'application/json')
	                   ->write(json_encode($resultado));
	    });

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
						->write(json_encode($this->model->asistencia->findBy($args['f'], $args['v'])));			
		});
	});
?>