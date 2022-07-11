<?php
namespace App\Traits;

use Auth;
use DB;
use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Http\Functions\MyHelper;
use App\Http\Functions\CheckTrigger;
use App\Http\Requests\v3\ContactRequest;
use Illuminate\Support\Facades\Log;
use App\Support\Collection;


trait ModelsTraits {

    protected  $DELETED = 1;
    protected  $DELETE = [NULL,0];
    protected  $DELETE_COLUMN = 'is_delete';
    protected  $SELECT_COLUMN = 'id';
    protected  $ORDERBY = 'id:asc';
    protected  $FROM = 0;
    protected  $TAKE = 10;
    protected  $FILLABLE = '';
    /**
     * Set value delete column
     * @param   [string]  $DELETE_COLUMN
     */
    public function setDeleteColumn($column) {   
        $this->DELETE_COLUMN = $column;
        return $this;
    }
    /**
     * Set value delete value
     * @param   [string]  $DELETE
     */
    public function setDeleteValue($value) {    
        $this->DELETE = $value;
        return $this;
    }
    /**
     * Set value select column
     * @param   [string]  $DELETE_COLUMN
     */
    public function setSelectColumn($column) {   
        $this->SELECT_COLUMN = $column;
        return $this;
    }
    public function getListDefault($req,$with = ''){
        $delete = $this->DELETE;
        $groupid = auth()->user()->groupid;
        $res = new self;
        if ($with) {
            $res = $res->with($with);
        }
        /// paginate
        if (array_key_exists('page', $req) && rtrim($req['page']) != '') {
            $from = (intval($req['page']) - 1) * $this->TAKE;
        }else{
            $from = $this->FROM;
        }    
        /// litmit ofset
        if (array_key_exists('limit', $req) && rtrim($req['limit']) != '') {
            $limit = $req['limit'];
            if (intval($limit) > 100) {
                $limit = 100;
            }
        }else{
            $limit = $this->TAKE;
        }
        /// select
        if (array_key_exists('fields', $req) && rtrim($req['fields']) != '') {
            $res = $res->selectRaw('id,'.$req['fields']);
        }else{
            $res = $res->selectRaw(implode(',', $this->fillable));
        }
        
        /// search
        if (array_key_exists('search', $req) && rtrim($req['search']) != '') {
            $req['search'] = explode(',', $req['search']);
            foreach ($req['search'] as $key => $value) {
                $type = MyHelper::get_string_between($value,'<','>');
                $search = $value;
                $query = explode('<'.$type.'>', $value);
                $column = $query[0];
                $values = $query[1];

                if($type == 'like'){
                    $values = '%'.$values.'%';
                }else if($type == '!='){
                    $type = '<>';
                }else if($type == 'beetwen'){
                    $date_from = explode('|', $values)[0];
                    $date_to = explode('|', $values)[1];
                    $res = $res->whereBetween($column,[$date_from,$date_to]);
                    break;
                }
                $res = $res->orwhere($column,$type,$values);
            }
        }
        
        if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
            $order_by = explode(',', $req['order_by']);
            foreach ($order_by as $key => $value) {
                $c = explode(':', $value);
                $by = $c[0];
                $order = $c[1];
                $res->orderBy($by, $order);
            }
        }else{
            $c = explode(':', $this->ORDERBY);
            $by = $c[0];
            $order = $c[1];
            $res->orderBy($by, $order);
        }
        $res = $res->where(function($q) use ($delete) {
                    if (is_array($delete)) {
                        $q->where($this->DELETE_COLUMN, $delete[0])
                        ->orWhere($this->DELETE_COLUMN, $delete[1]);
                    }else{
                        $q->where($this->DELETE_COLUMN, $delete);
                    }
                })
        ->where('groupid',$groupid)
        ->offset($from)
        ->limit($limit)->get();
        return $res;
    }

    public function showOne($id)
    {
        $delete = $this->DELETE;
        $select_id = $this->SELECT_COLUMN;

        $groupid = auth()->user()->groupid;
        $res = new self;
        if ($this->getTable() == 'contact_'.$groupid) {
            switch ($groupid) {
                case '196':
                    $res = $res->selectRaw('id,'.$this->fillable)->where(function($q) use ($id) {
                        $q->Where('ext_contact_id', $id)->orwhere('phone', $id);
                    });
                    break;
                case '103':
                    $res = $res->where(function($q) use ($id) {
                        $q->Where($select_id, $id)->orwhere('phone', $id);
                    }); 
                    break;
                default:
                    $res = $res->selectRaw(implode(',', $this->fillable));
                    $res = $res->where([['groupid',$groupid],[$select_id,$id]]);
                    break;
            }    
        }else{            
            $res = $res->selectRaw(implode(',', $this->fillable));
            $res = $res->where([['groupid',$groupid],[$select_id,$id]]);
        }
        $res =  $res->where(function($q) use ($delete) {
                    if (is_array($delete)) {
                        $q->where($this->DELETE_COLUMN, $delete[0])
                          ->orWhere($this->DELETE_COLUMN, $delete[1]);
                    }else{
                        $q->where($this->DELETE_COLUMN, $delete);
                    }
                })->first();
        return $res;
    }
}