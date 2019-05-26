<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Special;
use Validator;
use Image;
use App\Restaurant;
use App\RestaurantMenu;
use Illuminate\Support\Facades\DB;

class RestaurantSpecialController extends Controller
{
   public function get_restaurant_special_list($id){
    	$special = Special::where('rest_id',$id)->get();
    	return response()->json($special);
   }
   
   public function get_special($spe_id){
        $special = Special::where('id',$spe_id)->get();
        return response()->json($special);
   }

   public function get_restaurant_special($spe_id,$rest_id){
        $prev_click=Special::select('click_count')->where('id',$spe_id)->first();
        DB::beginTransaction();
        try {
            Special::where('id',$spe_id)->update([
                'click_count' => $prev_click->click_count+1
            ]);
            DB::commit();
        }catch (Exception $e){
          DB::rollback();
        } 

        $restaurant = Restaurant::find($rest_id); 
        $not_like = "";
        $special = array();
        $total = 0;
        for ($i=0; $i < 8; $i++) { 
            $tomorrow = date("l", strtotime("+$i day"));
            if($i == 0) $l['day'] = "Today";
            else $l['day'] = $tomorrow;
            $special_sql = "SELECT s.id as spe_id,s.title,s.description s_description, s.price, s.discount, s.for, s.available, s.click_count, s.image, s.status,r.id as rest_id ,r.name as restaurant_name, r.address FROM specials s JOIN restaurants r ON r.id   = s.rest_id 
                            WHERE s.rest_id = $rest_id AND s.id != $spe_id AND s.available LIKE '%$tomorrow%'  $not_like ";
            $l['list'] = DB::select($special_sql) ;
            $not_like .= "AND available NOT LIKE '%$tomorrow%'";
            if(count($l['list']) > 0)array_push($special, $l);
            unset($l);
        }
        return response()->json(compact('restaurant','special'));
   }

   public function get_all_special(){
     	$specials = Special::all();
    	return response()->json($specials);
   }

   public function get_restaurant_details($spe_id,$rest_id){
   		$prev_click=Special::select('click_count')->where('id',$spe_id)->first();
      DB::beginTransaction();
      try {
              Special::where('id',$spe_id)->update([
                'click_count' => $prev_click->click_count+1
              ]);
              DB::commit();
          }catch (Exception $e){
              DB::rollback();
          }
   		$restaurant = Restaurant::find($rest_id); 
   		return response()->json(compact('special','restaurant'));
   }

