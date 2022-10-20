<?php

namespace App\Http\Controllers\v3;

use App\Http\Controllers\Controller;
use App\Http\Functions\CheckField;
use App\Http\Functions\MyHelper;
use App\Models\lable;
use Auth;
use DB;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $req = $request->all();
        if (array_key_exists('fields', $req) && rtrim($req['fields']) != '') {
            $checkFileds = CheckField::check_fields($req, 'ticket_label');
            if ($checkFileds) {
                return MyHelper::response(false, $checkFileds, [], 404);
            }
        }
        if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
            $checkFileds = CheckField::check_order($req, 'ticket_label');
            if ($checkFileds) {
                return MyHelper::response(false, $checkFileds, [], 404);
            }
        }
        if (array_key_exists('search', $req) && rtrim($req['search']) != '') {
            $checkFileds = CheckField::CheckSearch($req, 'ticket_label');
            if ($checkFileds) {
                return MyHelper::response(false, $checkFileds, [], 404);
            }
            $checksearch = CheckField::check_exist_of_value($req, 'ticket_label');

            if ($checksearch) {
                return MyHelper::response(false, $checksearch, [], 404);
            }
        }
        $label = new lable;
        $label = $label->getListDefault($req);
        return MyHelper::response(true, 'Successfully', $label, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $groupid = auth::user()->groupid;
        $name = $request->name;
        $color = $request->color;
        $type = $request->type && 'all';
        $creby = auth::user()->id;
        $time = time();
        $level = $request->level && 1;
        $parent = $request->parent;
        $parent2 = $request->parent2;

        DB::beginTransaction();
        try {
            DB::commit();
            $order = new lable;
            $order->groupid = $groupid;
            $order->name = $name;
            $order->color = $color;
            $order->type = $type;
            $order->level = $level;
            $order->parent = $parent;
            $order->parent2 = $parent2;
            $order->createby = $creby;
            $order->datecreate = $time;
            $order->save();
            return MyHelper::response(true, 'Created label successfully', ['id' => $order->id], 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return MyHelper::response(false, $ex->getMessage(), [], 403);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id_label)
    {
        $label = (new lable)->checkExist($id_label);
        if (!$label) {
            return MyHelper::response(false, 'Macro not found', [], 404);
        } else {

            return MyHelper::response(true, 'successfully', $label, 200);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id, Request $request)
    {
        $data = array_filter($request->all());
        $label = (new lable)->checkExist($id);
        $lableupdate = lable::find($id);
        if (!$label) {
            return MyHelper::response(false, 'lable not found', [], 404);
        }
        $data['dateupdate'] = time();
        $lableupdate->update($data);

        return MyHelper::response(true, 'updated lable successfully', [], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $lable = (new lable)->checkExist($id);
        if (!$lable) {
            return MyHelper::response(false, 'lable Not Found', [], 404);
        } else {
            $lable->delete();
        }
        return MyHelper::response(true, 'Delete lable Successfully', [], 200);
    }
}