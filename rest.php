<?php 
	
	require_once('constant.php');

	/**
	 * 
	 */
	class Rest 
	{
		protected $request;
		protected $serviceName;
		protected $param;
		protected $dbConn;
		protected $userId;
		
		function __construct()
		{
			if($_SERVER['REQUEST_METHOD'] !== "POST")
			{
				$this->throwError(REQUEST_METHOD_NOT_VALID, "request method not valid");
			}
			else
			{
				//$data = json_decode(file_get_contents('php://input'));
				$handler = fopen('php://input','r');
				$this->request = stream_get_contents($handler);
				$this->validateRequest();

				$db = new DbConnect();
				$this->dbConn = $db->connect();

				if( 'generatetoken' != strtolower( $this->serviceName) ) 
				{
					$this->validateToken();
				}
			}
		}

		function validateRequest()
		{
			//echo $_SERVER['CONTENT_TYPE'];exit;
			if ($_SERVER['CONTENT_TYPE'] !== 'application/json') 
			{
				$this->throwError(REQUEST_CONTENTTYPE_NOT_VALID, "REQUEST_CONTENTTYPE_NOT_VALID");
			}
			else
			{
				$data = json_decode($this->request,true);
				//print_r($data);

				if(!isset($data['name']) || $data['name'] == "")
				{
					$this->throwError(API_NAME_REQUIRED, "Name is required");
				}
				else
				{
					$this->serviceName = $data['name'];
				}

				if(!is_array($data['param']))
				{
					$this->throwError(API_PARAM_REQUIRED, "Parameter is required");
				}
				else
				{
					$this->param = $data['param'];
				}


			}
		}

		function validateParameter($fieldName, $value, $dataType, $required=true)
		{
			if($required && empty($value))
			{
				$this->throwError(VALIDATE_PARAMETER_REQUIRED, $fieldName.' PARAMETER_REQUIRED');
			}

			switch ($dataType) {
				case BOOLEAN:
					if(is_bool($value))
					{
						$this->throwError(VALIDATE_PARAMETER_DATATYPE, "datatype not valid for ". $fieldName . " it should be boolean.");
					}
					break;

				case INTEGER:
					if(is_bool($value))
					{
						$this->throwError(VALIDATE_PARAMETER_DATATYPE, "datatype not valid for ". $fieldName . " it should be integer.");
					}
					break;

				case STRING:
					if(is_bool($value))
					{
						$this->throwError(VALIDATE_PARAMETER_DATATYPE, "datatype not valid for ". $fieldName . " it should be string.");
					}
					break;
				
				default:
					$this->throwError(VALIDATE_PARAMETER_DATATYPE, "datatype not valid for ". $fieldName);
					break;
			}

			return $value;
		}

		public function validateToken() {
			try {
				$token = $this->getBearerToken();
				$payload = JWT::decode($token, SECRETE_KEY, ['HS256']);

				$stmt = $this->dbConn->prepare("SELECT * FROM users WHERE id = :userId");
				$stmt->bindParam(":userId", $payload->userId);
				$stmt->execute();
				$user = $stmt->fetch(PDO::FETCH_ASSOC);
				if(!is_array($user)) {
					$this->returnResponse(INVALID_USER_PASS, "This user is not found in our database.");
				}

				if( $user['active'] == 0 ) {
					$this->returnResponse(USER_NOT_ACTIVE, "This user may be decactived. Please contact to admin.");
				}
				$this->userId = $payload->userId;
			} catch (Exception $e) {
				$this->throwError(ACCESS_TOKEN_ERRORS, $e->getMessage());
			}
		}

		function processApi()
		{
			$api = new API;
			$rMethod = new reflectionMethod('API', $this->serviceName);
			if(!method_exists($api, $this->serviceName))
			{
				$this->throwError(API_DOST_NOT_EXIST,'API_DOST_NOT_EXIST');
			}
			else
			{
				$rMethod->invoke($api);
			}

			 /*$serviceName = $this->serviceName;
			 if (is_callable([$this, $serviceName])) {
			  $this->$serviceName();
			 } else {
			  $this->throwError(API_DOES_NOT_EXIST, 'Api does not exists');
			 }*/


		}



		function throwError($code,$message)
		{	
			header('Content-Type: application/json');
			$errorMessage = json_encode(["error"=>['status'=>$code, "message"=>$message]]);
			echo $errorMessage;
			exit;
		}
		
		function returnResponse($code, $data)
		{
			header("Content-Type: application/json");
			$response = json_encode(['response' => ['staus'=>$code, 'message'=>$data]]);

			echo $response;
			exit;
		}


		/**
	    * Get hearder Authorization
	    * */
	    public function getAuthorizationHeader(){
	        $headers = null;
	        if (isset($_SERVER['Authorization'])) {
	            $headers = trim($_SERVER["Authorization"]);
	        }
	        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
	            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
	        } elseif (function_exists('apache_request_headers')) {
	            $requestHeaders = apache_request_headers();
	            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
	            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
	            if (isset($requestHeaders['Authorization'])) {
	                $headers = trim($requestHeaders['Authorization']);
	            }
	        }
	        return $headers;
	    }
	    /**
	     * get access token from header
	     * */
	    public function getBearerToken() {
	        $headers = $this->getAuthorizationHeader();
	        // HEADER: Get the access token from the header
	        if (!empty($headers)) {
	            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
	                return $matches[1];
	            }
	        }
	        $this->throwError( ATHORIZATION_HEADER_NOT_FOUND, 'Access Token Not found');
	    }
	}
 ?>