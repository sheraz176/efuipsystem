<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class Usercontroller extends Controller
{

    function index(Request $request)
    {
        $user= User::where('name', $request->name)->first();
        // print_r($data);
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response([
                    'message' => ['These credentials do not match our records.']
                ], 404);
            }

            date_default_timezone_set('Asia/Karachi'); 
            $currentDateTime = date('Y-m-d H:i:s');
            $ldate = date('Y-m-d H:i:s', strtotime($currentDateTime . ' +30 minutes'));

             $token = $user->createToken('my-app-token')->plainTextToken;
        
            $response = [
                'token' => $token,
                'token expiration'=> $ldate
            ];
        
             return response($response, 201);
    }

public function getProducts(Request $request)
    {

        $planId = $request->input('plan_id');

    // Retrieve active products associated with the specified plan ID
    $products = ProductModel::where('plan_id', 1)
                            ->where('status', 1)
                            ->get();

    return response()->json($products);    
    }

public function Subscription(Request $request)
    {
        // $payer_cnic = $request->input("payer_cnic");
        // $payer_msisdn = $request->input("payer_msisdn");
        $subscriber_cnic = $request->input("subscriber_cnic");
        $subscriber_msisdn = $request->input("subscriber_msisdn");
        //$beneficinary_name = $request->input("beneficinary_name");
        // $benficinary_msisdn = $request->input("benficinary_msisdn");
        $transaction_amount = $request->input("transaction_amount");
        $transactionStatus = $request->input("transactionStatus");
        $cpsOriginatorConversationId = $request->input("cpsOriginatorConversationId");
        $cpsTransactionId = $request->input("cpsTransactionId");
        $cpsRefundTransactionId = "1";
        $cpsResponse = $request->input("cpsResponse");
        $planId = $request->input("planId");
        $planCode = $request->input("planCode");
        //$plan_status = $request->input("plan_status");
        $APIsource = $request->input("APIsource");

        $payer_cnic = $subscriber_cnic;
        $payer_msisdn = $subscriber_msisdn;
        $plan_status = 1;
        
        $benficinary_msisdn = 0;
        $beneficinary_name = "Need to Filled in Future";
        

        $customer_id = '0011' . $subscriber_msisdn;
        
        $rules=[
            // 'payer_cnic' => 'required|numeric',
            // 'payer_msisdn' => 'required|numeric',
            'subscriber_cnic' => 'required|numeric',
            'subscriber_msisdn' => 'required|numeric',
            // 'beneficinary_name' => 'required|string',
            // 'benficinary_msisdn' => 'required|numeric',
            'transaction_amount' => 'required|numeric',
            'transactionStatus' => 'required|string',
            'cpsOriginatorConversationId' => 'required|string',
            'cpsTransactionId' => 'required|string',
            //'cpsRefundTransactionId' => 'required|string',
            'cpsResponse' => 'required|string',
            'planId' => 'required|numeric',
            'planCode' => 'required|string',
            // 'plan_status' => 'required|string',
            'APIsource' => 'required|string'
        ];
        $validator = Validator::make($request->all(), $rules);


        if ($validator->fails()) 
        {
        return response()->json(['error' => $validator->errors()], 400);
         }
    
	$products = ProductModel::where('plan_id', $planId)
                        ->where('product_code', $planCode) // Add this line
                        ->where('status', 1)
                        ->select('fee', 'duration', 'status','product_id')
                        ->first();

	$fee = $products->fee;
        $duration = $products->duration;
	$productid = $products->product_id;
        
       
        //Grace Period
        $grace_period='14';
        
        $current_time = time(); // Get the current Unix timestamp
        $future_time = strtotime('+14 days', $current_time); // Add 14 days to the current time

        $activation_time=date('Y-m-d H:i:s');
        // Format the future time if needed
        $grace_period_time = date('Y-m-d H:i:s', $future_time);


        //Recusive Charging Date 

        $future_time_recursive = strtotime("+" . $duration . " days", $current_time);
        $future_time_recursive_formatted = date('Y-m-d H:i:s', $future_time_recursive);
        
        $subscription=0;
                    
        //$subscription->makeHidden(['created_at', 'updated_at']);    

        if ($subscription) {
            // Record exists and status is 1 (subscribed)
        return response()->json([
            'error' => false,
            'messageCode' => 2001,
            'message' => 'Already subscribed to the plan.',
            'planCode' => $subscription['planCode'],
            'transactionAmount' => $subscription['transaction_amount'],
            'Subscriber Number' =>  $subscription['subscriber_msisdn'],
            'Subcription Time'  =>  $subscription['subcription_time']
        ]);
        } 
        
        else {
            
             $CustomerSubscriptionData = CustomerSubscription::create([
                        'customer_id'=> $customer_id,
                        'payer_cnic' => -1,
                        'payer_msisdn' => $subscriber_msisdn,
                        'subscriber_cnic' =>-1,
                        'subscriber_msisdn' =>$subscriber_msisdn,
                        'beneficiary_name' =>-1,
                        'beneficiary_msisdn' =>-1,
                        'transaction_amount' =>$fee,
                        'transaction_status' =>1,
                        'referenceId' =>$cpsOriginatorConversationId,
                        'cps_transaction_id' =>$cpsTransactionId,
                        'cps_response_text' =>"Service Activated Sucessfully",
                        'product_duration' =>$duration,
                        'plan_id' =>$planId,
                        'productId' =>$productid,
                        'policy_status' =>1,
                        'pulse' =>"Recusive Charging",
                        'api_source' => "IVR Subscription",
                        'recursive_charging_date' => $future_time_recursive_formatted,
                        'subscription_time' =>$activation_time,
                        'grace_period_time' => $grace_period_time,
                        'sales_agent' => -1,
                        'company_id' =>12
                    ]);     

        $CustomerSubscriptionDataID=$CustomerSubscriptionData->id;
        
       $subscriptionData = CustomerSubscription::where('subscription_id', $CustomerSubscriptionDataID)->get();
        $CustomerSubscriptionData->makeHidden(['created_at', 'updated_at']);

        
            
        return response()->json(['error'=>'false', 'messageCode' => 2002, 'message' =>'Policy Subscribed Sucessfuly' ,'policy_subscription_id'=>$CustomerSubscriptionDataID,'Information'=>$CustomerSubscriptionData,'message'=>'Customer Subscribed Sucessfully','Status Code'=>200]);
        
            
        }

        
    
    }
	
        public function activesubscriptions(Request $request)
    {
        $subscriber_msisdn = $request->input("subscriber_msisdn");
        $rules=[
            'subscriber_msisdn' => 'required|numeric'
        ];

        $validator = Validator::make($request->all(), $rules);
                if ($validator->fails()) 
                {
                return response()->json(['error' => $validator->errors()], 400);
                }

                $subscription = CustomerSubscription::where('subscriber_msisdn', $subscriber_msisdn)
                    ->where('plan_id', 1)
                    ->first();   

                
                if ($subscription)
                {
                return response()->json(['error' => false, 'is_policy_data' => 'true', 'message' => 'Active Policies', 'Active Subscriptions' => $subscription]);     
                }
                else
                {
                return response()->json(['error' => false, 'is_policy_data' => 'false', 'message' => 'Customer Didnt Subscribed to any Policy', 'Active Subscriptions' => $subscription]);
                }
        
    }


    public function unsubscribeactiveplan(Request $request)
    {
        $subscriber_msisdn = $request->input("subscriber_msisdn");
        $subscriptionId = $request->input("id");

        //Get Grace Period Time 
	$subscription = CustomerSubscription::findOrFail($subscriptionId);        

        if (!$subscription) {
            return response()->json(['error' => 'Subscription not found.'], 404);
        }

        $grace_period_time=$subscription->grace_period_time;
        $transaction_amount=$subscription->transaction_amount;
        $planCode=$subscription->planCode;
        $Subscription_id=$subscription->subscription_id;


        $current_time=date('Y-m-d H:i:s');
        $grace_period_datetime = new \DateTime($grace_period_time);
        $current_datetime = new \DateTime($current_time);

        if ($grace_period_datetime < $current_datetime) {

            $current_datetime = new \DateTime($current_time);

            $subscription->update(['policy_status' => 0]);

        if ($subscription) {
            return response()->json(['status_code' => 200, 'refund' => 'false','message' => 'Package Unsubscribe Sucessfullly and Your are Not Eligible for Refund Because Grace Period is Over']);
        } else {
            return response()->json(['error' => 'No records updated.'], 404);
        }

        } 
        elseif ($grace_period_datetime > $current_datetime) 
        {

            $current_datetime = new \DateTime($current_time);

            $affectedRows = CustomerSubscriptionModel::updateStatusByMsisdnAndSubscriptionId($subscriber_msisdn, $Id);
            
            $RefundRow = CustomerRefundModel::create([
                'subscriber_msisdn' => $subscriber_msisdn, // Replace with the actual value
                'refund_amount' => $transaction_amount, // Replace with the actual value
                'plan_code' => $planCode, // Replace with the actual value
                'refund_status' => 0, // Replace with the actual value
                'RefundDate' => $current_datetime, // Replace with the actual value
                'IsAmountTransfer' => 0, // Replace with the actual value
                'subscription_id' => $Id // Replace with the actual value
            ]);
        

        if ($affectedRows > 0) {
            return response()->json([
                'message' => 'Package Unsubscribe Sucessfullly, and You are Eligible for Refund',
                'status_code' => 200,
                'refund' => 'true',
                'data_for_refund' => [
                    'Refund API Data'=>$RefundRow,
                    'refund_api' => 'https://mhealth.efulife.com/public/api/closeRefundCase',
                ]
            ]);

        } else {
            return response()->json(['error' => 'No records updated.'], 404);
        }


        } 
        
        else {
            return response()->json("Grace period time is the same as the current time.");
        }


    }
		
}


