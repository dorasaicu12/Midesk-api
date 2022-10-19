<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Http\Functions\MyHelper;
use Illuminate\Support\Facades\Log;
use DB;
class Customer extends Model
{
    public $timestamps = false;
    protected $table = 'customer';

    protected $fillable = ['groupid','fullname','phone','email','address','province','createby','datecreate','channel','dateupdate','createby_update'];
    
    const DELETED = 1;
    const DELETE = [NULL,0];
    const SORT = 'id';
    const ORDERBY = 'id:asc';
    const TAKE = 10;
    const FROM = 0;
    const KEYS = 'fullname';

    public function getListDefault($req)
    {
        $res = new self;
        /// paginate
        if (array_key_exists('page', $req) && rtrim($req['page']) != '') {
            $from = intval($req['page']) * self::TAKE;
        }else{
            $from = self::FROM;
        }
        /// litmit ofset
        if (array_key_exists('limit', $req) && rtrim($req['limit']) != '') {
            $limit = $req['limit'];
            if (intval($limit) > 100) {
                $limit = 100;
            }
        }else{
            $limit = self::TAKE;
        }
        /// select
        if (array_key_exists('fields', $req) && rtrim($req['fields']) != '') {
            $res = $res->selectRaw('id,'.$req['fields']);
        }
        /// search
        if (array_key_exists('search', $req) && rtrim($req['search']) != '') {

            if(strpos($req['search'], '<=>') !== false){
                $key_search = explode('<=>', $req['search']);
                $type = '=';
            }else if(strpos($req['search'], '<like>') !== false){
                $key_search = explode('<like>', $req['search']);
                $type = 'like';
                $key_search[1] = '%'.$key_search[1].'%';
            }else if(strpos($req['search'], '<>') !== false){
                $key_search = explode('like', $req['search']);
                $type = '<>';
            }
            $res = $res->where($key_search[0],$type,$key_search[1]);
        }
        //order by

        if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
            $order_by = explode(',', $req['order_by']);
            foreach ($order_by as $key => $value) {
                $c = explode(':', $value);
                $by = $c[0];
                $order = $c[1];
                $res = $res->orderBy($by, $order);
            }
        }else{
            $c = explode(':', self::ORDERBY);
            $by = $c[0];
            $order = $c[1];
            $res = $res->orderBy($by, $order);
        }
        $delete = self::DELETE;
        return $res->where(function($q) use ($delete) {
                $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
        })->where('groupid',auth::user()->groupid)
        ->offset($from)
        ->limit($limit)
        ->paginate($limit)->appends(request()->query());
    }
    static function ShowOne($id)
    {
        $delete = self::DELETE;
        $groupid = auth::user()->groupid;
        return self::where(function($q) use ($delete) {
                $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
            })->where([['groupid',$groupid],['id',$id]])->first();
    }
    public function createCustomer($customer,$request){
        $check_customer_by_email=self::where('email',$customer['email'])->first();
        $check_customer_by_phone=self::where('phone',$customer['phone'])->first();

        if($customer['phone']==''){
            if($check_customer_by_email){
                Log::channel('customer_history')->info('customer already exist',['client'=>['authorization'=>$request->header('Authorization'),'Content-type'=>$request->header('Accept'),'host'=>request()->getHttpHost()],'request'=>$request->all()]);
                return MyHelper::response(false,'customer already exist', ['id'=>$check_customer_by_email->id,'customer_id'=>$check_customer_by_email->customer_id],400);
            } else{
                DB::beginTransaction();
                try {
                    $response = self::create($customer);
        
                DB::commit();
                $customer = self::ShowOne($response->id);
                Log::channel('customer_history')->info('Create customer successfully',['client'=>['authorization'=>$request->header('Authorization'),'Content-type'=>$request->header('Accept'),'host'=>request()->getHttpHost()],'request'=>$request->all(),'data'=>['id' => $customer->id,'customer_id' => $customer->customer_id]]);
                    return MyHelper::response(true,'Create customer successfully', ['id' => $customer->id,'customer_id' => $customer->customer_id],200);
                } catch (\Exception $ex) {
        
                DB::rollback();
                Log::channel('customer_history')->info($ex->getMessage(),['client'=>['authorization'=>$request->header('Authorization'),'Content-type'=>$request->header('Accept'),'host'=>request()->getHttpHost()],'request'=>$request->all()]);
                    return MyHelper::response(false,$ex->getMessage(), [],500);
                }
            }
        }else if($customer['email']==''){

                 if($check_customer_by_phone){
                    Log::channel('customer_history')->info('customer already exist',['client'=>['authorization'=>$request->header('Authorization'),'Content-type'=>$request->header('Accept'),'host'=>request()->getHttpHost()],'request'=>$request->all()]);
                return MyHelper::response(false,'customer already exist', ['id'=>$check_customer_by_phone->id,'customer_id'=>$check_customer_by_phone->customer_id],400);
            } else{
                DB::beginTransaction();
                try {
                    $response = self::create($customer);
        
                DB::commit();
                $customer = self::ShowOne($response->id);
                Log::channel('customer_history')->info('Create customer successfully',['client'=>['authorization'=>$request->header('Authorization'),'Content-type'=>$request->header('Accept'),'host'=>request()->getHttpHost()],'request'=>$request->all(),'data'=>['id' => $customer->id,'customer_id' => $customer->customer_id]]);
                    return MyHelper::response(true,'Create customer successfully', ['id' => $customer->id,'customer_id' => $customer->customer_id],200);
                } catch (\Exception $ex) {
        
                DB::rollback();
                    return MyHelper::response(false,$ex->getMessage(), [],500);
                }
            }

        }else{
            if($check_customer_by_email){
                Log::channel('customer_history')->info('customer already exist',['client'=>['authorization'=>$request->header('Authorization'),'Content-type'=>$request->header('Accept'),'host'=>request()->getHttpHost()],'request'=>$request->all()]);
                return MyHelper::response(false,'customer already exist', [$check_customer_by_phone->id,'customer_id'=>$check_customer_by_phone->customer_id],400);
 
            }else if($check_customer_by_phone){
                Log::channel('customer_history')->info('customer already exist',['client'=>['authorization'=>$request->header('Authorization'),'Content-type'=>$request->header('Accept'),'host'=>request()->getHttpHost()],'request'=>$request->all()]);
                return MyHelper::response(false,'customer already exist', [$check_customer_by_phone->id,'customer_id'=>$check_customer_by_phone->customer_id],400);
            } else{
                DB::beginTransaction();
                try {
                    $response = self::create($customer);
        
                DB::commit();
                $customer = self::ShowOne($response->id);
                Log::channel('customer_history')->info('Create customer successfully',['client'=>['authorization'=>$request->header('Authorization'),'Content-type'=>$request->header('Accept'),'host'=>request()->getHttpHost()],'request'=>$request->all(),'data'=>['id' => $customer->id,'customer_id' => $customer->customer_id]]);
                    return MyHelper::response(true,'Create customer successfully', ['id' => $customer->id,'customer_id' => $customer->customer_id],200);
                } catch (\Exception $ex) {
        
                DB::rollback();
                    return MyHelper::response(false,$ex->getMessage(), [],500);
                }
            }
        }

    }
}