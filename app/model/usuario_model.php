<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;
	use Slim\Http\UploadedFile;
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	require '../vendor/autoload.php';

	class UsuarioModel {
		private $db;
		private $table = 'access_user';
		private $tableP = 'seg_permiso';
		private $tableA = 'seg_accion';
		private $tableM = 'seg_modulo';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
			require_once './core/defines.php';
		}

		// Obtener usuario por id
		public function get($id) {
			$usuario = $this->db
				->from($this->table)
				->where('id_usuario', $id)
				->fetch();
			if($usuario) {
				unset($usuario->contrasena);
				$this->response->result = $usuario;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->table);
			}
		}

		// Agregar usuario
		public function add($data){
			$data['password'] = strrev(md5(sha1($data['password'])));
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$resultado = $this->db
								->insertInto($this->table, $data)
								->execute();
			}catch(\PDOException $ex){
				$this->response->result = $resultado;
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, 'catch: Add model usuario');	
			}
			if($resultado!=0){
				$this->response->result = $resultado;
				return $this->response->SetResponse(true, 'Id del registro: '.$resultado);    
			}else{
				$this->response->result = $resultado;
				return $this->response->SetResponse(false, "No se inserto el registro en ".$this->table);
			}	
		}

		// Obtener todos los usuarios
		public function getAll($pagina, $limite, $usuario_tipo_id, $busqueda) {
			if($usuario_tipo_id==null){
				$usuario_tipo_id=0;
			}
			$inicial = $pagina * $limite;
			$busqueda = $busqueda==null ? "_" : $busqueda;
			$usuario = $this->db
				->from($this->table)
                ->select(null)->select("id_usuario, tipo, nombre, apellidos, username, email, last_access, status")
				->where("CONCAT_WS(' ', nombre, apellidos, email, username) LIKE '%$busqueda%'")
				->where("tipo".($usuario_tipo_id==0? ">": "=").$usuario_tipo_id)
				->where("status", 1)
				->limit("$inicial, $limite")
				->orderBy('apellidos ASC, nombre ASC')
				->fetchAll();
			$this->response->result = $usuario;
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->where("CONCAT_WS(' ', nombre, apellidos, email, username) LIKE '%$busqueda%'")
				->where("tipo".($usuario_tipo_id==0? ">": "=").$usuario_tipo_id)
				->where("status", 1)
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

		// Editar usuario
		public function edit($data, $id) {
			if($data['password']==''){
				unset($data['password']);
			}else{
				if(isset($data['password'])) $data['password'] = strrev(md5(sha1($data['password'])));
			}
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id_usuario', $id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Id actualizado: '.$id); 
				} else { 
					return $this->response->SetResponse(false, "No se edito el registro en ".$this->table); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->table");
			}
		}

		// Eliminar usuario
		public function del($id){
			$set = array('status' => 0,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->table)
				->set($set)
				->where('id_usuario', $id)
				->execute();
			if($id!=0){
				return $this->response->SetResponse(true, "Id baja: $id");
			}else{
				return $this->response->SetResponse(true, "Id incorrecto");
			}
		}

		// inicio de sesión
		public function login($username, $password) {
			$password = strrev(md5(sha1($password)));
			$usuario = $this->db
				->from($this->table)
				->where('username', $username)
				->where('password', $password)
				//->where('tipo != 3')
				->where('status', 1)
				->fetch();
			if(is_object($usuario)) {
				unset($usuario->password);
				$this->ultimoAcceso($usuario->id_usuario);
				$newModulos = array();
				$newModulos = $this->getPermisos($usuario->id_usuario);
				$this->addSessionLogin($usuario, $newModulos);
				$this->response->SetResponse(true, 'Acceso correcto');
			} else {
				$this->response->SetResponse(false, 'Error Login, Verifica tus datos');
			}
			$this->response->result = $usuario;
			return $this->response;
		}

		// Modificar ultimo acceso
		public function ultimoAcceso($id) {
			date_default_timezone_set('America/Mexico_City');
			$data['last_access'] = date("Y-m-d H:i:s");
			try {
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id_usuario', $id)
					->execute();
				if($this->response->result!=0) { 
					return $this->response->SetResponse(true, 'Id actualizado: '.$id);
				}else { 
					return $this->response->SetResponse(false, "No se edito el ultimo acceso en ".$this->table);
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Edit model $this->table");
			}
		}

		// Obtener permisos
		public function getPermisos($usuario){
			$newModulos = array();
			$modulos = $this->getModulos();
			foreach ($modulos as $modulo) {
				$acciones = $this->getAcciones($usuario, $modulo->id);
				$contador = count($acciones);
				$accionesUrl = 0;
				if($contador>0){
					$modulo->acciones = $acciones;
					foreach ($acciones as $accion)
						if($accion->url != '') $accionesUrl++;
					$newModulos[] = $modulo;  
				}	
				$modulo->accionesUrl = $accionesUrl;
			}
			return $newModulos;
		}

		// Obtener modulos de permisos
		public function getModulos(){
			return $this->db
				->from($this->tableM)
				->where('estado', 1)
				->orderBy('orden')
				->fetchAll();
		}

		// Obtener acciones de permisos
		public function getAcciones($usuario_id, $seg_modulo_id){
			return $this->db
				->from($this->tableP)
				->select(null)->select("DISTINCT $this->tableA.id, $this->tableA.nombre, $this->tableA.url, $this->tableM.id_html, $this->tableA.icono")
				->innerJoin("$this->tableA on $this->tableA.id = $this->tableP.seg_accion_id")
				->innerJoin("$this->tableM on $this->tableM.id = $this->tableA.seg_modulo_id")
				->where("$this->tableP.usuario_id", $usuario_id)
				->where(intval($seg_modulo_id)>0? "$this->tableA.seg_modulo_id = $seg_modulo_id": "TRUE")
				->where("$this->tableA.estado", 1)
				->fetchAll();
		}

		// Agregar session
		public function addSessionLogin($usuario, $permisos){
			$browser = $_SERVER['HTTP_USER_AGENT'];
			$ipAddr = $_SERVER['REMOTE_ADDR'];
			if (!isset($_SESSION)) { session_start(); }
			$_SESSION['ip']  = $ipAddr;
			$_SESSION['navegador']  = $browser;
			$_SESSION['usuario']  = $usuario;
			$_SESSION['permisos']  = $permisos;
		}

		// Cambiar contraseña de usuario
		public function changePassword($data, $id) {
			$old_password = strrev(md5(sha1($data['old_password'])));
			$password = [
				'password'=>strrev(md5(sha1($data['new_password'])))
			];
			$this->response->result = $this->db
				->update($this->table, $password)
				->where('id_usuario', $id)
				->where('password', $old_password)
				->execute();
			if($this->response->result == 1) { 
				return $this->response->SetResponse(true, 'contraseña actualizada'); 
			}else { 
				return $this->response->SetResponse(false, 'Verifica la contraseña actual'); 
			}
		}

		/* 

		// Buscar usuario
		public function find($busqueda, $usuario_tipo=0) {
			if($usuario_tipo==null){
				$usuario_tipo=0;
			}
			$usuarios = $this->db
				->from($this->table)
				->where("CONCAT_WS(' ', nombre, apellidos, email, username) LIKE '%$busqueda%'")
				->where("tipo".($usuario_tipo==0? ">": "=").$usuario_tipo)
				->where("estado", 1)
				->fetchAll();
			foreach($usuarios as $usuario) { unset($usuario->contrasena); }
			$this->response->result = $usuarios;
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select("COUNT(*) AS total")
				->where("CONCAT_WS(' ', nombre, apellidos, email, username) LIKE '%$busqueda%'")
				->where("tipo".($usuario_tipo==0? ">": "=").$usuario_tipo)
				->where("estado", 1)
				->fetch()
				->total;
			return $this->response->SetResponse(true);
		} */

		function moveUploadedFile($directory, UploadedFile $uploadedFile, $filename) {
			$uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
			return $filename;
		}

		// find by field = value
		public function findBy($field, $value){
			$this->response->result = $this->db
				->from($this->table)
				->where($field, $value)
				->where('status', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}

		public function resize($img, $thumb_width, $newfilename){
			$max_width=$thumb_width;
	  
			//Check if GD extension is loaded
			if (!extension_loaded('gd') && !extension_loaded('gd2')){
					trigger_error("GD is not loaded", E_USER_WARNING);
				return false;
			}
		
			//Get Image size info
			list($width_orig, $height_orig, $image_type) = getimagesize($img);
			
			switch ($image_type){
				case 1: $im = imagecreatefromgif($img); break;
				case 2: $im = imagecreatefromjpeg($img);  break;
				case 3: $im = imagecreatefrompng($img); break;
				default:  trigger_error('Unsupported filetype!', E_USER_WARNING);  break;
			}
			
			/*** calculate the aspect ratio ***/
			$aspect_ratio = (float) $height_orig / $width_orig;
		
			/*** calulate the thumbnail width based on the height ***/
			$thumb_height = round($thumb_width * $aspect_ratio);
		
			while($thumb_height>$max_width){
				$thumb_width-=10;
				$thumb_height = round($thumb_width * $aspect_ratio);
			}
			
			$newImg = imagecreatetruecolor($thumb_width, $thumb_height);
			
			/* Check if this image is PNG or GIF, then set if Transparent*/ 
			if(($image_type == 1) OR ($image_type==3)){
				imagealphablending($newImg, false);
				imagesavealpha($newImg,true);
				$transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
				imagefilledrectangle($newImg, 0, 0, $thumb_width, $thumb_height, $transparent);
			}
			imagecopyresampled($newImg, $im, 0, 0, 0, 0, $thumb_width, $thumb_height, $width_orig, $height_orig);
			
			//Generate the file, and rename it to $newfilename
			switch ($image_type){
				case 1: imagegif($newImg,$newfilename); break;
				case 2: imagejpeg($newImg,$newfilename);  break;
				case 3: imagepng($newImg,$newfilename); break;
				default:  trigger_error('Failed resize image!', E_USER_WARNING);  break;
			}
		
			return $newfilename;
		}

	}
?>