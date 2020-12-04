<?php 
	
	/*require_once('rest.php');
	require_once('jwt.php');
	require_once('dbconnect.php');*/
	/**
	 * 
	 */
	class Api extends Rest
	{
		
		function __construct()
		{
			parent::__construct(); 
		}

		function generateToken()
		{
			//print_r($this->param);
			$email = $this->validateParameter( 'email', $this->param['email'], STRING);

			$password = $this->validateParameter( 'password', $this->param['pass'], STRING);

			//echo $password;
			try 
			{
				$stm = $this->dbConn->prepare("SELECT * FROM users WHERE email = :email AND password = :password");

				$stm->bindParam(":email", $email);
				$stm->bindParam(":password", $password);

				$stm->execute();

				$user = $stm->fetch(PDO::FETCH_ASSOC);

				//print_r($user);

				if(!is_array($user))
				{
					$this->returnResponse(INVALID_USER_PASS, "Email and password not matched");
				}

				if($user['active'] == 0)
				{
					$this->returnResponse(USER_NOT_ACTIVE, "user not active. Please contact us for further wuary");
				}

				$payload = [
					'iat' => time(),
					'iss' => 'localhost',
					'exp' => time() + (60*60),
					'userId' => $user['id']
				];

				$token = JWT::encode($payload, SECRETE_KEY);

				//echo $token;

				$data = ['token'=>$token];

				$this->returnResponse(SUCCESS_RESPONSE, $data);
				
			} catch (Exception $e) 
			{
				$this->throwError(JWT_PROCESSING_ERROR, $e->getMessage());
			}
			
		}

		public function addCustomer() 
		{
			$name = $this->validateParameter('name', $this->param['name'], STRING, false);
			$email = $this->validateParameter('email', $this->param['email'], STRING, false);
			$addr = $this->validateParameter('addr', $this->param['addr'], STRING, false);
			$mobile = $this->validateParameter('mobile', $this->param['mobile'], INTEGER, false);

			$cust = new Customer;
			$cust->setName($name);
			$cust->setEmail($email);
			$cust->setAddress($addr);
			$cust->setMobile($mobile);
			$cust->setCreatedBy($this->userId);
			$cust->setCreatedOn(date('Y-m-d'));

			if(!$cust->insert()) {
				$message = 'Failed to insert.';
			} else {
				$message = "Inserted successfully.";
			}

			$this->returnResponse(SUCCESS_RESPONSE, $message);
		}

		function getCustomerDetails()
		{
			$customerId = $this->validateParameter('customerId', $this->param['customerId'], STRING, false);
			$cust = new Customer;

			$cust->setId($customerId);
			$customerDetails = $cust->getCustomerDetailsByID();
			//print_r($customerDetails);
			if(!is_array($customerDetails))
			{
				$this->returnResponse(SUCCESS_RESPONSE, "No customer with this id");
			}
			else
			{
				$response['customerId'] 	= $customerDetails['id'];
				$response['cutomerName'] 	= $customerDetails['name'];
				$response['email'] 			= $customerDetails['email'];
				$response['mobile'] 		= $customerDetails['mobile'];
				$response['address'] 		= $customerDetails['address'];
				$response['createdBy'] 		= $customerDetails['created_user'];
				$response['lastUpdatedBy'] 	= $customerDetails['updated_user'];
				$this->returnResponse(SUCCESS_RESPONSE, $response);
			}
		}

		public function updateCustomer() 
		{
			$customerId = $this->validateParameter('customerId', $this->param['customerId'], STRING, false);
			$name = $this->validateParameter('name', $this->param['name'], STRING, false);
			$addr = $this->validateParameter('addr', $this->param['addr'], STRING, false);
			$mobile = $this->validateParameter('mobile', $this->param['mobile'], INTEGER, false);

			$cust = new Customer;
			$cust->setId($customerId);
			$cust->setName($name);
			$cust->setAddress($addr);
			$cust->setMobile($mobile);
			$cust->setUpdatedBy($this->userId);
			$cust->setUpdatedOn(date('Y-m-d'));

			if(!$cust->update()) {
				$message = 'Failed to update.';
			} else 
			{
				$message = "updated successfully.";
			}

			$this->returnResponse(SUCCESS_RESPONSE, $message);
		}


		public function deleteCustomer() 
		{
			$customerId = $this->validateParameter('customerId', $this->param['customerId'], STRING, false);

			$cust = new Customer;
			$cust->setId($customerId);

			if(!$cust->delete())
			{
				$message = 'Failed to updeletedate.';
			} else 
			{
				$message = "deleted successfully.";
			}

			$this->returnResponse(SUCCESS_RESPONSE, $message);
		}
	}

 ?>