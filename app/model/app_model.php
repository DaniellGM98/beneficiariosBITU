<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;

	class AppModel {
		private $db;
		private $tableT = 'cat_titular';
		private $tableI = 'cat_integrante';
		private $tableF = 'faltas';
		private $tablePL = 'pase_lista_qr';
		private $tableAU = 'access_user';
		private $tableAQR = 'asistencia_qr';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Ruta para obtener listas
		public function getListas() {
			$lista = $this->db
				->from($this->tableT)
                ->select(null)->select("DISTINCT lista")
				->where('estatus', 1)
                ->where('lista != 0')
				->orderBy('lista')
				->fetchAll();
			if($lista!="")	{
				return $lista;
			}else{
				return $this->response->SetResponse(false, 'No existe el registro en '.$this->tableT);
			}
		}

        // Ruta para obtener beneficiarios
		public function getIntegrantes($lista, $pagina) {
			ini_set('memory_limit', '64M');
			$desde=2000*$pagina;
			$integrantes = $this->db
				->from($this->tableT)
				->select(null)->select("$this->tableT.id_titular, $this->tableT.credencial, $this->tableT.fecha_visita, $this->tableT.fk_municipio, $this->tableT.estatus, $this->tableT.fecha_baja, $this->tableT.faltas, $this->tableT.ultima_visita, $this->tableT.ultima_falta, $this->tableT.bajas, $this->tableT.tipo, $this->tableT.observaciones_asist, $this->tableI.id_integrante, $this->tableI.fk_titular, $this->tableI.parentesco, $this->tableI.nombre, $this->tableI.apaterno, $this->tableI.amaterno, $this->tableT.lista")
				->innerJoin($this->tableI." ON $this->tableT.id_titular = $this->tableI.fk_titular")
				->where("$this->tableT.lista", $lista)
				->where("$this->tableI.parentesco", 'TITULAR')
				->limit("$desde,2000")
				->fetchAll();
			if($integrantes!="")	{
				return $integrantes;
			}else{
				return $this->response->SetResponse(false, 'No existe el registro en '.$this->tableT+' y '.$this->tableI);
			}
		}

		// Ruta para obtener listas
		public function getUltimoPaseLista($lista) {
			$ultimoPase = $this->db
				->from($this->tablePL)
                ->select(null)->select("fecha")
				->where('lista', $lista)
				->orderBy('id_pase_lista DESC')
				->limit('1')
				->fetchAll();
			if($ultimoPase!="")	{
				return $ultimoPase;
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tablePL);
			}
		}

		// ruta para verificar si cuenta con falta en periodo solicitado
		public function getFaltasComunidades($id_titular, $fecha){
			$fecha_fin = date('Y-m-d', strtotime($fecha . ' + 15 days'));
			$resultado = $this->db
				->from($this->tableF)
				->where('fk_titular', $id_titular)
				->where("DATE(fecha) >= ?", $fecha)
				->where("DATE(fecha) <= ?", $fecha_fin)
				->where('estatus', 1)
				->fetch();
			if (!empty($resultado)) {
				$this->response->result = $resultado;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, 'No existe el registro en '.$this->tableF);
			}
		}

		public function delFaltaComunidades($id) {
			$data['estatus'] = 0;
			try {
				$this->response->result = $this->db
					->update($this->tableF, $data)
					->where('id', $id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Falta actualizada: '.$id); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableF); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tablePL");
			}
		}

		// Ruta para verificar login
		public function postAccessUser($username, $password) {
			$fecha= date("Y-m-d H-i-s");
			$postAccessUser = $this->db
				->from($this->tableAU)
				->where('username', $username)
				->where('password', $password)
				->where('status', '1')
				->fetch();
			if($postAccessUser!="")	{
				$this->lastAccess($fecha, $username, $password);
				return array('error' => false,'mensaje' => $postAccessUser->username,'id_usuario' => $postAccessUser->id_usuario);
			}else{
				return array('error' => true, 'mensaje' => 'Usuario o contraseña incorrectos, vuelva a intentarlo');
			}
		}

		// Editar ultimo acceso login
		public function lastAccess($fecha, $username, $password) {
			$data = [
				'last_access'=> $fecha
			];
			try {
				$this->response->result = $this->db
					->update($this->tableAU, $data)
					->where('username', $username)
					->where('password', $password)
					->where('status', '1')
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Lats_Access actualizado: '.$username); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableAU); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableAU");
			}
		}

		// Ruta para agregar asistencia qr
		public function postAsistencia($fk_titular, $fecha) {
			return $this->db
				->from($this->tableAQR)
				->where('fk_titular', $fk_titular)
				->where('fecha', $fecha)
				->fetch();
		}

		// Agregar asistencia qr
		public function addAsistenciaQR($fk_titular, $fecha, $usuario) {
			$data = [
				'fk_titular'=> $fk_titular,
				'fecha'=> $fecha,
				'usuario'=> $usuario
			];
			try {
				$this->response->result = $this->db
					->insertInto($this->tableAQR, $data)
					->execute();
				if($this->response->result != 0){
					return array('response'=>true,'mensaje'=>'La consulta fue ejecutada correctamente');
				}else { 
					return array('response'=>false,'mensaje'=>"Error al ejecutar la consulta en ".$this->tableAQR);
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Add model $this->tableAQR");
			}
		}

		// Ruta para actualizar despues de asistencia qr
		public function actualizar($id_titular, $ultima_visita, $observaciones_asist) {
			$data = [
				//'ultima_visita'=> $ultima_visita,
				'ultima_visita'=> date('Y-m-d', strtotime($ultima_visita)),
				'observaciones_asist'=> $observaciones_asist,
				'faltas'=> 0
			];
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tableT, $data)
					->where('id_titular', $id_titular)
					->where('estatus', '1')
					->execute();
				if($this->response->result!=0) { 
					return array('response'=>true,'mensaje'=>'La consulta fue ejecutada correctamente'); 
				} else { 
					return array('response'=>false,'mensaje'=>"Error al ejecutar la consulta en ".$this->tableT); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableT");
			}
		}

		// find by field = value
		public function findBy($field, $value){
			$this->response->result = $this->db
				->from($this->tableT)
				->where($field, $value)
				->where('estatus', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}

		// Ruta para obtener usuarios por fecha
		public function getUsuariosByFecha($fechaInicio, $fechaFin) {
			return $this->db	
				->from($this->tableAQR)	
                ->select(null)->select("id_usuario, nombre, apellidos")	
				->innerJoin($this->tableAU.' ON id_usuario = usuario')	
				->where("DATE_FORMAT(fecha, '%Y-%m-%d') >= ?", $fechaInicio)
				->where("DATE_FORMAT(fecha, '%Y-%m-%d') <= ?", $fechaFin)
				->groupBy('usuario')
				->fetchAll();
		}

		public function getListasByFecha($fechaInicio, $fechaFin) {
			return $this->db	
				->from($this->tableAQR)	
				->select(null)->select("lista")
				->innerJoin('cat_titular ON id_titular = fk_titular')	
				->where("DATE_FORMAT(fecha, '%Y-%m-%d') >= ?", $fechaInicio)
				->where("DATE_FORMAT(fecha, '%Y-%m-%d') <= ?", $fechaFin)
				->groupBy('lista')	
				->fetchAll();	
		}		

		// Ruta para obtener lista por usuario
		public function getListaByUser($usuario, $fechaInicio, $fechaFin) {
			return $this->db	
				->from($this->tableT)->disableSmartJoin()	
				/*->select(null)->select("DISTINCT cat_titular.*, CONCAT_WS(' ', nombre, apaterno, amaterno) AS nombre, DATE_FORMAT(asistencia_qr.fecha,'%H:%i') AS hora, faltas")*/
				->select(null)->select("DISTINCT cat_titular.*, CONCAT_WS(' ', nombre, apaterno, amaterno) AS nombre, asistencia_qr.fecha AS hora, faltas")
				->innerJoin($this->tableAQR.' ON fk_titular = id_titular')	
				->innerJoin($this->tableI." ON cat_integrante.fk_titular = id_titular AND parentesco = 'TITULAR'")	
				->where("usuario", "$usuario")	
				
				//->where("DATE_FORMAT(asistencia_qr.fecha,'%Y-%m-%d')", "$fecha")

				->where("DATE_FORMAT(asistencia_qr.fecha, '%Y-%m-%d') >= ?", $fechaInicio)
				->where("DATE_FORMAT(asistencia_qr.fecha, '%Y-%m-%d') <= ?", $fechaFin)

				->orderBy("asistencia_qr.fecha DESC, nombre")	
				->fetchAll();	
		}	

		// Ruta para obtener lista por numero de lista
		public function getListaByNum($lista, $fechaInicio, $fechaFin) {
			return $this->db->getPdo()->query(	
				/*"SELECT cat_titular.*, CONCAT_WS(' ', nombre, apaterno, amaterno) AS nombre, DATE_FORMAT(asistencia_qr.fecha,'%H:%i') AS hora, faltas*/
				"SELECT cat_titular.*, CONCAT_WS(' ', nombre, apaterno, amaterno) AS nombre, asistencia_qr.fecha AS hora, faltas
				FROM cat_titular 	
				INNER JOIN cat_integrante 	
				ON cat_integrante.fk_titular = id_titular AND parentesco = 'TITULAR' 	
				LEFT JOIN asistencia_qr 	
				ON asistencia_qr.fk_titular = id_titular 
				
				/*AND DATE_FORMAT(asistencia_qr.fecha, '%Y-%m-%d') = '$fecha' */

				AND DATE_FORMAT(asistencia_qr.fecha, '%Y-%m-%d') >= '$fechaInicio' 
				AND DATE_FORMAT(asistencia_qr.fecha, '%Y-%m-%d') <= '$fechaFin' 

				WHERE lista = $lista AND cat_titular.estatus = 1 	
				ORDER BY CASE WHEN asistencia_qr.fecha IS NULL THEN 0 ELSE 1 END DESC, fecha DESC, nombre, apaterno, amaterno;"	
			)->fetchAll();	
		}	
	}
?>