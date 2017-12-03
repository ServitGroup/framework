<?php
namespace Servit\Restsrv\RestServer;

use Servit\Restsrv\Libs\DbTrait;
use Servit\Restsrv\Libs\Nonce;
use Servit\Restsrv\Libs\Csrf;
use Servit\Restsrv\Libs\Request;
use Servit\Restsrv\RestServer\Restjwt;

class RestController
{

    public function __construct()
    {
        consolelog('-----construct----first function----------------');
        // $hostName = explode(':',$_SERVER['HTTP_HOST'])[0];
        // $file = @file_get_contents('http://127.0.0.1:8000/license.txt');
        // $file = @file_get_contents(__DIR__.'/license.txt');
        // if(strpos($file,$hostName) == false){
        // 	exit("Domain [$hostName] not registered. Please contact (limweb@hotmail.com) for a license");
        // }
        $this->jwt = (new Restjwt());
        $this->rbac = (new RestRbac($this->jwt));
        $this->input = Request::getInstance();
    }

    public function init()
    {
        consolelog('-----init------second function --------------');
        // dump($this->jwt->tokenverify());
        // dump($this->jwt->chkauth());
    }

    public function authorize()
    {
        consolelog('-----Authorize------third function --------------');
        $chk = 0;
        if (AUTHTYPE == 'session') {
            if ($this->input->user) {
                $chk = 1;
            }
        } else {
            if ($this->jwt) {
                $this->jwt->server = $this->server;
                $chk =  $this->jwt->chkauth();
                // dump($chk);
            }
        }
        // dump($chk);
        consolelog('---chk--', $chk);
        return $chk;
    }
}
