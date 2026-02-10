<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;
	use Slim\Http\UploadedFile;

	class TitularModel {
		private $db;
		private $tableT = 'cat_titular';
		private $tableI = 'cat_integrante';
		private $tableFN = 'ficha_nutricion';
		private $tableFT = 'ficha_tecnica';
		private $tableS = 'salario';
		private $tableVT = 'vivienda_titular';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

        // Agregar titular
		public function add($data){
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			$data['fecha_consulta'] = date('Y-m-d H:i:s');
			try{
				$resultado = $this->db
								->insertInto($this->tableT, $data)
								->execute();
			}catch(\PDOException $ex){
				$this->response->result = '';
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, 'catch: Add model titular');	
			}
			if($resultado!=0){
				$this->response->result = $resultado;
				return $this->response->SetResponse(true, 'Id del registro: '.$resultado);    
			}else{
				$this->response->result = $resultado;
				return $this->response->SetResponse(false, "No se inserto el registro en ".$this->tableT);
			}	
		}

		// Obtener todos los titulares
		public function getAll($pagina, $limite, $titular_tipo_id, $busqueda) {
			if($titular_tipo_id==null){
				$titular_tipo_id=0;
			}
			$inicial = $pagina * $limite;
			$busqueda = $busqueda==null ? "_" : $busqueda;
			$titulares = $this->db
				->from($this->tableT)
                // ->select(null)->select("id_titular, credencial, fecha_solicitud, fecha_visita, fecha_ingreso, fecha_actualizacion, personas_depen, telefono, domicilio, colonia, fk_municipio, estado_civil, discapacidad, otro_apoyo, ingreso_otro_apoyo, gasto_baceh, observaciones, fecha_baja, faltas, ultima_visita, ultima_falta, bajas, actualizo, tipo, observaciones_asist, vialidad, asentamiento, lista")
				->select(null)->select("id_titular, credencial, fecha_solicitud, fecha_visita, fecha_ingreso, fecha_actualizacion, personas_depen, telefono, domicilio, colonia, fk_municipio, estado_civil, discapacidad, otro_apoyo, ingreso_otro_apoyo, gasto_baceh, observaciones, fecha_baja, faltas, ultima_visita, ultima_falta, bajas, actualizo, tipo, observaciones_asist, lista")
				->where("CONCAT_WS(' ', credencial, fk_municipio, lista) LIKE '%$busqueda%'")
				->where("tipo".($titular_tipo_id==0? ">": "=").$titular_tipo_id)
				->where("estatus", 1)
				->limit("$inicial, $limite")
				->orderBy('credencial ASC')
				->fetchAll();
			$this->response->result = $titulares;
			$this->response->total = $this->db
				->from($this->tableT)
				->select(null)->select('COUNT(*) Total')
				->where("CONCAT_WS(' ', credencial, fk_municipio, lista) LIKE '%$busqueda%'")
				->where("tipo".($titular_tipo_id==0? ">": "=").$titular_tipo_id)
				->where("estatus", 1)
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

		// Obtener todos los titulares con filtros
		public function getAllInte($pagina, $limite, $filtros, $busqueda) {
			$inicial = $pagina * $limite;
			$busqueda = $busqueda==null ? "_" : $busqueda;

			$sexo = $filtros['sexo'] == '0'?'TRUE':"sexo='".$filtros['sexo']."'";
			//$edad = $filtros['edad'] == '0'?'TRUE':"edad=".$filtros['edad']."'";
			//$peso = $filtros['peso'] == '0'?'TRUE':"peso=".$filtros['peso']."'";						
			$fk_municipio = $filtros['fk_municipio'] == '0'?'TRUE':"fk_municipio='".$filtros['fk_municipio']."'";
			$parentesco = $filtros['parent'] == '0'?'TRUE':($filtros['parent'] == 'TITULARES'?"parentesco='TITULAR'":($filtros['parent'] == 'INTEGRANTES'?"parentesco!='TITULAR'":"parentesco='".$filtros['parent']."'"));
			$escolaridad = $filtros['escolaridad'] == '-1'?'TRUE':"escolaridad='".$filtros['escolaridad']."'";
			$estatus = $filtros['status'] == '0'?'TRUE':"cat_integrante.estatus='".$filtros['status']."'";
			$tipo = $filtros['tipo'] == '0'?'TRUE':"tipo='".$filtros['tipo']."'";
			$padecimiento = $filtros['padecimiento'] == '-1'?'TRUE':"padecimiento='".$filtros['padecimiento']."'";
			$dia_visita = $filtros['visita'] == '0'?'TRUE':"fecha_visita='".$filtros['visita']."'";

				$titulares = $this->db
				->from($this->tableT)
                ->select(null)->select("credencial, CONCAT_WS(' ',nombre,apaterno,amaterno) as nombre_completo, parentesco, fecha_nacimiento, sexo, escolaridad, colonia, fk_municipio, fk_titular, id_integrante, cat_titular.estatus")
				->innerJoin($this->tableI.' ON id_titular = fk_titular')
				->where("CONCAT_WS(' ', nombre, apaterno, amaterno, credencial) LIKE '%$busqueda%'")
				->where($sexo)
				->where($fk_municipio)
				->where($parentesco)
				->where($escolaridad)
				->where($estatus)
				->where($tipo)
				->where($padecimiento)
				->where($dia_visita)
				->where("cat_titular.estatus", 1)
				->limit("$inicial, $limite")
				->orderBy('credencial ASC')
				->fetchAll();
				$this->response->result = $titulares;
				$this->response->total = $this->db
					->from($this->tableT)
					->select(null)->select('COUNT(*) Total')
					->innerJoin($this->tableI.' ON id_titular = fk_titular')
					->where("CONCAT_WS(' ', nombre, apaterno, amaterno, credencial) LIKE '%$busqueda%'")
					->where($sexo)
					->where($fk_municipio)
					->where($parentesco)
					->where($escolaridad)
					->where($estatus)
					->where($tipo)
					->where($padecimiento)
					->where($dia_visita)
					->where("cat_titular.estatus", 1)
					->fetch()
					->Total;
				return $this->response->SetResponse(true);
		}

		// Obtener todos los titulares con filtros
		public function getAllInteAjax($inicial, $limite, $busqueda, $filtros, $orden = "cat_titular.estatus, credencial") {
			$sexo = $filtros['sexo'] == '0'?'TRUE':"sexo='".$filtros['sexo']."'";
			$fk_municipio = $filtros['fk_municipio'] == '0'?'TRUE':"fk_municipio='".$filtros['fk_municipio']."'";
			$parentesco = $filtros['parent'] == '0'?'TRUE':($filtros['parent'] == 'TITULARES'?"parentesco='TITULAR'":($filtros['parent'] == 'INTEGRANTES'?"parentesco!='TITULAR'":"parentesco='".$filtros['parent']."'"));
			$escolaridad = $filtros['escolaridad'] == '-1'?'TRUE':"escolaridad='".$filtros['escolaridad']."'";
			$estatus = $filtros['status'] == '0'?'cat_titular.estatus!=0':"cat_titular.estatus='".$filtros['status']."'";
			$tipo = $filtros['tipo'] == '0'?'TRUE':"tipo='".$filtros['tipo']."'";
			$padecimiento = $filtros['padecimiento'] == '-1'?'TRUE':"padecimiento='".$filtros['padecimiento']."'";
			$dia_visita = $filtros['visita'] == '0'?'TRUE':"fecha_visita=".$filtros['visita']."";

			$edad = 'TRUE';
			if($filtros['edad'] != 0){
				$arrEdad = array('','0 AND 3','4 AND 9','10 AND 17','18 AND 30','31 AND 50','51 AND 150');
				$edad = "(YEAR(CURDATE())-YEAR(fecha_nacimiento)+IF(DATE_FORMAT(CURDATE(),'%m-%d') > DATE_FORMAT(fecha_nacimiento,'%m-%d'), 0, -1)) BETWEEN ".$arrEdad[$filtros['edad']]." ";
			}
			$peso = 'TRUE';
			if($filtros['peso'] != 0){
				$arrPeso = array('Todos','Desnutrición Grave', 'Desnutrición moderada', 'Desnutrición leve', 'Delgadez Severa', 'Delgadez', 'Peso Bajo Severo', 'Peso Bajo', 'Bajo peso', 'Normal', 'Peso Normal', 'Con riesgo de sobrepeso', 'Sobrepeso', 'Obesidad', 'Obesidad I', 'Obesidad II', 'Obesidad III');
				$peso = "IFNULL((SELECT IF(imc='',IF(peso_talla='',peso_edad,peso_talla),imc) FROM historial_nutricion WHERE fk_integrante = id_integrante ORDER BY fecha DESC LIMIT 1),'') = '".$arrPeso[$filtros['peso']]."'";
			}


			$titulares = $this->db
				->from($this->tableT)->disableSmartJoin()
                ->select(null)->select("SQL_CALC_FOUND_ROWS credencial, CONCAT_WS(' ',nombre,apaterno,amaterno) as nombre_completo, parentesco, fecha_nacimiento, sexo, escolaridad, colonia, fk_municipio, fk_titular, id_integrante, $this->tableT.estatus, $this->tableI.estatus as estatusInt, (YEAR(CURDATE())-YEAR(fecha_nacimiento)+IF(DATE_FORMAT(CURDATE(),'%m-%d') > DATE_FORMAT(fecha_nacimiento,'%m-%d'), 0, -1)) AS edad, IFNULL((SELECT IF(imc='',IF(peso_talla='',peso_edad,peso_talla),imc) FROM historial_nutricion WHERE fk_integrante = id_integrante ORDER BY fecha DESC LIMIT 1),'') AS peso, numero_telefono")
				->innerJoin($this->tableI.' ON id_titular = fk_titular')
				->where("CONCAT_WS(' ', nombre, apaterno, amaterno, credencial) LIKE '%$busqueda%'")
				->where($sexo)
				->where($fk_municipio)
				->where($parentesco)
				->where($escolaridad)
				->where($estatus)
				->where($tipo)
				->where($padecimiento)
				->where($dia_visita)
				->where($edad)
				->where($peso)
				->limit("$inicial, $limite")
				->orderBy($orden)
				->fetchAll();
			$this->response->result = $titulares;
			$this->response->total = $this->db->getPdo()->query('SELECT FOUND_ROWS()')->fetchColumn();
			$this->response->totalInte = $this->db
					->from($this->tableT)
					->select(null)->select('COUNT(*) Total')
					->innerJoin($this->tableI.' ON id_titular = fk_titular')
					->fetch()
					->Total;
			return $this->response->SetResponse(true);
		}

		// Editar titular
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tableT, $data)
					->where('id_titular', $id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Id actualizado: '.$id); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableT); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableT");
			}
		}

		// Eliminar titular
		public function del($id){
			$set = array('estatus' => 0,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->tableT)
				->set($set)
				->where('id_titular', $id)
				->execute();
			if($id!=0){
				return $this->response->SetResponse(true, "Id baja: $id");
			}else{
				return $this->response->SetResponse(true, "Id incorrecto");
			}
		}

		// Editar estatus titular
		public function changeStatus($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tableT, $data)
					->where('id_titular', $id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Id actualizado: '.$id); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableT); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableT");
			}
		}
		
		// Obtener titular por id
		public function get($id) {
			$titular = $this->db
				->from($this->tableT)
				// ->select(null)->select("credencial, fecha_solicitud, fecha_visita, fecha_ingreso, fecha_actualizacion, estado_civil, otro_apoyo, ingreso_otro_apoyo, estatus, nombre, apaterno, amaterno, fecha_nacimiento, escolaridad, ocupacion")
				// ->select(null)->select("credencial, fecha_solicitud, fecha_visita, fecha_ingreso, fecha_actualizacion, estado_civil, otro_apoyo, ingreso_otro_apoyo, cat_titular.estatus, nombre, apaterno, amaterno, fecha_nacimiento, escolaridad, ocupacion, id_titular, id_integrante")
				->select(null)->select('*, cat_titular.estatus AS estatus')
				->innerJoin($this->tableI.' ON id_titular = fk_titular')
				->where('id_titular', $id)
				->where('parentesco', 'TITULAR')
				->where('cat_titular.estatus != 0')
				->fetch();
			if($titular) {
				$this->response->result = $titular;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableT);
			}
		}

		// Obtener titular por id solo cat_titular
		public function getByID($id) {
			$titular = $this->db
				->from($this->tableT)
				->where('id_titular', $id)
				->fetch();
			if($titular) {
				$this->response->result = $titular;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableT);
			}
		}

		// Obtener titular por credencia
		public function getByCred($credencial) {
			$titular = $this->db
				->from($this->tableT)
				->where('credencial', $credencial)
				->where('estatus', 1)
				->fetch();
			if($titular) {
				$this->response->result = $titular;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableT);
			}
		}

		// Obtener titular por credencia
		public function getByCredComplete($credencial) {
			$titular = $this->db
				->from($this->tableT)
				->innerJoin($this->tableI.' ON id_titular = fk_titular')
				->where('credencial', $credencial)
				->where('estatus', 1)
				->fetch();
			if($titular) {
				$this->response->result = $titular;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableT);
			}
		}

		// find by field = value
		public function findBy($field, $value){
			$this->response->result = $this->db
				->from($this->tableT)
				->where($field, $value)
				->where('estatus', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}

		public function resize($img, $thumb_width, $newfilename){
			$max_width=$thumb_width;
	  
		  //Check if GD extension is loaded
		  if (!extension_loaded('gd') && !extension_loaded('gd2')){
				 trigger_error("GD is not loaded", E_USER_WARNING);
			  return false;
		  }
	  
		  //Get Image size info
		  list($width_orig, $height_orig, $image_type) = getimagesize($img);
		 
		  switch ($image_type){
			  case 1: $im = imagecreatefromgif($img); break;
			  case 2: $im = imagecreatefromjpeg($img);  break;
			  case 3: $im = imagecreatefrompng($img); break;
			  default:  trigger_error('Unsupported filetype!', E_USER_WARNING);  break;
		  }
		 
		  /*** calculate the aspect ratio ***/
		  $aspect_ratio = (float) $height_orig / $width_orig;
	  
		  /*** calulate the thumbnail width based on the height ***/
		  $thumb_height = round($thumb_width * $aspect_ratio);
	  
		  while($thumb_height>$max_width){
			  $thumb_width-=10;
			  $thumb_height = round($thumb_width * $aspect_ratio);
		  }
		 
		  $newImg = imagecreatetruecolor($thumb_width, $thumb_height);
		 
		  /* Check if this image is PNG or GIF, then set if Transparent*/ 
		  if(($image_type == 1) OR ($image_type==3)){
			  imagealphablending($newImg, false);
			  imagesavealpha($newImg,true);
			  $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
			  imagefilledrectangle($newImg, 0, 0, $thumb_width, $thumb_height, $transparent);
		  }
		  imagecopyresampled($newImg, $im, 0, 0, 0, 0, $thumb_width, $thumb_height, $width_orig, $height_orig);
		 
		  //Generate the file, and rename it to $newfilename
		  switch ($image_type){
			  case 1: imagegif($newImg,$newfilename); break;
			  case 2: imagejpeg($newImg,$newfilename);  break;
			  case 3: imagepng($newImg,$newfilename); break;
			  default:  trigger_error('Failed resize image!', E_USER_WARNING);  break;
		  }
	   
		  return $newfilename;
		}

		public function getMunicipios(){
			return $this->db->from('cat_municipio')->orderBy('nombre')->fetchAll();
		}

		public function getFirma($tit){
			return $this->db->from('huella')->where('id_titular', $tit)/* ->where('status',1) */->fetch();
		}

		public function getComunidades(){
			return $this->db->from($this->tableT)
					->select(null)->select('fk_municipio AS id, cat_municipio.nombre AS nombre')
					->innerJoin('cat_municipio ON id_municipio = fk_municipio')
					->where('estatus', 1)
					->where('tipo', 3)
					->groupBy('fk_municipio')
					->orderBy('cat_municipio.nombre')
					->fetchAll();
		}

		public function getByComu($muni){
			return $this->db->from($this->tableT)
					->select(null)->select('credencial, colonia, CONCAT_WS(" ",nombre, apaterno, amaterno) AS nombre')
					->innerJoin('cat_integrante ON id_titular = fk_titular AND parentesco = "TITULAR"')
					->where('cat_titular.estatus', 1)
					->where('tipo', 3)
					->where('fk_municipio', $muni)
					->orderBy('credencial')
					->fetchAll();
		}

		function moveUploadedFile($directory, UploadedFile $uploadedFile, $filename) {
			$uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
			return $filename;
		}

		public function getDatos($campos, $page=0, $limit=0){
			$inicial = $page * $limit;
			$limite="$inicial,$limit";
			if($page==0 && $limit==0) $limite=True;
			$resultado = array();
			$resultado['datos'] = $this->db
					->from($this->tableI)
					->select(null)->select("SQL_CALC_FOUND_ROWS $campos")
					->innerJoin("$this->tableT ON cat_integrante.fk_titular = cat_titular.id_titular")
					->leftJoin("$this->tableFN ON cat_integrante.id_integrante = ficha_nutricion.fk_integrante")
					->leftJoin("$this->tableFT ON cat_integrante.fk_titular = ficha_tecnica.fk_titular")
					->leftJoin("$this->tableVT ON cat_integrante.fk_titular = vivienda_titular.fk_titular")
					->where("$this->tableT.estatus != 0")
					->where("$this->tableI.estatus != 0")
					->limit($limite)
					->orderBy("$this->tableT.estatus, $this->tableT.credencial")
					->fetchAll();
			$resultado['total'] = $this->db->getPdo()->query('SELECT FOUND_ROWS()')->fetchColumn();
			return $resultado;
		}

		// Obtener salario minimo
		public function getSalario() {
			$salario = $this->db
				->from($this->tableS)
				->select(null)->select('salario_minimo, limite_salarial')
				->fetch();
			if($salario!="") {
				$this->response->result = $salario;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableS);
			}
		}

		// Editar salario minimo
		public function editSalario($data) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tableS, $data)
					->where("id",1)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Salario mínimo actualizado'); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableS); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableS");
			}
		}

		// Editar consulta
		public function editConsulta($id) {
			$data['fecha_consulta'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tableT, $data)
					->where("id_titular",$id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Fecha consulta actualizada'); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableT); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableT");
			}
		}

		// Editar consulta ficha
		public function editConsultaFicha($id) {
			$data['ultima_consulta'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tableFT, $data)
					->where("fk_titular",$id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Ultima consulta actualizada'); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableFT); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableFT");
			}
		}

		// Editar consulta ficha
		public function editFechaActualizacion($id) {
			$data['fecha_actualizacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tableT, $data)
					->where("id_titular",$id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Fecha actualizacion actualizada'); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableT); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableT");
			}
		}
	}
?>