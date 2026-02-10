<?php
	use App\Lib\Response;
 
	$app->group('/app/', function () {
		$this->get('', function ($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de app');
		});

		$this->get('asistencia[/{fechaInicio}[/{fechaFin}]]', function ($request, $response, $arguments) {
			// Crear un objeto DateTime con la fecha actual
			$fechaActual = new DateTime();
			// Restar 15 días a la fecha actual
			$fechaActual->sub(new DateInterval('P8D'));
			// Obtener la fecha resultante en el formato 'Y-m-d'
			$fechaMenos15Dias = $fechaActual->format('Y-m-d');

			$fechaInicio = isset($arguments['fechaInicio']) ? $arguments['fechaInicio'] : $fechaMenos15Dias;
			$fechaFin = isset($arguments['fechaFin']) ? $arguments['fechaFin'] : date('Y-m-d');

			$usuarios = $this->model->app->getUsuariosByFecha($fechaInicio, $fechaFin);
			$numero = $this->model->app->getListasByFecha($fechaInicio, $fechaFin);
			$params = array('vista' => 'Asistencia APP', 'usuarios' => $usuarios, 'numero' => $numero, 'fechaInicio' => $fechaInicio, 'fechaFin' => $fechaFin);
			return $this->renderer->render($response, 'app_asistencia.phtml', $params);
		});

		$this->get('lista/{tipo}/{query}[/{fechaInicio}[/{fechaFin}]]', function ($request, $response, $arguments) {
			$fechaInicio = isset($arguments['fechaInicio']) ? $arguments['fechaInicio'] : date('Y-m-d');
			$fechaFin = isset($arguments['fechaFin']) ? $arguments['fechaFin'] : date('Y-m-d');

			$usuario = '';	
			$id = $arguments['query'];			
			if($arguments['tipo'] == 1){	
				$lista = $this->model->app->getListaByUser($arguments['query'], $fechaInicio, $fechaFin);
				$user = $this->model->usuario->get($arguments['query'])->result;	
				$usuario = $user->nombre.' '.$user->apellidos;	
			}else if($arguments['tipo'] == 2){	
				$lista = $this->model->app->getListaByNum($arguments['query'], $fechaInicio, $fechaFin);
			}	
			$params = array('vista' => 'Lista Asistencia APP', 'lista' => $lista, 'tipo' => $arguments['tipo'], 'usuario' => $usuario, 'id' => $id, 'fechaInicio' => $fechaInicio, 'fechaFin' => $fechaFin);
			return $this->renderer->render($response, 'app_lista.phtml', $params);
		});

		$this->get('listaAsist/{tipo}/{query}[/{fecha}]', function ($request, $response, $arguments) {
			$fecha = date('Y-m-d');
			//$fecha = '2022-09-19';
			$usuario = '';
			if($arguments['tipo'] == 1){
				$lista = $this->model->app->getListaByUser($arguments['query'], $fecha);
				$user = $this->model->usuario->get($arguments['query'])->result;
				$usuario = $user->nombre.' '.$user->apellidos;
			}else if($arguments['tipo'] == 2){
				$lista = $this->model->app->getListaByNum($arguments['query'], $fecha);
			}
			$params = array('vista' => 'Lista Asistencia APP', 'lista' => $lista, 'tipo' => $arguments['tipo'], 'usuario' => $usuario);
			return $response->withJson($params);
		});

		// Obtener lista de asistencia qr por usuario (pdf)
		$this->get('listaAsistUsu/{tipo}/{query}[/{fechaInicio}[/{fechaFin}]]', function($request, $response, $arguments) {
			$fechaInicio = isset($arguments['fechaInicio']) ? $arguments['fechaInicio'] : date('Y-m-d');
			$fechaFin = isset($arguments['fechaFin']) ? $arguments['fechaFin'] : date('Y-m-d');
			
			$arrMes = array('','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');	
    		$sub2 = "Del ".substr($fechaInicio,8,10)." de ".$arrMes[intval(substr($fechaInicio,5,7))]." de ".substr($fechaInicio,0,4)." al ".substr($fechaFin,8,10)." de ".$arrMes[intval(substr($fechaFin,5,7))]." de ".substr($fechaFin,0,4);
			$usuario = '';	
			$lista = $this->model->app->getListaByUser($arguments['query'], $fechaInicio, $fechaFin);
			$user = $this->model->usuario->get($arguments['query'])->result;
			$usuario = $user->nombre.' '.$user->apellidos;
			$paramss = array('vista' => 'Lista Asistencia APP', 'lista' => $lista, 'tipo' => $arguments['tipo'], 'usuario' => $usuario);
			$total = count($paramss['lista']);
			$titulo = "Lista de Asistencia APP";
			$params = array('vista' => $titulo);
			$params['sub'] = "El usuario ".$usuario." capturo ".$total." asistencias";
			$params['sub2'] = $sub2;
			$params['registros'] = $paramss['lista'];
			return $this->view->render($response, 'rptListaAsistenciaQR.php', $params);
		});

		// Obtener lista de asistencia qr por lista (pdf)
		$this->get('listaAsistList/{tipo}/{query}[/{fechaInicio}[/{fechaFin}]]', function($request, $response, $arguments) {	
			$fechaInicio = isset($arguments['fechaInicio']) ? $arguments['fechaInicio'] : date('Y-m-d');
			$fechaFin = isset($arguments['fechaFin']) ? $arguments['fechaFin'] : date('Y-m-d');
			
			$arrMes = array('','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');	
			$sub2 = "Del ".substr($fechaInicio,8,10)." de ".$arrMes[intval(substr($fechaInicio,5,7))]." de ".substr($fechaInicio,0,4)." al ".substr($fechaFin,8,10)." de ".$arrMes[intval(substr($fechaFin,5,7))]." de ".substr($fechaFin,0,4);
			$usuario = '';	
			$lista = $this->model->app->getListaByNum($arguments['query'], $fechaInicio, $fechaFin);
			$paramss = array('vista' => 'Lista Asistencia APP', 'lista' => $lista, 'tipo' => $arguments['tipo'], 'usuario' => $usuario);
			$total = count($paramss['lista']);
			$asistencias=0;
			foreach($paramss['lista'] as $data){
				if($data->hora != null) $asistencias++;
			}
			$faltas=$total-$asistencias;
			$titulo = "Lista de Asistencia APP";
			$params = array('vista' => $titulo);
			$params['sub'] = $total." titulares en lista. ".$asistencias." asistencias, ".$faltas." faltas";
			$params['sub2'] = $sub2;
			$params['registros'] = $paramss['lista'];
			return $this->view->render($response, 'rptListaAsistenciaQR.php', $params);
		});

        // Ruta para obtener listas
		$this->get('getListas/', function ($request, $response, $arguments) {
			return $response->withJson($this->model->app->getListas());
		});

		// Ruta para obtener beneficiarios
		$this->get('getIntegrantes/{lista}/{pagina}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->app->getIntegrantes($arguments['lista'], $arguments['pagina']));
		});

		// Ruta para obtener ultimo pase lista
		$this->get('getUltimoPaseLista/{lista}', function ($request, $response, $arguments) {	
			return $response->withJson($this->model->app->getUltimoPaseLista($arguments['lista']));
		});

		// Ruta para verificar login
		$this->post('postAccessUser/', function ($request, $response, $arguments) {
			$parsedBody= $request->getParsedBody();
			return json_encode($this->model->app->postAccessUser($parsedBody['username'], $parsedBody['password']));
		});

		// Ruta para agregar asistencia qr
		$this->post('postAsistencia/{fk_titular}/{fecha}/{usuario}[/{fechaDescarga}]', function ($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody= $request->getParsedBody();
			
			if(isset($parsedBody['fechaDescarga']) && $parsedBody['fechaDescarga'] != "") {
				$result = $this->model->app->getFaltasComunidades($parsedBody['fk_titular'], $parsedBody['fechaDescarga']);
				if($result->response!=""){
					$this->model->app->delFaltaComunidades($result->result->id);

					$resp = $this->model->titular->get($parsedBody['fk_titular']);
					if(intval($resp->result->faltas)>0){
						$faltas = intval($resp->result->faltas)-1;
					}else{
						$faltas = 0;
					}
					$dataTitular = [ 
						'faltas'=>$faltas,
						'ultima_falta'=>null, 
					];
					$this->model->titular->edit($dataTitular, $parsedBody['fk_titular']);
				}
			}

			$postAsistencia = $this->model->app->postAsistencia($parsedBody['fk_titular'], $parsedBody['fecha']);
			if($postAsistencia!="")	{
				$this->model->transaction->confirmaTransaccion();
				return $response->withJson(array('response'=>false,'mensaje'=>'Ya se ha sincronizado la asistencia'));
			}else{
				$addAsistenciaQR = $this->model->app->addAsistenciaQR($parsedBody['fk_titular'], $parsedBody['fecha'], $parsedBody['usuario']);
				if(!$addAsistenciaQR['response']){
					$this->model->transaction->regresaTransaccion();
					return $response->withJson($addAsistenciaQR); 
				}
				$this->model->transaction->confirmaTransaccion();
				return $response->withJson($addAsistenciaQR);
			}


		});

		// Ruta para actualizar despues de asistencia qr
		$this->post('actualizar/{id_titular}/{ultima_visita}/{observaciones_asist}', function ($request, $response, $arguments) {
			$parsedBody= $request->getParsedBody();
			$this->model->transaction->iniciaTransaccion();
			$actualizar = $this->model->app->actualizar($parsedBody['id_titular'], $parsedBody['ultima_visita'], $parsedBody['observaciones_asist']);
			if(!$actualizar['response']){
				$this->model->transaction->regresaTransaccion();
				return $response->withJson($actualizar); 
			}
			$this->model->transaction->confirmaTransaccion();			
			return $response->withJson($actualizar);
		});

		// Ruta para actualizar faltas de asistencia qr
		$this->post('actualizarFaltas/{id_titular}/{faltas}/{fechaDescarga}', function ($request, $response, $arguments) {
			$parsedBody= $request->getParsedBody();
			$this->model->transaction->iniciaTransaccion();

			$result = $this->model->app->getFaltasComunidades($parsedBody['id_titular'], $parsedBody['fechaDescarga']);
			
			if($result->response!=""){
				$this->model->transaction->regresaTransaccion(); 
				$result->result = "";
				$result->response = false;
				$result->message = "Ya tiene falta en el periodo ".$parsedBody['fechaDescarga'];
				return $response->withJson($result);
			}else{

				if($parsedBody['faltas'] == '3'){
					$faltas = 3;
				}else{
					if($parsedBody['faltas'] != null || $parsedBody['faltas'] != ""){
						$faltas = intval($parsedBody['faltas'])+1;
					}else{
						$faltas = 1;
					}
				}

				$baja = "";
				$esbaja = "0";
				/*
				if($faltas >= 4){
					$baja = ", estatus = 2, bajas = (bajas + 1), fecha_baja = '$fecha ".date('H:i:s')."' ";
					$esbaja = "1";
					$numBajas++;
					$seg_log = $this->model->seg_log->add('Baja beneficiario', $parsedBody['id_titular'], 'cat_titular'); 
					if(!$seg_log->response){
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
						return $response->withJson($seg_log);
					}
				}*/

				$editFaltas = $this->model->pase_lista->editFaltas($parsedBody['id_titular'], $faltas, $baja, date('Y-m-d'));
				if($editFaltas=="0"){
					$this->model->transaction->regresaTransaccion(); 
					return $response->withJson("Error transaccion editFaltas ".$editFaltas);
				}
				$addFalta = $this->model->asistencia->addFalta($parsedBody['id_titular'], $faltas, $esbaja);
				if(!$addFalta->response){
					$addFalta->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($addFalta);
				}
				$this->model->transaction->confirmaTransaccion();			
				return $response->withJson($addFalta);
				//print_r($result); exit;
			}
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
						->write(json_encode($this->model->app->findBy($args['f'], $args['v'])));			
		});

		// Ruta para obtener usuarios por fecha
		$this->get('getUsuariosByFecha[/{fecha}]', function ($request, $response, $arguments) {
			$arguments['fecha'] = isset($arguments['fecha']) ? $arguments['fecha'] : date('Y-m-d');
			return $response->withJson($this->model->app->getUsuariosByFecha($arguments['fecha']));
		});

		// Ruta para obtener lista por usuario
		$this->get('getListaByUser/{user}[/{fecha}]', function ($request, $response, $arguments) {
			$arguments['fecha'] = isset($arguments['fecha']) ? $arguments['fecha'] : date('Y-m-d');
			return $response->withJson($this->model->app->getListaByUser($arguments['user'], $arguments['fecha']));
		});

		// Ruta para obtener lista por numero de lista
		$this->get('getListaByNum/{lista}[/{fecha}]', function ($request, $response, $arguments) {
			$arguments['fecha'] = isset($arguments['fecha']) ? $arguments['fecha'] : date('Y-m-d');
			return $response->withJson($this->model->app->getListaByNum($arguments['lista'], $arguments['fecha']));
		});
	});
?>