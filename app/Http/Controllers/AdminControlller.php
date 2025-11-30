<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Models\Coupon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Intervention\Image\Laravel\Facades\Image;

class AdminControlller extends Controller
{
    
    public function index(){
        return view("admin.index");
    }
    public function brands(){
        $brands=Brand::orderBy('id','DESC')->paginate(10);
        return view('admin.brands',compact('brands'));
    }
    public function add_brand(){
        return view('admin.brand-add');
    }
    public function brands_edit($id){
        $brand=Brand::find($id);
        return view('admin.brand-edit',compact('brand'));

    }
    public function brands_delete($id){
        $brand=Brand::find($id);
        $path=public_path('upload/brands/'.$brand->image);
        if(File::exists($path)){
            File::delete($path);
        }
        $brand->delete();
        return redirect()->route('admin.brands')->with('status',"brand deleted successfully");
    }

    public function brands_store(Request $request){
        $request->validate([
            'name'=>'required',
            'slug'=>'required|unique:brands,slug',
            'image'=>'mimes:png,jpg,jpeg|max:2048'
        ]);
        $brand=new Brand();
        $brand->name=$request->name;
        $brand->slug= Str::slug($request->name);
        $image=$request->file('image');
        $file_extension=$request->file('image')->extension();
        $file_name=Carbon::now()->timestamp . "." . $file_extension;
        $this->GenerateBrandThumbailsImage($image,$file_name);
        $brand->image=$file_name;
        $brand->save();
        return redirect()->route('admin.brands')->with('status',"brand added successfully");
    }
    

    public function brands_update(Request $request){
        $request->validate([
            'name'=>'required',
            'slug'=>'required|unique:brands,slug,'.$request->id,
            'image'=>'mimes:png,jpg, jpeg|max:2048'
        ]);
        $brand=Brand::find($request->id);
        $brand->name=$request->name;
        $brand->slug= Str::slug($request->name);
        if($request->hasFile('image')){
            $path=public_path('upload/brands/'.$brand->image);
            if(File::exists($path)){
                File::delete($path);
            }
            $image=$request->file('image');
            $file_extension=$request->file('image')->extension();
            $file_name=Carbon::now()->timestamp . "." . $file_extension;
            $this->GenerateBrandThumbailsImage($image,$file_name);
            $brand->image=$file_name;
        }
       
        $brand->save();
        return redirect()->route('admin.brands')->with('status',"brand updated successfully");
    }

   public function GenerateBrandThumbailsImage($image,$image_name){
        $destinationPath = public_path('upload/brands');
        $img=Image::read($image->path());
        $img->cover(124,124,"top");
        $img->resize(124,124,function($constraint){
            $constraint->aspectRatio();
        })->save($destinationPath . "/" .$image_name); 
   }
   /// category
   public function categories(){

     $categories=Category::orderBy('id','DESC')->paginate(10);
     return view('admin.categories',compact('categories'));
   }
   public function add_category(){

    return view("admin.category-add");
   }
   public function category_store( Request $request){
    $request->validate([
        'name'=>'required',
        'slug'=>'required|unique:categories,slug',
        'image'=>'mimes:png,jpg,jpeg|max:2048'
    ]);
    $category=new Category();
    $category->name=$request->name;
    $category->slug= Str::slug($request->name);
    $image=$request->file('image');
    $file_extension=$request->file('image')->extension();
    $file_name=Carbon::now()->timestamp . "." . $file_extension;
    $this->GenerateCategoryThumbailsImage($image,$file_name);
    $category->image=$file_name;
    $category->save();
    return redirect()->route('admin.categories')->with('status',"category added successfully");
   }
   
   public function GenerateCategoryThumbailsImage($image,$image_name){
        $destinationPath = public_path('upload/categories');
        $img=Image::read($image->path());
        $img->cover(124,124,"top");
        $img->resize(124,124,function($constraint){
            $constraint->aspectRatio();
        })->save($destinationPath . "/" .$image_name);
    }

