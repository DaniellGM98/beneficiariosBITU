<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;

	class EgresosModel {
		private $db;
        private $tableET = 'egresos_titular';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

        // Agregar egresos
		public function add($data){
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$resultado = $this->db
								->insertInto($this->tableET, $data)
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
				return $this->response->SetResponse(false, "No se inserto el registro en ".$this->tableET);
			}	
		}

        // Obtener egresos por id
		public function get($id) {
			$egresos = $this->db
				->from($this->tableET)
				->where('id_egresos', $id)
				->fetch();
			if($egresos) {
				$this->response->result = $egresos;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableET);
			}
        }

        // Obtener egresos por titular
		public function getByTit($id) {
			$egresos = $this->db
				->from($this->tableET)
				->where('fk_titular', $id)
				->fetch();
			if($egresos) {
				$this->response->result = $egresos;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableET);
			}
        }

        // Editar egresos
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tableET, $data)
					->where('id_egresos', $id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Id actualizado: '.$id); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableET); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableET");
			}
		}

        // Eliminar egresos
		public function del($id){
			$set = array('estatus' => 0,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->tableET)
				->set($set)
				->where('id_egresos', $id)
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
				->from($this->tableET)
				->where($field, $value)
				->where('estatus', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}
	}
?>