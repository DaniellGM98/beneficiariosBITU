<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response,
    	Envms\FluentPDO\Literal;

	class SegLogModel {
		private $db;
		private $table = 'seg_log';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Agregar log
		public function add($descripcion, $registro, $tipo, $mostrar=0){
			if (!isset($_SESSION)) {
				session_start();
			}
			if(isset($_SESSION['usuario'])){
				$user = $_SESSION['usuario']->id_usuario;
				$sesion = $_SESSION['id_sesion'];
			}else{
				$user = 1;
				$sesion = 1;
			}
			$data = array(
				'usuario_id' => $user, 
				'seg_sesion_id' => $sesion, 
				'descripcion' => $descripcion, 
				'registro' => $registro,
				'mostrar' => $mostrar,
				'tabla' => $tipo);
			try {
				$this->response->result = $this->db
					->insertInto($this->table, $data)
					->execute();
				if($this->response->result != 0){
					$this->response->SetResponse(true, 'id_seg_log del registro: '.$this->response->result);    	
					$dataSesion = array('finalizada' => new Literal('NOW()'));
					$this->db->update('seg_sesion', $dataSesion, $_SESSION['id_sesion'])->execute();
				}
				else { $this->response->SetResponse(false, 'No se inserto el registro'); }
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, 'catch: Add model seg_log');
			}
			return $this->response;
		}

		// find by field = value
		public function findBy($field, $value){
			$this->response->result = $this->db
				->from($this->table)
				->where($field, $value)
				//->where('estado', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}
	}
?>