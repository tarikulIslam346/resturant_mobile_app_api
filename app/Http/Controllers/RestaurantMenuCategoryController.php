<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Validator;
use App\RestaurantMenuCategory;
use Illuminate\Support\Facades\DB;

class RestaurantMenuCategoryController extends Controller
{
    public function get_category_list($rest_id) {
     	$catagory = RestaurantMenuCategory::where('rest_id',$rest_id)->get();
 		return response()->json($catagory);
    }
    public function show_all() {
     	$catagory = RestaurantMenuCategory::all();
 		return response()->json($catagory);
    }
    public function store(Request $request,$id) {
        $validator = Validator::make($request->all(),[
            'name'=> 'required'		
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        } 
        $catagory = new RestaurantMenuCategory;
        $catagory->rest_id = $id;
        $catagory->name = request('name');
        DB::beginTransaction();
        try {
                $catagory->save();
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Catagory  inserted Succesfully'
                ]);
        }catch (Exception $e){
                DB::rollback();
                return response()->json([ 'success' => false,'message' => 'failed']);
        }
        //return response()->json(['success'=> true, 'message'=> 'Catagory  inserted Succesfully']);
   	}
    public function update(Request $request,$category_id) {
        $validator = Validator::make($request->all(),[
            'name'=> 'required',		
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }
        DB::beginTransaction();
        try {
                RestaurantMenuCategory::where('id','=',$category_id)->update([
                    'name' => request('name')
                ]);
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Catagory data updated properly'
                ]);
        }catch (Exception $e){
                DB::rollback();
                return response()->json([ 'success' => false,'message' => 'failed']);
        }
        //return response()->json(['success'=> true, 'message'=> 'Catagory data updated properly']);
   }
    public function delete($id) {
    	RestaurantMenuCategory::destroy($id);	
    	return response()->json(['success'=> true, 'message'=> 'Catagory data deleted properly']);
    }
}
