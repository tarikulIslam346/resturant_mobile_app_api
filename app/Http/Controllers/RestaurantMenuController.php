<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Restaurant;
use App\RestaurantMenu;
use App\Special;
use App\RestaurantMenuCategory;
use  Validator;
use Illuminate\Support\Facades\DB;

class RestaurantMenuController extends Controller
{
    public function get_menu_list($rest_id) {
     	$menus = DB::table('restaurant_menus')
     				->join('restaurant_menu_categories', 'restaurant_menus.category_id', '=', 'restaurant_menu_categories.id')
     				->select(DB::raw('restaurant_menus.id as menu_id'),'restaurant_menus.rest_id','restaurant_menus.category_id',DB::raw('restaurant_menu_categories.name as category_name '),'restaurant_menus.name','restaurant_menus.details','restaurant_menus.price','restaurant_menus.is_active')
     				->where('restaurant_menus.rest_id',$rest_id)
     				->get();
 		return response()->json($menus);
	}
	public function get_menu_with_restaurant_details($rest_id){
        $restaurant = Restaurant::find($rest_id);
        $catagory = RestaurantMenuCategory::where('rest_id', $rest_id)->get();

        $menus = array();
        foreach ($catagory as $c) {
            $m['category'] = $c['name'];
            $m['list'] = RestaurantMenu::where('category_id', $c['id'])->get();
            if(count($m['list']) > 0)
                array_push($menus, $m);
            unset($m);
        }
     	/* $menus = DB::table('restaurant_menus')
     				->join('restaurant_menu_categories', 'restaurant_menus.category_id', '=', 'restaurant_menu_categories.id')
     				->select(DB::raw('restaurant_menus.id as menu_id'),'restaurant_menus.rest_id','restaurant_menus.category_id',DB::raw('restaurant_menu_categories.name as category_name '),'restaurant_menus.name','restaurant_menus.details','restaurant_menus.price','restaurant_menus.is_active')
     				->where('restaurant_menus.rest_id',$rest_id)
     				->get(); */
 		return response()->json(compact('restaurant','menus'));

    }
    public function show_all() {
     	$menus = RestaurantMenu::all();
 		return response()->json($menus);
	}
	
    public function store(Request $request) {
		$validator = Validator::make($request->all(),[  
		    'name'			=> 'required',
		    'details' 		=> 'required',
		    'price' 		=> 'required',
		    'is_active' 	=> 'required',
		    'category_id'	=> 'required',
		    'rest_id'		=> 'required'
		]);
		if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }
        $menu = new RestaurantMenu;
        $menu->category_id	= request('category_id');
        $menu->rest_id 		= request('rest_id');
        $menu->name 		= request('name');
        $menu->details 		= request('details');
        $menu->price 		= request('price');
        $menu->is_active	= request('is_active');
        DB::beginTransaction();
        try {
            $menu->save();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Menu  stored successfully'
            ]);
        }catch (Exception $e){
            DB::rollback();
            return response()->json([ 'success' => false,'message' => 'failed']);
        }
        //return response()->json(['success'=> true, 'message'=> 'Menu  stored successfully']);
    }
	
    public function update(Request $request,$id) {
		$validator = Validator::make($request->all(),[
		    'name'		=> 'required',
		    'details' 	=> 'required',
		    'price'		=> 'required',
		    'is_active' => 'required'		
		]);
		if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }
        DB::beginTransaction();
        try {
            RestaurantMenu::where('id',$id)->update([
                'category_id'   => request('category_id'),
                'name'          => request('name'),
                'details'       => request('details'),
                'price'         => request('price'),
                'is_active'     => request('is_active')
            ]);
             DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Menu  updated  successfully'
            ]);
        }catch (Exception $e){
            DB::rollback();
            return response()->json([ 'success' => false,'message' => 'failed']);
        }
        //return response()->json(['success'=> true, 'message'=> 'Menu  updated  successfully']);
   	}
	
    public function delete($id) {
    	RestaurantMenu::destroy($id);	
    	return response()->json(['success'=> true, 'message'=> 'Menu  deleted successfully']);
    }
}
