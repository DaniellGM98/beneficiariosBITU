<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;
	use Envms\FluentPDO\Literal;

	class AsistenciaModel {
		private $db;
        private $tableA = 'asistencia';
        private $tableAT = 'asist_temporal';
		private $tableF = 'faltas';
		private $tableT = 'cat_titular';
		private $tableI = 'cat_integrante';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

        // Obtener asistencia temporal del dia
		public function getAsistTemp() {
            $fecha = date('Y-m-d');
			$asist_temp = $this->db
				->from($this->tableAT)
				->where('fecha', $fecha)
				->fetch();
			if($asist_temp) {
				$this->response->result = $asist_temp;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableAT);
			}
        }

		// Editar contador asistencia_temp
		public function editContador($id) {
			$cantidad=1;
			$data = [
				'contador'=> new Literal('(contador + '.$cantidad.')')
			];
			try {
				return $this->response->result = $this->db
					->update($this->tableAT, $data)
					->where('id_temporal', $id)
					->execute();
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableAT");
			}
		}

		// Agregar asistencia_temp
		public function addAsistTemp(){
			$fecha = date('Y-m-d');
			$data = [
				'fecha'=> $fecha,
				'contador'=> '1'
			];
			try{
				return $resultado = $this->db
								->insertInto($this->tableAT, $data)
								->execute();
			}catch(\PDOException $ex){
				return "0";
			}
		}

		// Obtener datos de titular por credencial
		public function getByCred($cred) {
			$datos = $this->db
				->from($this->tableT)
				->select(null)->select("$this->tableT.*, DATE_FORMAT(ultima_visita, '%d/%m/%Y') AS visita, DATE_FORMAT(ultima_falta, '%d/%m/%Y') AS falta, CONCAT(nombre, ' ', apaterno, ' ', amaterno) as nomTit")
				->innerJoin("$this->tableI on $this->tableI.fk_titular = $this->tableT.id_titular")
				->where('parentesco', 'TITULAR')
				->where('cat_titular.estatus != 0')
				->where("trim(credencial)='$cred'")
				->fetch();
			if(is_object($datos)) {
				$this->response->result = $datos;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableT);
			}
        }

		// Editar estatus y bajas de titular
		public function editEstatusBajas($id) {
			$cantidad=1;
			$data = [
				'estatus'=>'2',
				'bajas'=> new Literal('(bajas + '.$cantidad.')'),
				'fecha_baja'=> date('Y-m-d H:i:s'),
				'fecha_modificacion' => date('Y-m-d H:i:s')
			];
			try {
				return $this->db
					->update($this->tableT, $data)
					->where('id_titular', $id)
					->execute();
			} catch(\PDOException $ex) {
				return "0";
			}
		}

		// Obtener asistencia de titular
		public function getAsistencia($id) {
			$datos = $this->db
				->from($this->tableA)
				->where('fk_titular', $id)
				->where("DATE_FORMAT(fecha, '%d/%m/%Y')", "DATE_FORMAT('".date('Y-m-d H:i:s')."', '%d/%m/%Y')")
				->fetch();
			if($datos) {
				$this->response->result = $datos;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableA);
			}
        }

		// Obtener asistencia de titular
		public function getAsistenciaByFecha($id, $fecha) {
			$datos = $this->db
				->from($this->tableA)
				->where('fk_titular', $id)
				->where("DATE_FORMAT(fecha, '%Y-%m-%d')", $fecha)
				->fetch();
			if($datos) {
				$this->response->result = $datos;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableA);
			}
        }

		// Obtener faltas
		public function getFaltas($id, $fecha) {
			$datos = $this->db
				->from($this->tableF)
				->where('fk_titular', $id)
				->where("DATE_FORMAT(fecha, '%Y-%m-%d')", $fecha)
				->where("estatus", 1)
				->fetch();
			if($datos) {
				$this->response->result = $datos;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableA);
			}
		}

		// Obtener ultima asistencia
		public function getUltimaAsist($id) {
			$datos = $this->db
				->from($this->tableA)
				->where('fk_titular', $id)
				->where('estatus', 1)
				->orderBy("fecha DESC")
				->fetch();
			if($datos) {
				$this->response->result = $datos;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableA);
			}
        }

		// Agregar asistencia
		public function addAsistencia($id, $faltas){
			$data = [
				'fk_titular'=> intval($id),
				'num_faltas'=> intval($faltas),
				'fecha'=> date('Y-m-d H:i:s')
			];
			try{
				return $this->db
					->insertInto($this->tableA, $data)
					->execute();
			}catch(\PDOException $ex){
				return "0";
			}
		}

		// Editar ultima_visita de titular
		public function editUltimaVisita($id) {
			$data = [
				'ultima_visita'=>date('Y-m-d'),
				'faltas'=>'0',
				'fecha_modificacion' => date('Y-m-d H:i:s')
			];
			try {
				return $this->db
					->update($this->tableT, $data)
					->where('id_titular', $id)
					->execute();
			} catch(\PDOException $ex) {
				return "0";
			}
		}

		// Obtener Lista
        public function getLista($tipo, $fecha) {
			$hoy = date('w', strtotime($fecha));
			return $this->db
				->from($this->tableT)->disableSmartJoin()
				->select(null)->select("id_titular, credencial, faltas, CONCAT(apaterno,' ',amaterno,' ',nombre) as nombre, DATE_FORMAT(fecha,'%H:%i') as hora")
				->innerJoin("$this->tableI on $this->tableI.fk_titular = $this->tableT.id_titular")
				->leftJoin("asistencia a ON a.fk_titular = id_titular AND DATE_FORMAT(fecha, '%Y-%m-%d') = '$fecha'")
				// ->where("DATE_FORMAT(fecha, '%d-%m-%Y')","$fecha")
				->where("($this->tableT.fecha_visita = $hoy OR $this->tableT.fecha_visita = 0)")
				->where("$this->tableI.parentesco", 'TITULAR')
				->where("$this->tableT.estatus", '1')
				->where("$this->tableT.tipo IN ($tipo)")
				->orderBy('hora DESC')
				->fetchAll();
		}

		// Obtener  Bajas
        public function getBajas($tipo, $fecha) {
			$hoy = date('w', strtotime($fecha));
			return $this->db
				->from($this->tableA)->disableSmartJoin()

				->select(null)->select("cat_titular.id_titular, cat_titular.credencial, cat_titular.faltas, CONCAT(cat_integrante.apaterno,' ',cat_integrante.amaterno,' ',cat_integrante.nombre) as nombre, DATE_FORMAT(asistencia.fecha,'%H:%i') as hora")

				->innerJoin("$this->tableT on $this->tableT.id_titular = $this->tableA.fk_titular")
				->innerJoin("$this->tableI on $this->tableI.fk_titular = $this->tableA.fk_titular")

				->where("DATE_FORMAT(fecha, '%Y-%m-%d') = '$fecha'")
				->where("($this->tableT.fecha_visita = $hoy OR $this->tableT.fecha_visita = 0)")
				->where("$this->tableI.parentesco", 'TITULAR')
				->where("$this->tableT.estatus", '2')
				->where("$this->tableT.tipo IN ($tipo)")
				->orderBy('hora DESC')
				->fetchAll();
		}

		// Obtener Lista
        public function getListaBajas($tipo, $fecha) {
			$hoy = date('w', strtotime($fecha));
			return $this->db
				->from($this->tableT)->disableSmartJoin()
				->select(null)->select("id_titular, credencial, faltas, CONCAT(apaterno,' ',amaterno,' ',nombre) as nombre, DATE_FORMAT(a.fecha_modificacion,'%H:%i') as hora")
				->innerJoin("$this->tableI on $this->tableI.fk_titular = $this->tableT.id_titular")
				->leftJoin("seg_log a ON a.registro = id_titular AND DATE_FORMAT(a.fecha_modificacion, '%Y-%m-%d') = '$fecha'")
				// ->where("DATE_FORMAT(fecha, '%d-%m-%Y')","$fecha")
				->where("a.descripcion", "Baja beneficiario")
				->where("a.tabla", "cat_titular")
				->where("($this->tableT.fecha_visita = $hoy OR $this->tableT.fecha_visita = 0)")
				->where("$this->tableI.parentesco", 'TITULAR')
				//->where("$this->tableT.estatus", '1')
				->where("$this->tableT.tipo IN ($tipo)")
				->orderBy('hora DESC')
				->fetchAll();
		}

		// Obtener fecha por Fk_titular
		public function getFechaByFkTitular($id) {
			$fecha = date('d-m-Y');
			$datos = $this->db
				->from($this->tableA)->disableSmartJoin()
				->select(null)->select("DATE_FORMAT(fecha,'%H:%i') as hora")
				->where('fk_titular', $id)
				// ->where("DATE_FORMAT(fecha, '%d-%m-%Y')","$fecha")
				->where("DATE_FORMAT(fecha, '%d-%m-%Y')","$fecha")
				->orderBy('hora DESC')
				->fetch();
			if($datos) {
				return $datos->hora;
			}else{
				return "--";
			}
        }

		// Agregar asistencia con response
		public function add($id){
			$data = [
				'fk_titular'=> intval($id),
				'fecha'=> date('Y-m-d H:i:s')
			];
			try{
				$resultado = $this->db
								->insertInto($this->tableA, $data)
								->execute();
			}catch(\PDOException $ex){
				$this->response->result = $resultado;
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Add model ". $this->tableA);	
			}
			if($resultado!=0){
				$this->response->result = $resultado;
				return $this->response->SetResponse(true, 'Id del registro: '.$resultado);    
			}else{
				$this->response->result = $resultado;
				return $this->response->SetResponse(false, "No se inserto el registro en ".$this->tableA);
			}	
		}

		// Agregar asistencia con response
		public function addFalta($id, $num_faltas, $baja){
			date_default_timezone_set('America/Mexico_City');
			$data = [
				'fk_titular'=> $id,
				'fecha'=> date('Y-m-d H:i:s'),
				'num_faltas'=> $num_faltas,
				'baja'=> $baja,
			];
			try{
				$resultado = $this->db
								->insertInto($this->tableF, $data)
								->execute();
			}catch(\PDOException $ex){
				$this->response->result = $resultado;
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Add model ". $this->tableA);	
			}
			if($resultado!=0){
				$this->response->result = $resultado;
				return $this->response->SetResponse(true, 'Id del registro: '.$resultado);    
			}else{
				$this->response->result = $resultado;
				return $this->response->SetResponse(false, "No se inserto el registro en ".$this->tableA);
			}	
		}

		// Obtener asistencia por id
		public function get($id) {
			$asistencia = $this->db
				->from($this->tableA)
				->where('id_asistencia', $id)
				->fetch();
			if($asistencia) {
				$this->response->result = $asistencia;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableA);
			}
        }

		// Obtener asistencia por titular
		public function getByTit($id, $pagina, $limite) {
			if($pagina!=null && $limite!=null){
				$inicial = $pagina * $limite;
				$asistencia = $this->db
				->from($this->tableA)
				->where('fk_titular', $id)
				->limit("$inicial, $limite")
				->fetchAll();
			}else{
				$asistencia = $this->db
				->from($this->tableA)
				->where('fk_titular', $id)
				->fetchAll();
			}
			if($asistencia) {
				$this->response->result = $asistencia;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableA);
			}
        }

		// Editar asistencia
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tableA, $data)
					->where('id_asistencia', $id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Id actualizado: '.$id); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableA); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableA");
			}
		}

		// Eliminar asistencia
		public function del($id){
			$set = array('estatus' => 0,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->tableA)
				->set($set)
				->where('id_asistencia', $id)
				->execute();
			if($id!=0){
				return $this->response->SetResponse(true, "Id baja: $id");
			}else{
				return $this->response->SetResponse(true, "Id incorrecto");
			}
		}

		// Eliminar asistencia
		public function delUltima($id){
			$this->response->result = $this->db
				->delete($this->tableA)
				->where('fk_titular', $id)
				->orderBy('fecha DESC')
				->limit("1")
				->execute();
			if($id!=0){
				return $this->response->SetResponse(true, "Id baja: $id");
			}else{
				return $this->response->SetResponse(true, "Id incorrecto");
			}
		}

        // find by field = value
		public function findBy($field, $value){
			$this->response->result = $this->db
				->from($this->tableA)
				->where($field, $value)
				->where('estatus', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}
	}
?>