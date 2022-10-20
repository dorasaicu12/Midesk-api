<?php
namespace App\Http\Functions;

use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Schema;

class CheckField
{
    public static function check_fields($req, $name)
    {
        $columns['fields'] = Schema::getColumnListing($name);
        $f = rtrim($req['fields'], ',');

        $field = explode(',', $req['fields']);

        $temp = [];
        $message = '';
        foreach ($field as $key => $value) {
            if (!in_array($value, $temp)) {
                $temp[] = $value;
                $check_array = in_array($value, $columns['fields']);
                if (!$check_array) {
                    $message .= 'Column ' . $value . ' can not be found.';
                } else {
                    $message = '';
                }
            }
            if ($message != '') {
                $message2 = $message;
            }
        }
        if (isset($message2)) {
            return $message2;
        }
    }

    public static function check_order($req, $name)
    {
        $columns['fields'] = Schema::getColumnListing($name);
        $order_by = explode(',', $req['order_by']);
        $message = '';
        $temp = [];
        foreach ($order_by as $key => $value) {
            $c = explode(':', $value);
            $by = $c[0];
            $order = $c[1];

            if (!in_array($by, $temp)) {
                $temp[] = $by;
                $check_array = in_array($by, $columns['fields']);
                if (!$check_array) {
                    $message .= 'Order by column ' . $by . ' can not be found.';
                } else {
                    $message = '';
                }
            }
            if ($message != '') {
                $message2 = $message;
            }

        }
        if (isset($message2)) {
            return $message2;
        }
    }

    public static function CheckSearch($req, $name)
    {
        $columns['fields'] = Schema::getColumnListing($name);
        $search = explode(',', $req['search']);
        $message = '';
        foreach ($search as $value) {
            if (strpos($value, '<=>') !== false) {
                $key_search = explode('<=>', $value);
                $check_array = in_array($key_search[0], $columns['fields']);
                if (!$check_array) {
                    $message .= 'Search column ' . $key_search[0] . ' can not be found.';
                } else {
                    $message = '';
                }
                if ($message != '') {
                    $message2 = $message;
                }
            } else if (strpos($value, '<like>') !== false) {
                $key_search = explode('<like>', $value);

                $check_array = in_array($key_search[0], $columns['fields']);

                if (!$check_array) {
                    $message .= 'Search column ' . $key_search[0] . ' can not be found.';
                } else {
                    $message = '';
                }
                if ($message != '') {
                    $message2 = $message;
                }
            } else if (strpos($value, '<>') !== false) {
                $key_search = explode('<>', $value);
                $check_array = in_array($key_search[0], $columns['fields']);
                if (!$check_array) {
                    $message .= 'Search column ' . $key_search[0] . ' can not be found.';
                } else {
                    $message = '';
                }
                if ($message != '') {
                    $message2 = $message;
                }
            }
        }
        if (isset($message2)) {
            return $message2;
        } else {
            $key_search[1] = '%' . $key_search[1] . '%';
        }
    }

    public static function CheckSearchOr($req, $name)
    {
        $columns['fields'] = Schema::getColumnListing($name);
        $search = explode(',', $req['search_or']);
        $message = '';
        foreach ($search as $value) {
            if (strpos($value, '<=>') !== false) {
                $key_search = explode('<=>', $value);
                $check_array = in_array($key_search[0], $columns['fields']);
                if (!$check_array) {
                    $message .= 'Search_or column ' . $key_search[0] . ' can not be found.';
                } else {
                    $message = '';
                }
                if ($message != '') {
                    $message2 = $message;
                }
            } else if (strpos($value, '<like>') !== false) {
                $key_search = explode('<like>', $value);
                $check_array = in_array($key_search[0], $columns['fields']);
                if (!$check_array) {
                    $message .= 'Search_or column ' . $key_search[0] . ' can not be found.';
                } else {
                    $message = '';
                }
                if ($message != '') {
                    $message2 = $message;
                }
            } else if (strpos($value, '<>') !== false) {
                $key_search = explode('<>', $value);
                $check_array = in_array($key_search[0], $columns['fields']);
                if (!$check_array) {
                    $message .= 'Search_or column ' . $key_search[0] . ' can not be found.';
                } else {
                    $message = '';
                }
                if ($message != '') {
                    $message2 = $message;
                }
            }
        }
        if (isset($message2)) {
            return $message2;
        } else {
            $key_search[1] = '%' . $key_search[1] . '%';
        }
    }

