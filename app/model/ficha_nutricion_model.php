<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;

	class FichaNutricionModel {
		private $db;
        private $tableET = 'egresos_titular';
        private $tableFN = 'ficha_nutricion';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

        // Agregar ficha_nutricion
		public function add($data){
			//$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$resultado = $this->db
								->insertInto($this->tableFN, $data)
								->execute();
			}catch(\PDOException $ex){
				$this->response->result = $resultado;
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, 'catch: Add model egresos');	
			}
			if($resultado!=0){
				$this->response->result = $resultado;
				return $this->response->SetResponse(true, 'Id del registro: '.$resultado);    
			}else{
				$this->response->result = $resultado;
				return $this->response->SetResponse(false, "No se inserto el registro en ".$this->tableFN);
			}	
		}

        // Obtener ficha_nutricion por id
		public function get($id) {
			$egresos = $this->db
				->from($this->tableFN)
				->where('id_ficha', $id)
				->fetch();
			if($egresos) {
				$this->response->result = $egresos;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableFN);
			}
        }

        // Obtener ficha_nutricion por titular
		public function getByTit($id) {
			$ficha_nutricion = $this->db
				->from($this->tableFN)
				->where('fk_integrante', $id)
				->fetch();
			if($ficha_nutricion) {
				$this->response->result = $ficha_nutricion;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableFN);
			}
        }

        // Editar ficha_nutricion por fk_integrante
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tableFN, $data)
					->where('fk_integrante', $id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Id actualizado: '.$id); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableFN); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableFN");
			}
		}

        // Eliminar ficha_nutricion
		public function del($id){
			$set = array('estatus' => 0,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->tableFN)
				->set($set)
				->where('id_ficha', $id)
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
				->from($this->tableFN)
				->where($field, $value)
				->where('estatus', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}
	}
?>