    public function category_edit($id){
        $category=Category::find($id);
        return view('admin.category-edit',compact('category'));
    }
    public function category_update(Request $request){
        $request->validate([
            'name'=>'required',
            'slug'=>'required|unique:categories,slug,'.$request->id,
            'image'=>'mimes:png,jpg, jpeg|max:2048'
        ]);
        $category=Category::find($request->id);
        $category->name=$request->name;
        $category->slug= Str::slug($request->name);
        if($request->hasFile('image')){
            $path=public_path('upload/categories/'.$category->image);
            if(File::exists($path)){
                File::delete($path);
            }
            $image=$request->file('image');
            $file_extension=$request->file('image')->extension();
            $file_name=Carbon::now()->timestamp . "." . $file_extension;
            $this->GenerateCategoryThumbailsImage1($image,$file_name);
            $category->image=$file_name;
        }
       
        $category->save();
        return redirect()->route('admin.categories')->with('status',"category updated successfully");
    }
    public function category_delete($id){
        $category=Category::find($id);
        $path=public_path('upload/categories/'.$category->image);
        if(File::exists($path)){
            File::delete($path);
        }
        $category->delete();
        return redirect()->route('admin.categories')->with('status',"category deleted successfully");
    }
    // Products
    public function products(){
        $products=Product::orderBy('created_at',"DESC")->paginate(10);
        return view('admin.products',compact('products'));
    }
    public function product_add(){
        $categories=Category::select('id','name')->orderBy('name')->get();
        $brands=Brand::select('id','name')->orderBy('name')->get();
        return view('admin.product-add',compact('categories','brands'));
    }
    public function product_store(Request $request){
        $request->validate([
            'name'=>'required',
            'slug'=>'required|unique:products,slug',
            'short_description'=>'required',
            'description'=>'required',
            'regular_price'=>'required|numeric',
            'sale_price'=>'nullable|numeric',
            'SKU'=>'required',
            'stock_status'=>'required|in:in_stock,out_of_stock',
            'featured'=>'nullable|in:0,1',
            'quantity'=>'required|numeric',
            'image'=>'mimes:png,jpg,jpeg|max:2048',
            'category_id'=>'nullable|exists:categories,id',
            'brand_id'=>'nullable|exists:brands,id',
            'images.*'=>'mimes:png,jpg,jpeg|max:2048'
        ]);
        $product=new Product();
        $product->name=$request->name;
        $product->slug= Str::slug($request->name);
        $product->short_description=$request->short_description;
        $product->description=$request->description;
        $product->regular_price=$request->regular_price;
        $product->sale_price=$request->sale_price;
        $product->SKU=$request->SKU;
        $product->stock_status=$request->stock_status;
        $product->featured = $request->featured;
        $product->quantity=$request->quantity;

        $product->category_id=$request->category_id;
        $product->brand_id=$request->brand_id;

        $curent_timestamp=Carbon::now()->timestamp;
        if($request->hasFile('image')){
            $image=$request->file('image');
            $imageName=$curent_timestamp . "." . $image->extension();
            $this->GenerateProductThumbailsImage($image,$imageName);
            $product->image=$imageName;
        }
          $gallery_image_names=array();
          $gallery_image='';
          $counter=1;

        if($request->hasFile('images')){
            $allowedfileExtension=['png','jpg','jpeg'];
            $files=$request->file('images');
            foreach($files as $file){
                $gextention=$file->getClientOriginalExtension();
                $gcheck=in_array($gextention,$allowedfileExtension);
                if($gcheck){
                    $gallery_image_name=$curent_timestamp . "-" . $counter . "." . $gextention;
                    $this->GenerateProductThumbailsImage($file,$gallery_image_name);
                   // array_push($gallery_image_names,$gallery_image_name);
                    $gallery_image_names[]=$gallery_image_name;
                    $counter++;
                }
            }

            $product->images=implode(",",$gallery_image_names);
        }
        
        $product->save();
        return redirect()->route('admin.products')->with('status',"Product added successfully");
    }

     public function GenerateProductThumbailsImage($image,$image_name){
        $destinationPath = public_path('upload/products');
        $destinationPath1 = public_path('upload/products/thumbnails');
        $img=Image::read($image->path());
        $img->cover(540,689,"top");
        $img->resize(540,689,function($constraint){
            $constraint->aspectRatio();
        })->save($destinationPath . "/" .$image_name);

        $img->resize(104,104,function($constraint){
            $constraint->aspectRatio();
        })->save($destinationPath1 . "/" .$image_name);
    }

    public function product_edit($id){
        $product=Product::find($id);
        $categories=Category::select('id','name')->orderBy('name')->get();
        $brands=Brand::select('id','name')->orderBy('name')->get();
        return view('admin.product-edit',compact('product','categories','brands')); 
    }
    