    public static function CheckDate($req, $name)
    {
        $columns['fields'] = Schema::getColumnListing($name);
        $date = explode('-', $req['date']);
        $dateStart = strtotime(Carbon::createFromFormat('d/m/Y', $date[0])->format('d-m-Y'));
        $dateEnd = strtotime(Carbon::createFromFormat('d/m/Y', $date[1])->format('d-m-Y'));
        $message = '';
        if ($dateStart > $dateEnd) {
            $message .= 'the day start should not bigger than day end.';
        }
        if ($dateStart < 0) {
            $message .= 'the day start is invalid.';
        }
        if ($dateEnd < 0) {
            $message .= 'the day end is invalid.';
        }
        if ($message != '') {
            $message2 = $message;
        }

        if (isset($message2)) {
            return $message2;
        }
    }

    public static function CheckDateNumber($req)
    {
        $columns['fields'] = ['day', 'month', 'week', 'year'];
        $date = explode('-', $req['datetime']);
        $message = '';
        $check_array = in_array($date[1], $columns['fields']);
        $startDate = time();
        $endDate = strtotime(date('d-m-y H:i.s', strtotime('- ' . $date[0] . ' ' . $date[1] . '')));
        if (!$check_array) {
            $message .= 'Invalid ' . $date[1] . ' can only use day,week,month,year.';
        }
        if ($startDate < $endDate) {
            $message .= 'The date time is invalid.';
        }

        if ($message != '') {
            $message2 = $message;
        }

        if (isset($message2)) {
            return $message2;
        }
    }

    public static function check_chat_field($req)
    {
        //check column exits
        if (array_key_exists('fields', $req) && rtrim($req['fields']) != '') {
            $columns['table'] = ['social_message', 'table_users'];
            $columns['fields'] = array_merge(Schema::getColumnListing('social_message'), Schema::getColumnListing('table_users'));
            $order_by = explode(',', $req['fields']);
            $message = '';
            $temp = [];
            foreach ($order_by as $key => $value) {
                $c = explode('.', $value);
                $by = $c[0];
                $order = $c[1];

                if (!in_array($by, $temp)) {
                    $temp[] = $by;
                    $check_array2 = in_array($by, $columns['table']);
                    if (!$check_array2) {
                        $message .= 'Order by table ' . $by . ' can not be found.';
                    }
                    if (!in_array($order, $columns['fields'])) {
                        $message .= 'Order by columms ' . $order . ' can not be found.';
                    }
                }
                if ($message != '') {
                    $message2 = $message;
                }
            }
            foreach ($order_by as $key => $value) {
                $c = explode('.', $value);
                $by = $c[0];
                $order = $c[1];
                if (!in_array($order, $columns['fields'])) {

                    $message .= 'Order by columms ' . $order . ' can not be found.';
                }
                if ($message != '') {
                    $message2 = $message;
                }
            }
            if (isset($message2)) {
                return $message2;
            }

        }
    }
    public static function check_exist_of_value($req, $name)
    {
        $message = '';
        $search = explode(',', $req['search']);
        foreach ($search as $value) {
            if (strpos($value, '<=>') !== false) {
                $key_search = explode('<=>', $value);
                $check_exits = DB::table($name)->where($key_search[0], '=', $key_search[1])->first();
                if ($check_exits == null) {
                    $message .= $key_search[1] . ' not found.';
                } else {
                    $message = '';
                }

            } else if (strpos($value, '<like>') !== false) {
                $key_search = explode('<like>', $value);
                $check_exits = DB::table($name)->where($key_search[0], 'like', '%' . $key_search[1] . '%')->first();
                if ($check_exits == null) {
                    $message .= $key_search[1] . ' not found.';
                } else {
                    $message = '';
                }
            } else if (strpos($value, '<>') !== false) {
                $key_search = explode('<like>', $value);
                $check_exits = DB::table($name)->where($key_search[0], 'like', '%' . $key_search[1] . '%')->first();
                if ($check_exits == null) {
                    $message .= $key_search[1] . ' not found.';
                } else {
                    $message = '';
                }

            }
        }
        if (isset($message)) {
            return $message;
        }

    }
}