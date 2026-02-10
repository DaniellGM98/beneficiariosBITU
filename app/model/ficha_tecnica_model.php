<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;

	class FichaTecnicaModel {
		private $db;
		private $tableFT = 'ficha_tecnica';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

        // Agregar ficha_tecnica
		public function add($data){
			try{
				$resultado = $this->db
								->insertInto($this->tableFT, $data)
								->execute();
			}catch(\PDOException $ex){
				$this->response->result = $resultado;
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, 'catch: Add model ficha_tecnica');	
			}
			if($resultado!=0){
				$this->response->result = $resultado;
				return $this->response->SetResponse(true, 'Id del registro: '.$resultado);    
			}else{
				$this->response->result = $resultado;
				return $this->response->SetResponse(false, "No se inserto el registro en ".$this->tableFT);
			}	
		}

		// Obtener ficha_tecnica por id
		public function get($id) {
			$egresos = $this->db
				->from($this->tableFT)
				->where('id_ficha', $id)
				->fetch();
			if($egresos) {
				$this->response->result = $egresos;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableFT);
			}
        }

		// Obtener egresos por titular
		public function getByTit($id) {
			$egresos = $this->db
				->from($this->tableFT)
				->where('fk_titular', $id)
				->fetch();
			if($egresos) {
				$this->response->result = $egresos;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableFT);
			}
        }

		// Editar ficha_tecnica
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tableFT, $data)
					->where('id_ficha', $id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Id actualizado: '.$id); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableFT); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableFT");
			}
		}

		public function editByTit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tableFT, $data)
					->where('fk_titular', $id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Id actualizado: '.$id); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableFT); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableI");
			}
		}

        // Eliminar ficha_tecnica
		public function del($id){
			$set = array('estatus' => 0,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->tableFT)
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
				->from($this->tableFT)
				->where($field, $value)
				->where('estatus', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}
	}
?>