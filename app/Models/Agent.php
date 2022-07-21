<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelsTraits;

class Agent extends Model
{
    use ModelsTraits;
    public $timestamps = false;
    
    protected $fillable = ['id','createby','firstname','lastname','groupid','username','active','datecreate','dateupdate','class_staff','user_type_id','fullname','phone','email','address','picture','level','password'];

    protected $table = 'table_users';
    
    
}