    public function get_today_special($lat,$lng,$page){
        $limit = 20;
        if ($page) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }
        $circle_radius = 3959;
        $max_distance = 5;
        $today = date('l');
        $today_special = DB::select("SELECT * FROM
                        (SELECT r.id as rest_id, r.name, r.lat, r.lng,r.description AS rest_description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening,s.id as spe_id, s.price, s.title, s.discount, s.available, s.image, s.description, s.for, f.user_id, f.id as fav_id,
                        ($circle_radius * acos(cos(radians($lat)) * cos(radians(r.lat)) * cos(radians(r.lng) - radians($lng)) + sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                        FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id 
                        WHERE s.available LIKE '%$today%') AS distances
                        WHERE distance <= $max_distance 
                        ORDER BY distance LIMIT 10 OFFSET $offset");

        $count_today_special = DB::select("SELECT count(*) AS total_row FROM
                        (SELECT s.id, ($circle_radius * acos(cos(radians($lat)) * cos(radians(r.lat)) * cos(radians(r.lng) - radians($lng)) + sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                        FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id WHERE s.available LIKE '%$today%') AS distances WHERE distance <= $max_distance");

        $other_specials = DB::select("SELECT * FROM
                        (SELECT r.id as rest_id, r.name, r.lat, r.lng,r.description AS rest_description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening,s.id as spe_id, s.price, s.title, s.discount, s.available, s.image, s.description, s.for, f.user_id, f.id as fav_id,
                        ($circle_radius * acos(cos(radians($lat)) * cos(radians(r.lat)) * cos(radians(r.lng) - radians($lng)) + sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                        FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id 
                        WHERE s.available LIKE '%$today%') AS distances WHERE distance > $max_distance ORDER BY distance LIMIT 10 OFFSET $offset");

        $count_other_special = DB::select("SELECT count(*) AS total_row FROM
                        (SELECT s.id, ($circle_radius * acos(cos(radians($lat)) * cos(radians(r.lat)) * cos(radians(r.lng) - radians($lng)) + sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                        FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id WHERE s.available LIKE '%$today%') AS distances WHERE distance > $max_distance");

        $total = 0;
        if(count($count_today_special) > 0) {
            $total += $count_today_special[0]->total_row;
        }

        if(count($count_other_special) > 0) {
            $total += $count_other_special[0]->total_row;
        }

        if ($total>0) {
            $mod = $total % $limit;
            if($mod>0){
                $total_pages = floor($total/$limit)+1;
            } else {
                $total_pages = floor($total/$limit);
            }
        } else {
            $total_pages = 0;
        }
        return response()->json(compact('today_special','other_specials','total_pages'));
    }

    public function search_today_special($lat, $lng, $page, $search){
        $limit = 20;
        if ($page) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }
        $circle_radius = 3959;
        $max_distance = 5;
        $today = date('l');
        $specials = DB::select("SELECT * FROM
                                (SELECT r.id as rest_id, r.name, r.lat, r.lng, r.description AS rest_description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening, s.id as spe_id, s.price, s.title, s.discount, s.available, s.image, s.description, s.for, f.user_id, f.id as fav_id,
                                ($circle_radius * acos(cos(radians($lat)) * cos(radians(r.lat)) * cos(radians(r.lng) - radians($lng)) + sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                                FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id 
                                WHERE s.available LIKE '%$today%' AND (s.title LIKE '%$search%'' OR r.category LIKE '%$search%' OR r.region LIKE '%$search%' )) AS distances
                                WHERE distance <= $max_distance 
                                ORDER BY distance LIMIT 10 OFFSET $offset");

        $count_specials = DB::select("SELECT count(*) AS total_row FROM
                                (SELECT  s.id,($circle_radius * acos(cos(radians($lat)) * cos(radians(r.lat)) * cos(radians(r.lng) - radians($lng)) + sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                                FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id 
                                WHERE s.available LIKE '%$today%' AND (s.title LIKE '%$search%'' OR r.category LIKE '%$search%' OR r.region LIKE '%$search%' )) AS distances
                                WHERE distance <= $max_distance ");

        $other_specials = DB::select("SELECT * FROM
                                (SELECT r.id as rest_id, r.name, r.lat, r.lng, r.description AS rest_description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening, s.id as spe_id, s.price, s.title, s.discount, s.available, s.image, s.description, s.for, f.user_id, f.id as fav_id,
                                ($circle_radius * acos(cos(radians($lat)) * cos(radians(r.lat)) * cos(radians(r.lng) - radians($lng)) + sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                                FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id 
                                WHERE s.available LIKE '%$today%' AND (s.title LIKE '%$search%'' OR r.category LIKE '%$search%' OR r.region LIKE '%$search%' )) AS distances
                                WHERE distance > $max_distance 
                                ORDER BY distance LIMIT 10 OFFSET $offset");

        $count_other_specials = DB::select("SELECT count(*) AS total_row  FROM
                                (SELECT  s.id,($circle_radius * acos(cos(radians($lat)) * cos(radians(r.lat)) * cos(radians(r.lng) - radians($lng)) + sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                                FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id 
                                WHERE s.available LIKE '%$today%' AND (s.title LIKE '%$search%'' OR r.category LIKE '%$search%' OR r.region LIKE '%$search%' )) AS distances
                                WHERE distance > $max_distance "); 

        $total = 0;
        if(count($count_specials) > 0) {
            $total += $count_specials[0]->total_row;
        }

        if(count($count_other_specials) > 0) {
            $total += $count_other_specials[0]->total_row;
        }
       if ($total>0) {
           $mod = $total % $limit;
           if($mod>0){
               $total_pages = floor($total/$limit)+1;
           }else{
               $total_pages = floor($total/$limit);
           }
       }else{
           $total_pages = 0;
       }
        return response()->json(compact('specials','other_specials','total_pages'));
    }

    public function filter_today_special($lat, $lng, $page, $min_price, $max_price){
        $limit = 20;
        if ($page) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }
        $circle_radius = 3959;
        $max_distance = 5;
        $today = date('l');

        $specials = DB::select("SELECT * FROM
                        (SELECT r.id as rest_id, r.name, r.lat, r.lng, r.description AS rest_description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening, s.id as spe_id, s.price, s.title, s.discount, s.available, s.image, s.description, s.for, f.user_id, f.id as fav_id, 
                        ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                        FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id
                        WHERE s.available LIKE '%$today%' AND s.price BETWEEN $min_price AND $max_price) AS distances
                        WHERE distance <= $max_distance 
                        ORDER BY distance LIMIT 10 OFFSET $offset ");

        $count_specials = DB::select("SELECT count(*) AS total_row FROM
                        (SELECT  s.id , ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                        FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id
                        WHERE s.available LIKE '%$today%' AND s.price BETWEEN $min_price AND $max_price) AS distances
                        WHERE distance <= $max_distance ");  

        $other_specials = DB::select("SELECT * FROM
                        (SELECT r.id as rest_id, r.name, r.lat, r.lng, r.description AS rest_description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening, s.id as spe_id, s.price, s.title, s.discount, s.available, s.image, s.description, s.for, f.user_id, f.id as fav_id, 
                        ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                        FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id
                        WHERE s.available LIKE '%$today%' AND s.price BETWEEN $min_price AND $max_price) AS distances
                        WHERE distance > $max_distance 
                        ORDER BY distance LIMIT 10 OFFSET $offset ");

        $count_other_specials = DB::select("SELECT count(*) AS total_row FROM
                        (SELECT  s.id , ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                        FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id
                        WHERE s.available LIKE '%$today%' AND s.price BETWEEN $min_price AND $max_price) AS distances
                        WHERE distance > $max_distance "); 
         $total = 0;
         if(count($count_specials) > 0) {
             $total += $count_specials[0]->total_row;
         }
 
         if(count($count_other_specials) > 0) {
             $total += $count_other_specials[0]->total_row;
         }
        if ($total>0) {
            $mod = $total % $limit;
            if($mod>0){
                $total_pages = floor($total/$limit)+1;
            }else{
                $total_pages = floor($total/$limit);
            }
        }else{
            $total_pages = 0;
        }
        return response()->json(compact('specials','other_specials','total_pages'));
    }

    public function filter_and_search_today_special($lat, $lng, $page, $search, $min_price, $max_price){
        $limit = 20;
        if ($page) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }
        $circle_radius = 3959;
        $max_distance = 5;
        $today = date('l');

        $specials = DB::select("SELECT * FROM
                                (SELECT r.id as rest_id, r.name, r.lat, r.lng, r.description AS rest_description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening, s.id as spe_id, s.price, s.title, s.discount, s.available, s.image, s.description, s.for, f.user_id, f.id as fav_id,
                                ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) * cos(radians(r.lng) - radians($lng)) + sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                                FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id 
                                WHERE s.price BETWEEN $min_price AND $max_price AND s.available LIKE '%$today%' AND (s.title LIKE '%$search%' OR r.category LIKE '%$search%' OR r.region LIKE '%$search%'  )) AS distances
                                WHERE distance <= $max_distance 
                                ORDER BY distance LIMIT 10 OFFSET $offset");

        $count_specials = DB::select("SELECT count(*) AS total_row FROM
                                (SELECT s.id ,($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) * cos(radians(r.lng) - radians($lng)) + sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                                FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id 
                                WHERE s.price BETWEEN $min_price AND $max_price AND s.available LIKE '%$today%' AND (s.title LIKE '%$search%' OR r.category LIKE '%$search%' OR r.region LIKE '%$search%'  )) AS distances
                                WHERE distance <= $max_distance");

         $other_specials = DB::select("SELECT * FROM
                            (SELECT r.id as rest_id, r.name, r.lat, r.lng, r.description AS rest_description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening, s.id as spe_id, s.price, s.title, s.discount, s.available, s.image, s.description, s.for, f.user_id, f.id as fav_id,
                            ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) * cos(radians(r.lng) - radians($lng)) + sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                            FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id 
                            WHERE s.price BETWEEN $min_price AND $max_price AND s.available LIKE '%$today%' AND (s.title LIKE '%$search%' OR r.category LIKE '%$search%' OR r.region LIKE '%$search%'  )) AS distances
                            WHERE distance <= $max_distance 
                            ORDER BY distance LIMIT 10 OFFSET $offset");

        $count_other_specials = DB::select("SELECT count(*) AS total_row FROM
                            (SELECT s.id ,($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) * cos(radians(r.lng) - radians($lng)) + sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                            FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id 
                            WHERE s.price BETWEEN $min_price AND $max_price AND s.available LIKE '%$today%' AND (s.title LIKE '%$search%' OR r.category LIKE '%$search%' OR r.region LIKE '%$search%'  )) AS distances
                            WHERE distance > $max_distance");  
        $total = 0;
        if(count($count_specials) > 0) {
             $total += $count_specials[0]->total_row;
        }
 
        if(count($count_other_specials) > 0) {
             $total += $count_other_specials[0]->total_row;
        }
        if ($total>0) {
           $mod = $total % $limit;
           if($mod>0){
               $total_pages = floor($total/$limit)+1;
           }else{
               $total_pages = floor($total/$limit);
           }
       }else{
           $total_pages = 0;
       }
        return response()->json(compact('specials','other_specials','total_pages'));
    }

    public function get_today_advanced_search(){
        $page = request('page'); 
        $limit = 20;
        if ($page) {
            $page = $page;
        } else {
            $page = 1;
        }
        $offset = ($page - 1) * $limit;

        $lng = 0.00;
        $lat = 0.00;
        $today = date('l');
        
        if(request('lat') && request('lat') != 'undefined' && request('lng') && request('lng') != 'undefined'){
           $lat = request('lat');
           $lng = request('lng');
        }

        $condition = " t.available LIKE '%$today%' ";

        if(request('price_lower')!='' && request('price_upper')!='' && request('price_lower') != 'undefined' && request('price_upper') != 'undefined'){
            $price_lower = request('price_lower');
            $price_upper = request('price_upper');
            $condition .=" AND ( t.price BETWEEN 0 AND $price_upper ) ";
        }

        if(request('distance') && request('distance') != 'undefined' ){
            $distance = request('distance');
            $condition .="AND ( t.distance BETWEEN 0 AND $distance)";
        } 

        if(request('city') && request('city') != 'undefined'&& !is_numeric(request('city'))){
            $unprocess_data = request('city');
            $city = explode(',', $unprocess_data);
            $condition .=" AND ( t.region LIKE '$city[0]%' OR t.locality LIKE '$city[0]%'  ) ";
        }
        
        if(request('city') && request('city') != 'undefined'&& is_numeric(request('city'))){
            $city = request('city');
            $condition .=" AND  t.postcode =  $city ";
        }

        if(request('restaurant') && request('restaurant') != 'undefined'){
            $restaurant = request('restaurant');
            $condition .=" AND ( t.name LIKE '%$restaurant%' OR t.type  LIKE '%$restaurant%' OR t.ethnicity  LIKE '%$restaurant%' OR t.category_labels  LIKE '%$restaurant%' OR t.cuisine  LIKE '%$restaurant%' ) ";
        }

        if(request('food') && request('food') != 'undefined'){
            $food = request('food');
            $condition .=" AND t.title LIKE '%$food%'";
        }

        $circle_radius = 3959;
        $max_distance = 5;
  
                
        $sql_today = "SELECT * FROM (SELECT r.id rest_id, r.factual_id, r.name, r.description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality,
        r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening, r.closing,r.lat, r.lng, r.logo, r.banner, s.id spe_id, s.code, s.title, s.description s_description, s.price, s.discount, s.for, s.available, s.click_count, s.image, s.status, 
                    ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                    FROM restaurants r  RIGHT JOIN specials s ON r.id = s.rest_id) t   WHERE     $condition ORDER BY distance LIMIT  $limit OFFSET {$offset}";

        $sql_today_count = "SELECT * FROM (SELECT r.id rest_id, r.factual_id, r.name, r.description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality,
        r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening, r.lat, r.lng, r.logo, r.banner, s.id spe_id, s.code, s.title, s.description s_description, s.price, s.discount, s.for, s.available, s.click_count, s.image, s.status, 
                    ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                    FROM restaurants r RIGHT JOIN specials s ON r.id = s.rest_id) t   WHERE     $condition  ";

        $today_specials = DB::select($sql_today);
        $count_specials = count(DB::select($sql_today_count)); 

        $total = 0;
        if($count_specials > 0) {
             $total += $count_specials;
        }

        if ($total>0) {
           $mod = $total % $limit;
           if($mod>0){
               $total_pages = floor($total/$limit)+1;
           }else{
               $total_pages = floor($total/$limit);
           }
       }else{
           $total_pages = 0;
       }
       return response()->json(compact('today_specials','total_pages'));
    }

    public function get_today_advanced_search_old(){
        $page = request('page'); 
        $limit = 20;
        if ($page) {
            $page = $page;
        } else {
            $page = 1;
        }
        $offset = ($page - 1) * $limit;

        $lng = 0.00;
        $lat = 0.00;
        $today = date('l');
        if(request('lat') && request('lat') != 'undefined' && request('lng') && request('lng') != 'undefined'){
           $lat = request('lat');
           $lng = request('lng');
        }

        $condition = " s.available LIKE '%$today%' ";

        if(request('price_lower')!='' && request('price_upper')!='' && request('price_lower') != 'undefined' && request('price_upper') != 'undefined'){
            $price_lower = request('price_lower');
            $price_upper = request('price_upper');
            $condition .=" AND ( s.price BETWEEN $price_lower AND $price_upper ) ";
        }

        $distance_condition = '';
        if(request('distance') && request('distance') != 'undefined' ){
            $distance = request('distance');
            $distance_condition ="WHERE distance BETWEEN 6 AND $distance";
        } else {
            $distance_condition = "WHERE distance > 5 AND distance <= 10";
        }

        if(request('city') && request('city') != 'undefined'){
            $unprocess_data = request('city');
            $city = str_replace(", ", " ", $unprocess_data);
            $condition .=" AND ( r.region LIKE '%$city%' OR r.locality LIKE '%$city%' ) ";
        }

        if(request('restaurant') && request('restaurant') != 'undefined'){
            $restaurant = request('restaurant');
            $condition .=" AND r.name LIKE '%$restaurant%' ";
        }

        if(request('food') && request('food') != 'undefined'){
            $food = request('food');
            $condition .=" AND ( s.title LIKE '%$food%' OR r.ethnicity LIKE '%$food%' OR r.cuisine LIKE '%$food%' ) ";
        }

        $circle_radius = 3959;
        $max_distance = 5;
        $sql_today = "SELECT * FROM (SELECT r.id as rest_id, r.name, r.lat, r.lng, r.description AS rest_description, r.type, r.ethnicity, r.category,
                r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine,
                r.opening, s.id as spe_id,s.price, s.title, s.discount, s.available, s.image, s.description, s.for, f.user_id, f.id as fav_id,
                ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                FROM restaurants r LEFT JOIN specials s ON r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id 
                WHERE  $condition) AS t WHERE distance <= 5
                ORDER BY distance LIMIT 10 OFFSET $offset";

        $sql_today_count = "SELECT count(*) AS total_row FROM (SELECT r.id as rest_id, r.name, r.lat, r.lng, r.description AS rest_description, r.type, r.ethnicity, r.category,
                r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine,
                r.opening, s.id as spe_id,s.price, s.title, s.discount, s.available, s.image, s.description, s.for, f.user_id, f.id as fav_id,
                ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                FROM restaurants r LEFT JOIN specials s ON r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id 
                WHERE  $condition) AS t WHERE distance <= 5";

        $sql_others = "SELECT * FROM (SELECT r.id as rest_id, r.name, r.lat, r.lng, r.description AS rest_description, r.type, r.ethnicity, r.category,
                r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine,
                r.opening, s.id as spe_id,s.price, s.title, s.discount, s.available, s.image, s.description, s.for, f.user_id, f.id as fav_id,
                ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                FROM restaurants r LEFT JOIN specials s ON r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id 
                WHERE  $condition) AS t $distance_condition 
                ORDER BY distance LIMIT 10 OFFSET $offset";

        $sql_others_count = "SELECT count(*) AS total_row FROM (SELECT r.id as rest_id, r.name, r.lat, r.lng, r.description AS rest_description, r.type, r.ethnicity, r.category,
                r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine,
                r.opening, s.id as spe_id,s.price, s.title, s.discount, s.available, s.image, s.description, s.for, f.user_id, f.id as fav_id,
                ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                FROM restaurants r LEFT JOIN specials s ON r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id 
                WHERE  $condition) AS t $distance_condition ";

        $today_specials = DB::select($sql_today);
        $count_specials = DB::select($sql_today_count); 
        $other_specials = DB::select($sql_others);
        $count_other_specials = DB::select($sql_others_count);
        $total = 0;
        if(count($count_specials) > 0) {
             $total += $count_specials[0]->total_row;
        }
        if(count($count_other_specials) > 0) {
            $total += $count_other_specials[0]->total_row;
        }
        if ($total>0) {
           $mod = $total % $limit;
           if($mod>0){
               $total_pages = floor($total/$limit)+1;
           }else{
               $total_pages = floor($total/$limit);
           }
       }else{
           $total_pages = 0;
       }
       return response()->json(compact('today_specials','other_specials','total_pages'));
    }

    public function find_special_new($lat,$lng,$page){
      $limit = 21;
      if ($page) {
          $page = $page;
          $offset = ($page - 1) * $limit;
      } else {
          $page = 0;
          $offset = 0;
      }
      $circle_radius = 3959;
      $max_distance = 5;

      $not_like = "";
      $specials = array();
      $total = 0;
      for ($i=0; $i < 8; $i++) { 
        $tomorrow = date("l", strtotime("+$i day"));
        if($i == 0) $l['day'] = "Today";
          else $l['day'] = $tomorrow;
        $sql = "SELECT * FROM 
                    (SELECT r.id as rest_id, r.name, r.lat, r.lng, r.description AS rest_description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening,s.id as spe_id, s.price, s.title, s.discount, s.available, s.image, s.description, s.for,f.user_id, f.id as fav_id,
                    ($circle_radius * acos(cos(radians($lat)) * cos(radians(r.lat)) * cos(radians(r.lng) - radians($lng)) + sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                    FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id) AS distances
                    WHERE available LIKE '%$tomorrow%' $not_like
                    ORDER BY distance ASC LIMIT 3 OFFSET $offset"; 
        $l['list'] = DB::select($sql);

        $count_sql = "SELECT count(*) AS total_row FROM 
                    (SELECT r.id, s.available, ($circle_radius * acos(cos(radians($lat)) * cos(radians(r.lat)) * cos(radians(r.lng) - radians($lng)) + sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                    FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id) AS distances
                    WHERE available LIKE '%$tomorrow%' $not_like"; 
        $count_total = DB::select($count_sql);
        if(count($count_total) > 0) {
            $total += $count_total[0]->total_row;
        }
        $not_like .= "AND available NOT LIKE '%$tomorrow%'";
        if(count($l['list']) > 0)
          array_push($specials, $l);
        unset($l);
      }
      $total_specials = $total;
        if ($total_specials>0) {
            $mod = $total_specials % $limit;
            if($mod>0){
                $total_pages = floor($total_specials/$limit)+1;
            }else{
                $total_pages = floor($total_specials/$limit);
            }
        }else{
            $total_pages = 0;
        }
      return response()->json(compact('specials','total_pages'));
    }

    public function search_find_special($lat, $lng, $page, $search){
        $limit = 21;
        if ($page) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }
        $circle_radius = 3959;
        $max_distance = 5;

        $not_like = "";
        $specials = array();
        $total = 0;
        for ($i=0; $i < 8; $i++) { 
            $tomorrow = date("l", strtotime("+$i day"));
            if($i == 0) $l['day'] = "Today";
            else $l['day'] = $tomorrow;
            $sql = "  SELECT * FROM
                            (SELECT r.id as rest_id, r.name, r.lat, r.lng, r.description AS rest_description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening,s.id as spe_id, s.price, s.title, s.discount, s.available, s.image, s.description, s.for, f.user_id, f.id as fav_id,
                            ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                            FROM restaurants r JOIN specials s on r.id = s.rest_id  LEFT JOIN favourites f ON s.id = f.spe_id
                            WHERE s.title LIKE '%$search%' OR r.category LIKE '%$search%' OR r.region LIKE '%$search%') AS distances
                            WHERE  available LIKE '%$tomorrow%' $not_like
                            ORDER BY distance ASC LIMIT 3 OFFSET $offset "; 
            
            $l['list'] = DB::select($sql);

            $count_sql =" SELECT count(*) AS total_row FROM
                            (SELECT r.id , s.available,($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                            FROM restaurants r JOIN specials s on r.id = s.rest_id  LEFT JOIN favourites f ON s.id = f.spe_id
                            WHERE s.title LIKE '%$search%' OR r.category LIKE '%$search%' OR r.region LIKE '%$search%') AS distances
                            WHERE  available LIKE '%$tomorrow%' $not_like ";
            $count_total = DB::select($count_sql);
            if(count($count_total) > 0) {
            $total += $count_total[0]->total_row;
            }
            $not_like .= "AND available NOT LIKE '%$tomorrow%'";
            if(count($l['list'])  > 0)
                array_push($specials, $l);
            unset($l);
        }
        $total_specials = $total;
        if ($total_specials>0) {
            $mod = $total_specials % $limit;
            if($mod>0){
                $total_pages = floor($total_specials/$limit)+1;
            }else{
                $total_pages = floor($total_specials/$limit);
            }
        }else{
            $total_pages = 0;
        }
        return response()->json(compact('specials','total_pages'));
    }

    public function filter_find_special($lat, $lng, $page, $min_price, $max_price){
        $limit = 21;
        if ($page) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }
        $circle_radius = 3959;
        $max_distance = 5;

        $not_like = "";
        $specials = array();
        $total = 0;
        for ($i=0; $i < 8; $i++) { 
            $tomorrow = date("l", strtotime("+$i day"));
            if($i == 0) $l['day'] = "Today";
            else $l['day'] = $tomorrow;
            $sql = "SELECT * FROM
                          (SELECT r.id as rest_id, r.name, r.lat, r.lng, r.description AS rest_description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening, s.id as spe_id, s.price, s.title, s.discount, s.available, s.image, s.description, s.for, f.user_id, f.id as fav_id,
                          ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) * cos(radians(r.lng) - radians($lng)) + sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                          FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id
                          WHERE s.price BETWEEN $min_price AND $max_price) AS distances
                          WHERE available LIKE '%$tomorrow%' $not_like
                          ORDER BY distance ASC LIMIT 3 OFFSET $offset"; 
            $l['list'] = DB::select($sql);
            $count_sql = "SELECT count(*) AS total_row FROM
                            (SELECT r.id , s.available,($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) * cos(radians(r.lng) - radians($lng)) + sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                            FROM restaurants r JOIN specials s on r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id
                            WHERE s.price BETWEEN $min_price AND $max_price) AS distances
                            WHERE available LIKE '%$tomorrow%' $not_like";
            $count_total = DB::select($count_sql);
            if(count($count_total) > 0) {
                $total += $count_total[0]->total_row;
            }
            $not_like .= "AND available NOT LIKE '%$tomorrow%'";
            if(count($l['list'])  > 0)
                array_push($specials, $l);
            unset($l);
        }
        $total_specials = count($specials);
        if ($total_specials>0) {
            $mod = $total_specials % $limit;
            if($mod>0){
                $total_pages = floor($total_specials/$limit)+1;
            }else{
                $total_pages = floor($total_specials/$limit);
            }
        }else{
            $total_pages = 0;
        }
        return response()->json(compact('specials','total_pages'));
    }

    public function filter_and_search_find_special($lat, $lng, $page, $search, $min_price, $max_price){
        $limit = 21;
        if ($page) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }
        $circle_radius = 3959;
        $max_distance = 5;

        $not_like = "";
        $specials = array();
        $total = 0;
        for ($i=0; $i < 8; $i++) { 
            $tomorrow = date("l", strtotime("+$i day"));
            if($i == 0) $l['day'] = "Today";
            else $l['day'] = $tomorrow;
            $sql = "  SELECT * FROM
                            (SELECT r.id AS rest_id, r.name, r.lat, r.lng, r.description AS rest_description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening,s.id as spe_id, s.price, s.title, s.discount, s.available, s.image, s.description, s.for, f.user_id, f.id as fav_id,
                            ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                            FROM restaurants r JOIN specials s on r.id = s.rest_id  LEFT JOIN favourites f ON s.id = f.spe_id
                            WHERE  s.price BETWEEN $min_price AND $max_price AND s.title LIKE '%$search%' OR r.category LIKE '%$search%' OR r.region LIKE '%$search%' ) AS distances
                            WHERE available LIKE '%$tomorrow%' $not_like
                            ORDER BY distance ASC LIMIT 3 OFFSET $offset ";

            $l['list'] = DB::select($sql);
            $count_sql = "  SELECT count(*) AS total_row FROM
                            (SELECT r.id , s.available,($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                            FROM restaurants r JOIN specials s on r.id = s.rest_id  LEFT JOIN favourites f ON s.id = f.spe_id
                            WHERE  s.price BETWEEN $min_price AND $max_price AND s.title LIKE '%$search%' OR r.category LIKE '%$search%' OR r.region LIKE '%$search%' ) AS distances
                            WHERE available LIKE '%$tomorrow%' $not_like ";
            $count_total = DB::select($count_sql);
            if(count($count_total) > 0) {
                $total += $count_total[0]->total_row;
            }
            $not_like .= "AND available NOT LIKE '%$tomorrow%'";
            if(count($l['list'])  > 0)
                array_push($specials, $l);
            unset($l);
        }
        $total_specials = $total;
        if ($total_specials>0) {
            $mod = $total_specials % $limit;
            if($mod>0){
                $total_pages = floor($total_specials/$limit)+1;
            }else{
                $total_pages = floor($total_specials/$limit);
            }
        }else{
            $total_pages = 0;
        }
        return response()->json(compact('specials','total_pages'));
    }

    public function search_food( $lat,$lng,$page, $city_name, $zip_code, $food_name){
        if($city_name == "undefined") $city_name = '';
        if($food_name == "undefined") $food_name = '';
        if($zip_code == "undefined") $zip_code = '';

        $limit = 21;
        if ($page) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }
        $circle_radius = 3959;
        $not_like = "";
        $specials = array();
        $total = 0;
        $sql ="";
        for ($i=0; $i < 8; $i++) { 
            $tomorrow = date("l", strtotime("+$i day"));
            if($i == 0) $l['day'] = "Today";
            else $l['day'] = $tomorrow;
            $sql = "SELECT r.id rest_id, r.factual_id, r.name, r.description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening, r.lat, r.lng, r.logo, r.banner, s.id spe_id, s.code, s.title, s.description, s.price, s.discount, s.for, s.available, s.click_count, s.image, s.status, f.id fav_id,
                    ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                    FROM restaurants r LEFT JOIN specials s ON r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id WHERE (r.postcode LIKE '%{$zip_code}%' AND r.locality LIKE '%{$city_name}%' AND r.region LIKE '%{$city_name}%' AND s.title LIKE '%{$food_name}%') AND s.status = 1 AND available LIKE '%{$tomorrow}%' $not_like ORDER BY r.id LIMIT 3 OFFSET {$offset}";
            $l['list'] = DB::select($sql);
            $count_sql = "SELECT count(r.id) AS total_row , s.available FROM restaurants r LEFT JOIN specials s ON r.id = s.rest_id LEFT JOIN favourites f ON s.id = f.spe_id WHERE (r.postcode LIKE '%{$zip_code}%' AND r.locality LIKE '%{$city_name}%' AND r.region LIKE '%{$city_name}%' AND s.title LIKE '%{$food_name}%') AND s.status = 1 AND available LIKE '%{$tomorrow}%' $not_like ";  
            $not_like .= "AND available NOT LIKE '%$tomorrow%'";
            $count_total = DB::select($count_sql);
            if(count($count_total) > 0) {
                $total += $count_total[0]->total_row;
            }
            if(count($l['list'])  > 0)
                array_push($specials, $l);
            unset($l);
        }
        $total_specials = $total;
        if ($total_specials>0) {
            $mod = $total_specials % $limit;
            if($mod>0){
                $total_pages = floor($total_specials/$limit)+1;
            } else {
                $total_pages = floor($total_specials/$limit);
            }
        } else {
            $total_pages = 0;
        }
        return response()->json(compact('specials','total_pages'));
    }

    public function get_find_special_advanced_search(){
        
      
        $page = request('page'); 
        $limit = 21;
        if ($page>0) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }

        $lng = 0.00;
        $lat = 0.00;
        $today = date('l');
        if(request('lat') && request('lat') != 'undefined' && request('lng') && request('lng') != 'undefined'){
           $lat = request('lat');
           $lng = request('lng');
        }

        $condition = " ";

        if(request('price_lower')!='' && request('price_upper')!='' && request('price_lower') != 'undefined' && request('price_upper') != 'undefined' ){
            $price_lower = request('price_lower');
            $price_upper = request('price_upper');
            $condition .=" AND ( t.price BETWEEN $price_lower AND $price_upper ) ";
        }

        if(request('distance')>0 && request('distance') != 'undefined' ){
            $distance = request('distance');
            $condition ="AND t.distance BETWEEN 0 AND $distance";
        } 

        if(request('city') && request('city') != 'undefined' && !is_numeric(request('city'))){
            $unprocess_data = request('city');
            $city = explode(',', $unprocess_data);
            $condition .=" AND ( t.region LIKE '$city[0]%' OR t.locality LIKE '$city[0]%'  ) ";
        }

        if(request('city') && request('city') != 'undefined'&& is_numeric(request('city'))){
            $city = request('city');
            $condition .=" AND  t.postcode =  $city ";
        }

        if(request('restaurant') && request('restaurant') != 'undefined'){
            $restaurant = request('restaurant');
            $condition .=" AND ( t.name LIKE '%$restaurant%' OR t.type  LIKE '%$restaurant%' OR t.ethnicity  LIKE '%$restaurant%' OR t.category_labels  LIKE '%$restaurant%' OR t.cuisine  LIKE '%$restaurant%' ) ";
        }

        if(request('food') && request('food') != 'undefined'){
            $food = request('food');
            $condition .=" AND t.title LIKE '%$food%'";
        }

          
           
        $circle_radius = 3959;
        $not_like = "";
        $specials = array();
        $total = 0;
        $sql ="";
        for ($i=0; $i < 8; $i++) { 
            $tomorrow = date("l", strtotime("+$i day"));
            if($i == 0) $l['day'] = "Today";
            else $l['day'] = $tomorrow;
            $sql = "SELECT * FROM (SELECT r.id rest_id, r.factual_id, r.name, r.description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening,r.closing, r.lat, r.lng, r.logo, r.banner, s.id spe_id, s.code, s.title, s.description s_description, s.price, s.discount, s.for, s.available, s.click_count, s.image, s.status, 
                    ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                    FROM restaurants r LEFT JOIN specials s ON r.id = s.rest_id) t  LEFT JOIN (select DISTINCT f.user_id, f.id as fav_id, f.spe_id from favourites f group by f.spe_id ) AS d    ON t.spe_id = d.spe_id  WHERE  available  LIKE '%{$tomorrow}%' $not_like  $condition ORDER BY distance LIMIT 3 OFFSET {$offset}";
            //var_dump($sql);
            $l['list'] = DB::select($sql);
            $count_sql = "SELECT * FROM (SELECT r.id rest_id, r.factual_id, r.name, r.description, r.type, r.ethnicity, r.category, r.address, r.postcode, r.locality, r.region, r.contact, r.email, r.web, r.rating, r.category_labels, r.cuisine, r.opening, r.lat, r.lng, r.logo, r.banner, s.id spe_id, s.code, s.title, s.description s_description, s.price, s.discount, s.for, s.available, s.click_count, s.image, s.status, 
                    ($circle_radius  * acos(cos(radians($lat)) * cos(radians(r.lat)) *cos(radians(r.lng) - radians($lng)) +sin(radians($lat)) * sin(radians(r.lat)))) AS distance
                    FROM restaurants r LEFT JOIN specials s ON r.id = s.rest_id) t  LEFT JOIN (select DISTINCT f.user_id, f.id as fav_id, f.spe_id from favourites f group by f.spe_id ) AS d    ON t.spe_id = d.spe_id  WHERE  available  LIKE '%{$tomorrow}%' $not_like  $condition";
            $count_total = count(DB::select($count_sql));
            if($count_total > 0) {
                $total += $count_total;
            }
            if(count($l['list'])  > 0)
                array_push($specials, $l);
            unset($l);
        }
        
        
        $total_specials = $total;
        if ($total>0) {
           $mod = $total % $limit;
           if($mod>0){
               $total_pages = floor($total/$limit)+1;
           }else{
               $total_pages = floor($total/$limit);
           }
       }else{
           $total_pages = 0;
       }
       return response()->json(compact('specials','total_pages'));
    }

   	public function store(Request $request){
	 	   $validator = Validator::make($request->all(),[
	        'title'			=> 'required',
	        'description'	=> 'required',
	        'price'			=> 'required',
	        'discount'		=> 'required',
	        'for'			=> 'required',
	        'available'		=> 'required',
	        'status' 		=> 'required',
	        'image' 		=> 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
	     ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }
        $special = new Special;
        if($request->hasfile('image')) {
            $image_file = request()->file('image');
            $image = time()."_".$image_file->getClientOriginalName();
            $img = Image::make($image_file->getRealPath());            
            $img->resize(200, 200, function ($constraint) {
                $constraint->aspectRatio();
            })->save( public_path().'/images/specials/' . $image);
            $special->image = $image;
        }
        $special->rest_id = request('rest_id');
        $special->code = rand(1,10000);
        $special->title = request('title');
        $special->description = request('description');
        $special->price = request('price');
        $special->discount = request('discount');
        $special->for = request('for');
        $special->available = request('available');
        $special->status = request('status');
        $special->save();
        DB::beginTransaction();
        try {
              $special->save();
              DB::commit();
              return response()->json(['success' => true, 'message' => 'Special data inserted successfully']);
            } catch (Exception $e) {
                DB::rollback();
                return response()->json([ 'success' => false,'message' => 'Special data insertion failed']);
            }
   }

   public function update(Request $request,$id){
 		$validator = Validator::make($request->all(),[     
	        'title'			=> 'required',
	        'description'	=> 'required',
	        'price'			=> 'required',
	        'discount'		=> 'required',
	        'for'			=> 'required',
	        'available'		=> 'required',
	        'status' 		=> 'required'	    
    	]);
        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }
        $special =  Special::find($id);
        if($request->hasfile('image')) {
            $image_file = request()->file('image');
            $fileName = str_replace(' ', '', $image_file->getClientOriginalName());
            $image = time()."_".$fileName;
            $img = Image::make($image_file->getRealPath());
            if (!empty($special->image)) {
                unlink(public_path() . '/images/specials/' . $special->image);
            }
            $img->resize(200, 200, function ($constraint) {
                $constraint->aspectRatio();
            })->save( public_path().'/images/specials/' . $image);
            $special->image = $image;
        }
        $special->code = rand(1,10000);
        $special->title = request('title');
        $special->description = request('description');
        $special->price = request('price');
        $special->discount = request('discount');
        $special->for = request('for');
        $special->available = request('available');
        $special->status = request('status');
        DB::beginTransaction();
        try {
              $special->save();
              DB::commit();
              return response()->json(['success' => true, 'message' => 'Special data updated successfully']);
            } catch (Exception $e) {
                DB::rollback();
                return response()->json([ 'success' => false,'message' => 'Special data updated failed']);
            }
    }

    public function destroy($id){
        $special = Special::find($id);
        $special->delete();
       	return response()->json(['success'=> true, 'message'=> 'special data deleted properly']);
    }

}
