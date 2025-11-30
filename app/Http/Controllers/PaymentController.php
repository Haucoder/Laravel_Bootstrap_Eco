<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class PaymentController extends Controller
{
    // ====================================================
    // 1. VNPAY SANDBOX (DEMO)
    // ====================================================
    public function vnpay_payment(Request $request)
    {
        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_Returnurl = route('payment.vnpay_return');
        
        // Cấu hình Key Sandbox (Dùng chung cho developer)
        $vnp_TmnCode = "CGXZLS0Z"; // Mã website tại VNPAY 
        $vnp_HashSecret = "XNBCJFAKRNQTIGURFVJRETINQUYBHTEJ"; // Chuỗi bí mật

        $vnp_TxnRef = time(); // Mã đơn hàng (Duy nhất)
        $vnp_OrderInfo = "Thanh toan don hang test";
        $vnp_OrderType = "billpayment";
        $vnp_Amount = 200000 * 100; // Số tiền 200,000 VND (nhân 100 là quy tắc VNPay)
        $vnp_Locale = "vn";
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef
        );

        // Sắp xếp dữ liệu (Bắt buộc)
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }

        return redirect($vnp_Url);
    }

    public function vnpay_return(Request $request){
        // Logic kiểm tra chữ ký trả về (Demo thì bỏ qua check hash cho nhanh)
        if($request->vnp_ResponseCode == "00") {
            // Thanh toán thành công -> Update DB
            dd("Thanh toán VNPay Thành Công!", $request->all());
        } else {
            dd("Thanh toán VNPay Lỗi / Hủy bỏ");
        }
    }

    // ====================================================
    // 2. MOMO SANDBOX (DEMO)
    // ====================================================
    public function momo_payment(Request $request)
    {
        // Bạn PHẢI lấy key này từ https://developers.momo.vn/ (Mục Dashboard > Testing)
        $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";
        $partnerCode = "MOMO..."; // <--- Điền Partner Code của bạn
        $accessKey = "F8...";     // <--- Điền Access Key của bạn
        $secretKey = "K9...";     // <--- Điền Secret Key của bạn

        $orderInfo = "Thanh toan qua MoMo";
        $amount = "50000"; // 50,000 VND (Momo Sandbox phải là String, không dấu phẩy)
        $orderId = time() ."";
        $requestId = time() ."";
        $redirectUrl = route('payment.momo_return');
        $ipnUrl = route('payment.momo_return');
        $extraData = "";

        // Tạo chữ ký
        $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $ipnUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $redirectUrl . "&requestId=" . $requestId . "&requestType=captureWallet";
        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        $data = [
            'partnerCode' => $partnerCode,
            'partnerName' => "Test",
            "storeId" => "MomoTestStore",
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => 'captureWallet',
            'signature' => $signature
        ];

        $response = Http::post($endpoint, $data);
        $json = $response->json();

        if(isset($json['payUrl'])){
            return redirect($json['payUrl']);
        }
        dd("Lỗi tạo đơn MoMo", $json);
    }

    public function momo_return(Request $request){
        if($request->resultCode == 0){
             dd("Thanh toán MoMo Thành Công!", $request->all());
        } else {
             dd("Thanh toán MoMo Thất bại");
        }
    }
}