<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;

	class HistorialModel {
		private $db;
        private $tableHN = 'historial_nutricion';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Agregar historial
		public function add($data){
			$data['fecha'] = date('Y-m-d H:i:s');
			try{
				$resultado = $this->db
								->insertInto($this->tableHN, $data)
								->execute();
			}catch(\PDOException $ex){
				$this->response->result = $resultado;
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, 'catch: Add model historial');	
			}
			if($resultado!=0){
				$this->response->result = $resultado;
				return $this->response->SetResponse(true, 'Id del registro: '.$resultado);    
			}else{
				$this->response->result = $resultado;
				return $this->response->SetResponse(false, "No se inserto el registro en ".$this->tableHN);
			}	
		}

		// Obtener historial por id
		public function get($id) {
			$historial = $this->db
				->from($this->tableHN)
				->where('id_historial', $id)
				->fetch();
			if($historial) {
				$this->response->result = $historial;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableHN);
			}
        }

		// Obtener historial por titular
		public function getByInte($id) {
			$historial = $this->db
				->from($this->tableHN)
				->where('fk_integrante', $id)
				->orderBy('fecha')
				->fetchAll();
			if($historial) {
				$this->response->result = $historial;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->tableHN);
			}
        }

		// Obtener IMC por Integrante
		public function getIMC($id) {
			$historial = $this->db
				->from($this->tableHN)
				->where('fk_integrante', $id)
				->orderBy('fecha DESC')
				->fetch();
			if($historial) {
				if($historial->imc==""){
					if($historial->peso_talla==""){
						if($historial->peso_edad==""){
							return "";
						}else{
							return $historial->peso_edad;
						}
					}else{
						return $historial->peso_talla;
					}
				}else{
					return $historial->imc;
				}								
			}else{
				return "";
			}
        }

		// Editar historial por fk_integrante
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->tableHN, $data)
					->where('fk_integrante', $id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Id actualizado: '.$id); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->tableHN); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->tableHN");
			}
		}

		// Eliminar historial
		public function del($id){
			$set = array('estatus' => 0,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->tableHN)
				->set($set)
				->where('id_historial', $id)
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
				->from($this->tableHN)
				->where($field, $value)
				->where('estatus', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}

		public function calcularIMC($edad, $sexo, $peso, $talla){
			include_once('../public/core/antro.php');

			$imc = ''; $te = '';	$pt = '';	$pe = '';

			if($edad < 25){													// LACTANTES
				// PESO / EDAD
				$rango = $arrPELac[$sexo][$edad];
					if($peso <= $rango[0]){									// -3 y menos
						$pe = 'Desnutrición Grave';
					}else if($peso > $rango[0] && $peso <= $rango[1]){		// -2 a -2.99
						$pe = 'Desnutrición moderada';
					}else if($peso > $rango[1] && $peso <= $rango[2]){		// -1 a -1.99
						$pe = 'Desnutrición leve';
					}else if($peso > $rango[2] && $peso <= $rango[4]){		// -1 a +1
						$pe = 'Peso Normal';
					//}else if($peso > $rango[3] && $peso <= $rango[4]){		// 1
	
					}else if($peso > $rango[4] && $peso <= $rango[5]){		// +1 a +1.99
						$pe = 'Sobrepeso';
					}else if($peso > $rango[5] /*&& $peso <= $rango[6]*/){		// +2 a + 3 [MAYOR QUE +2]
						$pe = 'Obesidad';
					}
	
				// TALLA / EDAD
				$rango = $arrTELac[$sexo][$edad];
					if($talla <= $rango[1]){									// -2 y menos
						$te = 'Baja';
					}else if($talla > $rango[1] && $talla <= $rango[2]){		// -1 a -1.99
						$te = 'Ligeramente Baja';
					}else if($talla > $rango[2] && $talla <= $rango[4]){		// -1 a +1
						$te = 'Estatura Normal';
					}else if($talla > $rango[4] && $talla <= $rango[5]){		// +1 a +1.99
						$te = 'Ligeramente Alta';
					}else if($talla > $rango[5]){								// +2 a + 3 [MAYOR QUE +2]
						$te = 'Alta';
					}
	
				// PESO / TALLA
				$rango = $arrPTLac[$sexo][$this->roundTalla($talla)];
					if($peso <= $rango[0]){									// -3 y menos
						$pt = 'Desnutrición Grave';
					}else if($peso > $rango[0] && $peso <= $rango[1]){		// -2 a -2.99
						$pt = 'Desnutrición moderada';
					}else if($peso > $rango[1] && $peso <= $rango[2]){		// -1 a -1.99
						$pt = 'Desnutrición leve';
					}else if($peso > $rango[2] && $peso <= $rango[4]){		// -1 a +1
						$pt = 'Peso Normal';
					}else if($peso > $rango[4] && $peso <= $rango[5]){		// +1 a +1.99
						$pt = 'Sobrepeso';
					}else if($peso > $rango[5]){							// +2 a + 3 [MAYOR QUE +2]
						$pt = 'Obesidad';
					}
			}else if($edad > 24 && $edad < 61){								// PREESCOLARES
				// PESO / EDAD
				$rango = $arrPEPre[$sexo][$edad];
					if($peso <= $rango[0]){									// -3 y menos
						$pe = 'Desnutrición Grave';
					}else if($peso > $rango[0] && $peso <= $rango[1]){		// -2 a -2.99
						$pe = 'Desnutrición moderada';
					}else if($peso > $rango[1] && $peso <= $rango[2]){		// -1 a -1.99
						$pe = 'Desnutrición leve';
					}else if($peso > $rango[2] && $peso <= $rango[4]){		// -1 a +1
						$pe = 'Peso Normal';
					}else if($peso > $rango[4] && $peso <= $rango[5]){		// +1 a +1.99
						$pe = 'Sobrepeso';
					}else if($peso > $rango[5]){							// +2 a + 3 [MAYOR QUE +2]
						$pe = 'Obesidad';
					}
	
				// TALLA / EDAD
				$rango = $arrTEPre[$sexo][$edad];
					if($talla <= $rango[1]){								// -2 y menos
						$te = 'Baja';
					}else if($talla > $rango[1] && $talla <= $rango[2]){	// -1 a -1.99
						$te = 'Ligeramente Baja';
					}else if($talla > $rango[2] && $talla <= $rango[4]){	// -1 a +1
						$te = 'Estatura Normal';
					}else if($talla > $rango[4] && $talla <= $rango[5]){	// +1 a +1.99
						$te = 'Ligeramente Alta';
					}else if($talla > $rango[5]){							// +2 a + 3 [MAYOR QUE +2]
						$te = 'Alta';
					}
	
				// PESO / TALLA
				$rango = $arrPTPre[$sexo][$this->roundTalla($talla)];
					if($peso <= $rango[0]){									// -3 y menos
						$pt = 'Desnutrición Grave';
					}else if($peso > $rango[0] && $peso <= $rango[1]){		// -2 a -2.99
						$pt = 'Desnutrición moderada';
					}else if($peso > $rango[1] && $peso <= $rango[2]){		// -1 a -1.99
						$pt = 'Desnutrición leve';
					}else if($peso > $rango[2] && $peso <= $rango[4]){		// -1 a +1
						$pt = 'Peso Normal';
					}else if($peso > $rango[4] && $peso <= $rango[5]){		// +1 a +1.99
						$pt = 'Sobrepeso';
					}else if($peso > $rango[5]){							// +2 a + 3 [MAYOR QUE +2]
						$pt = 'Obesidad';
					}
			}else if($edad > 60 && $edad < 121){							// ESCOLARES
				// PESO / EDAD
				$rango = $arrPEEsc[$sexo][$edad];
					if($peso < $rango[0]){									// menor que -3
						$pe = 'Peso Bajo Severo';
					}else if($peso >= $rango[0] && $peso < $rango[1]){		// menor que -2
						$pe = 'Peso Bajo';
					}else if($peso >= $rango[1] && $peso < $rango[4]){		// de -2 a +1
						$pe = 'Peso Normal';
					}else if($peso >= $rango[4] && $peso < $rango[5]){		// mayor que +1
						$pe = 'Con riesgo de sobrepeso';
					}else if($peso >= $rango[5] && $peso < $rango[6]){		// mayor que +2
						$pe = 'Sobrepeso';
					}else if($peso >= $rango[6]){							// mayor que +3
						$pe = 'Problema de crecimiento';
					}
	
				// TALLA / EDAD
				$rango = $arrTEEsc[$sexo][$edad];
					if($talla <= $rango[0]){									// menor que -3
						$te = 'Talla Baja Severa';
					}else if($talla > $rango[0] && $talla <= $rango[1]){	// menor que -2
						$te = 'Talla Baja';
					}else if($talla > $rango[1] && $talla <= $rango[6]){	// de -2 a +3
						$te = 'Talla Normal';
					}else if($talla > $rango[6]){							// mayor que +3
						$te = 'Talla Muy Alta';
					}
	
				// IMC
				$talla = $talla / 100;
				$talla = $talla * $talla;
				$imc = $peso / $talla;
				$rango = $arrIMCEsc[$sexo][$edad];
					if($imc < $rango[0]){									// menor que -3
						$imc = 'Delgadez Severa';
					}else if($imc >= $rango[0] && $imc < $rango[1]){		// menor que -2
						$imc = 'Delgadez';
					}else if($imc >= $rango[1] && $imc < $rango[4]){		// de -2 a +1
						$imc = 'Normal';
					}else if($imc >= $rango[4] && $imc < $rango[5]){		// mayor que +1
						$imc = 'Sobrepeso';
					}else if($imc >= $rango[5]){							// mayor que +2
						$imc = 'Obesidad';
					}
			}else if($edad > 120 && $edad < 217){							// ADOLECENTES
				// IMC
				$talla = $talla / 100;
				$talla = $talla * $talla;
				$imc = $peso / $talla;
				$rango = $arrIMCAdo[$sexo][$edad];
					if($imc < $rango[0]){									// menor que -3
						$imc = 'Delgadez Severa';
					}else if($imc >= $rango[0] && $imc < $rango[1]){		// menor que -2
						$imc = 'Delgadez';
					}else if($imc >= $rango[1] && $imc < $rango[4]){		// de -2 a +1
						$imc = 'Normal';
					}else if($imc >= $rango[4] && $imc < $rango[5]){		// mayor que +1
						$imc = 'Sobrepeso';
					}else if($imc >= $rango[5]){							// mayor que +2
						$imc = 'Obesidad';
					}
			}else if($edad > 216){											// ADULTOS
				$talla = $talla / 100;
				$talla = $talla * $talla;
				$imc = $peso / $talla;
				
				if($imc < 18.5){
					$imc = 'Bajo peso';
				}else if($imc > 18.4 && $imc < 25){
					$imc = 'Normal';
				}else if($imc > 24.9 && $imc < 30){
					$imc = 'Sobrepeso';
				}else if($imc > 29.9 && $imc < 35){
					$imc = 'Obesidad I';
				}else if($imc > 34.9 && $imc < 40){
					$imc = 'Obesidad II';
				}else if($imc > 39.9){
					$imc = 'Obesidad III';
				}
			}

			return array($imc, $te, $pt, $pe);
		}

		function roundTalla($talla){
			$talla = $talla * 10;
			$talla = round($talla/5) * 5;
			$talla = $talla / 10;
			return $talla;
		}


	}
?>