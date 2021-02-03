<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Session;
use Stripe;
use App\User;
use App\Mail\FeedbackMail;
use App\Mail\Receipts;

use Illuminate\Support\Facades\Mail;
use Exception;

class StripePaymentController extends Controller
{
    private $_useremail;
    private $_amount;
    private $_name;
    private $_phone;
    private $_address;
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function stripe(Request $request)
    {
        $post_id = $request->get('post_id');
        $cur_poster = Poster::find($post_id);

        return view('stripe',compact('cur_poster'));
    }
  
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function stripePost(Request $request)
    {
        $this->_useremail = $request->get('user_email');
        $this->_amount = $request->get('total_price');
        $this->_name = $request->get('user_name');
        $this->_address = $request->get('address');
        $this->_phone = $request->get('phonenumber');    

        $price = $request->total_price;
        Stripe\Stripe::setApiKey(env('STRIPE_SECRET')); 
       
        try {
            $temp = Stripe\Charge::create ([
                "amount" => $price*100,
                "currency" => "usd",
                "source" => $request->stripeToken,
                "description" => "Payment from HACKERODE." 
            ]);            
        }
        catch(Exception $e) {           
            return back()->with("error",$e->getMessage()); 
        }
        
        if($temp->status == "succeeded")
        {     
             
            $feedback = array();
            $feedback['amount'] = $this->_amount;
            $feedback['name'] = $this->_name;
            $feedback['address'] = $this->_address;
            $feedback['phone'] = $this->_phone;
            $feedback['mail'] = $this->_useremail;
            $feedback['role'] = "user";
            $feedback['unit'] = "$";
            $toEmail = $this->_useremail;
            Mail::to($toEmail)->send(new FeedbackMail($feedback));
            
            $toEmail = env('ADMIN_MAIL');
            $feedback['role'] = "admin";
            Mail::to($toEmail)->send(new FeedbackMail($feedback));

            session()->flash('success', 'Your payment has been prosessed successfully!');
            return redirect(url('/'));
        }
        else
        {
            return back();
        }


    }
    
   
}