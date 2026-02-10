<?php
	use App\Lib\Response,
		App\Lib\MiddlewareToken,
		PHPMailer\PHPMailer\PHPMailer,
		PHPMailer\PHPMailer\Exception,
		Slim\Http\UploadedFile;
		require_once './core/defines.php';

	$app->group('/usuario/', function() use ($app) {
		$this->get('', function($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Ruta de usuario');
		});

		// Agregar usuario
		$this->post('add/', function($request, $response, $arguments) {
			require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$usuario = $this->model->usuario->add($parsedBody);
			if($usuario->response){
				$usuario_id = $usuario->result;
				$seg_log = $this->model->seg_log->add('Registro nuevo usuario', $usuario_id, 'access_user'); 
				if(!$seg_log->response){
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
						return $response->withJson($seg_log);
				}
			}else{
				$usuario->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($usuario); 
			}
			$usuario->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($usuario);
		});

		// Obtener todos los usuarios
		$this->get('getAll/{pagina}/{limite}[/{usuario_tipo}[/{busqueda}]]', function($request, $response, $arguments) {
			$arguments['usuario_tipo'] = isset($arguments['usuario_tipo'])? $arguments['usuario_tipo'] : 0;
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->usuario->getAll($arguments['pagina'], $arguments['limite'], $arguments['usuario_tipo'], $arguments['busqueda']));
		})->add( new MiddlewareToken() );

		// Editar usuario
		$this->put('edit/{id}', function($request, $response, $arguments) {
			require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$usuario_id = $arguments['id'];
			/* $dataUsuario = [ 
				'tipo'=>$parsedBody['tipo'], 
				'nombre'=>$parsedBody['nombre'], 
				'apellidos'=>$parsedBody['apellidos'], 
				'username'=>$parsedBody['username'], 
				'password'=>$parsedBody['password'], 
				'email'=>$parsedBody['email']
			]; */
			$usuario = $this->model->usuario->edit($parsedBody, $usuario_id); 
			if($usuario->response) {
				$seg_log = $this->model->seg_log->add('Actualización información usuario', $usuario_id, 'usuario', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			}else{
				$usuario->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($usuario);
			}
			$usuario->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($usuario);
		})->add( new MiddlewareToken() );

		// Eliminar usuario
		$this->put('del/{id}', function ($req, $res, $args) {
			$this->response = new Response();
			$this->model->transaction->iniciaTransaccion();
			$resultado = $this->model->usuario->del($args['id']);
			if($resultado->response){
				$add = $this->model->seg_log->add('Elimina Usuario', $args['id'], 'usuario');
				if(!$add->response){
					$add->state = $this->model->transaction->regresaTransaccion();
					return $this->response->withJson($add); 
				}
			}else{
				$resultado->state = $this->model->transaction->regresaTransaccion(); 
				return $this->withJson($resultado); 
			}
			$resultado->state = $this->model->transaction->confirmaTransaccion();
	        return $res->withHeader('Content-type', 'application/json')
	                   ->write(json_encode($resultado));
	    })->add( new MiddlewareToken());

		// Inicio de sesion
		$this->post('login/', function($request, $response, $arguments) {
			if(!isset($_SESSION)) { session_start(); }
			$parsedBody= $request->getParsedBody();
			$username= $parsedBody['username'];
			$password = $parsedBody['password'];
			$usuario = $this->model->usuario->login($username, $password);
			if($usuario->response) {
				$token = $this->model->seg_sesion->crearToken($usuario->result);
				$data = [
					'usuario_id' => $usuario->result->id_usuario,
					'ip_address' => $_SERVER['REMOTE_ADDR'],
					'user_agent' => $_SERVER['HTTP_USER_AGENT'],
					'iniciada' => date('Y-m-d H:i:s'),
					'token' => $token
				];
				$this->model->seg_sesion->add($data);
				$this->model->seg_log->add('Inicio de sesión', $usuario->result->id_usuario, 'usuario');
				$this->logger->info("Slim-Skeleton 'usuario/login/' ".$usuario->result->id_usuario);
				$_SESSION['usuario'] = $this->model->usuario->get($usuario->result->id_usuario)->result;

				$home = URL_ROOT.'/beneficiarios';
				/* if(array_search('/almacen', array_column($_SESSION['permisos'], 'url')) !== false) $home = URL_ROOT.'/almacen';
				else if(array_search('/tiendita', array_column($_SESSION['permisos'], 'url')) !== false) $home = URL_ROOT.'/tiendita';
				else if(array_search('/comunidades', array_column($_SESSION['permisos'], 'url')) !== false) $home = URL_ROOT.'/comunidades';
				else if(array_search('/produccion', array_column($_SESSION['permisos'], 'url')) !== false) $home = URL_ROOT.'/produccion'; */
				$_SESSION['home'] = $home;
				$usuario->home = $home;
			}
			return $response->withJson($usuario);
		});

		// Ver datos de session
		$this->get('getSession/', function($request, $response, $arguments) {
			print_r ($_SESSION);
		})->add( new MiddlewareToken() );

		// Obtener usuario por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->usuario->get($arguments['id']));
		})->add( new MiddlewareToken() );

		// Cierre de sesión
		$this->get('logout', function($request, $response, $arguments) use ($app) {
			$result = $this->model->seg_log->add('Cierre de sesión', $_SESSION['usuario']->id_usuario, 'usuario');
			if(!isset($_SESSION)) { 
				session_start(); 
			}
			$resultado = $this->model->seg_sesion->logout();
			//return $response->withJson($result);
			return $this->response->withRedirect('../login');
		});

		// Obtener permisos
        $this->get('getPermisos/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->usuario->getPermisos($arguments['id']));
		})->add( new MiddlewareToken() );

		$this->post('uploadImagenUsuario[/{usuario_id}]', function($request, $response, $arguments) {
			$this->response = new Response();
			$parsedBody = $request->getParsedBody();
			$usuario_id = isset($arguments['usuario_id'])? $arguments['usuario_id']: $_SESSION['usuario']->id_usuario;

			$directory = 'data/foto/';
			$uploadedFiles = $request->getUploadedFiles();
			$uploadedFile = $uploadedFiles['imagen'];
			$filename = '0';
			if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
				//session_start();
				$filename = md5($usuario_id).'.jpg';
				//$filename = $this->model->usuario->moveUploadedFile($directory, $uploadedFile, $fileName);

				$this->model->usuario->resize($uploadedFile->file, 720, $directory.$filename);
				unlink($uploadedFile->file);
				
				if($filename == '0') {
					$this->response->result = 0;
					return $this->response->SetResponse(false, 'Extensión de archivo invalido, solo se aceptan imagenes en formato jpg');
				} else {
					if(!isset($arguments['usuario_id'])) { $_SESSION['usuario']->foto = true; }
					$this->response->result = 1;
					$this->response->filename = $filename.'?'.rand();
					$this->response->SetResponse(true,'Archivo cargado con exito: ' . $filename);
					return $response->withjson($this->response);
				}
			}

			$this->response->result = 1;
			return $this->response->SetResponse(true,'Archivo cargado con exito: ' . $filename);
		})->add( new MiddlewareToken() );

		// Cambiar contraseña de usuario
		$this->put('changePassword/', function($request, $response, $arguments) {
			require_once './core/defines.php';
			$this->response = new Response();
			$this->model->transaction->iniciaTransaccion();
			$resultado = $this->model->usuario->changePassword($request->getParsedBody(),  $_SESSION['usuario']->id_usuario);
			
			if($resultado->response) {
				$seg_log = $this->model->seg_log->add('Cambiar Contraseña', $_SESSION['usuario']->id_usuario, 'usuario');
				if($seg_log->response) {
					$this->response->result = $resultado->result;
					$this->response->state = $this->model->transaction->confirmaTransaccion();
					$this->response->SetResponse(true);
				} else {
					$this->response->result = $seg_log->result;
					$this->response->errors = $seg_log->errors;
					$this->response->state = $this->model->transaction->regresaTransaccion();
					$this->response->SetResponse(false, $seg_log->message);
				}
			} else {
				$this->response->result = $resultado->result;
				$this->response->errors = $resultado->errors;
				$this->response->state = $this->model->transaction->regresaTransaccion();
				$this->response->SetResponse(false, $resultado->message);
			}
			$this->response->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($this->response);
		})->add( new MiddlewareToken() );

		// Actualiza los datos del usuario logeado
		$this->put('editProfile/', function($request, $response, $arguments) {
			$parsedBody = $request->getParsedBody();
			$profileInfo = $this->model->usuario->get($_SESSION['usuario']->id_usuario)->result;
			$areTheSame = true; 
			foreach($profileInfo as $field => $value) { 
				if(isset($parsedBody[$field]) && $parsedBody[$field] != $value) {
					$areTheSame = false; 
					break; 
				}
			}
			$resultado = $this->model->usuario->edit($parsedBody, $_SESSION['usuario']->id_usuario);
			if($resultado->response || $areTheSame) { 
				$resultado->areTheSame = $areTheSame;
				if(!$resultado->areTheSame) {
					$_SESSION['usuario']->nombre = $parsedBody['nombre'];
					$_SESSION['usuario']->apellidos = $parsedBody['apellidos'];
					$resultado->nombre = $parsedBody['nombre'].' '.$parsedBody['apellidos'];
				}
				$resultado->SetResponse(true);
			}
			return $response->withJson($resultado);
		})->add( new MiddlewareToken() );

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
						->write(json_encode($this->model->usuario->findBy($args['f'], $args['v'])));			
		});
	});	
?>