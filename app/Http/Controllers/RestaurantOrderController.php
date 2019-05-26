<?php

namespace App\Http\Controllers;

use App\Restaurant;
use App\Special;
use App\RestaurantOrder;
use App\RestaurantOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Validator;

class RestaurantOrderController extends Controller
{   
    public function getClient($rest_id, $client_id){
        $circle_radius = 3959;
        $client = RestaurantOrder::join('users', 'users.id', '=', 'restaurant_orders.order_by')
                    ->join('restaurants','restaurants.id','=','restaurant_orders.order_for')
                    ->select('restaurant_orders.*', 'users.first_name', 'users.last_name', 'users.profile_pic','users.client_id','users.contact','users.email',DB::raw("($circle_radius  * acos(cos(radians(users.lat)) * cos(radians(restaurants.lat)) *cos(radians(restaurants.lng) - radians(users.lng)) +sin(radians(users.lat)) * sin(radians(restaurants.lat))))  AS distance"))
                    ->where([ ['order_for',$rest_id], ['client_id','like',''.$client_id.'%'] ])
                    ->whereDate('restaurant_orders.created_at', Carbon::today())
                    ->orderBy('distance','asc')
                    ->get();
        return response()->json($client);
    }
    
    public function get_restaurant_report( Request $request ){
        $limit = 20;
        $total_report=0;
        $page = 1;
        $page = request('page');
        if ($page) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }
        $validator = Validator::make($request->all(), [
            'rest_id'   => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $condition = '';
        //$spe_id = 0;
    /*     if(request('special')!=''){
            $special = request('special');
            $special = Special::select('id')->whereRaw("title LIKE '%$special%' ")->first();
            $spe_id = $special->id;
            //dd($spe_id);
        } */
 /*        if(request('day')!=''){
            $day = request('day');
            $condition .=" AND DAYNAME(o.created_at)LIKE '%$day%' ";
        } */
        $from = $to = '';
        if(request('from')!='' && request('to')!=''&& request('from')!='null' && request('to')!='null'){
            $from = request('from');
            //dd($from);
            $to = request('to');
        }
        //dd($from);
        $rest_id = request('rest_id');
        $sql = "SELECT * FROM ( SELECT s.title, getTotalOrder( $spe_id, 1,'$from', '$to') pending,
                getTotalOrder( $spe_id, 2,'$from', '$to') confirmed, 
                getTotalOrder( $spe_id, 3,'$from', '$to') cancelled 
                FROM specials s WHERE s.rest_id = $rest_id ) t 
                WHERE pending IS NOT NULL OR confirmed IS NOT NULL OR cancelled IS NOT NULL";
       // dd(  $sql);
        /* 
        BEGIN
        DECLARE total int;

        SET @total = 0;
        SELECT SUM(i.qty) total into @total 
            FROM restaurant_orders o, restaurant_order_items i 
            WHERE o.id = i.order_id 
            AND DATE(o.created_at) = CURDATE() 
            AND i.special_id = special_id 
            AND o.status = status;
        return @total;    
        END
        */
        $sql_count = "SELECT * FROM ( SELECT s.title, getTotalOrder(s.id, 1, '$from', '$to') pending,
                        getTotalOrder(s.id, 2, '$from', '$to') confirmed, 
                        getTotalOrder(s.id, 3, '$from', '$to') cancelled 
                        FROM specials s WHERE s.rest_id = $rest_id) t 
                        WHERE pending IS NOT NULL OR confirmed IS NOT NULL OR cancelled IS NOT NULL";
        $report = DB::select($sql);
        $report_count = count(DB::select($sql_count)); 
        $total_report = $report_count;
        if ($total_report>0) {
            $mod = $total_report % $limit;
            if($mod>0){
                $total_pages = floor($total_report/$limit)+1;
            }else{
                $total_pages = floor($total_report/$limit);
            }
        }else{
            $total_pages = 0;
        }
        return response()->json(compact('report','total_pages'));
    }
    public function reservation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'   => 'required',
            'rest_id'   => 'required',
            'approximate_time'   => 'required',
            'item_list' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $order = new RestaurantOrder();
        $order->order_by = request('user_id');
        $order->order_for = request('rest_id');
        $order->approximate_time = request('approximate_time');
        $item_list = request('item_list');
        try {
            DB::beginTransaction();
            try {
                $order->status = 1;
                $order_save = $order->save();
                $item_list = request('item_list');
                $item_arr = json_decode($item_list, TRUE);
                $total = 0;
                if ($order_save) {
                    foreach ($item_arr as $row) {
                        $orderItem = new RestaurantOrderItem();
                        $orderItem->order_id = $order->id;
                        $orderItem->special_id = $row["id"];
                        $orderItem->qty = $row["qty"];
                        $orderItem->price = $row["price"];
                        $orderItem->save();
                        $total += $row["price"];
                    }
                    $order->total = $total;
                    $order->save();
                }
                DB::commit();
                return response()->json(['success' => true, 'message' => 'Order placed successfully']);
            } catch (Exception $e) {
                DB::rollback();
                return response()->json([ 'success' => false,'message' => 'Order place failed']);
            }
        } catch(Exception $e ) {
            return response()->json([ 'success' => false,'message' => 'Order place failed']);
        }
    }

    public function reservationDetails($order_id)
    {
        $details = DB::table('restaurant_order_items')
            ->join('specials', 'specials.id', '=', 'restaurant_order_items.special_id')
            ->select('restaurant_order_items.*','specials.title as sp_title','specials.image','specials.discount')
            ->where('restaurant_order_items.order_id',$order_id)->get();

        return response()->json($details);
    }

    public function reservationCancel($order_id)
    {
        $order_info = RestaurantOrder::find($order_id);
        if ($order_info) {
            try {
                RestaurantOrder::where('id', $order_id)->update([
                    'status' => 0
                ]);
                return response()->json(['success' => true, 'message' => 'Order cancelled successfully']);
            } catch(Exception $e ) {
                return response()->json([ 'success' => false,'message' => 'Order cancel failed']);
            }
        }else{
            return response()->json([ 'success' => false,'message' => 'Order not found']);
        }
    }

    public function reservationConfirm($order_id)
    {
        $order_info = RestaurantOrder::find($order_id);
        if ($order_info) {
            try {
                RestaurantOrder::where('id', $order_id)->update([
                    'status' => 2
                ]);
                return response()->json(['success' => true, 'message' => 'Order confirmed successfully']);
            } catch(Exception $e ) {
                return response()->json([ 'success' => false,'message' => 'Order confirm failed']);
            }
        }else{
            return response()->json([ 'success' => false,'message' => 'Order Not found']);
        }
    }

    public function userReservationList($user_id) {
        $todays = DB::table('restaurant_orders')
            ->join('users', 'users.id', '=', 'restaurant_orders.order_by')
            ->join('restaurants', 'restaurants.id', '=', 'restaurant_orders.order_for')
            ->select('restaurant_orders.*','restaurants.name as rest_name')
            ->where('order_by',$user_id)
            ->whereDate('restaurant_orders.created_at',Carbon::today())
            ->get();
        $previous = DB::table('restaurant_orders')
            ->join('users', 'users.id', '=', 'restaurant_orders.order_by')
            ->join('restaurants', 'restaurants.id', '=', 'restaurant_orders.order_for')
            ->select('restaurant_orders.*','restaurants.name as rest_name')
            ->where('order_by',$user_id)
            ->whereDate('restaurant_orders.created_at','<',Carbon::today())
            ->orderBy('restaurant_orders.created_at','desc')
            ->limit(30)
            ->get();
        return response()->json(compact('todays','previous'));
    }

    public function restaurantReservationListToday($rest_id) {
        $circle_radius = 3959;
        $reservation = RestaurantOrder::join('users', 'users.id', '=', 'restaurant_orders.order_by')
            ->join('restaurants','restaurants.id','=','restaurant_orders.order_for')
            ->select('restaurant_orders.*', 'users.first_name', 'users.last_name', 'users.profile_pic','users.client_id','users.contact','users.email',DB::raw("($circle_radius  * acos(cos(radians(users.lat)) * cos(radians(restaurants.lat)) *cos(radians(restaurants.lng) - radians(users.lng)) +sin(radians(users.lat)) * sin(radians(restaurants.lat))))  AS distance"))
            ->where('order_for',$rest_id)
            ->whereDate('restaurant_orders.created_at', Carbon::today())
            ->orderBy('distance','asc')
            ->get();
        return response()->json($reservation);
    }

    public function restaurantReservationListConfirmed($rest_id,$page) {
        $limit = 20;
        if ($page) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }
        $reservation = DB::table('restaurant_orders')
            ->join('users', 'users.id', '=', 'restaurant_orders.order_by')
            ->select('restaurant_orders.*', 'users.first_name', 'users.last_name', 'users.profile_pic','users.client_id','users.contact','users.email')
            ->where('order_for',$rest_id)
            ->where('restaurant_orders.status',2)
            ->limit($limit)
            ->offset($offset)
            ->get();
        $reservation_count = DB::table('restaurant_orders')
            ->join('users', 'users.id', '=', 'restaurant_orders.order_by')
            ->where('order_for',$rest_id)
            ->where('restaurant_orders.status',2)
            ->count();
        $total_reservation = $reservation_count;
        if ($total_reservation>0) {
            $mod = $total_reservation % $limit;
            if($mod>0){
                $total_pages = floor($total_reservation/$limit)+1;
            }else{
                $total_pages = floor($total_reservation/$limit);
            }
        }else{
            $total_pages = 0;
        }
        return response()->json(compact('reservation','total_pages'));
    }

    public function restaurantReservationListPending($rest_id,$page) {
        $limit = 20;
        $total_reservation=0;
        if ($page) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }
        $reservation = DB::table('restaurant_orders')
            ->join('users', 'users.id', '=', 'restaurant_orders.order_by')
            ->select('restaurant_orders.*', 'users.first_name', 'users.last_name', 'users.profile_pic','users.client_id','users.contact','users.email')
            ->where('order_for',$rest_id)
            ->where('restaurant_orders.status',1)
            ->limit($limit)
            ->offset($offset)
            ->get();
        $reservation_count = DB::table('restaurant_orders')
            ->join('users', 'users.id', '=', 'restaurant_orders.order_by')
            ->where('order_for',$rest_id)
            ->where('restaurant_orders.status',1)
            ->count();
        $total_reservation = $reservation_count;
        if ($total_reservation>0) {
            $mod = $total_reservation % $limit;
            if($mod>0){
                $total_pages = floor($total_reservation/$limit)+1;
            }else{
                $total_pages = floor($total_reservation/$limit);
            }
        }else{
            $total_pages = 0;
        }
        return response()->json(compact('reservation','total_pages'));
    }

    public function restaurantReservationListCancelled($rest_id,$page) {
        $limit = 20;
        if ($page) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }
        $reservation = DB::table('restaurant_orders')
            ->join('users', 'users.id', '=', 'restaurant_orders.order_by')
            ->select('restaurant_orders.*', 'users.first_name', 'users.last_name', 'users.profile_pic','users.client_id','users.contact','users.email')
            ->where('order_for',$rest_id)
            ->where('restaurant_orders.status',0)
            ->limit($limit)->offset($offset)->get();
        $reservation_count = DB::table('restaurant_orders')
            ->join('users', 'users.id', '=', 'restaurant_orders.order_by')
            ->where('order_for',$rest_id)
            ->where('restaurant_orders.status',0)
            ->count();
        $total_reservation = $reservation_count;
        if ($total_reservation>0) {
            $mod = $total_reservation % $limit;
            if($mod>0){
                $total_pages = floor($total_reservation/$limit)+1;
            }else{
                $total_pages = floor($total_reservation/$limit);
            }
        }else{
            $total_pages = 0;
        }
        return response()->json(compact('reservation','total_pages'));
    }

    public function  adminReservations($page=1) {
        $limit = 20;
        if ($page) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }
        $restaurant_details = DB::table("restaurants")
            ->join('restaurant_orders', 'restaurants.id', '=', 'restaurant_orders.order_for')
            ->select("restaurants.id", "restaurants.name",
                DB::raw("(SELECT count(restaurant_orders.id) FROM restaurant_orders
                                                    WHERE restaurants.id = restaurant_orders.order_for AND restaurant_orders.status = 0) as cancelled"),
                DB::raw("(SELECT count(restaurant_orders.id) FROM restaurant_orders
                                                    WHERE restaurants.id = restaurant_orders.order_for AND restaurant_orders.status = 1) as pending"),
                DB::raw("(SELECT count(restaurant_orders.id) FROM restaurant_orders
                                                    WHERE restaurants.id = restaurant_orders.order_for AND restaurant_orders.status = 2) as confirmed"))
            ->orderBy('restaurant_orders.order_for', 'ASC')
            ->groupBy('restaurant_orders.order_for')
            ->limit($limit)->offset($offset)->get();
        $restaurant_details_count = DB::table("restaurants")
            ->join('restaurant_orders', 'restaurants.id', '=', 'restaurant_orders.order_for')
            ->select("restaurants.id", "restaurants.name",
                DB::raw("(SELECT count(restaurant_orders.id) FROM restaurant_orders
                                                    WHERE restaurants.id = restaurant_orders.order_for AND restaurant_orders.status = 0) as cancelled"),
                DB::raw("(SELECT count(restaurant_orders.id) FROM restaurant_orders
                                                    WHERE restaurants.id = restaurant_orders.order_for AND restaurant_orders.status = 1) as pending"),
                DB::raw("(SELECT count(restaurant_orders.id) FROM restaurant_orders
                                                    WHERE restaurants.id = restaurant_orders.order_for AND restaurant_orders.status = 2) as confirmed"))
            ->orderBy('restaurant_orders.order_for', 'ASC')
            ->groupBy('restaurant_orders.order_for')
            ->get();
        $total_reservation = count($restaurant_details_count);
            if ($total_reservation>0) {
                $mod = $total_reservation % $limit;
                if($mod>0){
                    $total_pages = floor($total_reservation/$limit)+1;
                }else{
                    $total_pages = floor($total_reservation/$limit);
                }
            }else{
                $total_pages = 0;
            }
        return response()->json(compact('restaurant_details','total_pages'));
    }

    public function  adminReservationsRestaurant($rest_id,$page=1) {
        $limit = 20;
        if ($page) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }
        $orders = DB::table("restaurant_orders")
            ->select(DB::raw("DATE(created_at) date"),
                     DB::raw("(SELECT count(o.id) FROM restaurant_orders o WHERE o.order_for = $rest_id AND o.status = 0 AND DATE(o.created_at) = date) AS cancelled"),
                     DB::raw("(SELECT count(o.id) FROM restaurant_orders o WHERE o.order_for = $rest_id AND o.status = 1 AND DATE(o.created_at) = date) AS pending"),
                     DB::raw("(SELECT count(o.id) FROM restaurant_orders o WHERE o.order_for = $rest_id AND o.status = 2 AND DATE(o.created_at) = date) AS confirmed")
            )
            ->where('restaurant_orders.order_for',$rest_id)
            ->groupBy(DB::raw("DATE(created_at)"))
            ->limit($limit)->offset($offset)->get();
        $orders_count = DB::table("restaurant_orders")
            ->select(DB::raw("DATE(created_at) date"),
                     DB::raw("(SELECT count(o.id) FROM restaurant_orders o WHERE o.order_for = $rest_id AND o.status = 0 AND DATE(o.created_at) = date) AS cancelled"),
                     DB::raw("(SELECT count(o.id) FROM restaurant_orders o WHERE o.order_for = $rest_id AND o.status = 1 AND DATE(o.created_at) = date) AS pending"),
                     DB::raw("(SELECT count(o.id) FROM restaurant_orders o WHERE o.order_for = $rest_id AND o.status = 2 AND DATE(o.created_at) = date) AS confirmed")
            )
            ->where('restaurant_orders.order_for',$rest_id)
            ->groupBy(DB::raw("DATE(created_at)"))
            ->get();
        $total_reservation = count($orders_count);
            if ($total_reservation>0) {
                $mod = $total_reservation % $limit;
                if($mod>0){
                    $total_pages = floor($total_reservation/$limit)+1;
                }else{
                    $total_pages = floor($total_reservation/$limit);
                }
            }else{
                $total_pages = 0;
            }
        return response()->json(compact('orders','total_pages'));
    }
    public function  adminReservationsRestaurantSearch( Request $request) {
        $validator = Validator::make($request->all(), [
            'rest_id'   => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => 'Restaurant not found'], 422);
        }
        $rest_id = request('rest_id');
        $page = request('page');
        $limit = 20;
        if ($page) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }
        $condition = '';

        if(request('from')!='' && request('to')!='' &&  request('from')!='undefined' && request('to')!='undefined'){
            $from = request('from');
            $to = request('to');
            $condition .=" AND  Date(restaurant_orders.created_at) BETWEEN '$from' AND  '$to' ";
        }
        $orders = DB::table("restaurant_orders")
            ->select(DB::raw("DATE(created_at) date"),
                     DB::raw("(SELECT count(o.id) FROM restaurant_orders o WHERE o.order_for = $rest_id AND o.status = 0 AND DATE(o.created_at) = date) AS cancelled"),
                     DB::raw("(SELECT count(o.id) FROM restaurant_orders o WHERE o.order_for = $rest_id AND o.status = 1 AND DATE(o.created_at) = date) AS pending"),
                     DB::raw("(SELECT count(o.id) FROM restaurant_orders o WHERE o.order_for = $rest_id AND o.status = 2 AND DATE(o.created_at) = date) AS confirmed"))
            ->whereRaw("restaurant_orders.order_for = $rest_id $condition")
            ->groupBy(DB::raw("DATE(created_at)"))
            ->limit($limit)->offset($offset)->get();
        $orders_count = DB::table("restaurant_orders")
            ->select(DB::raw("DATE(created_at) date"),
                     DB::raw("(SELECT count(o.id) FROM restaurant_orders o WHERE o.order_for = $rest_id AND o.status = 0 AND DATE(o.created_at) = date) AS cancelled"),
                     DB::raw("(SELECT count(o.id) FROM restaurant_orders o WHERE o.order_for = $rest_id AND o.status = 1 AND DATE(o.created_at) = date) AS pending"),
                     DB::raw("(SELECT count(o.id) FROM restaurant_orders o WHERE o.order_for = $rest_id AND o.status = 2 AND DATE(o.created_at) = date) AS confirmed")
            )
            ->whereRaw("restaurant_orders.order_for = $rest_id $condition")
            ->groupBy(DB::raw("DATE(created_at)"))
            ->get();
        $total_reservation = count($orders_count);
            if ($total_reservation>0) {
                $mod = $total_reservation % $limit;
                if($mod>0){
                    $total_pages = floor($total_reservation/$limit)+1;
                }else{
                    $total_pages = floor($total_reservation/$limit);
                }
            }else{
                $total_pages = 0;
            }
        return response()->json(compact('orders','total_pages'));
    }
}
