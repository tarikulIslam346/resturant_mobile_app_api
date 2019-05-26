<?php

namespace App\Http\Controllers;

use App\Restaurant;
use App\User;
use Illuminate\Http\Request;
use App\RestaurantReviews;
use App\RestaurantReviewImages;
use Illuminate\Support\Facades\DB;
use Validator;
use Image;

class RestaurantReviewsController extends Controller
{
    public function addReview(Request $request, $user_id, $rest_id){
        $validator = Validator::make($request->all(), [
            'rest_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }
        $review = new RestaurantReviews();
        $review->review_for = $rest_id;
        $review->review_by = $user_id;
        $review->review = request('review');
        if($request->hasfile('image')) {
            $image_file = request()->file('image');
            $image = time()."_".$image_file->getClientOriginalName();
            $img = Image::make($image_file->getRealPath());
            $img->resize(200, 200, function ($constraint) {
                $constraint->aspectRatio();
            })->save( public_path().'/images/reviews/' . $image);
            $review->image = $image;
        }
        DB::beginTransaction();
        try {
                $review->save();
                DB::commit();
                return response()->json(['success' => true, 'message' => 'Review  updated successfully']);
        }catch (Exception $e){
              DB::rollback();
              return response()->json([ 'success' => false,'message' => 'Review  update failed']);
            }
    }
    public function addReviewImage(Request $request, $user_id, $rest_id){
        $validator = Validator::make($request->all(), [
            'rest_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }
        $review = new RestaurantReviewImages();
        $review->review_for = $rest_id;
        $review->review_by = $user_id;
        if($request->hasfile('image')) {
            $image_file = request()->file('image');
            $image = time()."_".$image_file->getClientOriginalName();
            $img = Image::make($image_file->getRealPath());
            $img->resize(200, 200, function ($constraint) {
                $constraint->aspectRatio();
            })->save( public_path().'/images/review_images/' . $image);
            $review->image = $image;
        }
        DB::beginTransaction();
        try {
                $review->save();
                DB::commit();
                return response()->json(['success' => true, 'message' => 'Review Image updated successfully']);
        }catch (Exception $e){
              DB::rollback();
              return response()->json([ 'success' => false,'message' => 'Review Image update failed']);
            }
    }

    public function reviewList($rest_id){
     
        $reviews = DB::table('restaurant_reviews')
            ->join('users', 'users.id', '=', 'restaurant_reviews.review_by')
            ->select('restaurant_reviews.*', 'users.first_name', 'users.last_name', 'users.profile_pic')
            ->where('review_for',$rest_id)
            ->get();

        $restaurant = Restaurant::find($rest_id);

        return response()->json(compact('reviews','restaurant'));
    }
    public function reviewImageList($rest_id){
     
        $review_images = DB::table('restaurant_review_images')
            ->join('users', 'users.id', '=', 'restaurant_review_images.review_by')
            ->select('restaurant_review_images.*', 'users.first_name', 'users.last_name', 'users.profile_pic')
            ->where('review_for',$rest_id)
            ->get();

        $restaurant = Restaurant::find($rest_id);

        return response()->json(compact('review_images','restaurant'));
    }
}
