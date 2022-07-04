<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Traits\ModelsTraits;

class Customer extends Model
{
    use ModelsTraits;

    public $timestamps = false;
    
    protected $table = 'customer';
    protected $DELETE = [null,0];
    protected $fillable = [
                            'id',
                            'groupid',
                            'fullname',
                            'phone',
                            'phone_other',
                            'email',
                            'email_other',
                            'address',
                            'province',
                            'district',
                            'area_code',
                            'area',
                            'country_code',
                            'country',
                            'channel',                            
                            'datecreate',                       
                            'dateupdate',
                            'createby'
                        ];

    public function checkCustomer($phone = '',$email = '')
    {
        $delete = $this->DELETE;
        $groupid = auth::user()->groupid;
        
        if(!empty($email) && !empty($phone)){
            $check_customer = self::where(function($q) use ($phone, $email) {
                $q->where('phone', $phone)->orWhere('email', $email);
            })->where(function($q) use ($delete) {
                $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
            });
        }elseif(empty($phone)){
            $check_customer = self::where('email',$email)->where(function($q) use ($delete) {
                $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
            });
        }else{
            $check_customer = self::where('phone',$phone)->where(function($q) use ($delete) {
                $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
            });
        }
        return $check_customer->where('groupid',$groupid)->first();
    }
}
