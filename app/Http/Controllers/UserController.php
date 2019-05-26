<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTFactory;
use JWTAuth;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Image;
use App\Favourite;

class UserController extends Controller
{
    /**** 1:admin; 2:restaurant owner; 3: general user ****/
    public function edit_user_info(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'contact' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $user_id = $request->get('user_id');
        $first_name = $request->get('first_name');
        $last_name = $request->get('last_name');
        $contact = $request->get('contact');
        $dob = $request->get('dob');

        $update_array = array();
        if (!is_null($first_name)) {
            $update_array['first_name'] = $first_name;
        }
        if (!is_null($last_name)) {
            $update_array['last_name'] = $last_name;
        }
        if (!is_null($contact)) {
            $update_array['contact'] = $contact;
        }
        if (!is_null($dob)) {
            $update_array['dob'] = $dob;
        }
        if (!is_null($user_id)) {
            $user = User::find($user_id);
        }
        if ($request->hasfile('image')) {
            $image_file = request()->file('image');
            $fileName = str_replace(' ', '', $image_file->getClientOriginalName());
            $image = time() . '_' . $fileName;
            $originalPath = public_path() . '/images/users/';
            $img = Image::make($image_file->getRealPath());
            // if (!empty($user->profile_pic)) {
            //     unlink(public_path() . '/images/users/' . $user->profile_pic);
            // }
            $img->resize(200, 200, function ($constraint) {
                $constraint->aspectRatio();
            })->save($originalPath . '/' . $image);

            $update_array['profile_pic'] = $image;
        }
        if (!is_null($user_id)) {
            DB::beginTransaction();
            try {
                    User::where('id', $user_id)->update($update_array);
                    DB::commit();
                    return response()->json([
                    'success' => true,
                    'message' => 'Information updated successfully'
                    ]);
            }catch (Exception $e){
                    DB::rollback();
                    return response()->json([ 'success' => false,'message' => 'Information update failed']);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No user found for update'
            ]);
        }
    }

    public function getUsers()
    {
        $users = User::paginate(10);
        return view('user.list', compact('users'));
    }

    public function update_user_status($id)
    {
        $status = Input::has('status_change') ? 1 : 0;
        $user = User::find($id);
        $user->status = $status;
        DB::beginTransaction();
        try {
                $user->save();
                DB::commit();
                return response()->json([
                'success' => true,
                'message' => 'User status updated succesfully'
                ]);
        }catch (Exception $e){
                DB::rollback();
                return response()->json([ 'success' => false,'message' => 'Information updated failed']);
        }
    }

    public function updateLocation(Request $request)
    {
        $user_id = $request->get('user_id');
        $lat = $request->get('lat');
        $lng = $request->get('lng');
        $user = User::find($user_id);
        if($user){
            $user->lat = $lat;
            $user->lng = $lng;
            $user->save();
            return response()->json([ 'success' => true,'message' => 'Location updated']);
        }
        else{
            return response()->json([ 'success' => false,'message' => 'Location update failed']);
        }
    }

    public function updateLocationFromBackground(Request $request , $user_id)
    {
        $user = User::find($user_id);
        $bodyContent = $request->getContent();

        if($user){
            $data = json_decode($bodyContent, true);
            $lat = $data[0]['latitude'];
            $lng = $data[0]['longitude'];

            $user->lat = $lat;
            $user->lng = $lng;
            $user->save();
            return response()->json([ 'success' => true,'message' => 'Location updated']);
        }
        else{
            return response()->json([ 'success' => false,'message' => 'Location update failed']);
        }
    }

    public function add_favourite(Request $request){
    	$validator = Validator::make($request->all(), [
            'spe_id' => 'required',
            'user_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }
        $res = Favourite::where([['spe_id',request('spe_id')],['user_id',request('user_id')]])->first();
        if(!$res){
            DB::beginTransaction();
            try {
                    Favourite::create([
                        'spe_id' => request('spe_id'),
                        'user_id' => request('user_id')
                    ]);
                    DB::commit();
                    return response()->json([ 'success' => true,'message' => 'Favourite added successfully']);
            }catch (Exception $e){
                    DB::rollback();
                    return response()->json([ 'success' => false,'message' => 'Favourite added failed']);
            }            
    	}
        return response()->json([ 'success' => false,'message' => 'Added Previously']);  
    }

    public function removeFavourite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'spe_id' => 'required',
            'user_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }
        $res = Favourite::where([['spe_id',request('spe_id')],['user_id',request('user_id')]])->first();
        if($res){
            $res->delete();
            return response()->json([ 'success' => true,'message' => 'Favourite removed successfully']);
        }
        return response()->json([ 'success' => false,'message' => 'Favourite remove failed']);
    }
    public function favouriteList($user_id,$page) {
        $limit = 20;
        if ($page) {
            $page = $page;
            $offset = ($page - 1) * $limit;
        } else {
            $page = 0;
            $offset = 0;
        }
        $favourites = DB::table('favourites')
                    ->join('users', 'users.id', '=', 'favourites.user_id')
                    ->join('specials', 'specials.id', '=', 'favourites.spe_id')
                    ->join('restaurants', 'restaurants.id', '=', 'specials.rest_id')
                    ->select('restaurants.id as rest_id','restaurants.name as restaurant_name','favourites.spe_id','specials.id', 'specials.title','specials.description','specials.image','specials.price','specials.discount','specials.for','specials.available')
                    ->where('favourites.user_id',$user_id)
                    ->limit($limit)->offset($offset)->get();
        $favourites_count = DB::table('favourites')
                    ->join('users', 'users.id', '=', 'favourites.user_id')
                    ->join('specials', 'specials.id', '=', 'favourites.spe_id')
                    ->join('restaurants', 'restaurants.id', '=', 'specials.rest_id')
                    ->select('restaurants.id as rest_id','restaurants.name as restaurant_name','favourites.spe_id','specials.id', 'specials.title','specials.description','specials.image','specials.price','specials.discount','specials.for','specials.available')
                    ->where('favourites.user_id',$user_id)->get();
        $total_reservation = count($favourites_count);
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
        return response()->json(compact('favourites','total_pages'));
    }

    public function favouriteListSearch(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }
        $user_id = request('user_id');
        $limit = 100;
        $condition = '';
        if(request('food')!='' && request('food') != 'undefined'){
            $food = request('food');
            $condition .=" AND  specials.title LIKE '%$food%'   ";
        }
        $favourites = DB::table('favourites')
                    ->join('users', 'users.id', '=', 'favourites.user_id')
                    ->join('specials', 'specials.id', '=', 'favourites.spe_id')
                    ->join('restaurants', 'restaurants.id', '=', 'specials.rest_id')
                    ->select('restaurants.id as rest_id','restaurants.name as restaurant_name','restaurants.*','favourites.spe_id','specials.id', 'specials.title','specials.description','specials.image','specials.price','specials.discount','specials.for','specials.available')
                    ->whereRaw("favourites.user_id = $user_id $condition")
                    ->limit($limit)
                    ->orderBy('specials.title')
                    ->get();
    
        return response()->json(compact('favourites'));
    }


    public function adminActivation(Request $request){
        $validator = Validator::make($request->all(),[
            'rest_id'             =>  'required',
            'status'          =>  'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => 'Restaurant owner /status not found'], 401);
        }
        $rest_id = request('rest_id');
        $status = request('status');
        DB::beginTransaction();
        try {
                User::where('rest_id',$rest_id)->update(['status'=>   $status]);
                DB::commit();
                return response()->json(['success' => true, 'message' => 'Restaurant activation successfully']);
        }catch (Exception $e){
              DB::rollback();
              return response()->json([ 'success' => false,'message' => 'Restaurant activation  failed']);
        }
    }

    public function get_user_search(Request $request){
        $client = request('client');
        if ($client != '') {
            if (is_numeric($client)) {
                $user_info = DB::select("SELECT * from users WHERE client_id LIKE '$client%'");
            }else {
                $user_info = DB::select("SELECT * from users WHERE first_name LIKE '$client%' OR last_name LIKE '$client%'");
            }
        } else {
            $user_info = DB::select("SELECT * from users LIMIT 100");
        }
        return response()->json(compact('user_info'));
    }

    public function userActivation(Request $request){
        $validator = Validator::make($request->all(),[
            'user_id'             =>  'required',
            'status'          =>  'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => 'User /status not found'], 401);
        }
        $user_id = request('user_id');
        $status = request('status');
        if($status == 2){
            $status = 0;
        }
        DB::beginTransaction();
        try {
                User::where('id',$user_id)->update(['status'=>   $status]);
                DB::commit();
                return response()->json(['success' => true, 'message' => 'User status update  successfully']);
        }catch (Exception $e){
              DB::rollback();
              return response()->json([ 'success' => false,'message' => 'User status update  failed']);
        }
        
    }
    
}
