<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Auth;
use App\Traits\ModelsTraits;

class Contact extends Model
{
    use ModelsTraits;

    public $timestamps = false;
    protected $DELETE = [NULL,0];
    protected $table = 'contact';
    protected $guarded = [];
    protected $fillable = [
                            'id',
                            'contact_id',
                            'honor',
                            'firstname',
                            'lastname',
                            'fullname',
                            'gender',
                            'phone',
                            'email',
                            'address',
                            'province',
                            'district',
                            'birthday',
                            'facebook_id',
                            'facebook_name',
                            'zalo_id',
                            'zalo_name',
                            'zalo_avatar',
                        ];

    function __construct()
    {
        $groupid = auth::user()->groupid;
        if ($groupid == '196') {
            $fillable = 'ext_contact_id,phone_other,phone,fullname,birthday,province,email,branch,card_type,creator,created,identity_number,identity_date,identity_location,gender';
            $this->setFillable($fillable);
        }
        self::setTable('contact_'.$groupid);
    }
    
    public function checkContact($phone = '',$email = '',$id = '')
    {
        $delete = $this->DELETE;
        $groupid = auth::user()->groupid;
        
        if ($groupid == '196') {
            $check_contact = self::where(function($q) use ($phone,$id) {
                    $q->Where('ext_contact_id', $id)->orwhere('phone', $phone)->orwhere('phone', $id);
                })->where(function($q) use ($delete) {
                    $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
                })->first();
        }else{
            if(empty($email) && empty($phone) || empty($id)){
                return false;
            }elseif(!empty($email) && !empty($phone)){
                $check_contact = self::where(function($q) use ($phone, $email) {
                    $q->where('phone', $phone)->orWhere('email', $email);
                })->where(function($q) use ($delete) {
                    $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
                })->first();
            }elseif(empty($phone)){
                $check_contact = self::where('email',$email)->where(function($q) use ($delete,$id) {
                    $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
                })->orwhere('id', $id)->first();
            }elseif(empty($email)){
                $check_contact = self::where('phone',$phone)->where(function($q) use ($delete,$id) {
                    $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1])->orwhere('id', $id);
                })->orwhere('id', $id)->first();
            }
        }
        return $check_contact;
    }

    static function customQuery($query,$arr=''){
        if(!empty($arr)){
            $res = DB::select($query);
            $res = array_map(function($item){
                return (array) $item;
            },$res);
        }else{
            $res = DB::select($query);
            $res = (array) reset($res);
        }       
        return $res;
    }
}
