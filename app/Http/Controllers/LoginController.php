<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use JWTFactory;
use JWTAuth;
use App\User;
use Illuminate\Support\Facades\Auth;
use DB;
use Response;
use App\Mail\reset_password;
use Carbon\Carbon;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password'=> 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => 'Email or Password error'], 401);
        }
        $credentials = $request->only('email', 'password');
        try {
            if ($token = JWTAuth::attempt($credentials)) {
                $user = JWTAuth::toUser($token);
                $status = User::select('status')->where('email',request('email'))->first();
                if($status->status == 1)
                return response()->json(compact('token','user'))->header("token", $token);
                 else  return response()->json(['error' => 'Your not Active'], 401);
            } else {
                return response()->json(['error' => 'Invalid email or password'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Login Failed'], 500);
        }
        
    }
    public function logout(Request $request) {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['success' => true, 'message'=> "You have successfully logged out."]);
        } catch (JWTException $e) {
            return response()->json(['success' => false, 'error' => 'Failed to logout, please try again.'], 500);
        }
    }

    public function forgotPassword(){
        $verification_code = rand(100000,100000000);
        $email = request('email');
        $has_mail= User::where('email',$email)->get();
        if(count($has_mail)>0){
            DB::table('password_resets')->insert([
                    'email' => $email,
                    'token' => $verification_code,
                    'created_at' => Carbon::now(),
            ]);
            \Mail::to(request('email'))->send(new reset_password($email,$verification_code ));
            return response()->json([
                    'success' => true,
                    'message' => 'Please check your email to reset your password.'
            ]);
        }else{
            return response()->json([
                'success' => false,
                'error' => 'Unathorized Email'
            ],401);
        }
    }

    public function verifyPassword(){
        $verify = DB::table('password_resets')->where([
            ['email', request('email')],
            ['token', request('token')]
            ])->get();
        if(count($verify)>0){
            return response()->json([
                'success' => true,
                'message' => 'Go to reset your password page.']);
        }else{
            return response()->json(['success' => false, 'error' => 'Unathorized'], 401);
        }
        
    }

    public function resetPassword(Request $request){
       
        $validator = Validator::make($request->all(),[
            'email'             =>  'required|email',
            'password'          =>  'required|min:4', // |regex:/^(?=\S*[a-z])(?=\S*[!@#$&*])(?=\S*[A-Z])(?=\S*[\d])\S*$/
            'confirmPassword'   =>  'required|same:password',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $email = request('email');
        //dd($email);
        $password = request('password'); 
        User::where('email',$email)->update(['password'=>bcrypt($password)]);
        return response()->json([
            'success' => true,
            'message' => 'Password Updated.']);
    }

}
