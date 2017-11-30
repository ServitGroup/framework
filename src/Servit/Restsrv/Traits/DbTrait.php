<?php
namespace Servit\Restsrv\Traits;

trait DbTrait
{

  /**
  * @ noAuth
  * @url GET /all
 */
    public function all()
    {
        if ($this->model()) {
            return ['db'=>true,'data'=> $this->model()->get(), 'status'=>'true'];
        } else {
            return ['db'=>false,'data'=>[],'status'=>true];
        }
    }

  /**
  * @ noAuth
  * @url GET /show/$id
 */
    public function show($id = null)
    {
        if ($id && $this->model()) {
            return ['db'=>true,'data'=> $this->model()->find($id),'status'=>true];
        } else {
             return ['db'=>false,'data'=>[],'status'=>true];
        }
    }


  /**
  * @ noAuth
  * @url GET /insert
  * @url POST /insert
  */
    public function store()
    {
        if ($this->model() && $this->server->data) {
            $item = $this->model();
            foreach ($this->server->data as $key => $value) {
                $item->$key = $value;
            }
            return ['db'=>true,'rs'=>$item->save(),'status'=>true];
        } else {
            return ['db'=>false,'rs'=>null,'status'=>true];
        }
    }


  /**
  * @ noAuth
  * @url GET /update/$id
  * @url PUT /update/$id
  */
    public function update($id = null)
    {
        if ($this->model() && $id) {
            $item = $this->model()->find($id);
            if ($item) {
                foreach ($this->server->data as $key => $value) {
                    $item->$key = $value;
                }
                $rs = $item->save();
                return ['db'=>true,'rs'=>$rs, 'data'=>$item,'status'=>true];
            }
            return ['db'=>true,'data'=>[],'rs'=>0, 'status'=>true,'msg'=>'no by id'];
        } else {
            return ['db'=>false,'data'=>[],'status'=>true];
        }
    }

  /**
  * @ noAuth
  * @url GET  /delete/$id
  * @url DELETE /delete/$id
  */
    public function destroy($id = null)
    {
        if ($this->model() && $id) {
            $item = $this->model()->find($id);
            return ['db'=>true,'data'=>$item,'rs'=> $this->model()->destroy($id),'status'=>true];
        } else {
            return ['db'=>false,'data'=>[],'status'=>true];
        }
    }


  /**
   *@noAuth
   *@url GET /get/$page/$perpage
   *@url POST /get/$page/$perpage
   */
    public function dbbypage($page = 1, $perpage = null)
    {
        if ($this->model()) {
             $page = $page=='$page' ?1:1;
             $perpage = $perpage =='$perpage'?PERPAGE:PERPAGE;
             $items = $this->model()->skip(($page-1)*$perpage)->take($perpage)->get();
            return ['db'=>true,'data'=> $items, 'status'=>'true'];
        }
        return ['db'=>false,'data'=>[],'status'=>true];
    }


    protected function model()
    {
        return null;
    }
}
