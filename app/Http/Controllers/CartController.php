<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Surfsidemedia\Shoppingcart\Facades\Cart; 
use App\Models\Coupon;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CartController extends Controller
{

    public function index(){
        $items=Cart::instance('cart')->content();
        return view('cart', compact('items')); 
    }
    public function add_to_cart(Request $request){
        Cart::instance('cart')->add($request->id,$request->name,$request->quantity,$request->price)->associate('App\Models\Product');
        return redirect()->back();
    }
    public function increase_quantity($rowId){
        $product=Cart::instance('cart')->get($rowId);
        $qty=$product->qty+1;
        Cart::instance('cart')->update($rowId, $qty);
        return redirect()->back();
    }
    public function decrease_quantity($rowId){
        $product=Cart::instance('cart')->get($rowId);
        $qty=$product->qty-1;
        Cart::instance('cart')->update($rowId, $qty);
        return redirect()->back();
    }
    public function remove_item($rowId){
        Cart::instance('cart')->remove($rowId);
        return redirect()->back();
    }
    public function clear_cart(){
        Cart::instance('cart')->destroy();
        return redirect()->back();
    }


    public function apply_coupon_code( Request $request){
        $coupon_code=$request->coupon_code;
        if(isset($coupon_code)){
            $coupon_code=Coupon::where('code',$coupon_code)->where('expory_date','>=',Carbon::today())
            ->where('cart_value','<=',Cart::instance('cart')->subtotal())->first();
            if(!$coupon_code){
                return redirect()->back()->with('Error','Invalid coupon code.');
            }  else{

                Session::put(
                    'coupon',
                    [
                        'code'=>$coupon_code->code,
                        'type'=>$coupon_code->type,
                        'value'=>$coupon_code->value,
                        'cart_value'=>$coupon_code->cart_value
                    ]
                    );
                    $this->calculate_discount();
                return redirect()->back()->with('Success','Coupon code applied successfully.');
            }
        } else{
            return redirect()->back()->with('Error','Please enter a coupon code.');
        }

    }
    public function calculate_discount(){
        $discount=0;
        if(Session::has('coupon')){
            if(Session::get('coupon')['type']=='fixed'){
                 $discount=Session::get('coupon')['value'];

            } else {
                $discount=(Cart::instance('cart')->subtotal() *Session::get('coupon')['value'])/100;
            }
            $subtotal_after_discount=Cart::instance('cart')->subtotal() - $discount;
            $tax_after_discount=($subtotal_after_discount * config('cart.tax'))/100;
            $total_after_discount=$subtotal_after_discount + $tax_after_discount;
            Session::put('discounts',[
                'discount'=>number_format(floatval($discount),2,'.',''),
                'subtotal' => number_format(floatval($subtotal_after_discount),2,'.',''),
                'tax' => number_format(floatval($tax_after_discount),2,'.',''),
                'total' => number_format(floatval($total_after_discount),2,'.','')
            ]);
            
        }
    }
    public function remove_coupon_code(){
        Session::forget('coupon');
        Session::forget('discounts');
        return redirect()->back()->with('Success','Coupon code removed successfully.');
    }
    public function checkout(){
        if(!Auth::check()){
            return redirect()->route('login')->with('Error','Please login to proceed to checkout.');
        }
        $address=Address::where('user_id',Auth::user()->id)->where('isdefault',1)->first();
        return view('checkout', compact('address'));
    }
    public function plance_an_order(Request $request){
        $user=Auth::user();
        $address=Address::where('user_id',$user->id)->where('isdefault',true)->first();
        //place order logic here
        if(!$address){
            $request->validate([
                'name'=>'required|string|max:255',
                'phone'=>'required|numeric|digits:10',
                'address'=>'required',
                'city'=>'required',
                'state'=>'required',
                 'zip'=>'required|numeric|digits:6',
                'landmark'=>'required',
                'locality'=>'required'
                
            ]);
            $address=new Address();
            $address->user_id=$user->id;
            $address->name=$request->name;
            $address->phone=$request->phone;
            $address->address=$request->address;
            $address->city=$request->city;
            $address->state=$request->state;
            $address->zip=$request->zip;
            $address->landmark=$request->landmark;
            $address->locality=$request->locality;
            $address->country='Viet Nam';
            $address->isdefault=true;   
            $address->save();
        }
        $this->setAmountforCheckout();
         $order=new Order();
        $order->user_id=$user->id;
        $order->name=$address->name;
        $order->phone=$address->phone;
        $order->locality=$address->locality;
        $order->address=$address->address;
        $order->city=$address->city;
        $order->state=$address->state;
        $order->zip=$address->zip;
        $order->country=$address->country;
        $order->landmark=$address->landmark;
        $order->subtotal=Session::get('checkout')['subtotal'];
        $order->discount=Session::get('checkout')['discount'];
        $order->tax=Session::get('checkout')['tax'];
        $order->total=Session::get('checkout')['total'];
        $order->save();
        foreach(Cart::instance('cart')->content() as $item){
             $orderitem=new OrderItem();
             $orderitem->order_id=$order->id;
             $orderitem->product_id=$item->id;
             $orderitem->price=$item->price;
             $orderitem->quantity=$item->qty;
             $orderitem->save();
        }
        if( $request->mode=='card'){
           //

        } else if($request->mode=='paypal'){
            //paypal logic
           
        } else if($request->mode=='cod'){
            //cod logic
          $transaction= new Transaction();
          $transaction->user_id=$user->id;
          $transaction->order_id=$order->id;
          $transaction->mode=$request->mode;
          $transaction->status='pending';
          $transaction->save();
        }

        Cart::instance('cart')->destroy();
        Session::forget('checkout');
        Session::forget('coupon');
        Session::forget('discounts');
        Session::put('order_id',$order->id);
        return redirect()->route('cart.order_confirmation',compact('order'))->with('Success','Your order has been placed successfully.');
    }
    public function setAmountforCheckout(){
        if(!Cart::instance('cart')->content()->count() >0){
            Session::forget('checkout');
            return;
        }
        if(Session::has('coupon')){
            Session::put('checkout',[
                'discount'=>Session::get('discounts')['discount'],
                'subtotal'=>Session::get('discounts')['subtotal'],
                'tax'=>Session::get('discounts')['tax'],
                'total'=>Session::get('discounts')['total']
            ]);
        } else{
            Session::put('checkout',[
                'discount'=>0,
                'subtotal'=>Cart::instance('cart')->subtotal(),
                'tax'=>Cart::instance('cart')->tax(),
                'total'=>Cart::instance('cart')->total()
            ]);
        }
    }
    public function order_confirmation(){
        if(Session::has('order_id')){
            $order=Order::find(Session::get('order_id'));
            return view('order_confirmation',compact('order'));
        }
       
        return redirect()->route('cart.index');
    }
}
