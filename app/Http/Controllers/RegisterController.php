<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\Restaurant;
use JWTFactory;
use JWTAuth;
use Validator, DB;
use Response;
use App\Mail\email_verify;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|string|email|max:255|unique:users',
            'password'=> 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }       
        $first_name = $request->get('first_name');
        $email = $request->get('email');
        $client_id = mt_rand(100000, 999999);
        $data = User::create([
            'first_name' => $request->get('first_name'),
            'last_name' => $request->get('last_name'),
            'client_id' => $client_id,
            'email' => $request->get('email'),
            'contact' => $request->get('contact'),
            'dob' => $request->get('dob'),
            'password' => bcrypt($request->get('password')),
            'status' => 0,
            'type' => 3
        ]);
        $user = User::first();
        $last_inserted_id = $data->id;
        $verification_code = str_random(6);
        DB::beginTransaction();
        try {
                $verification_code_update = User::where('id', $last_inserted_id)
                                                ->update(['verification_code' => $verification_code]);
                DB::commit();
                \Mail::to($email)->send(new email_verify($first_name,$verification_code ));
                return response()->json([
                    'success' => true,
                    'message' => 'Thanks for signing up! Please check your email to complete your registration.'
                ]);
        }catch (Exception $e){
                DB::rollback();
                return response()->json([ 'success' => false,'message' => 'failed']);
        }       
    }
    public function verifyUser($verification_code)
    {
        $check = DB::table('users')->where('verification_code',$verification_code)->first();
        if(!is_null($check)){
            $user = User::find($check->id);
            if($user->status == 1){
                return response()->json([
                    'success'=> true,
                    'message'=> 'Account already verified..'
                ]);
            }
            DB::beginTransaction();
            try {
                    $user->update(['status' => 1]);
                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => 'You have successfully verified your email address.'
                    ]);
            }catch (Exception $e){
                    DB::rollback();
                    return response()->json([ 'success' => false,'message' => 'failed']);
            }
        }
        return response()->json(['success'=> false, 'error'=> "Verification code is invalid."]);
    }
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password'=> 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $user_id = $request->get('user_id');
        $current_password = $request->get('current_password');
        $new_password = bcrypt($request->get('new_password'));
        if (!is_null($user_id)) {
            $user = User::find($user_id);
            $password = $user->password;
            if (Hash::check($current_password, $password)) {
                $user->password = $new_password;
                DB::beginTransaction();
                try {
                        $user->save();
                        DB::commit();
                        return response()->json([
                        'success' => true,
                        'message' => 'Password changed successfully'
                        ]);
                }catch (Exception $e){
                        DB::rollback();
                        return response()->json([ 'success' => false,'message' => 'failed']);
                }
            } else {
                return response()->json(['success' => false, 'error' => 'Current password does not match'], 500);
            }
        }
    }

    public function RestaurantOwnerSignUp(Request $request)
    {
        /*restaurant third party api data*/
        /***parse json data**/
        $factual_id = $name =  $description = $type = $ethnicity = $category = $address =$postcode = $locality = $region = $contact = $email = $web = $category_labels = $cuisine =$opening = $closing = $lat = $lng = ''; 
        if(request('isManual')!='true'){
            $jsondata = $_POST['restaurant'];
            $restaurant = json_decode($jsondata );
            $factual_id = $restaurant->factual_id;
            $name = $restaurant->name;
            $description = $restaurant->description;
            $type = $restaurant->type;
            $ethnicity = $restaurant->ethnicity;
            $category = $restaurant->category;
            $address = $restaurant->address;
            $postcode = $restaurant->postcode;
            $locality = $restaurant->locality;
            $region = $restaurant->region;
            $contact = $restaurant->contact;
            $email = $restaurant->email;
            $web = $restaurant->web;
            $category_labels = $restaurant->category_labels;
            $cuisine = $restaurant->cuisine;
            $opening = $restaurant->opening;
            $closing = $restaurant->closing;
            $lat = $restaurant->lat;
            $lng = $restaurant->lng;
            /**find restaurant data in foodoli database**/
            $find_restaurant = Restaurant::select('id AS rest_id')
                                ->where('factual_id',$factual_id)
                                ->first();
            $rest_id=null;
            if(count($find_restaurant)>0 ){
                return response()->json(['error' => 'Aleardy claimed'],401);
            }else{
                $rest_id =  $restaurant->id;
                $restaurant = new Restaurant;
                $restaurant->factual_id = $factual_id;
                $restaurant->name = $name;
                $restaurant->description = $description;
                $restaurant->type = $type;
                $restaurant->ethnicity = $ethnicity;
                $restaurant->category = $category;
                $restaurant->address = $address;
                $restaurant->postcode = $postcode;
                $restaurant->locality = $locality;
                $restaurant->region = $region;
                $restaurant->contact = $contact;
                $restaurant->email = $email;
                $restaurant->web = $web;
                $restaurant->category_labels = $category_labels;
                $restaurant->cuisine = $cuisine;
                $restaurant->opening = $opening;
                $restaurant->closing = $closing;
                $restaurant->lat = $lat;
                $restaurant->lng = $lng;
                DB::beginTransaction();
                try {
                        $restaurant->save();
                        DB::commit();
                }catch (Exception $e){
                        DB::rollback();
                        return response()->json([ 'success' => false,'message' => 'failed']);
                }
                 /**entry restaurant data from api**/
                /**validation */
                $validator = Validator::make($request->all(), [
                    'first_name'    => 'required',
                    'last_name'     => 'required',
                    'email'         => 'required|string|email|max:255|unique:users',
                    'password'      => 'required'
                ]);
                if ($validator->fails()) {
                    return response()->json($validator->errors());
                } 
                /**valid data **/
                /**create user*/      
                $first_name = $request->get('first_name');
                $email = $request->get('email');
                $client_id = mt_rand(100000, 999999);
                DB::beginTransaction();
                try {
                    $data = User::create([
                        'first_name'    => $request->get('first_name'),
                        'last_name'     => $request->get('last_name'),
                        'client_id'     => $client_id,
                        'email'         => $request->get('email'),
                        'contact'       => $request->get('contact'),
                        'dob'           => $request->get('dob'),
                        'password'      => bcrypt($request->get('password')),
                        'status'        => 0,
                        'type'          => 2,
                        'rest_id'       => $rest_id
                    ]);
                    DB::commit();
                }catch (Exception $e){
                    DB::rollback();
                    return response()->json([ 'success' => false,'message' => 'failed']);
                }
                $user = User::first();
                $last_inserted_id = $data->id;
                $verification_code = str_random(6);
                DB::beginTransaction();
                try {
                        $verification_code_update = User::where('id', $last_inserted_id)
                                                        ->update(['verification_code' => $verification_code]);
                        DB::commit();
                        \Mail::to($email)->send(new email_verify($first_name,$verification_code ));
                        return response()->json([
                            'success' => true,
                            'message' => 'Thanks for signing up! Please check your email to complete your registration.'
                        ]);
                }catch (Exception $e){
                        DB::rollback();
                        return response()->json([ 'success' => false,'message' => 'failed']);
                } 
            }
        }
       
        if(request('isManual')=='true'){
            $f_id = mt_rand(100000000, 999999999); 
            $restaurant = new Restaurant;
            $restaurant->factual_id = $f_id;
            $restaurant->name = request('restaurant_name');
            $restaurant->address = request('address');
            $restaurant->postcode = request('postcode');
            $restaurant->opening =  request('opening');
            $restaurant->closing =  request('closing');
            $restaurant->lat = request('lat');
            $restaurant->lng = request('lng');
            DB::beginTransaction();
            try {
                    $restaurant->save();
                    DB::commit();
            }catch (Exception $e){
                    DB::rollback();
                    return response()->json([ 'success' => false,'message' => 'failed']);
            }
            /**validation */
            $validator = Validator::make($request->all(), [
                'first_name'    => 'required',
                'last_name'     => 'required',
                'email'         => 'required|string|email|max:255|unique:users',
                'password'      => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors());
            } 
            /**valid data **/
            /**create user*/ 
            $rest_id = null;     
            $client_id = mt_rand(100000, 999999);
            $restaurant = Restaurant::where('factual_id',$f_id)->first();
            DB::beginTransaction();
            try {
                $data = User::create([
                    'first_name'    => $request->get('first_name'),
                    'last_name'     => $request->get('last_name'),
                    'client_id'     => $client_id,
                    'email'         => $request->get('email'),
                    'contact'       => $request->get('contact'),
                    'dob'           => $request->get('dob'),
                    'password'      => bcrypt($request->get('password')),
                    'status'        => 0,
                    'type'          => 2,
                    'rest_id'       => $restaurant->id
                ]);
                DB::commit();
            }catch (Exception $e){
                DB::rollback();
                return response()->json([ 'success' => false,'message' => 'failed']);
            }
            $user = User::first();
            $first_name = $request->get('first_name');
            $email = $request->get('email');
            $last_inserted_id = $data->id;
            $verification_code = str_random(6);
            DB::beginTransaction();
            try {
                    $verification_code_update = User::where('id', $last_inserted_id)
                                                    ->update(['verification_code' => $verification_code]);
                    DB::commit();
                    \Mail::to($email)->send(new email_verify($first_name,$verification_code ));
                    return response()->json([
                        'success' => true,
                        'message' => 'Thanks for signing up! Please check your email to complete your registration.'
                    ]);
            }catch (Exception $e){
                    DB::rollback();
                    return response()->json([ 'success' => false,'message' => 'failed']);
            } 
        }
            
           
    }
}