   public function product_update(Request $request){
      $request->validate([
            'name'=>'required',
            'slug'=>'required|unique:products,slug,'.$request->id.',id',
            'short_description'=>'required',
            'description'=>'required',
            'regular_price'=>'required|numeric',
            'sale_price'=>'nullable|numeric',
            'SKU'=>'required',
            'stock_status'=>'required|in:in_stock,out_of_stock',
            'featured'=>'nullable|in:0,1',
            'quantity'=>'required|numeric',
            'image'=>'mimes:png,jpg,jpeg|max:2048',
            'category_id'=>'nullable|exists:categories,id',
            'brand_id'=>'nullable|exists:brands,id',
            
        ]);
        $product=Product::find($request->id);
        $product->name=$request->name;
        $product->slug= Str::slug($request->name);
        $product->short_description=$request->short_description;
        $product->description=$request->description;
        $product->regular_price=$request->regular_price;
        $product->sale_price=$request->sale_price;
        $product->SKU=$request->SKU;
        $product->stock_status=$request->stock_status;
        $product->featured = $request->featured;
        $product->quantity=$request->quantity;

        $product->category_id=$request->category_id;
        $product->brand_id=$request->brand_id;
        $path=public_path('upload/products/'.$product->image);
        $path1=public_path('upload/products/thumbnails/'.$product->image);
        $curent_timestamp=Carbon::now()->timestamp;
        if($request->hasFile('image')){
            
            if(File::exists($path)){
                File::delete($path);
            }
            if(File::exists($path1)){
                File::delete($path1);
            }
            $image=$request->file('image');
            $imageName=$curent_timestamp . "." . $image->extension();
            $this->GenerateProductThumbailsImage($image,$imageName);
            $product->image=$imageName;
        }

          $gallery_image_names=array();
          $gallery_image=' ';
          $counter=1;

        if($request->hasFile('images')){
             if(!empty($product->images)){
             foreach(explode(',',$product->images) as $ofile)
            {
                $path=public_path('upload/products/'.$ofile);
                $path1=public_path('upload/products/thumbnails/'.$ofile);
                if(File::exists($path)){
                    File::delete($path);
                }
                if(File::exists($path1)){
                    File::delete($path1);
                }
             }
             }
            $allowedfileExtension=['png','jpg','jpeg'];
            $files=$request->file('images');
            foreach($files as $file){
                $gextention=$file->getClientOriginalExtension();
                $gcheck=in_array($gextention,$allowedfileExtension);
                if($gcheck){
                    $gallery_image_name=$curent_timestamp . "-" . $counter . "." . $gextention;
                    $this->GenerateProductThumbailsImage($file,$gallery_image_name);
                   // array_push($gallery_image_names,$gallery_image_name);
                    $gallery_image_names[]=$gallery_image_name;
                    $counter++;
                }
            }

            $product->images=implode(",",$gallery_image_names);
        }
        
        $product->save();
        return redirect()->route('admin.products')->with('status',"Product updated successfully");

     }
     public function product_delete($id){
        $product=Product::find($id);
        $path=public_path('upload/products/'.$product->image);
        $path1=public_path('upload/products/thumbnails/'.$product->image);
        if(File::exists($path)){
            File::delete($path);
        }
        if(File::exists($path1)){
            File::delete($path1);
        }
        foreach(explode(',',$product->images) as $ofile)
            {
                $path3=public_path('upload/products/'.$ofile);
                $path4=public_path('upload/products/thumbnails/'.$ofile);
                if(File::exists($path3)){
                    File::delete($path3);
                }
                if(File::exists($path4)){
                    File::delete($path4);
                }
             }
        if($product){
            $product->delete();
            return redirect()->route('admin.products')->with('status',"Product deleted successfully");
        }
        return redirect()->back()->withErrors('Product not found');
     }
     public function coupons(){
        $coupons=Coupon::orderBy('expory_date','DESC')->paginate(12);
        return view('admin.coupons',compact('coupons'));
     }
     public function coupon_add(){
        return view('admin.coupons-add');
     }
     
     public function coupon_store(Request $request){
           $request->validate([
            'code'=>'required|unique:coupons,code',
            'type'=>'required|in:fixed,percent',
            'value'=>'required|numeric',
            'cart_value'=>'required|numeric',
            'expory_date'=>'required|date'
           ]);
           $coupon=new Coupon();
           $coupon->code=$request->code;
           $coupon->type=$request->type;
           $coupon->value=$request->value;
           $coupon->cart_value=$request->cart_value;
           $coupon->expory_date=$request->expory_date;
           $coupon->save();
           return redirect()->route('admin.coupons')->with('status',"Coupon added successfully");
     }
     public function coupon_delete($id){
        $coupon=Coupon::find($id);
       
        $coupon->delete();
        return redirect()->route('admin.coupons')->with('status',"Coupon deleted successfully");
    
     }
     public function coupon_edit($id){
        $coupon=Coupon::find($id);
        return view('admin.coupons-edit',compact('coupon'));
     }
        public function coupon_update(Request $request){
            $request->validate([
            'code'=>'required|unique:coupons,code',
            'type'=>'required|in:fixed,percent',
            'value'=>'required|numeric',
            'cart_value'=>'required|numeric',
            'expory_date'=>'required|date'
           ]);
            $coupon=Coupon::find($request->coupon_id);
            $coupon->code=$request->code;
            $coupon->type=$request->type;
            $coupon->value=$request->value;
            $coupon->cart_value=$request->cart_value;
            $coupon->expory_date=$request->expory_date;
            $coupon->save();
            return redirect()->route('admin.coupons')->with('status',"Coupon updated successfully");
        }
      public function Order(){
        $orders=Order::orderBy('created_at','DESC')->paginate(12);
        return view('admin.orders',compact('orders'));


      }  
      public function order_details($order_id){
        $order=Order::find($order_id);
        $orderItems=OrderItem::where('order_id',$order_id)->orderBy('id')->paginate(12);
        $transactions=Transaction::where('order_id',$order_id)->first();
        return view('admin.order-details',compact('order','orderItems','transactions'));
      }
      public function update_order_status(Request $request){
         $order=Order::find($request->order_id);
         $order->status=$request->order_status;
         if($request->order_status=='delivered'){
            $order->delivered_date=Carbon::now();
         } else if($request->order_status=='canceled'){
            $order->canceled_date=Carbon::now();
         }

         $order->save();

         if($request->order_status=='delivered'){

            $transaction=Transaction::where('order_id',$request->order_id)->first();

            $transaction->status='approved';
            $transaction->save();

         }  
         return back()->with('status','order status updated successfully'); 
      }
}

