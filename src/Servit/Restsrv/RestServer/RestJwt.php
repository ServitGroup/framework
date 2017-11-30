<?php
namespace Servit\Restsrv\RestServer;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Token;
use Servit\Restsrv\RestServer\RestException;

class Restjwt {

	private $token = null; // String   header.palyload.signature
	public  $signer=null ; //obj of Lcobucci\JWT\Signer
	private $jwt = null;  //obj of Lcobucci\JWT\Token;
	public  $_status = false;
	private $_verify = false;
	private $_validate = false;

	public function __construct() {
		$this->signer = new Sha256();
		$this->token = $this->getBearerToken();
		// $this->token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImp0aSI6IjRmMWcyM2ExMmFhIn0.eyJpc3MiOiIxMjcuMC4wLjEiLCJhdWQiOiIxMjcuMC4wLjEiLCJqdGkiOiI0ZjFnMjNhMTJhYSIsImlhdCI6MTUwMTc1NjMyMywiZXhwIjoxNTAxNzk5NTIzLCJ1c2VybmFtZSI6ImFkbWluIiwidWlkIjoxLCJyb2xlIjoiYWRtaW4iLCJsZXZlbCI6IkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkYifQ.03GVyuD08Enl-7hKqU_Z7c4gDp3q2vYQrqMPeRJjoaw';
		$this->initjwtobj();
	}


   public function getStatus() {
   		return $this->_status;
   }

	 // return  String   header.palyload.signature
	public function getToken() {
		return $this->token;
	}

	//retrun obj of Lcobucci\JWT\Token;
	public function getJwt() {
		if($this->jwt) return $this->jwt;
		return null;
	}

	public function getJwtobjdata($datas=[]){
		if($this->jwt && $this->_status){
			if($datas == []) $datas = ['username','uid','role','level','iss','aud','jti','iat','exp',];
			$o = new \stdClass();
			foreach ($datas as $data) {
				$o->{$data} = $this->jwt->getClaim($data);
			}
			return $o;
		} else {
			return null;
		}
	}


	/***---- gen token for jwt ----
		{ 
			username: '',
			uid: 1 ,
			role: 'admin',
			level: 'FF'
		}
	*/	
	public function token($user=null) {

		// – iss (issuer) : เว็บหรือบริษัทเจ้าของ token
		// – sub (subject) : subject ของ token
		// – aud (audience) : ผู้รับ token
		// – exp (expiration time) : เวลาหมดอายุของ token
		// – nbf (not before) : เป็นเวลาที่บอกว่า token จะเริ่มใช้งานได้เมื่อไหร่
		// – iat (issued at) : ใช้เก็บเวลาที่ token นี้เกิดปัญหา
		// – jti (JWT id) : เอาไว้เก็บไอดีของ JWT แต่ละตัวนะครับ
		if($user) {
		 	$now = time();
		    $remotehost = $_SERVER['REMOTE_ADDR'];
			$builder = new Builder();
			$builder->setIssuer($remotehost) // Configures the issuer (iss claim)
                    ->setAudience($remotehost) // Configures the audience (aud claim)
                    ->setId(uuid(), true) // Configures the id (jti claim), replicating as a header item
			        ->setIssuedAt($now)
			        ->setExpiration($now + EXPTIME)
		            ->set('username', $user->username)
		            ->set('uid',$user->id)
		            ->set('role',$user->role)
		            ->set('level',$user->level)
		            ->sign($this->signer, SECRETKEY);
	        return $builder->getToken()->__toString();
		} return null;
	}

	// get old token  and gen new token for add time  
	// return payload headr.paylaod.sginer
	public function jwtrefreshobj() {
			$o = new \stdClass();
			$o->verify = $this->tokenverify();
			$o->status  = $this->chkauth();
			$remotehost = $_SERVER['REMOTE_ADDR'];
			$host = $_SERVER['HTTP_HOST'];
			$now = time();
			if($o->status && $o->verify){
				$builder = new Builder();
				$builder->setIssuer($host) // Configures the issuer (iss claim)
				->setAudience($remotehost) // Configures the audience (aud claim)
				->setId('4f1g23a12aa', true) // Configures the id (jti claim), replicating as a header item
				->setIssuedAt($now)
				->setExpiration($now + EXPTIME)
				->set('username',$this->jwt->getClaim('username'))
				->set('uid',$this->jwt->getClaim('uid'))
				->set('role',$this->jwt->getClaim('role'))
				->set('level',$this->jwt->getClaim('level'))
				->sign($this->signer,SECRETKEY);
				$o->jwt = $builder->getToken()->__toString();
				$o->token = $builder->getToken()->__toString();
			}
			return $o;
	}

	//---- chk for verify signer and validate data  all pass --- 
	// return true/false
	public function chkauth() {
		$o = new \stdClass();
		$o->status =  false;
		$o->verify = false;
		if($this->jwt) {
			$remotehost = $_SERVER['REMOTE_ADDR'];
			$host = $_SERVER['HTTP_HOST'];
			$o->verify = $this->jwt->verify($this->signer, SECRETKEY);
			if($o->verify){
			    $validationData = new ValidationData();
			    $validationData->setIssuer($host);
			    $validationData->setAudience($remotehost);
			    $validate = $this->jwt->validate($validationData);
			    $o->status =  $validate;
			}
		}
		if($o->status){
			return $o->status;
		} else {
			throw new RestException(401, "You are not authorized to access this resource.");
		}
	}	

	//---- verify from token payload check signer only ----
	// return true/false
	public function tokenverify()   {
		if ($this->jwt) {
			return $this->jwt->verify($this->signer,SECRETKEY);
		} else {
			return false;
		}
	}

	/** 
	 * Get hearder Authorization
	 * */
	private function getAuthorizationHeader(){
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
	            //print_r($requestHeaders);
	            if (isset($requestHeaders['Authorization'])) {
	                $headers = trim($requestHeaders['Authorization']);
	            }
	        }
	        return $headers;
	}

	/**
	 * get access token from header
	 * */
	private function getBearerToken() {
	    $headers = $this->getAuthorizationHeader();
	    // HEADER: Get the access token from the header
	    // /Bearer\s((.*)\.(.*)\.(.*))/
	    if (!empty($headers)) {
	        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
	            return $matches[1];
	        }
	    } else {
	    	$posttoken = filter_input(INPUT_POST,REFTOKEN);
	    	if($posttoken) return $posttoken;

	    	$gettoken = filter_input(INPUT_GET,REFTOKEN);
	    	if($gettoken) return $gettoken;

	    	$data = file_get_contents('php://input');

			$data = json_decode($data);
			if(isset($data->ref_token)) return $data->{REFTOKEN};
	    }
	    return null;
	}


	//---- gen  Lcobucci\JWT\Token; obj form toekn
	private function initjwtobj() {
		$remotehost = $_SERVER['REMOTE_ADDR'];
		$host = $_SERVER['HTTP_HOST'];
		if($this->token){
		    $token = (new Parser())->parse($this->token);
		    $this->_verify = $token->verify($this->signer,SECRETKEY);
			if($this->_verify) {
			    $validationData = new ValidationData();
			    $validationData->setIssuer($remotehost);
			    $validationData->setAudience($remotehost);
			    $this->_validate = $token->validate($validationData);
				$this->_status = $this->_validate;
				if($this->_validate) $this->jwt = $token;
			}
		}
	}

}