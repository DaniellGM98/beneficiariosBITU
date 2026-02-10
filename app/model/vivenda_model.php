<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;

	class ViviendaModel {
		private $db;
        private $tableV = 'vivienda_titular';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

        // Agregar vivienda
		public function add($data){
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$resultado = $this->db
								->insertInto($this->tableV, $data)
								->execute();
			}catch(\PDOException $ex){
				$this->response->result = $resultado;
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, 'catch: Add model vivienda');	
			}
			if($resultado!=0){
				$this->response->result = $resultado;
				return $this->response->SetResponse(true, 'Id del registro: '.$resultado);    
			}else{
				$this->response->result = $resultado;
				return $this->response->SetResponse(false, "No se inserto el registro en ".$this->tableV);
			}	
		}

        // Obtener vivienda por id
		public function get($id) {
			$vivienda = $this->db
				->from($this->tableV)
				->where('id_vivienda', $id)
				->fetch();
			if($vivienda) {
				$this->response->result = $vivienda;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableV);
			}
		}

        // Obtener vivienda por titular
		public function getByTit($id) {
			$vivienda = $this->db
				->from($this->tableV)
				->where('fk_titular', $id)
				->fetch();
			if($vivienda) {
				$this->response->result = $vivienda;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableV);
			}
		}

        // Editar vivienda
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tableV, $data)
					->where('id_vivienda', $id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Id actualizado: '.$id); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableV); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableV");
			}
		}

        // Eliminar vivienda
		public function del($id){
			$set = array('estatus' => 0,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->tableV)
				->set($set)
				->where('id_vivienda', $id)
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
				->from($this->tableV)
				->where($field, $value)
				->where('estatus', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}
	}
?>