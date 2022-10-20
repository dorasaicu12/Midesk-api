<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Model;

class TagModel extends Model
{
    protected $table = 'tags';
    public $timestamps = false;
    protected $fillable = [
        'id',
        'groupid',
        'name',
        'color',
        'type',
    ];
    const ORDERBY = 'id:asc';
    const TAKE = 10;
    const FROM = 0;

    public function getListDefault($req)
    {
        $res = new self;
        $groupid = auth::user()->groupid;
        /// paginate
        if (array_key_exists('page', $req) && rtrim($req['page']) != '') {
            $from = intval($req['page']) * self::TAKE;
        } else {
            $from = self::FROM;
        }
        /// litmit ofset
        if (array_key_exists('limit', $req) && rtrim($req['limit']) != '') {
            $limit = $req['limit'];
            if (intval($limit) > 100) {
                $limit = 100;
            }
        } else {
            $limit = self::TAKE;
        }
        /// select
        if (array_key_exists('fields', $req) && rtrim($req['fields']) != '') {
            $res = $res->selectRaw('id,' . $req['fields']);
        }
        /// search
        if (array_key_exists('search', $req) && rtrim($req['search']) != '') {

            if (strpos($req['search'], '<=>') !== false) {
                $key_search = explode('<=>', $req['search']);
                $type = '=';
            } else if (strpos($req['search'], '<like>') !== false) {
                $key_search = explode('<like>', $req['search']);
                $type = 'like';
                $key_search[1] = '%' . $key_search[1] . '%';
            } else if (strpos($req['search'], '<>') !== false) {
                $key_search = explode('like', $req['search']);
                $type = '<>';
            }
            $res = $res->where($key_search[0], $type, $key_search[1]);
        }

        if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
            $order_by = explode(',', $req['order_by']);
            foreach ($order_by as $key => $value) {
                $c = explode(':', $value);
                $by = $c[0];
                $order = $c[1];
                $res = $res->orderBy($by, $order);
            }
        } else {
            $c = explode(':', self::ORDERBY);
            $by = $c[0];
            $order = $c[1];
            $res = $res->orderBy($by, $order);
        }
        return $res->where(function ($q) use ($groupid) {

        })
            ->offset($from)
            ->limit($limit)
            ->paginate($limit)->appends(request()->query());
    }

    public static function ShowOne($id)
    {

        $groupid = auth::user()->groupid;
        return self::where(function ($q) use ($groupid) {
        })->find($id);
    }
}