<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Surfsidemedia\Shoppingcart\Facades\Cart; 
use App\Models\Coupon;
use Illuminate\Support\Facades\Session;
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

}
