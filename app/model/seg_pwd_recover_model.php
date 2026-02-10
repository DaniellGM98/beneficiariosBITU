<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;

	class SegPwdRecoverModel {
		private $db;
		private $table = 'seg_recuperar_contrasena'; 
		private $response;

		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Función para obtener un registro de la base de datos por medio del ID
		public function get($id_seg_pwd_recover) {
			$this->response->result = $this->db
				->from($this->table)
				->where('id', $id_seg_pwd_recover)
				->fetch();

			return $this->response->SetResponse(true, ($this->response->result? '': 'No existe registro con ese ID'));
		}

		// Función para obtener un registro de la base de datos por medio de la clave generada
		public function getByCodigo($codigo) {
			$this->response->result = $this->db
				->from($this->table)
				->where('codigo', $codigo)
				->orderBy('fecha_modificacion desc')
				->fetch();

			return $this->response->SetResponse(true, ($this->response->result? '': 'No existe registro con ese codigo'));
		}

		// Función para agregar un nuevo registro a la base de datos
		public function add($data) {
			try {
				$this->response->result = $this->db
					->insertInto($this->table, $data)
					->execute();

				if($this->response->result) {
					return $this->response->SetResponse(true, 'id_seg_pwd_recover del registro: '.$this->response->result);
				} else {
					return $this->response->SetResponse(false, 'no se inserto el registro');
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, 'catch: add model seg_pwd_recover');
			}
		}

		// Función para editar un registro de la tabla mediante el ID del registro
		public function edit($data, $id_seg_pwd_recover) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id', $id_seg_pwd_recover)
					->execute();
					
				if($this->response->result) {
					return $this->response->SetResponse(true, 'id_seg_pwd_recover actualizado: '.$id_seg_pwd_recover);    
				} else {
					return $this->response->SetResponse(false, 'no se edito el registro');
				}
			}catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, 'catch: edit model seg_pwd_recover');
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