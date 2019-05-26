<?php

namespace App\Http\Controllers;

use App\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\City;
use Validator;
use Image;

class RestaurantController extends Controller
{   
    public function show($id) {
        $resturant = Restaurant::find($id);
        return response()->json($resturant );
    }

    public function search_restaturant(){
        $limit = 20;
        $condition ='' ;
        if(request('restaurant') && request('restaurant') != 'undefined' ){
            $restaurant_name = request('restaurant') ;
            if($condition=='') $condition .=" name LIKE '%$restaurant_name%' ";
            else $condition .=" AND name LIKE '%$restaurant_name%' ";

        }
        if(request('city') && request('city') != 'undefined'&& !is_numeric(request('city'))){
            $unprocess_data = request('city');
            $city = explode(',', $unprocess_data);
            if($condition=='') $condition .=" region LIKE '$city[0]%' OR locality LIKE '$city[0]%'   ";
            else $condition .=" AND ( region LIKE '$city[0]%' OR locality LIKE '$city[0]%'  ) ";
        }
        if(request('city') && request('city') != 'undefined' && is_numeric(request('city'))){
            $city = request('city');
            if($condition=='')$condition .=" postcode = $city ";
            else $condition .=" AND postcode = $city ";
        }
        if($condition=='')
        $restaurant = Restaurant::orderBy('name','asc')->limit($limit)->get();
        else $restaurant = Restaurant::whereRaw("$condition")->orderBy('name','asc')->get();

        return response()->json(compact('restaurant'));
    }
    public function get_city($city_name){
        $city = City::where('name','like',''.$city_name.'%')->get();
        return response()->json($city);
    }

    public function show_all() {
        $resturants = Restaurant::all();
        return response()->json($resturants);
    }

    public function show_restaurant_info($id) {
        $resturants = Restaurant::select('name','description','type','ethnicity','category','address','postcode','locality'	,'region','contact','email','web','rating','cuisine','category_labels','lat','lng','opening','closing','logo','banner')
                                ->where('id',$id)
                                ->get();
        return response()->json($resturants);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(),[
        'factual_id' 	=> 'required',
        'name'  		=> 'required',
        'description'	=> 'required',
        'type'			=> 'required',
        'ethnicity'		=> 'required',
        'category'		=> 'required',
        'address'		=> 'required',
        'postcode'		=> 'required',
        'locality'		=> 'required',
        'region'		=> 'required',
        'contact'		=> 'required',
        'email'			=> 'required',
        'web'			=> 'required',
        'cuisine'		=> 'required',
        'opening'		=> 'required',
        'closing'		=> 'required'
    	]);
        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }
        $restaurant = new Restaurant;
        $restaurant->factual_id = request('factual_id');
        $restaurant->name = request('name');
        $restaurant->description = request('description');
        $restaurant->type = request('type');
        $restaurant->ethnicity = request('ethnicity');
        $restaurant->category = request('category');
        $restaurant->address = request('address');
        $restaurant->postcode = request('postcode');
        $restaurant->locality = request('locality');
        $restaurant->region = request('region');
        $restaurant->contact = request('contact');
        $restaurant->email = request('email');
        $restaurant->web = request('web');
        $restaurant->category_labels = request('category_labels');
        $restaurant->cuisine = request('cuisine');
        $restaurant->opening = request('opening');
        $restaurant->closing = request('closing');
        $restaurant->lat = request('lat');
        $restaurant->lng = request('lng');
        DB::beginTransaction();
        try {
                $restaurant->save();
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Restaurent data inserted proper'
                ]);
        }catch (Exception $e){
                DB::rollback();
                return response()->json([ 'success' => false,'message' => 'failed']);
        }
    }

    public function update(Request $request,$id) {
        $validator = Validator::make($request->all(),[
        'name'  		=> 'required',
        'description'	=> 'required',
        'type'			=> 'required',
        'ethnicity'		=> 'required',
        'category'		=> 'required',
        'address'		=> 'required',
        'postcode'		=> 'required',
        'locality'		=> 'required',
        'region'		=> 'required',
        'contact'		=> 'required',
        'email'			=> 'required',
        'cuisine'		=> 'required',
        'opening'		=> 'required',
        'closing'		=> 'required'
    	]);
        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }
        DB::beginTransaction();
        try {       
                Restaurant::where('id', $id)->update([
                    'name'          => request('name'),
                    'description'   => request('description'),
                    'type'          => request('type'),
                    'ethnicity'     => request('ethnicity'),
                    'category'      => request('category'),
                    'address'       => request('address'),
                    'postcode'      => request('postcode'),
                    'locality'      => request('locality'),
                    'region'        => request('region'),
                    'contact'       => request('contact'),
                    'email'         => request('email'),
                    'web'           => request('web'),
                    'category_labels'=> request('category_labels'),
                    'cuisine'       => request('cuisine'),
                    'opening'       => request('opening'),
                    'closing'       => request('closing'),
                ]);
                DB::commit();
                return response()->json(['success'=> true, 'message'=> 'Updated succesfully']);
        }catch (Exception $e){
                DB::rollback();
                return response()->json([ 'success' => false,'message' => 'failed']);
        }
    }

    public function destroy($id) {
        $restaurant = Restaurant::find($id);
        $restaurant->delete();
       return response()->json(['success'=> true, 'message'=> 'restaurent data deleted successfully']);
    }

    public function logo_banner(Request $request) {
        $validator = Validator::make($request->all(), [
            'rest_id' => 'required',
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'banner' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        if (request('rest_id') != "null") {
            $id = request('rest_id');
            $logo_img = "";
            $banner_img = "";
            $restaurant = Restaurant::find($id);
            if ($request->hasfile('logo')) {
                $logo_file = request()->file('logo');
                $fileName = str_replace(' ', '', $logo_file->getClientOriginalName());
                $logo = time() . "_" . $fileName;
                $img = Image::make($logo_file->getRealPath());
                if (!empty($restaurant->logo)) {
                    unlink(public_path() . '/images/logos/' . $restaurant->logo);
                }
                $img->resize(null, 200, function ($constraint) {
                    $constraint->aspectRatio();
                })->save(public_path() . '/images/logos/' . $logo);
                $logo_img = $logo;
            }
            if ($request->hasfile('banner')) {
                $banner_file = request()->file('banner');
                $fileName = str_replace(' ', '', $banner_file->getClientOriginalName());
                $banner = time() . "_" . $fileName;
                $img = Image::make($banner_file->getRealPath());
                if (!empty($restaurant->banner)) {
                    unlink(public_path() . '/images/banners/' . $restaurant->banner);
                }
                $img->resize(null, 200, function ($constraint) {
                    $constraint->aspectRatio();
                })->save(public_path() . '/images/banners/' . $banner);
                $banner_img = $banner;
            }
            DB::beginTransaction();
            try {
                    Restaurant::where('id', $id)->update([
                        'logo' => $logo_img,
                        'banner' => $banner_img
                    ]);
                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => 'updated successfully'
                    ]);
            }catch (Exception $e){
                    DB::rollback();
                    return response()->json([ 'success' => false,'message' => 'failed']);
            }
        } else {
            return response()->json(['success'=> false, 'message'=> 'Unathorized'],401);
        }
    }

    public function show_logo_banner($rest_id) {
    	$restaurant = Restaurant::select('logo','banner')->where('id',$rest_id)->get();
    	return response()->json($restaurant);
    }
}
