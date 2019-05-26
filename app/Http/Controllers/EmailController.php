<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\welcome;

class EmailController extends Controller
{
    //
    public function send_mail(){
    	$user = 'tarik@example.com';
    	$msg = request('msg');
    	$phone = '01796248701';

    	\Mail::to($user)->send(new welcome($msg,$phone ));
    }
}
