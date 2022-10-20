<?php

namespace App\Models\v2;

use Auth;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'event';

    const DELETED = 1;
    const DELETE = [null, 0];
    const ORDERBY = 'id:asc';
    const TAKE = 10;
    const FROM = 0;
    protected $fillable = ['event_title', 'remind_time', 'event_assign_agent', 'event_assign_team', 'note', 'event_location', 'remind_type', 'event_source', 'event_source_id', 'updated_by', 'contact_id'];
    protected $dateFormat = 'Y-m-d H:i:s';

    public function getListDefault($req)
    {
        $res = new self;
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
        $delete = self::DELETE;
        return $res->where(function ($q) use ($delete) {
            $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
        })->where('groupid', auth::user()->groupid)
            ->offset($from)
            ->limit($limit)
            ->paginate($limit)->appends(request()->query());
    }
}