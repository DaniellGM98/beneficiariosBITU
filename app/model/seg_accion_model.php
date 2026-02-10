<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;

	class SegAccionModel {
		private $db;
		private $table = 'seg_accion';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Obtener los datos de seg_accion por medio del ID
		public function get($id) {
			$this->response->result = $this->db
				->from($this->table)
				->where('id', $id)
				->fetch();
			if($this->response->result)	{
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, 'no existe el registro');
			}
		}

		// Obtener los datos de los seg_accion
		public function getAll() {
			$this->response->result = $this->db
				->from($this->table)
				->where('estado', 1)
				->fetchAll();
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->where('estado', 1)
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

		// Obtener los datos de los seg_accion mediante el ID del modulo
		public function getByModulo($seg_modulo_id) {
			$this->response->result = $this->db
				->from($this->table)
				->where('seg_modulo_id', $seg_modulo_id)
				->where('estado', 1)
				->fetchAll();
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->where('estado', 1)
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

		// Agregar un seg_accion
		public function add($data) {
			try {
				$this->response->result = $this->db
					->insertInto($this->table, $data)
					->execute();
				if($this->response->result != 0){
					return $this->response->SetResponse(true, 'id del registro: '.$this->response->result);
				}else { 
					return $this->response->SetResponse(false, 'No se inserto el registro'); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Add model $this->table");
			}
		}

		// Modificar un seg_accion
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id', $id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, "id actualizado: $id"); 
				}else { 
					return $this->response->SetResponse(false, 'No se edito el registro'); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->table");
			}
		}

		// Dar de baja un seg_accion
		public function del($id) {
			try{
				$data['estado'] = 0;
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id', $id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, "id baja: $id"); 
				}else { 
					return $this->response->SetResponse(false, 'no se dio de baja el registro'); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: del model $this->table");
			}
		}

		// find by field = value
		public function findBy($field, $value){
			$this->response->result = $this->db
				->from($this->table)
				->where($field, $value)
				->where('estado', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}
	}
?>