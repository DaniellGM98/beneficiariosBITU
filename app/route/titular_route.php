<?php
	use App\Lib\Response;
	use Envms\FluentPDO\Literal;
	use function Complex\argument;
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Csv;
	use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

	$app->group('/titular/', function () {
		$this->get('', function ($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de titular');
		});

		$this->get('{id}', function ($request, $response, $arguments) {
			$info = $this->model->titular->get($arguments['id'])->result;
			if(!is_object($info)){
				return $this->renderer->render($response, '404.phtml', []);
			}
			$info->edad = $this->model->integrante->getEdad($info->fecha_nacimiento);
			$info->firma = $this->model->titular->getFirma($arguments['id']);
			unset($info->firma->huella);
			$municipios = $this->model->titular->getMunicipios();
			$gastos = $this->model->egresos->getByTit($arguments['id']);
			$vivienda = $this->model->vivienda->getByTit($arguments['id']);
			$ficha = $this->model->ficha_tecnica->getByTit($arguments['id'])->result;

			$foto = 'data/fotos/'.$info->credencial.'.jpg';
			if(!file_exists($foto)){
				$foto = 'data/fotos/'.$info->credencial.'.JPG';
				if(!file_exists($foto)){
					$foto = '';
				}
				$foto = 'data/fotos/'.$info->credencial.'.png';
				if(!file_exists($foto)){
					$foto = '';
				}
			}
			$foto = $foto == '' ? URL_ROOT.'/assets/images/BITU blanco.png' : URL_ROOT.'/'.$foto;

			$can = 'No Aplica';
			$obe = 0; $dia = 0;   $hip = 0; $otras = '';

			$integrantes = $this->model->integrante->getByTit($arguments['id'], true)->result;
			foreach ($integrantes as $inte) {
				if(strpos(strtolower($inte->padecimiento), 'cancer')){
					$can = $inte->parentesco == 'TITULAR' ? 'Titular' : 'Integrante';
				}
				$nutri = $this->model->ficha_nutricion->getByTit($inte->id_integrante)->result;				
				if(is_object($nutri) && strlen($nutri->evaluacion_clinica)>3){
					$arrCli = str_split($nutri->evaluacion_clinica);
					if($arrCli[0] == 'S') $obe++;
					if($arrCli[1] == 'S') $dia++;
					if($arrCli[2] == 'S') $hip++;
				}
			}
			if($can == 'No Aplica') $otras = $info->padecimiento;
			$arrFicha = array('cancer' => $can, 'obesidad' => $obe, 'diabetes' => $dia, 'hiper' => $hip, 'otras' => $otras);

			/** NUEVAS PONDERACIONES **/
			// $puntos = 0;

			// if(is_object($gastos)){
			// 	// GASTO ALIMENTICIO
			// 	if($gastos->alimentacion < 500){
			// 		$puntos += 10;
			// 	}else if($gastos->alimentacion > 1000){
			// 		$puntos += 20;
			// 	}else{
			// 		$puntos = 15;
			// 	}

			// 	// VIVIENDA AGUA
			// 	if(intval($gastos->agua) == 0){
			// 		$puntos += 10;
			// 	}else{
			// 		$puntos += 15;
			// 	}

			// 	// TRANSPORTE
			// 	if($gastos->transporte < 500) {
			// 		$puntos += 5;
			// 	}else if($gastos->transporte > 500) {
			// 		$puntos += 15;
			// 	}
			// }
			

			// // INGRESO FAMILIAR
			// $ingFam = $this->model->integrante->getIngresosTit($arguments['id']) + $info->ingreso_otro_apoyo;
			// if($ingFam < 2500){
			// 	$puntos += 20;
			// }else if($ingFam > 5000){
			// 	$puntos += 6;
			// }else{
			// 	$puntos += 15;
			// }
			// $ingFam = number_format($ingFam,2);

			

			// // OTRO APOYO
			// if(intval($info->otro_apoyo) == 3){
			// 	$puntos += 20;
			// }else{
			// 	$puntos += 10;
			// }

			

			// // ESCOLARIDAD
			// if(intval($info->escolaridad) < 6){
			// 	$puntos += 10;
			// }else{
			// 	$puntos += 5;
			// }

			// if($puntos > 0 && $puntos < 60){
			// 	$nivel = 'BAJO';
			// }else if($puntos > 59 && $puntos < 81){
			// 	$nivel = 'MEDIO';
			// }else if($puntos > 80 && $puntos < 101){
			// 	$nivel = 'ALTO';
			// }

			$ingFam = $this->model->integrante->getIngresosTit($arguments['id']) + $info->ingreso_otro_apoyo;
			// $getSalario = $this->model->titular->getSalario();
			// $salario_minimo = floatval($getSalario->result->salario_minimo);
			// $limite_salarial = floatval($getSalario->result->limite_salarial);
			// $infoSeguro = $this->model->titular->getByID($arguments['id'])->result->seguro_social;
			$TotalIntegrantes = $this->model->integrante->getTotalIntegrantes($arguments['id'])->result->Total;
			$info->aporte_gasto_inte = round($info->aporte_gasto/($TotalIntegrantes),2);

			//validar SEGURO SOCIAL

			// pre nivel necesidad
			// if($ingFam > (2*$salario_minimo)){
			// 	$nivel = 'RECHAZADO';
			// }else{
			// 	if($ingFam > $limite_salarial){
			// 		if($infoSeguro==1){
			// 			$nivel = 'RECHAZADO';
			// 		}else{
			// 			$nivel = 'BAJO';
			// 		}
			// 	}else if($ingFam > $salario_minimo && $ingFam <= $limite_salarial){
			// 		if($infoSeguro==1){
			// 			$nivel = 'MEDIO';
			// 		}else{
			// 			$nivel = 'ALTO';
			// 		}
			// 	}else if($ingFam > 0 && $ingFam <= $salario_minimo){
			// 		$nivel = 'ALTO';
			// 	}
			// }

			// nivel de necesidad hasta 22-11-2023
			// if($ingFam > (2*$salario_minimo)){
			// 	$nivel = 'RECHAZADO ';
			// }else{
			// 	if($infoSeguro==1){
			// 		if($ingFam < $limite_salarial){
			// 			$nivel = 'BAJO';
			// 		}else{
			// 			$nivel = 'RECHAZADO';
			// 		}
			// 	}else{
			// 		if($ingFam <= $salario_minimo){
			// 			$nivel = 'ALTO';
			// 		}else if($ingFam > $salario_minimo && $ingFam <= $limite_salarial){
			// 			if($TotalIntegrantes > 4){
			// 				$nivel = 'ALTO';
			// 			}else{
			// 				$nivel = 'RECHAZADO';
			// 			}
			// 		}else{
			// 			if($TotalIntegrantes > 6){
			// 				$nivel = 'ALTO';
			// 			}else{
			// 				$nivel = 'RECHAZADO';
			// 			}
			// 		}
			// 	}
			// }



			// nivel de necesidad 19-12-2023

			$aporte_gasto = $info->aporte_gasto;
			$ingre = floatval($aporte_gasto) / intval($TotalIntegrantes);
			$puntos = 0;

			// aporte gasto
			if($ingre <= 4300){
				// 10 puntos
				$puntos += 10;
			}else if($ingre > 4300 && $ingre <= 6300){
				// 5 puntos
				$puntos += 5;
			}else if($ingre > 6300 && $ingre <= 8300){
				// 3 puntos
				$puntos += 3;
			}else if($ingre > 8300){
				// 1 puntos
				$puntos += 1;
			}

			// alimentos

			if($gastos->response){
				// hasta 7 puntos c/u -- total max 70
				$puntos += $gastos->result->huevo;
				$puntos += $gastos->result->proteina_animal;
				$puntos += $gastos->result->carbohidratos;
				$puntos += $gastos->result->basicos;
				$puntos += $gastos->result->azucares;
				$puntos += $gastos->result->lacteos;
				$puntos += $gastos->result->aceites_grasas;
				$puntos += $gastos->result->fruta_verdura;
				$puntos += $gastos->result->comida_fuera;
				$puntos += $gastos->result->comida_comprada;

				// vulnerabilidades
				if($gastos->result->vulnerabilidad == 1){
					// 10 pts
					$puntos += 10;
				}
				if($gastos->result->estabilidad == 0){
					// 5 pts
					$puntos += 5;
				}

				// hasta 10 pts
				if($gastos->result->egre_medicamento == 1){
					$puntos += 2;
				}else if($gastos->result->egre_medicamento == 2){
					$puntos += 4;
				}else if($gastos->result->egre_medicamento == 3){
					$puntos += 6;
				}else if($gastos->result->egre_medicamento == 4){
					$puntos += 8;
				}else if($gastos->result->egre_medicamento == 5){
					$puntos += 10;
				}

				// hasta 5 pts
				if($gastos->result->egre_educacion == 1){
					$puntos += 1;
				}else if($gastos->result->egre_educacion == 2){
					$puntos += 2;
				}else if($gastos->result->egre_educacion == 3){
					$puntos += 3;
				}else if($gastos->result->egre_educacion == 4){
					$puntos += 4;
				}else if($gastos->result->egre_educacion == 5){
					$puntos += 5;
				}

				// hasta 5 pts
				if($gastos->result->egre_transporte == 1){
					$puntos += 1;
				}else if($gastos->result->egre_transporte == 2){
					$puntos += 2;
				}else if($gastos->result->egre_transporte == 3){
					$puntos += 3;
				}else if($gastos->result->egre_transporte == 4){
					$puntos += 4;
				}else if($gastos->result->egre_transporte == 5){
					$puntos += 5;
				}
			}
				
			if($vivienda->response){
				// casa
				if($vivienda->result->estatus >= 0){
					if($vivienda->result->estatus == 0){
						// Casa Propia 0 pts
						$puntos += 0;
					}else if($vivienda->result->estatus == 2){
						// Rentada 1 pts
						$puntos += 1;
					}else if($vivienda->result->estatus == 3){
						// Pagándose 1 pts
						$puntos += 1;
					}else if($vivienda->result->estatus == 1){
						// Prestada 2 pts
						$puntos += 2;
					}else if($vivienda->result->estatus == 4){
						// Asentamiento regular 3 pts
						$puntos += 3;
					}else if($vivienda->result->estatus == 5){
						// Otro 1 pts
						$puntos += 1;
					}
				}

				// electrodomesticos
				if(strlen($vivienda->result->electrodomesticos) > 0){
					// Hasta 10 puntos de electrodomesticos
					if(($vivienda->result->electrodomesticos) == "0"){
						$puntos += 10;
					}
				}

				// luz, agua, combustible
				if($vivienda->result->agua == 0){
					// 15 punto
					$puntos += 15;
				}

				// internet
				if($vivienda->result->internet == 0){
					// 5 punto
					$puntos += 5;
				}

				// tv
				if($vivienda->result->tv == 0){
					// 5 punto
					$puntos += 5;
				}
			}

			// if($puntos <= 60){
			// 	$nivel = "RECHAZADA $puntos";
			// }else if($puntos > 60 && $puntos <= 96){
			// 	$nivel = "BAJA $puntos";
			// }else if($puntos > 96 && $puntos < 156){
			// 	$nivel = "ALTA $puntos";
			// }else if($puntos >= 156){
			// 	$nivel = "BECA $puntos";
			// }

			if($ingre == 0){
				$nivel = "ALTA $puntos";
			}else{
				if($puntos <= 60){
					$nivel = "RECHAZADA $puntos";
				}else if($puntos > 60 && $puntos <= 110){
					$nivel = "BAJA $puntos";
				}else if($puntos > 110 && $puntos <= 131){
					$nivel = "ALTA $puntos";
				}else if($puntos > 131){
					$nivel = "BECA $puntos";
				}
			}


			$perm = $this->model->usuario->getAcciones($_SESSION['usuario']->id_usuario, 2);
			$arrPerm = getPermisos($perm);
			$params = array('vista' => 'Beneficiario '.$info->credencial, 'permisos' => $arrPerm, 
							'info' => $info, 'municipios' => $municipios, 'gastos' => $gastos->result, 
							'vivienda' => $vivienda->result, 'ficha' => $ficha, 'foto' => $foto, 
							//'ingresos' => $ingFam, 'nivel' => $nivel.' ('.$puntos.')', 'arrFicha' => $arrFicha);
							'ingresos' => $ingFam, 'nivel' => $nivel, 'arrFicha' => $arrFicha);
			// print_r($info);
			// print_r(json_encode( $params)); exit;

			return $this->renderer->render($response, 'titular.phtml', $params);
		});

        // Agregar titular
		$this->post('add/', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$data = [
				'credencial'=>$parsedBody['credencial'], 
				'fecha_solicitud'=>$parsedBody['fecha_solicitud'], 
				'fecha_visita'=>$parsedBody['fecha_visita'], 
				// 'personas_depen'=>$parsedBody['personas_depen'], 
				'personas_depen'=>0, 
				'telefono'=>$parsedBody['telefono'], 
				'domicilio'=>$parsedBody['domicilio'].' '.$parsedBody['numero'], 
				'colonia'=>$parsedBody['colonia'], 
				'fk_municipio'=>$parsedBody['fk_municipio'], 
				'estado_civil'=>$parsedBody['estado_civil'], 
				'discapacidad'=>$parsedBody['discapacidad'], 
				'otro_apoyo'=>$parsedBody['otro_apoyo'], 
				'ingreso_otro_apoyo'=>$parsedBody['ingreso_otro_apoyo'], 
				// 'gasto_baceh'=>$parsedBody['gasto_baceh'], 
				'gasto_baceh'=>'0', 
				'observaciones'=>$parsedBody['observaciones'], 
				'tipo'=>$parsedBody['tipo'], 
				'actualizo'=>$parsedBody['actualizo'], 
				// 'vialidad'=>$parsedBody['vialidad'], 
				// 'asentamiento'=>$parsedBody['asentamiento'], 
				'vialidad'=>0, 
				'asentamiento'=>0,
				'lista'=>$parsedBody['lista'],
				'folio_sigo'=>$parsedBody['folio_sigo'],
				'seguro_social'=>$parsedBody['seguro_social'],
				'fecha_ingreso' => new Literal('NOW()'),
				'fecha_actualizacion' => new Literal('NOW()'),
			];
			$titular = $this->model->titular->add($data);
			if($titular->response){
				$titular_id = $titular->result;
				$seg_log = $this->model->seg_log->add('Registro nuevo titular', $titular_id, 'cat_titular'); 
				if(!$seg_log->response){
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
                        return $response->withJson($seg_log);
				}
			}else{
				$titular->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson("Error transaccion addTitular"); 
			}

            $dataIntegrante = [
                'fk_titular'=> $titular_id,
				'parentesco'=>'TITULAR', 
				'nombre'=>$parsedBody['nombre'], 
				'apaterno'=>$parsedBody['apaterno'], 
				'amaterno'=>$parsedBody['amaterno'], 
				'fecha_nacimiento'=>$parsedBody['fecha_nacimiento'], 
				'sexo'=>$parsedBody['sexo'], 
				'escolaridad'=>$parsedBody['escolaridad'], 
				'ocupacion'=>$parsedBody['ocupacion'], 
				'tipo_ocupacion'=>$parsedBody['tipo_ocupacion'], 
				'ingreso'=>$parsedBody['ingreso'], 
				'aporte_gasto'=>$parsedBody['aporte_gasto'], 
				'padecimiento'=>$parsedBody['padecimiento'], 
				'discapacidad'=>$parsedBody['discapacidad'], 
				'curp'=>$parsedBody['curp'],
				'numero_telefono'=>$parsedBody['numero_telefono'],
				'estado_civil'=>$parsedBody['estado_civil'], 
			];
            $integrante = $this->model->integrante->add($dataIntegrante);
			if($integrante->response){
				$integrante_id = $integrante->result;
				$seg_log = $this->model->seg_log->add('Registro nuevo integrante', $integrante_id, 'cat_integrante'); 
				if(!$seg_log->response){
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
                        return $response->withJson($seg_log);
				}
			}else{
				$integrante->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson("Error transaccion addIntegrante ".$integrante); 
			}

			$enfermedades = 0;
			$pade = strtolower($parsedBody['padecimiento']);
			if(strpos($pade,'cancer') || strpos($pade,'diabet') || strpos($pade,'hipert')) $enfermedades = 3;
			$edad = $this->model->integrante->getEdad($parsedBody['fecha_nacimiento']);
			$mayor = $edad > 59 ? 3 : 0;
            $dataFichaTecnica = [
                'fk_titular'=> $titular_id,
                'enfermedades'=> $enfermedades,
                'adulto_mayor'=> $mayor,
				'sindrome'=>$parsedBody['sindrome'], 
				'discapacidad'=>$parsedBody['discapacidad'], 
				'tipo_empleo'=>$parsedBody['tipo_empleo'], 
				'transporte'=>$parsedBody['transporte'], 
				'tc'=>$parsedBody['tc'], 
				// 'imagen'=>$parsedBody['imagen']
				'ultima_consulta' => new Literal('NOW()'),
			];
            $ficha_tecnica = $this->model->ficha_tecnica->add($dataFichaTecnica);
			if($ficha_tecnica->response){
				$ficha_tecnica_id = $ficha_tecnica->result;
				$seg_log = $this->model->seg_log->add('Registro nuevo ficha', $ficha_tecnica_id, 'ficha_tecnica'); 
				if(!$seg_log->response){
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
                        return $response->withJson($seg_log);
				}
			}else{
				$ficha_tecnica->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($ficha_tecnica); 
			}

			// FOTO

			$titular->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($titular);
		});

        // Obtener todos los titulares
		$this->get('getAll/{pagina}/{limite}[/{usuario_tipo}[/{busqueda}]]', function($request, $response, $arguments) {
			$arguments['usuario_tipo'] = isset($arguments['usuario_tipo'])? $arguments['usuario_tipo'] : 0;
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->titular->getAll($arguments['pagina'], $arguments['limite'], $arguments['usuario_tipo'], $arguments['busqueda']));
		});

		// Obtener todos los titulares con filtros
		$this->get('getAllInte/{pagina}/{limite}[/{busqueda}]', function($request, $response, $arguments) {
			$parsedBody = $request->getParsedBody();

			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			$filtros = $request->getQueryParams();

			$resultado = $this->model->titular->getAllInte($arguments['pagina'], $arguments['limite'], $filtros, $arguments['busqueda']);

			foreach($resultado->result as $item){
				$item->edad = $this->model->integrante->getEdad($item->fecha_nacimiento);
				$item->peso = $this->model->historial->getIMC($item->id_integrante);
			}
			$resultado->filtros = $filtros;
			return $response->withJson($resultado);
		});

		$this->get('getAllInteAjax/{inicial}/{limite}/{busqueda}/[{filtros}]', function($request, $response, $arguments) {
			include_once('../public/core/actions.php');
			$inicial = isset($_GET['start'])? $_GET['start']: $arguments['inicial'];
			$limite = isset($_GET['length'])? $_GET['length']: $arguments['limite'];
			$busqueda = isset($_GET['search']['value'])? (strlen($_GET['search']['value'])>0? $_GET['search']['value']: '_'): $arguments['busqueda'];
			$orden = isset($_GET['order'])
			? $_GET['columns'][$_GET['order'][0]['column']]['data']
			: 'cat_titular.estatus, credencial';
			$orden .= isset($_GET['order'])? " ".$_GET['order'][0]['dir']: " asc";
			$filtros = $request->getQueryParams();

			if(count($_GET['order'])>1){
				for ($i=1; $i < count($_GET['order']); $i++) { 
					$orden .= ', '.$_GET['columns'][$_GET['order'][$i]['column']]['data'].' '.$_GET['order'][$i]['dir'];
				}
			}

			$modulo = 2; $user = $_SESSION['usuario']->id_usuario; $perm = $this->model->usuario->getAcciones($user, $modulo); $permisos = getPermisos($perm);
			$resultado = $this->model->titular->getAllInteAjax($inicial, $limite, $busqueda, $filtros, $orden);
			
			$data = [];
			foreach($resultado->result as $integ) {
				//$estado = '<span class="status label label-'.($integ->estatus==1? 'success': 'warning').'">'.($integ->estatus==1? 'Activo': 'Inactivo').'</span>';

				if($integ->estatus==1){
					if($integ->estatusInt==1){
						$estado = '<span class="status label label-success">Activo</span>';
					}else if($integ->estatusInt==2){
						$estado = '<span class="status label label-warning">Inactivo</span>';
					}else{
						$estado = '<span class="status label label-danger">Baja</span>';
					}
				}else if($integ->estatus==2){
					$estado = '<span class="status label label-warning">Inactivo</span>';
				}else{
					$estado = '<span class="status label label-danger">Baja</span>';
				}
				
				$acciones = '';
				if($integ->parentesco == 'TITULAR'){
					if($integ->estatus == 1){
						$acciones .= (in_array(MOD_BENE_BAJA, $permisos)? '<a href="#" data-popup="tooltip" title="Dar de baja" class="btnStat" data-nuevo="2"><i class="mdi mdi-close-circle fa-lg" style="color:red;"></i></a>': '');
					}else if($integ->estatus == 2){
						$acciones .= (in_array(MOD_BENE_BAJA, $permisos)? '<a href="#" data-popup="tooltip" title="Dar de alta" class="btnStat text-success" data-nuevo="1"><i class="mdi mdi-check-circle fa-lg"></i></a>': '');
					}
					$acciones .= (in_array(MOD_BENE_DEL, $permisos)? '<a href="#" data-popup="tooltip" title="Eliminar" class="btnDel"><i class="mdi mdi-delete fa-lg" style="color:red;"></i></a>': '');
				}
				$data[] = array(
					"credencial" 		=> $_SESSION['usuario']->tipo == 3 ? '<a>'.$integ->credencial.'</a>' : '<a href="'.URL_ROOT.'/titular/'.$integ->fk_titular.'" class="text-info">'.$integ->credencial.'</a>', 
					"nombre_completo" 	=> "<small class='nombre'>$integ->nombre_completo</small>",
					"parentesco" 		=> "<small>$integ->parentesco</small>",
					"fecha_nacimiento" 	=> "<small>$integ->fecha_nacimiento</small>",
					"sexo" 				=> "<small>$integ->sexo</small>",
					"escolaridad" 		=> $integ->escolaridad,
					"colonia" 			=> "<small>$integ->colonia</small>",
					"fk_municipio" 		=> $integ->fk_municipio,
					"fk_titular" 		=> $integ->fk_titular,
					"id_integrante" 	=> $integ->id_integrante,
					"estatus" 			=> $estado,
					"edad" 				=> "<small>$integ->edad</small>",
					"peso"				=> "<small>$integ->peso</small>",
					"numero_telefono" 	=> "<small>$integ->numero_telefono</small>",
					"acciones" 			=> $acciones,
				);
			}

			echo json_encode(array(
				'draw'=>$_GET['draw'],
				'data'=>$data,
				'recordsTotal'=>$resultado->totalInte,
				'recordsFiltered'=>$resultado->total,
			));
			exit(0);
		});

		// Editar titular
		$this->put('edit/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$titular_id = $arguments['id'];
			$dataTitular = [ 
				'fecha_solicitud'=>$parsedBody['fecha_solicitud'], 
				'tipo'=>$parsedBody['tipo'], 
				// 'vialidad'=>$parsedBody['vialidad'], 
				// 'asentamiento'=>$parsedBody['asentamiento'], 
				'vialidad'=>0, 
				'asentamiento'=>0,
				'domicilio'=>$parsedBody['domicilio'], 
				'colonia'=>$parsedBody['colonia'], 
				'fk_municipio'=>$parsedBody['fk_municipio'], 
				// 'gasto_baceh'=>$parsedBody['gasto_baceh'], 
				'gasto_baceh'=>"0", 
				'estado_civil'=>$parsedBody['estado_civil'], 
				'credencial'=>$parsedBody['credencial'], 
				'fecha_visita'=>$parsedBody['fecha_visita'], 
				'discapacidad'=>$parsedBody['discapacidad'], 
				// 'personas_depen'=>$parsedBody['personas_depen'], 
				'personas_depen'=>0, 
				'telefono'=>$parsedBody['telefono'], 
				'otro_apoyo'=>$parsedBody['otro_apoyo'], 
				'ingreso_otro_apoyo'=>$parsedBody['ingreso_otro_apoyo'], 
				'observaciones'=>$parsedBody['observaciones'], 
				'observaciones_asist'=>$parsedBody['observaciones_asist'], 
				'actualizo'=>$parsedBody['actualizo'], 
				'lista'=>$parsedBody['lista'],
				'folio_sigo'=>$parsedBody['folio_sigo'],
				'seguro_social'=>$parsedBody['seguro_social'],
				'fecha_actualizacion' => new Literal('NOW()'),
				'fecha_consulta'=>$parsedBody['fecha_consulta'],
			];
			$titular = $this->model->titular->edit($dataTitular, $titular_id); 
			if($titular->response) {
				$seg_log = $this->model->seg_log->add('Actualización información titular', $titular_id, 'titular', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			}else{
				$titular->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($titular); 
			}
			$dataIntegrante = [ 
				'nombre'=>$parsedBody['nombre'], 
				'apaterno'=>$parsedBody['apaterno'], 
				'amaterno'=>$parsedBody['amaterno'], 
				'fecha_nacimiento'=>$parsedBody['fecha_nacimiento'], 
				'curp'=>$parsedBody['curp'],
				'sexo'=>$parsedBody['sexo'], 
				'escolaridad'=>$parsedBody['escolaridad'], 
				'padecimiento'=>$parsedBody['padecimiento'], 
				'ocupacion'=>$parsedBody['ocupacion'], 
				'tipo_ocupacion'=>$parsedBody['tipo_ocupacion'], 
				'tipo_empleo'=>$parsedBody['tipo_empleo'], 
				'ingreso'=>$parsedBody['ingreso'], 
				'discapacidad'=>$parsedBody['discapacidad'], 
				'aporte_gasto'=>$parsedBody['aporte_gasto'], 
				'numero_telefono'=>$parsedBody['numero_telefono'], 
				'estado_civil'=>$parsedBody['estado_civil'], 
			];
			$integrante = $this->model->integrante->editByFkTitular($dataIntegrante, $titular_id); 
			if(!$integrante->response) {
				$integrante->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($integrante); 
			}

			$enfermedades = 0;
			$pade = strtolower($parsedBody['padecimiento']);
			if(strpos($pade,'cancer') || strpos($pade,'diabet') || strpos($pade,'hipert')) $enfermedades = 3;
			$edad = $this->model->integrante->getEdad($parsedBody['fecha_nacimiento']);
			$mayor = $edad > 59 ? 3 : 0;
            $dataFichaTecnica = [
                'enfermedades'=> $enfermedades,
                'adulto_mayor'=> $mayor,
				'sindrome'=>$parsedBody['sindrome'], 
				'discapacidad'=>$parsedBody['discapacidad'], 
				'tipo_empleo'=>$parsedBody['tipo_empleo'], 
				'transporte'=>$parsedBody['transporte'], 
				'tc'=>$parsedBody['tc'], 
			];
            $ficha_tecnica = $this->model->ficha_tecnica->editByTit($dataFichaTecnica, $titular_id);
			if(!$ficha_tecnica->response){
				$ficha_tecnica->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($ficha_tecnica); 
			}

			$titular->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($titular);
		});

		// Eliminar titular
		$this->put('del/{id}', function ($req, $res, $args) {
			$this->response = new Response();
			$this->model->transaction->iniciaTransaccion();
			$info = $this->model->titular->get($args['id'])->result;
			$resultado = $this->model->titular->del($args['id']);
			if($resultado->response){
				$dataInte = array('estatus' => 0);
				$delInte = $this->model->integrante->editByFkTitular($dataInte, $args['id'], false);
				if(!$delInte->response){						
					$delInte->state = $this->model->transaction->regresaTransaccion();
					return $this->response->withJson($delInte); 
				}

				$foto = 'data/fotos/'.$info->credencial.'.jpg';
				if(file_exists($foto)){
					unlink($foto);
				}
				$foto2 = 'data/fotos/'.$info->credencial.'.JPG';
				if(file_exists($foto2)){
					unlink($foto2);
				}
				$foto3 = 'data/fotos/'.$info->credencial.'.png';
				if(file_exists($foto3)){
					unlink($foto3);
				}

				$add = $this->model->seg_log->add('Elimina Titular', $args['id'], 'titular');
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

		// Modificar estatus de titular
		$this->put('changeStatus/{id}', function ($req, $res, $args) {
			$this->response = new Response();
			$parsedBody = $req->getParsedBody();
			$parsedBody['faltas'] = 0;
			$parsedBody['fecha_actualizacion'] = new Literal('NOW()');
			$parsedBody['fecha_baja'] = new Literal('NOW()');
			if($parsedBody['estatus'] == 2) $parsedBody['bajas'] = new Literal('bajas + 1');
			$this->model->transaction->iniciaTransaccion();
			$resultado = $this->model->titular->changeStatus($parsedBody, $args['id']);
			if($resultado->response){
				$add = $this->model->seg_log->add('Modificar Titular', $args['id'], 'titular');
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

		// Obtener titular por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			$datos = $this->model->titular->get($arguments['id']);
			if($datos!=null){
				$datos->result->edad = $this->model->integrante->getEdad($datos->result->fecha_nacimiento);
			}
			return $response->withJson($datos);
		});

		// Obtener titular por credencial
		$this->get('getByCredComplete/{credencial}', function($request, $response, $arguments) {
			$datos = $this->model->titular->getByCredComplete($arguments['credencial']);
			return $response->withJson($datos);
		});

		//Cargar imagen de titular
		$this->post('uploadFoto/{cred}', function($request, $response, $arguments) {
			$this->response = new Response();

			$directory = 'data/fotos/';
			$uploadedFiles = $request->getUploadedFiles();
			$uploadedFile = $uploadedFiles['imagen'];
			$filename = '0';
			if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
				$foto = 'data/fotos/'.$arguments['cred'].'.jpg';
				if(file_exists($foto)){
					unlink($foto);
				}
				$foto2 = 'data/fotos/'.$arguments['cred'].'.JPG';
				if(file_exists($foto2)){
					unlink($foto2);
				}
				$foto3 = 'data/fotos/'.$arguments['cred'].'.png';
				if(file_exists($foto3)){
					unlink($foto3);
				}
				//session_start();
				$filename = $arguments['cred'].'.jpg';
				//$filename = $this->model->usuario->moveUploadedFile($directory, $uploadedFile, $fileName);

				$this->model->usuario->resize($uploadedFile->file, 720, $directory.$filename);
				unlink($uploadedFile->file);

				if($filename == '0') {
					$this->response->result = 0;
					return $this->response->SetResponse(false, 'Extensión de archivo invalido, solo se aceptan imagenes en formato jpg');
				} else {
					$this->model->seg_log->add('Cambia Foto Titular', $arguments['cred'], 'titular');
					//if(!isset($arguments['cred'])) { $_SESSION['usuario']->foto = true; }
					$this->response->result = 1;
					$this->response->filename = $filename.'?'.rand();
					$this->response->SetResponse(true,'Archivo cargado con exito: ' . $filename);
					return $response->withjson($this->response);
				}
			}

			$this->response->result = 1;
			return $this->response->SetResponse(true,'Archivo cargado con exito: ' . $filename);
		});

		// Obtener excel (xlsx)
		$this->get('getExcel/{campos}', function($request, $response, $arguments){
			ini_set('memory_limit','1024M');
			ini_set('max_execution_time', 300);
			$spreadsheet = new Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();

			$arrContent = array();

			$campos = explode(',', $arguments['campos']);

			$arrFields = array(
				'CREDENCIAL' => "cat_titular.credencial",
				'NOMBRE' => "CONCAT_WS(' ',cat_integrante.nombre, cat_integrante.apaterno, cat_integrante.amaterno) as nombre",
				'PARENTESCO' => "cat_integrante.parentesco",
				'DOMICILIO' => "cat_titular.domicilio",
				// 'VIALIDAD' => "cat_titular.vialidad",
				// 'ASENTAMIENTO' => "cat_titular.asentamiento",
				'COLONIA' => "cat_titular.colonia",
				'FECHA_NAC' => "cat_integrante.fecha_nacimiento as fecha_nac",
				'EDAD' => "cat_integrante.fecha_nacimiento as edad",
				//'ESTADO_CIVIL' => "cat_titular.estado_civil",
				'ESTADO_CIVIL' => "cat_integrante.estado_civil",
				'PADECIMIENTO' => "cat_integrante.padecimiento",
				'ESCOLARIDAD' => "cat_integrante.escolaridad",
				'OCUPACION' => "cat_integrante.ocupacion",
				'TIPO_OCUPACION' => "cat_integrante.tipo_ocupacion",
				'SEXO' => "cat_integrante.sexo",
				'PESO' => "ficha_nutricion.peso",
				'TALLA' => "ficha_nutricion.talla",
				'CINTURA' => "ficha_nutricion.cintura",
				'IMC' => "cat_integrante.id_integrante as imc",
				'EMPLEO' => "ficha_tecnica.tipo_empleo as empleo",
				'DIA_VISITA' => "cat_titular.fecha_visita as dia_visita",
				'VIVIENDA_ZONA' => "vivienda_titular.zona as vivienda_zona",
				'CASA' => "vivienda_titular.estatus as casa",
				'SERVICIOS' => "vivienda_titular.agua as servicios",
				'CURP' => "cat_integrante.curp",
				'FECHA_INGRESO' => "cat_titular.fecha_ingreso",
				'ESTATUS' => "cat_titular.estatus",
				'TIPO_REGISTRO' => "cat_titular.tipo as tipo_registro",
				'NUMERO_TELEFONO' => "cat_integrante.numero_telefono",
				'MUNICIPIO' => "cat_titular.fk_municipio as municipio",
				'OBSERVACIONES' => "cat_titular.observaciones as observaciones",
				'BAJAS' => "cat_titular.bajas as bajas",
				'NIVEL_NECESIDAD'=>"cat_integrante.fk_titular as nivel_necesidad",
				'INGRESOS'=>"CAST(cat_integrante.ingreso AS UNSIGNED) as ingresos",
				// 'EGRESOS'=>"cat_integrante.fk_titular as egresos",
				'TICKET_1'=>"cat_integrante.fk_titular as ticket_1",
				'TICKET_2'=>"cat_integrante.fk_titular as ticket_2",
				'TICKET_3'=>"cat_integrante.fk_titular as ticket_3",
				'TICKET_4'=>"cat_integrante.fk_titular as ticket_4",
				'HUEVO'=>"cat_integrante.fk_titular as huevo",
				'PROTEINA_ANIMAL'=>"cat_integrante.fk_titular as proteina_animal",
				'CARBOHIDRATOS'=>"cat_integrante.fk_titular as carbohidratos",
				'BASICOS'=>"cat_integrante.fk_titular as basicos",
				'AZUCARES'=>"cat_integrante.fk_titular as azucares",
				'LACTEOS'=>"cat_integrante.fk_titular as lacteos",
				'ACEITES_GRASAS'=>"cat_integrante.fk_titular as aceites_grasas",
				'FRUTA_VERDURA'=>"cat_integrante.fk_titular as fruta_verdura",
				'COMIDA_FUERA'=>"cat_integrante.fk_titular as comida_fuera",
				'COMIDA_COMPRADA'=>"cat_integrante.fk_titular as comida_comprada",
				'FOLIO_SIGO' => "cat_titular.folio_sigo",
			);

			$SQL = "cat_integrante.parentesco, cat_integrante.fk_titular as nivel_necesidad, ";

			foreach ($campos as $campo) {
				$SQL .= $arrFields[$campo].', ';
			}

			$SQL = substr($SQL, 0, strlen($SQL) - 2);

			$page = 0;
			$limit = 5000;
			
			// Strings completas
				$arrEdoc = array('','1 - Soltero(a)','2 - Casado(a)','3 - Divorciado(a)','4 - Viudo','5 - Unión Libre','','7 - Madre Soltera');
				$arrEsco = array('0 - No Aplica','1 - Analfabeto','2 - Alfabeto','3 - Preescolar','4 - Primaria','5 - Secundaria','6 - Preparatoria',
                    '7 - Carrera técnica con primaria completa','8 - Carrera técnica con secundaria completa',
                    '9 - Carrera técnica con preparatoria completa','10 - Licenciatura');
				$arrEmp = array('Permanente','Propio Legal','No tiene','Temporal','Medio Tiempo','Informal');
				$arrVisita = array('','Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','');
				$arrZona = array('','Rural','Semi-urbana','Urbana');
				$arrCasa = array('Propia','Prestada','Rentada','Pagandose','Asentamiento irregular','Otro');
				$arrStatus = array('','Activo','Baja');
				$arrTipoReg = array('','Beneficiario','Voluntario','Comunidad','COVID-19');
				$arrMuni = array('','Acatlan','Acaxochitlan','Actopan','Agua Blanca de Iturbide','Ajacuba','Alfajayucan','Almoloya','Apan','Arenal','Atitalaquia','Atlapexco','Atotonilco el Grande','Atotonilco de Tula','Calnali','Cardonal','Cuautepec de Hinojosa','Chapantongo','Chapulhuacan','Chilcuautla','Eloxochitlan','Emiliano Zapata','Epazoyucan','Francisco I. Madero','Huasca de Ocampo','Huautla','Huazalingo','Huehuetla','Huejutla de Reyes','Huichapan','Ixmiquilpan','Jacala de Ledezma','Jaltocan','Juarez Hidalgo','Lolotla','Metepec','San Agustin Metzquititlan','Metztitlan','Mineral del Chico','Mineral del Monte','La Mision','Mixquiahuala de Juarez','Molango de Escamilla','Nicolas Flores','Nopala de Villagran','Omitlan de Juarez','San Felipe Orizatlan','Pacula','Pachuca de Soto','Pisaflores','Progreso de Obregon','Mineral de la Reforma','San Agustin Tlaxiaca','San Bartolo Tutotepec','San Salvador','Santiago de Anaya','Santiago Tulantepec de Lugo Guerrero','Singuilucan','Tasquillo','Tecozautla','Tenango de Doria','Tepeapulco','Tepehuacan de Guerrero','Tepeji del Rio de Ocampo','Tepetitlan','Tetepango','Villa de Tezontepec','Tezontepec de Aldama','Tianguistengo','Tizayuca','Tlahuelilpan','Tlahuiltepa','Tlanalapa','Tlanchinol','Tlaxcoapan','Tolcayuca','Tula de Allende','Tulancingo de Bravo','Xochiatipan','Xochicoatlan','Yahualica','Zacualtipan de Angeles','Zapotlan de Juarez','Zempoala','Zimapan','OTRO','ESTADO DE MEXICO');
				$arrOcup = array('Estudiante','Sin actividad por enfermedad','Sin actividad por edad','Jubilado','Empleado','Oficio','Profesionista','Obrero','Ama de casa','Servidor público','Infantil','Desempleado','Ninguno');
				$arrAlim = array('7 días', '6 días', '5 días', '4 días', '3 días', '2 días', '1 día', 'Ninguno');

				$arrPar = array(
					'TITULAR' 		=> '0 - TITULAR',
					'CÓNYUGE' 		=> '1 - CÓNYUGE',
					'HIJO (A)' 		=> '2 - HIJO (A)',
					'NIETO (A)' 	=> '3 - NIETO (A)',
					'BISNIETO (A)' 	=> '4 - BISNIETO (A)',
					'PADRE' 		=> '5 - PADRE',
					'MADRE' 		=> '6 - MADRE',
					'SUEGRO (A)' 	=> '7 - SUEGRO (A)',
					'HERMANO (A)' 	=> '8 - HERMANO (A)',
					'CUÑADO (A)' 	=> '9 - CUÑADO (A)',
					'YERNO' 		=> '10 - YERNO',
					'NUERA' 		=> '11 - NUERA',
					'TIO (A)' 		=> '12 - TIO (A)',
					'PRIMO (A)' 	=> '13 - PRIMO (A)',
					'OTRO' 			=> '14 - OTRO');
				// $arrDrenaje = array('Red Pública','Tubería que va a dar a  una grieta o barranca','Tubería que da a un río, lago o mar','No tiene drenaje','Fosa sética');
				// $arrBanio = array('Descarga directa','Agua con cubeta','Letrina seca','Pozo u hoyo','No tiene');
				// $arrVia = array('','Ampliación','Andador','Avenida','Boulevard','Calle','Callejón','Calzada','Cerrada','Circuito','Circunvalacion','Continuacion','Corredor',
				// 		'Diagonal','Eje Vial','Pasaje','Peatonal','Periferico','Privada','Prolongación','Retorno','Viaducto','Ninguno');
				// $arrAsen = array('','Aeropuerto','Ampliación','Barrio','Cantón','Ciudad','Industrial','Colonia','Condominio','Conjunto Hab','Corredor Industrial','Coto','Cuartes',
				// 'Ejido','Ex Hacienda','Fracción','Fraccionamiento','Granja','Hacienda','Ingenio','Manzana','Paraje','Parque Industrial','Privada','Prolongación',
				// 'Pueblo','Puerto','Ranchería','Rancho','Región','Residencial','Rinconada','Sección','Súpermanzana','Unidad','Unidad Hab.','Villa','Zona Federal',
				// 'Zona Industrial','Zona Militar','Zona Naval','Ninguno');

			$resultado = $this->model->titular->getDatos($SQL, $page, $limit);
			$total = $resultado['total'];

			$pagTotal = intval($total / $limit)+1;

			foreach($resultado['datos'] as $reg){
				//$reg->parentesco = $arrPar[$reg->parentesco];
				// if($reg->parentesco == "TITULAR") $reg->estado_civil = $arrEdoc[$reg->estado_civil]; else '';
				// if($reg->parentesco != "TITULAR") $reg->estado_civil_integrante = $arrEdoc[$reg->estado_civil_integrante]; else '';
				// if($reg->vialidad >= 0) $reg->vialidad = $arrVia[$reg->vialidad]; else '';
				// if($reg->asentamiento >= 0) $reg->asentamiento = $arrAsen[$reg->asentamiento]; else '';
				if(substr_count($arguments['campos'], "EDAD")>0) $reg->edad = $this->model->integrante->getEdad($reg->edad);
				if(substr_count($arguments['campos'], "ESTADO_CIVIL")>0) $reg->estado_civil = $arrEdoc[intval($reg->estado_civil)];
				if(substr_count($arguments['campos'], "ESCOLARIDAD")>0) if($reg->escolaridad >= 0) $reg->escolaridad = $arrEsco[intval($reg->escolaridad)]; else $reg->escolaridad = '';
				if(substr_count($arguments['campos'], "IMC")>0) $reg->imc = $this->model->historial->getIMC($reg->imc);
				if(substr_count($arguments['campos'], "EMPLEO")>0) if($reg->empleo >= 0) $reg->empleo = $arrEmp[intval($reg->empleo)]; else $reg->empleo = '';
				if(substr_count($arguments['campos'], "DIA_VISITA")>0) if($reg->dia_visita >= 0) $reg->dia_visita = $arrVisita[intval($reg->dia_visita)]; else $reg->dia_visita = '';
				if(substr_count($arguments['campos'], "VIVIENDA_ZONA")>0) if($reg->vivienda_zona >= 0) $reg->vivienda_zona = $arrZona[intval($reg->vivienda_zona)]; else $reg->vivienda_zona = '';
				if(substr_count($arguments['campos'], "CASA")>0) if($reg->casa >= 0) $reg->casa = $arrCasa[intval($reg->casa)]; else $reg->casa = '';
				if(substr_count($arguments['campos'], "SERVICIOS")>0) if($reg->servicios > 0) $reg->servicios = 'Si'; else $reg->servicios = 'No';
				if(substr_count($arguments['campos'], "ESTATUS")>0) if($reg->estatus >= 0) $reg->estatus = $arrStatus[intval($reg->estatus)]; else $reg->estatus = '';
				if(substr_count($arguments['campos'], "TIPO_REGISTRO")>0) if($reg->tipo_registro >= 0) $reg->tipo_registro = $arrTipoReg[intval($reg->tipo_registro)]; else $reg->tipo_registro = '';
				if(substr_count($arguments['campos'], "MUNICIPIO")>0) if($reg->municipio >= 0) $reg->municipio = $arrMuni[intval($reg->municipio)]; else $reg->municipio = '';
				if(substr_count($arguments['campos'], "TIPO_OCUPACION")>0) if($reg->tipo_ocupacion >= 0) $reg->tipo_ocupacion = $arrOcup[intval($reg->tipo_ocupacion)]; else $reg->tipo_ocupacion = '';
				if(substr_count($arguments['campos'], "BAJAS")>0) if($reg->parentesco != "TITULAR") $reg->bajas = "";
				if(substr_count($arguments['campos'], "HUEVO")>0) if($reg->parentesco == "TITULAR") $reg->huevo = $arrAlim[intval($this->model->egresos->getByTit($reg->huevo)->result->huevo)]; else $reg->huevo = '';
				if(substr_count($arguments['campos'], "PROTEINA_ANIMAL")>0) if($reg->parentesco == "TITULAR") $reg->proteina_animal = $arrAlim[intval($this->model->egresos->getByTit($reg->proteina_animal)->result->proteina_animal)]; else $reg->proteina_animal = '';
				if(substr_count($arguments['campos'], "CARBOHIDRATOS")>0) if($reg->parentesco == "TITULAR") $reg->carbohidratos = $arrAlim[intval($this->model->egresos->getByTit($reg->carbohidratos)->result->carbohidratos)]; else $reg->carbohidratos = '';
				if(substr_count($arguments['campos'], "BASICOS")>0) if($reg->parentesco == "TITULAR") $reg->basicos = $arrAlim[intval($this->model->egresos->getByTit($reg->basicos)->result->basicos)]; else $reg->basicos = '';
				if(substr_count($arguments['campos'], "AZUCARES")>0) if($reg->parentesco == "TITULAR") $reg->azucares = $arrAlim[intval($this->model->egresos->getByTit($reg->azucares)->result->azucares)]; else $reg->azucares = '';
				if(substr_count($arguments['campos'], "LACTEOS")>0) if($reg->parentesco == "TITULAR") $reg->lacteos = $arrAlim[intval($this->model->egresos->getByTit($reg->lacteos)->result->lacteos)]; else $reg->lacteos = '';
				if(substr_count($arguments['campos'], "ACEITES_GRASAS")>0) if($reg->parentesco == "TITULAR") $reg->aceites_grasas = $arrAlim[intval($this->model->egresos->getByTit($reg->aceites_grasas)->result->aceites_grasas)]; else $reg->aceites_grasas = '';
				if(substr_count($arguments['campos'], "FRUTA_VERDURA")>0) if($reg->parentesco == "TITULAR") $reg->fruta_verdura = $arrAlim[intval($this->model->egresos->getByTit($reg->fruta_verdura)->result->fruta_verdura)]; else $reg->fruta_verdura = '';
				if(substr_count($arguments['campos'], "COMIDA_FUERA")>0) if($reg->parentesco == "TITULAR") $reg->comida_fuera = $arrAlim[intval($this->model->egresos->getByTit($reg->comida_fuera)->result->comida_fuera)]; else $reg->comida_fuera = '';
				if(substr_count($arguments['campos'], "COMIDA_COMPRADA")>0) if($reg->parentesco == "TITULAR") $reg->comida_comprada = $arrAlim[intval($this->model->egresos->getByTit($reg->comida_comprada)->result->comida_comprada)]; else $reg->comida_comprada = '';
				// $gastos = $this->model->egresos->getByTit($reg->egresos)->result;
				// // $reg->egresos = floatval($gastos->alimentacion)+floatval($gastos->gas)+floatval($gastos->renta)+floatval($gastos->agua)+floatval($gastos->luz)+floatval($gastos->abonos)+floatval($gastos->ropa_calzado)+floatval($gastos->fondo_ahorro)+floatval($gastos->credito_vivienda)+floatval($gastos->transporte)+floatval($gastos->medicamento);
				// $reg->egresos = floatval(0);
				// if($reg->parentesco != "TITULAR") $reg->egresos = "";
				if(substr_count($arguments['campos'], "TICKET_1")>0) if($reg->parentesco == "TITULAR") $reg->ticket_1 = ($this->model->egresos->getByTit(intval($reg->ticket_1))->result->ticket_uno); else $reg->ticket_1 = '';
				if(substr_count($arguments['campos'], "TICKET_2")>0) if($reg->parentesco == "TITULAR") $reg->ticket_2 = ($this->model->egresos->getByTit(intval($reg->ticket_2))->result->ticket_dos); else $reg->ticket_2 = '';
				if(substr_count($arguments['campos'], "TICKET_3")>0) if($reg->parentesco == "TITULAR") $reg->ticket_3 = ($this->model->egresos->getByTit(intval($reg->ticket_3))->result->ticket_tres); else $reg->ticket_3 = '';
				if(substr_count($arguments['campos'], "TICKET_4")>0) if($reg->parentesco == "TITULAR") $reg->ticket_4 = ($this->model->egresos->getByTit(intval($reg->ticket_4))->result->ticket_cuatro); else $reg->ticket_4 = '';

				if(substr_count($arguments['campos'], "INGRESOS")>0){
					if($reg->parentesco == "TITULAR"){
						$info = $this->model->titular->get($reg->nivel_necesidad)->result;
						$reg->ingresos = floatval($this->model->integrante->getIngresosTit($reg->nivel_necesidad)) + floatval($info->ingreso_otro_apoyo);
					}else{
						$reg->ingresos = '';
					}
				}

				// nivel de necesidad

				if(substr_count($arguments['campos'], "NIVEL_NECESIDAD")>0){
					if($reg->parentesco == "TITULAR"){
						$info = $this->model->titular->get($reg->nivel_necesidad);
						if($info->response){
							$aporte_gasto = $info->result->aporte_gasto;
							$TotalIntegrantes = $this->model->integrante->getTotalIntegrantes($reg->nivel_necesidad)->result->Total;
							$ingre = floatval($aporte_gasto) / intval($TotalIntegrantes);
							$puntos = 0;

							// aporte gasto
							if($ingre <= 4300){
								// 10 puntos
								$puntos += 10;
							}else if($ingre > 4300 && $ingre <= 6300){
								// 5 puntos
								$puntos += 5;
							}else if($ingre > 6300 && $ingre <= 8300){
								// 3 puntos
								$puntos += 3;
							}else if($ingre > 8300){
								// 1 puntos
								$puntos += 1;
							}

							$gastos = $this->model->egresos->getByTit($reg->nivel_necesidad);
							if($gastos->response){
								// alimentos

								// hasta 7 puntos c/u -- total max 70
								$puntos += $gastos->result->huevo;
								$puntos += $gastos->result->proteina_animal;
								$puntos += $gastos->result->carbohidratos;
								$puntos += $gastos->result->basicos;
								$puntos += $gastos->result->azucares;
								$puntos += $gastos->result->lacteos;
								$puntos += $gastos->result->aceites_grasas;
								$puntos += $gastos->result->fruta_verdura;
								$puntos += $gastos->result->comida_fuera;
								$puntos += $gastos->result->comida_comprada;

								// vulnerabilidades
								if($gastos->result->vulnerabilidad == 1){
									// 10 pts
									$puntos += 10;
								}
								if($gastos->result->estabilidad == 0){
									// 5 pts
									$puntos += 5;
								}

								// hasta 10 pts
								if($gastos->result->egre_medicamento == 1){
									$puntos += 2;
								}else if($gastos->result->egre_medicamento == 2){
									$puntos += 4;
								}else if($gastos->result->egre_medicamento == 3){
									$puntos += 6;
								}else if($gastos->result->egre_medicamento == 4){
									$puntos += 8;
								}else if($gastos->result->egre_medicamento == 5){
									$puntos += 10;
								}

								// hasta 10 pts
								if($gastos->result->egre_educacion == 1){
									$puntos += 1;
								}else if($gastos->result->egre_educacion == 2){
									$puntos += 2;
								}else if($gastos->result->egre_educacion == 3){
									$puntos += 3;
								}else if($gastos->result->egre_educacion == 4){
									$puntos += 4;
								}else if($gastos->result->egre_educacion == 5){
									$puntos += 5;
								}

								// hasta 10 pts
								if($gastos->result->egre_transporte == 1){
									$puntos += 1;
								}else if($gastos->result->egre_transporte == 2){
									$puntos += 2;
								}else if($gastos->result->egre_transporte == 3){
									$puntos += 3;
								}else if($gastos->result->egre_transporte == 4){
									$puntos += 4;
								}else if($gastos->result->egre_transporte == 5){
									$puntos += 5;
								}
							}

							$vivienda = $this->model->vivienda->getByTit($reg->nivel_necesidad);
							if($vivienda->response){
								
								// casa
								if($vivienda->result->estatus >= 0){
									if($vivienda->result->estatus == 0){
										// Casa Propia 0 pts
										$puntos += 0;
									}else if($vivienda->result->estatus == 2){
										// Rentada 1 pts
										$puntos += 1;
									}else if($vivienda->result->estatus == 3){
										// Pagándose 1 pts
										$puntos += 1;
									}else if($vivienda->result->estatus == 1){
										// Prestada 2 pts
										$puntos += 2;
									}else if($vivienda->result->estatus == 4){
										// Asentamiento regular 3 pts
										$puntos += 3;
									}else if($vivienda->result->estatus == 5){
										// Otro 1 pts
										$puntos += 1;
									}
								}

								// electrodomesticos
								if(strlen($vivienda->result->electrodomesticos) > 0){
									// Hasta 10 puntos de electrodomesticos
									if(($vivienda->result->electrodomesticos) == "0"){
										$puntos += 10;
									}
								}

								// luz, agua, combustible
								if($vivienda->result->agua == 0){
									// 15 punto
									$puntos += 15;
								}

								// internet
								if($vivienda->result->internet == 0){
									// 5 punto
									$puntos += 5;
								}

								// tv
								if($vivienda->result->tv == 0){
									// 5 punto
									$puntos += 5;
								}
							}

							// if($puntos <= 60){
							// 	$reg->nivel_necesidad = "RECHAZADA";
							// }else if($puntos > 60 && $puntos <= 96){
							// 	$reg->nivel_necesidad = "BAJA";
							// }else if($puntos > 96 && $puntos < 156){
							// 	$reg->nivel_necesidad = "ALTA";
							// }else if($puntos >= 156){
							// 	$reg->nivel_necesidad = "BECA";
							// }

							if($puntos <= 60){
								$reg->nivel_necesidad = "RECHAZADA";
							}else if($puntos > 60 && $puntos <= 110){
								$reg->nivel_necesidad = "BAJA";
							}else if($puntos > 110 && $puntos <= 131){
								$reg->nivel_necesidad = "ALTA";
							}else if($puntos > 131){
								$reg->nivel_necesidad = "BECA";
							}
						}else{
							$reg->nivel_necesidad = '';	
						}
					}else{
						$reg->nivel_necesidad = '';
					}
				}

				// //nivel de necesidad e ingresos familiares
				// $info = $this->model->titular->get($reg->nivel_necesidad)->result;
				// $ingFam = floatval($this->model->integrante->getIngresosTit($reg->nivel_necesidad)) + floatval($info->ingreso_otro_apoyo);
				// $getSalario = $this->model->titular->getSalario();
				// $salario_minimo = floatval($getSalario->result->salario_minimo);
				// $limite_salarial = floatval($getSalario->result->limite_salarial);
				// $infoSeguro = $this->model->titular->getByID($reg->nivel_necesidad)->result->seguro_social;
				// $TotalIntegrantes = $this->model->integrante->getTotalIntegrantes($reg->nivel_necesidad)->result->Total;
				// if($ingFam > (2*$salario_minimo)){
				// 	$nivel = 'RECHAZADO ';
				// }else{
				// 	if($infoSeguro==1){
				// 		if($ingFam < $limite_salarial){
				// 			$nivel = 'BAJO';
				// 		}else{
				// 			$nivel = 'RECHAZADO';
				// 		}
				// 	}else{
				// 		if($ingFam <= $salario_minimo){
				// 			$nivel = 'ALTO';
				// 		}else if($ingFam > $salario_minimo && $ingFam <= $limite_salarial){
				// 			if($TotalIntegrantes > 4){
				// 				$nivel = 'ALTO';
				// 			}else{
				// 				$nivel = 'RECHAZADO';
				// 			}
				// 		}else{
				// 			if($TotalIntegrantes > 6){
				// 				$nivel = 'ALTO';
				// 			}else{
				// 				$nivel = 'RECHAZADO';
				// 			}
				// 		}
				// 	}
				// }
				// $reg->nivel_necesidad = $nivel;
				// $reg->ingresos = $ingFam;
				// if($reg->parentesco != "TITULAR") $reg->nivel_necesidad = "";
				// if($reg->parentesco != "TITULAR") $reg->ingresos = "";
				// //nivel de necesidad e ingresos familiares

				$arrContent[] = $reg;
			}

			for($page=1; $page<=$pagTotal;$page++){
				$resultado = $this->model->titular->getDatos($SQL, $page, $limit);
				foreach($resultado['datos'] as $reg){
					// if($reg->parentesco == "TITULAR") $reg->estado_civil = $arrEdoc[$reg->estado_civil]; else '';
					// if($reg->parentesco != "TITULAR") $reg->estado_civil_integrante = $arrEdoc[$reg->estado_civil_integrante]; else '';

					// if($reg->vialidad >= 0) $reg->vialidad = $arrVia[$reg->vialidad]; else '';
					// if($reg->asentamiento >= 0) $reg->asentamiento = $arrAsen[$reg->asentamiento]; else '';
					if(substr_count($arguments['campos'], "EDAD")>0) $reg->edad = $this->model->integrante->getEdad($reg->edad);
					if(substr_count($arguments['campos'], "ESTADO_CIVIL")>0) $reg->estado_civil = $arrEdoc[intval($reg->estado_civil)];
					if(substr_count($arguments['campos'], "ESCOLARIDAD")>0) if($reg->escolaridad >= 0) $reg->escolaridad = $arrEsco[intval($reg->escolaridad)]; else $reg->escolaridad = '';
					if(substr_count($arguments['campos'], "IMC")>0) $reg->imc = $this->model->historial->getIMC($reg->imc);
					if(substr_count($arguments['campos'], "EMPLEO")>0) if($reg->empleo >= 0) $reg->empleo = $arrEmp[intval($reg->empleo)]; else $reg->empleo = '';
					if(substr_count($arguments['campos'], "DIA_VISITA")>0) if($reg->dia_visita >= 0) $reg->dia_visita = $arrVisita[intval($reg->dia_visita)]; else $reg->dia_visita = '';
					if(substr_count($arguments['campos'], "VIVIENDA_ZONA")>0) if($reg->vivienda_zona >= 0) $reg->vivienda_zona = $arrZona[intval($reg->vivienda_zona)]; else $reg->vivienda_zona = '';
					if(substr_count($arguments['campos'], "CASA")>0) if($reg->casa >= 0) $reg->casa = $arrCasa[intval($reg->casa)]; else $reg->casa = '';
					if(substr_count($arguments['campos'], "SERVICIOS")>0) if($reg->servicios > 0) $reg->servicios = 'Si'; else $reg->servicios = 'No';
					if(substr_count($arguments['campos'], "ESTATUS")>0) if($reg->estatus >= 0) $reg->estatus = $arrStatus[intval($reg->estatus)]; else $reg->estatus = '';
					if(substr_count($arguments['campos'], "TIPO_REGISTRO")>0) if($reg->tipo_registro >= 0) $reg->tipo_registro = $arrTipoReg[intval($reg->tipo_registro)]; else $reg->tipo_registro = '';
					if(substr_count($arguments['campos'], "MUNICIPIO")>0) if($reg->municipio >= 0) $reg->municipio = $arrMuni[intval($reg->municipio)]; else $reg->municipio = '';
					if(substr_count($arguments['campos'], "TIPO_OCUPACION")>0) if($reg->tipo_ocupacion >= 0) $reg->tipo_ocupacion = $arrOcup[intval($reg->tipo_ocupacion)]; else $reg->tipo_ocupacion = '';
					if(substr_count($arguments['campos'], "BAJAS")>0) if($reg->parentesco != "TITULAR") $reg->bajas = "";
					if(substr_count($arguments['campos'], "HUEVO")>0) if($reg->parentesco == "TITULAR") $reg->huevo = $arrAlim[intval($this->model->egresos->getByTit($reg->huevo)->result->huevo)]; else $reg->huevo = '';
					if(substr_count($arguments['campos'], "PROTEINA_ANIMAL")>0) if($reg->parentesco == "TITULAR") $reg->proteina_animal = $arrAlim[intval($this->model->egresos->getByTit($reg->proteina_animal)->result->proteina_animal)]; else $reg->proteina_animal = '';
					if(substr_count($arguments['campos'], "CARBOHIDRATOS")>0) if($reg->parentesco == "TITULAR") $reg->carbohidratos = $arrAlim[intval($this->model->egresos->getByTit($reg->carbohidratos)->result->carbohidratos)]; else $reg->carbohidratos = '';
					if(substr_count($arguments['campos'], "BASICOS")>0) if($reg->parentesco == "TITULAR") $reg->basicos = $arrAlim[intval($this->model->egresos->getByTit($reg->basicos)->result->basicos)]; else $reg->basicos = '';
					if(substr_count($arguments['campos'], "AZUCARES")>0) if($reg->parentesco == "TITULAR") $reg->azucares = $arrAlim[intval($this->model->egresos->getByTit($reg->azucares)->result->azucares)]; else $reg->azucares = '';
					if(substr_count($arguments['campos'], "LACTEOS")>0) if($reg->parentesco == "TITULAR") $reg->lacteos = $arrAlim[intval($this->model->egresos->getByTit($reg->lacteos)->result->lacteos)]; else $reg->lacteos = '';
					if(substr_count($arguments['campos'], "ACEITES_GRASAS")>0) if($reg->parentesco == "TITULAR") $reg->aceites_grasas = $arrAlim[intval($this->model->egresos->getByTit($reg->aceites_grasas)->result->aceites_grasas)]; else $reg->aceites_grasas = '';
					if(substr_count($arguments['campos'], "FRUTA_VERDURA")>0) if($reg->parentesco == "TITULAR") $reg->fruta_verdura = $arrAlim[intval($this->model->egresos->getByTit($reg->fruta_verdura)->result->fruta_verdura)]; else $reg->fruta_verdura = '';
					if(substr_count($arguments['campos'], "COMIDA_FUERA")>0) if($reg->parentesco == "TITULAR") $reg->comida_fuera = $arrAlim[intval($this->model->egresos->getByTit($reg->comida_fuera)->result->comida_fuera)]; else $reg->comida_fuera = '';
					if(substr_count($arguments['campos'], "COMIDA_COMPRADA")>0) if($reg->parentesco == "TITULAR") $reg->comida_comprada = $arrAlim[intval($this->model->egresos->getByTit($reg->comida_comprada)->result->comida_comprada)]; else $reg->comida_comprada = '';
					// $gastos = $this->model->egresos->getByTit($reg->egresos)->result;
					// // $reg->egresos = floatval($gastos->alimentacion)+floatval($gastos->gas)+floatval($gastos->renta)+floatval($gastos->agua)+floatval($gastos->luz)+floatval($gastos->abonos)+floatval($gastos->ropa_calzado)+floatval($gastos->fondo_ahorro)+floatval($gastos->credito_vivienda)+floatval($gastos->transporte)+floatval($gastos->medicamento);
					// $reg->egresos = floatval(0);
					// if($reg->parentesco != "TITULAR") $reg->egresos = "";
					if(substr_count($arguments['campos'], "TICKET_1")>0) if($reg->parentesco == "TITULAR") $reg->ticket_1 = ($this->model->egresos->getByTit(intval($reg->ticket_1))->result->ticket_uno); else $reg->ticket_1 = '';
					if(substr_count($arguments['campos'], "TICKET_2")>0) if($reg->parentesco == "TITULAR") $reg->ticket_2 = ($this->model->egresos->getByTit(intval($reg->ticket_2))->result->ticket_dos); else $reg->ticket_2 = '';
					if(substr_count($arguments['campos'], "TICKET_3")>0) if($reg->parentesco == "TITULAR") $reg->ticket_3 = ($this->model->egresos->getByTit(intval($reg->ticket_3))->result->ticket_tres); else $reg->ticket_3 = '';
					if(substr_count($arguments['campos'], "TICKET_4")>0) if($reg->parentesco == "TITULAR") $reg->ticket_4 = ($this->model->egresos->getByTit(intval($reg->ticket_4))->result->ticket_cuatro); else $reg->ticket_4 = '';

					if(substr_count($arguments['campos'], "INGRESOS")>0){
						if($reg->parentesco == "TITULAR"){
							$info = $this->model->titular->get($reg->nivel_necesidad)->result;
							$reg->ingresos = floatval($this->model->integrante->getIngresosTit($reg->nivel_necesidad)) + floatval($info->ingreso_otro_apoyo);
						}else{
							$reg->ingresos = '';
						}
					}

				
				// nivel de necesidad

				if(substr_count($arguments['campos'], "NIVEL_NECESIDAD")>0){
					if($reg->parentesco == "TITULAR"){
						$info = $this->model->titular->get($reg->nivel_necesidad);
						if($info->response){
							$aporte_gasto = $info->result->aporte_gasto;
							$TotalIntegrantes = $this->model->integrante->getTotalIntegrantes($reg->nivel_necesidad)->result->Total;
							$ingre = floatval($aporte_gasto) / intval($TotalIntegrantes);
							$puntos = 0;

							// aporte gasto
							if($ingre <= 4300){
								// 10 puntos
								$puntos += 10;
							}else if($ingre > 4300 && $ingre <= 6300){
								// 5 puntos
								$puntos += 5;
							}else if($ingre > 6300 && $ingre <= 8300){
								// 3 puntos
								$puntos += 3;
							}else if($ingre > 8300){
								// 1 puntos
								$puntos += 1;
							}

							$gastos = $this->model->egresos->getByTit($reg->nivel_necesidad);
							if($gastos->response){
								// alimentos

								// hasta 7 puntos c/u -- total max 70
								$puntos += $gastos->result->huevo;
								$puntos += $gastos->result->proteina_animal;
								$puntos += $gastos->result->carbohidratos;
								$puntos += $gastos->result->basicos;
								$puntos += $gastos->result->azucares;
								$puntos += $gastos->result->lacteos;
								$puntos += $gastos->result->aceites_grasas;
								$puntos += $gastos->result->fruta_verdura;
								$puntos += $gastos->result->comida_fuera;
								$puntos += $gastos->result->comida_comprada;

								// vulnerabilidades
								if($gastos->result->vulnerabilidad == 1){
									// 10 pts
									$puntos += 10;
								}
								if($gastos->result->estabilidad == 0){
									// 5 pts
									$puntos += 5;
								}

								// hasta 10 pts
								if($gastos->result->egre_medicamento == 1){
									$puntos += 2;
								}else if($gastos->result->egre_medicamento == 2){
									$puntos += 4;
								}else if($gastos->result->egre_medicamento == 3){
									$puntos += 6;
								}else if($gastos->result->egre_medicamento == 4){
									$puntos += 8;
								}else if($gastos->result->egre_medicamento == 5){
									$puntos += 10;
								}

								// hasta 10 pts
								if($gastos->result->egre_educacion == 1){
									$puntos += 1;
								}else if($gastos->result->egre_educacion == 2){
									$puntos += 2;
								}else if($gastos->result->egre_educacion == 3){
									$puntos += 3;
								}else if($gastos->result->egre_educacion == 4){
									$puntos += 4;
								}else if($gastos->result->egre_educacion == 5){
									$puntos += 5;
								}

								// hasta 10 pts
								if($gastos->result->egre_transporte == 1){
									$puntos += 1;
								}else if($gastos->result->egre_transporte == 2){
									$puntos += 2;
								}else if($gastos->result->egre_transporte == 3){
									$puntos += 3;
								}else if($gastos->result->egre_transporte == 4){
									$puntos += 4;
								}else if($gastos->result->egre_transporte == 5){
									$puntos += 5;
								}
							}

							$vivienda = $this->model->vivienda->getByTit($reg->nivel_necesidad);
							if($vivienda->response){
								
								// casa
								if($vivienda->result->estatus >= 0){
									if($vivienda->result->estatus == 0){
										// Casa Propia 0 pts
										$puntos += 0;
									}else if($vivienda->result->estatus == 2){
										// Rentada 1 pts
										$puntos += 1;
									}else if($vivienda->result->estatus == 3){
										// Pagándose 1 pts
										$puntos += 1;
									}else if($vivienda->result->estatus == 1){
										// Prestada 2 pts
										$puntos += 2;
									}else if($vivienda->result->estatus == 4){
										// Asentamiento regular 3 pts
										$puntos += 3;
									}else if($vivienda->result->estatus == 5){
										// Otro 1 pts
										$puntos += 1;
									}
								}

								// electrodomesticos
								if(strlen($vivienda->result->electrodomesticos) > 0){
									// Hasta 10 puntos de electrodomesticos
									if(($vivienda->result->electrodomesticos) == "0"){
										$puntos += 10;
									}
								}

								// luz, agua, combustible
								if($vivienda->result->agua == 0){
									// 15 punto
									$puntos += 15;
								}

								// internet
								if($vivienda->result->internet == 0){
									// 5 punto
									$puntos += 5;
								}

								// tv
								if($vivienda->result->tv == 0){
									// 5 punto
									$puntos += 5;
								}
							}

							// if($puntos <= 60){
							// 	$reg->nivel_necesidad = "RECHAZADA";
							// }else if($puntos > 60 && $puntos <= 96){
							// 	$reg->nivel_necesidad = "BAJA";
							// }else if($puntos > 96 && $puntos < 156){
							// 	$reg->nivel_necesidad = "ALTA";
							// }else if($puntos >= 156){
							// 	$reg->nivel_necesidad = "BECA";
							// }

							if($puntos <= 60){
								$reg->nivel_necesidad = "RECHAZADA";
							}else if($puntos > 60 && $puntos <= 110){
								$reg->nivel_necesidad = "BAJA";
							}else if($puntos > 110 && $puntos <= 131){
								$reg->nivel_necesidad = "ALTA";
							}else if($puntos > 131){
								$reg->nivel_necesidad = "BECA";
							}
						}else{
							$reg->nivel_necesidad = '';	
						}
					}else{
						$reg->nivel_necesidad = '';
					}
				}

					// //nivel de necesidad e ingresos familiares
					// $info = $this->model->titular->get($reg->nivel_necesidad)->result;
					// $ingFam = floatval($this->model->integrante->getIngresosTit($reg->nivel_necesidad)) + floatval($info->ingreso_otro_apoyo);
					// $getSalario = $this->model->titular->getSalario();
					// $salario_minimo = floatval($getSalario->result->salario_minimo);
					// $limite_salarial = floatval($getSalario->result->limite_salarial);
					// $infoSeguro = $this->model->titular->getByID($reg->nivel_necesidad)->result->seguro_social;
					// $TotalIntegrantes = $this->model->integrante->getTotalIntegrantes($reg->nivel_necesidad)->result->Total;
					// if($ingFam > (2*$salario_minimo)){
					// 	$nivel = 'RECHAZADO ';
					// }else{
					// 	if($infoSeguro==1){
					// 		if($ingFam < $limite_salarial){
					// 			$nivel = 'BAJO';
					// 		}else{
					// 			$nivel = 'RECHAZADO';
					// 		}
					// 	}else{
					// 		if($ingFam <= $salario_minimo){
					// 			$nivel = 'ALTO';
					// 		}else if($ingFam > $salario_minimo && $ingFam <= $limite_salarial){
					// 			if($TotalIntegrantes > 4){
					// 				$nivel = 'ALTO';
					// 			}else{
					// 				$nivel = 'RECHAZADO';
					// 			}

					// 		}else{
					// 			if($TotalIntegrantes > 6){
					// 				$nivel = 'ALTO';
					// 			}else{
					// 				$nivel = 'RECHAZADO';
					// 			}
					// 		}
					// 	}
					// }
					// $reg->nivel_necesidad = $nivel;
					// $reg->ingresos = $ingFam;
					// if($reg->parentesco != "TITULAR") $reg->nivel_necesidad = "";
					// if($reg->parentesco != "TITULAR") $reg->ingresos = "";
					// //nivel de necesidad e ingresos familiares

					$arrContent[] = $reg;
				}
			}

			$titulo = "Beneficiarios";

			$arrMes = array('','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
    		$subtitulo = "Al ".date('d')." de ".$arrMes[intval(date('m'))]." de ".date('Y')." ".date('H:i:s');

			$sheet->setCellValue("A1", $titulo);
			$sheet->setCellValue("E1", $subtitulo);

			$colh = "A";
			foreach ($campos as $campo) {
				$sheet->setCellValue($colh."3", $campo);
				$colh++;
			}

			$row=4;
			foreach($arrContent as $reg){
				$col='A';
				$fila = json_decode(json_encode($reg), true);
				foreach ($campos as $campo) {
					$sheet->setCellValue($col.$row, $fila[strtolower($campo)]);
					$col++;
				}
				$row++;
			}

			$writer = new Csv($spreadsheet);
			$writer->setUseBOM(true);
			header('Content-Type: text/csv');
			header("Content-Disposition: attachment; filename=\"Beneficiarios_".date('YmdHi').".csv\"");
			$writer->save('php://output');
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
						->write(json_encode($this->model->titular->findBy($args['f'], $args['v'])));			
		});

		$this->get('getComunidades/', function ($req, $res, $qrgs){
			return $res->withHeader('Content-type', 'application/json')
						->write(json_encode($this->model->titular->getComunidades()));
		});

		$this->get('getByComu/{muni}', function ($req, $res, $args){
			return $res->withHeader('Content-type', 'application/json')
						->write(json_encode($this->model->titular->getbyComu($args['muni'])));
		});

		// Obtener lista de comunidad (pdf)
		$this->get('getPDFByComu/{muni}', function($request, $response, $arguments) {	
			$getLista = $this->model->titular->getbyComu($arguments['muni']);
			$getComu = $this->model->titular->getComunidades();
			$comu = '';
			foreach($getComu as $data){
				if($data->id == $arguments['muni']) $comu = $data->nombre;
			}
			$titulo = "Lista de Asistencia '".$comu."'";
			$total = count($getLista);
			$params = array('vista' => $titulo);
			$params['total'] = $total." titulares en lista";
        	$params['registros'] = $getLista;
			return $this->view->render($response, 'rptListaComunidad.php', $params);
		});

		// Obtener salario minimo
		$this->get('getSalario/', function($request, $response, $arguments) {
			$datos = $this->model->titular->getSalario();
			return $response->withJson($datos);
		});

		// Editar salario minimo
		$this->put('editSalario/', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$salario = $this->model->titular->editSalario($parsedBody); 
			if($salario->response) {
				$seg_log = $this->model->seg_log->add('Actualización información salario mnimo', 1, 'salario', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			}else{
				$salario->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($salario); 
			}
			$salario->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($salario);
		});

		// Editar fecha consulta titular
		$this->put('editConsulta/{id}', function ($req, $res, $args) {
			$this->response = new Response();
			$this->model->transaction->iniciaTransaccion();
			$resultado = $this->model->titular->editConsulta($args['id']);
			if(!$resultado->response){
				$resultado->state = $this->model->transaction->regresaTransaccion(); 
				return $this->withJson($resultado); 
			}
			$resultado->state = $this->model->transaction->confirmaTransaccion();
	        return $res->withHeader('Content-type', 'application/json')
	                   ->write(json_encode($resultado));
	    });

		// Editar ultima consulta ficha titular
		$this->put('editConsultaFicha/{id}', function ($req, $res, $args) {
			$this->response = new Response();
			$this->model->transaction->iniciaTransaccion();
			$resultado = $this->model->titular->editConsultaFicha($args['id']);
			if(!$resultado->response){
				$resultado->state = $this->model->transaction->regresaTransaccion(); 
				return $this->withJson($resultado); 
			}
			$resultado->state = $this->model->transaction->confirmaTransaccion();
	        return $res->withHeader('Content-type', 'application/json')
	                   ->write(json_encode($resultado));
	    });

		// Editar fecha actualizacion ficha titular
		$this->put('editFechaActualizacion/{id}', function ($req, $res, $args) {
			$this->response = new Response();
			$this->model->transaction->iniciaTransaccion();
			$resultado = $this->model->titular->editFechaActualizacion($args['id']);
			if(!$resultado->response){
				$resultado->state = $this->model->transaction->regresaTransaccion(); 
				return $this->withJson($resultado); 
			}
			$resultado->state = $this->model->transaction->confirmaTransaccion();
	        return $res->withHeader('Content-type', 'application/json')
	                   ->write(json_encode($resultado));
	    });
	});
?>