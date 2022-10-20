<?php
namespace App\Http\Functions;

class MyHelper
{

    public static function Response($status = false, $message = "Fobidden", $data = [], $code = 500)
    {
        return response()->json(['status' => $status, 'message' => $message, 'data' => $data], $code);
    }
    public static function get_string_between($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) {
            return '';
        }

        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
    public static function sla($datecreate, $array, $sla)
    {
        $create_day = date('D', $datecreate);
        $create_full = date('d/m/Y H:i:s', $datecreate);
        $create_date = date('d/m/Y', $datecreate);
        $second = (int) date('s', $datecreate);
        $minute = (int) date('i', $datecreate);
        $hour = (int) date('H', $datecreate);
        $create_time = $second + $minute * 60 + $hour * 3600;

        $holiday = json_decode($array['holiday'], true);
        $timework = json_decode($array['work_time'], true);

        $holiday = array_map(function ($item) {return $item['date'];}, $holiday);

        $begin = 0;
        $end = 0;

        $solved = $create_time + $sla;

        $date_end = $create_date;
        $hour_end = 0;
        $minute_end = 0;
        $time_sovled = 0; //thời gian cần xử lý xong
        $time_remaining = 0; //thời gian còn lại

        //check xem phiếu được tạo vào thứ mấy
        if (array_key_exists($create_day, $timework)) {
            // echo "Phiếu được tạo vào thứ $create_day<br>";
            $timework_create = $timework[$create_day];

            //nếu ngày tạo phiếu là ngày làm việc và không thuộc ngày lễ
            if ($timework_create['active'] == 1 && !in_array(date('d/m', ($datecreate)), $holiday)) {
                $tmp = strtotime($timework_create['begin'] . " " . $timework_create['begin_t']);
                $begin = date('H', $tmp) * 3600 + date('i', $tmp) * 60;
                $tmp = strtotime($timework_create['end'] . " " . $timework_create['end_t']);
                $end = date('H', $tmp) * 3600 + date('i', $tmp) * 60;
                $total_time = $end - $begin;

                // echo "giờ bắt đầu ($begin)".($begin/3600)." | giờ kết thúc ($end)".($end/3600)." | tổng giờ làm ($total_time)".($total_time/3600)." <br>";
            } else {
                $begin = $end = $total_time = 0;

                // echo "giờ bắt đầu $begin | giờ kết thúc $end | tổng giờ làm $total_time <br>";
            }
            $nextday = self::next_day($create_day);
        }

        // Trường hợp ticket vào trước giờ làm việc
        if ($create_time < $begin) {
            // echo '----------------Phiếu được tạo trước giờ làm việc<Br>';
            $time_remaining = $sla;

            //xem thời gian xử lý còn lại có lớn hơn tổng thời gian xử lý của ngày hôm nay không
            if ($time_remaining > $total_time) {
                // echo 'Ngày làm việc tiếp theo vẫn nhỏ hơn thời gian xử lý <br>';
                while ($time_remaining > $total_time) {
                    $time_remaining = $time_remaining - $total_time; //còn lại

                    // echo "Thứ ".pre_day($nextday).", giờ bắt đầu ($begin)".($begin/3600)." | giờ kết thúc ($end)".($end/3600)." | tổng giờ làm ($total_time)".($total_time/3600)." còn lại ".($time_remaining/3600)."<br>";

                    $solved = $begin + $time_remaining;
                    $hour_end = (int) ($solved / 3600);
                    $tmp_tmp = $solved % 3600;
                    $minute_end = round($tmp_tmp / 60);
                    $date_end = date('Y-m-d', $datecreate); // thêm 1 ngày

                    $datecreate = $datecreate + 3600 * 24; //ngày 4

                    foreach ($timework as $key => $time) { //lấy thòi gian ngày 4
                        if ($nextday == $key) {
                            if ($time['active'] == 1 && !in_array(date('d/m', $datecreate), $holiday)) {
                                $tmp = strtotime($time['begin'] . " " . $time['begin_t']);
                                $begin = date('H', $tmp) * 3600 + date('i', $tmp) * 60;
                                $tmp = strtotime($time['end'] . " " . $time['end_t']);
                                $end = date('H', $tmp) * 3600 + date('i', $tmp) * 60;
                                $total_time = $end - $begin;
                            } else {
                                // echo "-----------Thứ $key giờ ngoài<br>";
                                $begin = $end = $total_time = 0;
                            }
                            break;
                        }
                    }
                    $nextday = self::next_day($nextday); //ngày 5
                }

                //khi nhảy ra while thì thời gian còn lại nhỏ hơn thời gian xử lý của ngày 4
                $solved = $begin + $time_remaining;
                $hour_end = (int) ($solved / 3600);
                $tmp_tmp = $solved % 3600;
                $minute_end = round($tmp_tmp / 60);
                $date_end = date('Y-m-d', $datecreate);
                $datecreate = $datecreate + 3600 * 24; //ngày 4
            } else {
                // echo 'Ngày làm việc tiếp theo đủ thời gian xử lý <Br>';
                $solved = $begin + $time_remaining;
                $hour_end = (int) ($solved / 3600);
                $tmp_tmp = $solved % 3600;
                $minute_end = round($tmp_tmp / 60);
                $date_end = date('Y-m-d', $datecreate); // thêm 1 ngày
            }
        }
        //Trường hợp ticket vào sau giờ làm việc hoặc NGÀY NGHỈ
        else if ($create_time > $end) {
            // echo '----------------Phiếu được tạo sau giờ làm việc hoặc ngày nghỉ<Br>';

            $time_remaining = $sla; //còn lại
            $datecreate = $datecreate + 3600 * 24;
            // echo "---Ngày làm việc tiếp theo ".date('d/m/Y H:i:s',$datecreate)." là thứ $nextday<br>";

            //lấy ngày làm việc tiếp theo
            if (array_key_exists($nextday, $timework)) {
                $timework_create = $timework[$nextday];

                //nếu ngày tạo phiếu là ngày làm việc và không thuộc ngày lễ
                if ($timework_create['active'] == 1 && !in_array(date('d/m', ($datecreate)), $holiday)) {
                    // echo "Ngày làm việc tiếp theo là ngày thường<br>";
                    $tmp = strtotime($timework_create['begin'] . " " . $timework_create['begin_t']);
                    $begin = date('H', $tmp) * 3600 + date('i', $tmp) * 60;
                    $tmp = strtotime($timework_create['end'] . " " . $timework_create['end_t']);
                    $end = date('H', $tmp) * 3600 + date('i', $tmp) * 60;
                    $total_time = $end - $begin;
                    // echo "giờ bắt đầu ($begin)".($begin/3600)." | giờ kết thúc ($end)".($end/3600)." | tổng giờ làm ($total_time)".($total_time/3600)." <br>";
                } else {

                    $begin = $end = $total_time = 0;

                    // echo "giờ bắt đầu $begin | giờ kết thúc $end | tổng giờ làm $total_time <br>";
                }
                $nextday = self::next_day($nextday); //Web
            }

            // echo "Thời gian xử lý : ".($time_remaining/3600). " giờ làm việc ".($total_time/3600)."<bR>";

            //check tiếp ngày làm việc tiếp theo
            if ($time_remaining > $total_time) {
                // echo 'Ngày làm việc tiếp theo vẫn nhỏ hơn thời gian xử lý <br>';
                while ($time_remaining > $total_time) {
                    $time_remaining = $time_remaining - $total_time; //còn lại

                    // echo "Thứ ".pre_day($nextday).", giờ bắt đầu ($begin)".($begin/3600)." | giờ kết thúc ($end)".($end/3600)." | tổng giờ làm ($total_time)".($total_time/3600)." còn lại ".($time_remaining/3600)."<br>";

                    $solved = $begin + $time_remaining;
                    $hour_end = (int) ($solved / 3600);
                    $tmp_tmp = $solved % 3600;
                    $minute_end = round($tmp_tmp / 60);
                    $date_end = date('Y-m-d', $datecreate); // thêm 1 ngày

                    $datecreate = $datecreate + 3600 * 24; //ngày 4

                    foreach ($timework as $key => $time) { //lấy thòi gian ngày 4
                        if ($nextday == $key) {
                            if ($time['active'] == 1 && !in_array(date('d/m', $datecreate), $holiday)) {
                                $tmp = strtotime($time['begin'] . " " . $time['begin_t']);
                                $begin = date('H', $tmp) * 3600 + date('i', $tmp) * 60;
                                $tmp = strtotime($time['end'] . " " . $time['end_t']);
                                $end = date('H', $tmp) * 3600 + date('i', $tmp) * 60;
                                $total_time = $end - $begin;
                            } else {
                                // echo "-----------Thứ $key giờ ngoài<br>";
                                $begin = $end = $total_time = 0;
                            }
                            break;
                        }
                    }
                    $nextday = self::next_day($nextday); //ngày 5
                }

                //khi nhảy ra while thì thời gian còn lại nhỏ hơn thời gian xử lý của ngày 4
                $solved = $begin + $time_remaining;
                $hour_end = (int) ($solved / 3600);
                $tmp_tmp = $solved % 3600;
                $minute_end = round($tmp_tmp / 60);
                $date_end = date('Y-m-d', $datecreate);
                $datecreate = $datecreate + 3600 * 24; //ngày 4

            } else {
                // echo 'Ngày làm việc tiếp theo đủ thời gian xử lý <Br>';
                $solved = $begin + $time_remaining;
                $hour_end = (int) ($solved / 3600);
                $tmp_tmp = $solved % 3600;
                $minute_end = round($tmp_tmp / 60);
                // $date_end   = date('Y-m-d', strtotime(date('Y-m-d',$datecreate) . ' +1 day')); // thêm 1 ngày
                $date_end = date('Y-m-d', $datecreate); // thêm 1 ngày
            }
        }
        //Trường hợp ticket vào trong giờ làm việc
        else if ($create_time >= $begin || $create_time <= $end) {
            // echo '----------------Phiếu được tạo trong giờ làm việc<Br>';

            //xem thời gian tạo + xử lý có lớn hơn giờ kết thúc không
            if (($create_time + $sla) > $end) { // lớn hơn giờ làm việc
                // echo '---------------------- Nhưng thời gian xử lý quá giờ làm<Br>';

                $time_remaining = abs($create_time + $sla - $end); //còn lại
                //lấy ngày tiếp theo
                // echo "Thời gian xử lý còn lại ".($time_remaining/3600)."giờ <br>";

                //lấy ngày làm việc tiếp theo
                if (array_key_exists($nextday, $timework)) {
                    // echo "__ngày tiếp theo là thứ $nextday<br>";
                    $timework_create = $timework[$nextday];

                    $datecreate = $datecreate + 3600 * 24;

                    //nếu ngày tạo phiếu là ngày làm việc và không thuộc ngày lễ
                    if ($timework_create['active'] == 1 && !in_array(date('d/m', $datecreate), $holiday)) {
                        $tmp = strtotime($timework_create['begin'] . " " . $timework_create['begin_t']);
                        $begin = date('H', $tmp) * 3600 + date('i', $tmp) * 60;
                        $tmp = strtotime($timework_create['end'] . " " . $timework_create['end_t']);
                        $end = date('H', $tmp) * 3600 + date('i', $tmp) * 60;
                        $total_time = $end - $begin;
                        // echo "giờ bắt đầu ($begin)".($begin/3600)." | giờ kết thúc ($end)".($end/3600)." | tổng giờ làm ($total_time)".($total_time/3600)." <br>";
                    } else {

                        $begin = $end = $total_time = 0;

                        // echo "giờ bắt đầu $begin | giờ kết thúc $end | tổng giờ làm $total_time <br>";
                    }
                    $nextday = self::next_day($nextday);
                }

                //check tiếp ngày làm việc tiếp theo
                if ($time_remaining > $total_time) {
                    // echo 'Ngày làm việc tiếp theo vẫn nhỏ hơn thời gian xử lý <br>';
                    while ($time_remaining > $total_time) {
                        $time_remaining = $time_remaining - $total_time; //còn lại

                        // echo "Thứ ".pre_day($nextday).", giờ bắt đầu ($begin)".($begin/3600)." | giờ kết thúc ($end)".($end/3600)." | tổng giờ làm ($total_time)".($total_time/3600)." còn lại ".($time_remaining/3600)."<br>";

                        $solved = $begin + $time_remaining;
                        $hour_end = (int) ($solved / 3600);
                        $tmp_tmp = $solved % 3600;
                        $minute_end = round($tmp_tmp / 60);
                        $date_end = date('Y-m-d', $datecreate); // thêm 1 ngày

                        $datecreate = $datecreate + 3600 * 24; //ngày 4

                        foreach ($timework as $key => $time) { //lấy thòi gian ngày 4
                            if ($nextday == $key) {
                                if ($time['active'] == 1 && !in_array(date('d/m', $datecreate), $holiday)) {
                                    $tmp = strtotime($time['begin'] . " " . $time['begin_t']);
                                    $begin = date('H', $tmp) * 3600 + date('i', $tmp) * 60;
                                    $tmp = strtotime($time['end'] . " " . $time['end_t']);
                                    $end = date('H', $tmp) * 3600 + date('i', $tmp) * 60;
                                    $total_time = $end - $begin;
                                } else {
                                    // echo "-----------Thứ $key giờ ngoài<br>";
                                    $begin = $end = $total_time = 0;
                                }
                                break;
                            }
                        }
                        $nextday = self::next_day($nextday); //ngày 5
                    }

                    //khi nhảy ra while thì thời gian còn lại nhỏ hơn thời gian xử lý của ngày 4
                    $solved = $begin + $time_remaining;
                    $hour_end = (int) ($solved / 3600);
                    $tmp_tmp = $solved % 3600;
                    $minute_end = round($tmp_tmp / 60);
                    $date_end = date('Y-m-d', $datecreate);
                    $datecreate = $datecreate + 3600 * 24; //ngày 4
                } else {
                    // echo 'Ngày làm việc tiếp theo đủ thời gian xử lý <Br>';
                    $solved = $begin + $time_remaining;
                    $hour_end = (int) ($solved / 3600);
                    $tmp_tmp = $solved % 3600;
                    $minute_end = round($tmp_tmp / 60);
                    $date_end = date('Y-m-d', $datecreate); // thêm 1 ngày
                }
            } else {
                $solved = $create_time + $sla;
                $hour_end = (int) ($solved / 3600);
                $tmp_tmp = $solved % 3600;
                $minute_end = round($tmp_tmp / 60);
                $date_end = date('Y-m-d', $datecreate);
            }
        }

        if ($minute_end == 60) {
            $minute_end = 0;
            $hour_end++;
        }
        if ($hour_end == 25) {
            $hour_end = 0;
            $date_end = date('Y-m-d', $datecreate + 60 * 60 * 24);
        }

        $time_sovled = $date_end . " " . str_pad($hour_end, 2, '0', STR_PAD_LEFT) . ":" . str_pad($minute_end, 2, '0', STR_PAD_LEFT) . ":00";
        // echo "<br><br>Thời gian cần xử lý xong : $time_sovled (".strtotime($time_sovled).")<br>";
        $time_sovled = strtotime($time_sovled);
        return $time_sovled;
    }

    public static function next_day($day)
    {
        switch ($day) {
            case 'Mon':
                return 'Tue';
                break;
            case 'Tue':
                return 'Wed';
                break;
            case 'Wed':
                return 'Thu';
                break;
            case 'Thu':
                return 'Fri';
                break;
            case 'Fri':
                return 'Sat';
                break;
            case 'Sat':
                return 'Sun';
                break;
            case 'Sun':
                return 'Mon';
                break;
        }
    }
}