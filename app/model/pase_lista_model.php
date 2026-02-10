<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;
	use Envms\FluentPDO\Literal;

	class PaseListaModel {
		private $db;
        private $tablePL = 'pase_lista';
        private $tableA = 'asistencia';
		private $tableT = 'cat_titular';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

        // Obtener falta de titular
        public function getFaltas($fecha) {
            $hoy = date('w', strtotime($fecha));
			return $this->db->getPdo()->query(
				"SELECT id_titular, ultima_visita, ultima_falta, faltas
                FROM $this->tableT 
                    WHERE fecha_visita = $hoy AND estatus = 1 AND tipo != 3 
					AND lista = 1
					AND (SELECT COUNT(*) FROM cat_integrante where id_titular = fk_titular AND estatus = 1 AND parentesco='TITULAR') > 0
                        AND (SELECT COUNT(*) FROM $this->tableA WHERE fk_titular = id_titular AND DATE_FORMAT(fecha, '%Y-%m-%d') = DATE_FORMAT('$fecha', '%Y-%m-%d')) = 0 ;"
			)->fetchAll();
		}

        // Editar faltas de titular
        public function editFaltas($id, $faltas, $baja, $fecha) {
            $cantidad=1;
            if($baja==""){
                $data = [
                    'faltas'=> $faltas,
                    'ultima_falta'=>$fecha,
					'fecha_modificacion'=> date('Y-m-d H:i:s')
                ];
            }else{
                $data = [
                    'faltas'=> $faltas,
                    'ultima_falta'=> $fecha,
                    'estatus'=>'2',
                    'bajas'=> new Literal('(bajas + '.$cantidad.')'),
                    'fecha_baja'=> $fecha.date(' H:m:s'),
					'fecha_modificacion'=> date('Y-m-d H:i:s')
                ];
            }
			try {
				return $this->db
					->update($this->tableT, $data)
					->where('id_titular', $id)
					->execute();
			} catch(\PDOException $ex) {
				return "0";
			}
		}

        // Agregar pase_lista
		public function addPaseLista($user, $userName, $numFaltas, $numBajas, $fecha){
			$fecha .= date(' H:i:s');
			$data = [
                'fk_usuario'=>$user,
				'fecha'=> $fecha,
				'usuario'=>$userName,
                'faltas'=>$numFaltas,
                'bajas'=> $numBajas
			];
			try{
				return $resultado = $this->db
								->insertInto($this->tablePL, $data)
								->execute();
			}catch(\PDOException $ex){
				return "0";
			}
		}

        // Agregar pase_lista con response
		public function add($data){
            $data['fecha'] = date('Y-m-d H:i:s');
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$resultado = $this->db
								->insertInto($this->tablePL, $data)
								->execute();
			}catch(\PDOException $ex){
				$this->response->result = $resultado;
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Add model ". $this->tablePL);	
			}
			if($resultado!=0){
				$this->response->result = $resultado;
				return $this->response->SetResponse(true, 'Id del registro: '.$resultado);    
			}else{
				$this->response->result = $resultado;
				return $this->response->SetResponse(false, "No se inserto el registro en ".$this->tablePL);
			}	
		}

        // Obtener pase_lista por id
		public function get($id) {
			$pase_lista = $this->db
				->from($this->tablePL)
				->where('id_pase_lista', $id)
				->fetch();
			if($pase_lista) {
				$this->response->result = $pase_lista;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tablePL);
			}
        }

		public function getByFecha($fecha) {
			return  $this->db
				->from($this->tablePL)
				->where("DATE_FORMAT(fecha,'%Y-%m-%d') = '$fecha'")
				->where('estatus', 1)
				->fetch();
        }

        // Editar pase_lista
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tablePL, $data)
					->where('id_pase_lista', $id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Id actualizado: '.$id); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tablePL); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tablePL");
			}
		}

        // Eliminar pase_lista
		public function del($id){
			$set = array('estatus' => 0,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->tablePL)
				->set($set)
				->where('id_pase_lista', $id)
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
				->from($this->tablePL)
				->where($field, $value)
				->where('estatus', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}
	}
?>