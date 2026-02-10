<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;

	class IntegranteModel {
		private $db;
		private $tableI = 'cat_integrante';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

        // Agregar integrante
		public function add($data){
			try{
				$resultado = $this->db
								->insertInto($this->tableI, $data)
								->execute();
			}catch(\PDOException $ex){
				$this->response->result = $resultado;
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, 'catch: Add model integrante');	
			}
			if($resultado!=0){
				$this->response->result = $resultado;
				return $this->response->SetResponse(true, 'Id del registro: '.$resultado);    
			}else{
				$this->response->result = $resultado;
				return $this->response->SetResponse(false, "No se inserto el registro en ".$this->tableI);
			}	
		}

		//Editar Integrante por fk_titular
		public function editByFkTitular($data, $id, $titular = true) {
			$queryTit = $titular ? "parentesco = 'TITULAR'" : "TRUE";
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tableI, $data)
					->where('fk_titular', $id)
					->where($queryTit)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Id actualizado: '.$id); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableI); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableI");
			}
		}

		// Obtener integrante por id
		public function get($id) {
			$integrante = $this->db
				->from($this->tableI)
				->where('id_integrante', $id)
				->fetch();
			if($integrante) {
				$this->response->result = $integrante;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableI);
			}
        }

		// Obtener integrante por titular
		public function getByTit($id, $todos=false) {
			$todos = $todos ? "TRUE" : "parentesco != 'TITULAR'";
			$integrante = $this->db
				->from($this->tableI)
				->select(null)->select("CONCAT_WS(' ',nombre,apaterno,amaterno) as nombre_completo, parentesco, fecha_nacimiento, sexo, escolaridad, padecimiento, ingreso, id_integrante, fk_titular, estatus")
				->where('fk_titular', $id)
				->where("$todos")
				->where('estatus != 0')
				->fetchAll();
			$this->response->result = $integrante;
			if($integrante) {
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableI);
			}
        }

		// Obtener integrante por CURP
		public function findByNombre($nombre, $paterno, $materno) {
			$integrante = $this->db
				->from($this->tableI)
				->where('nombre', $nombre)
				->where('apaterno', $paterno)
				->where('amaterno', $materno)
				->where('estatus', 1)
				->fetch();
			$this->response->result = $integrante;
			if($integrante) {
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableI);
			}
        }

		// Obtener integrante por CURP
		public function getByCURP($curp) {
			$integrante = $this->db
				->from($this->tableI)
				->where('curp', $curp)
				->where('estatus', 1)
				->fetch();
			$this->response->result = $integrante;
			if($integrante) {
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableI);
			}
        }

		public function getIngresosTit($id) {
			return $this->db
				->from($this->tableI)
				->select(null)->select("SUM(ingreso) AS ingresos")
				->where('fk_titular', $id)
				->where('estatus', 1)
				->fetch()->ingresos;
        }

		public function getTotalIntegrantes($id) {
			$integrante = $this->db
				->from($this->tableI)
				->select(null)->select('COUNT(*) Total')
				->where('fk_titular', $id)
				->where('estatus', 1)
				->fetch();
			$this->response->result = $integrante;
			if($integrante) {
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableI);
			}
        }

		// Editar integrante
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tableI, $data)
					->where('id_integrante', $id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Id actualizado: '.$id); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableI); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableI");
			}
		}

		// Eliminar integrante
		public function del($id){
			$set = array('estatus' => 0,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->tableI)
				->set($set)
				->where('id_integrante', $id)
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
				->from($this->tableI)
				->where($field, $value)
				->where('estatus', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}

		public function getEdad($date){
			if(count(explode("-",$date))<3) return '--';
			list($ano,$mes,$dia) = explode("-",$date);
			$ano_diferencia  = date("Y") - $ano;
			$mes_diferencia = date("m") - $mes;
			$dia_diferencia   = date("d") - $dia;
			if (($mes_diferencia == 0 && $dia_diferencia < 0) || $mes_diferencia < 0)
			  $ano_diferencia--;
	
			if($ano_diferencia > 100) $ano_diferencia = '--';
			return $ano_diferencia;
		}
	}
?>