<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Setting;
use Illuminate\Support\Facades\Validator;
use Midtrans\Snap;
use Midtrans\Config;

class TransactionController extends Controller
{
    // show order form
    public function index()
    {
        $setting = Setting::orderByDesc('id')->first();
        return view('order.form', compact('setting'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'nullable|string|max:100',
            'liter' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $setting = Setting::orderByDesc('id')->first();
        if (! $setting) {
            return back()->withErrors(['setting' => 'Belum ada setting harga. Hubungi admin.'])->withInput();
        }

        $liter = (float) $request->input('liter');
        // rumus: total_harga = liter_input * (setting.price / setting.liter)
        $unit = $setting->price / $setting->liter;
        $total = (int) round($liter * $unit);

        $tx = Transaction::create([
            'customer_name' => $request->input('customer_name', 'Anon'),
            'liter' => $liter,
            'price' => $total,
            'order_id' => Transaction::generateOrderId(),
            'payment_status' => 'pending',
            'payment_type' => null,
        ]);

        return redirect()->route('order.payment', $tx->order_id);
    }

    public function payment($order_id)
    {
        $tx = Transaction::where('order_id', $order_id)->firstOrFail();

        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $params = [
            'transaction_details' => [
                'order_id' => $tx->order_id,
                'gross_amount' => $tx->price,
            ],
            'customer_details' => [
                'first_name' => $tx->customer_name,
            ],
            
            'enabled_payments' => ['qris', 'gopay', 'shopeepay', 'bank_transfer'],
        ];

        $snapToken = Snap::getSnapToken($params);

        return view('order.payment', compact('tx','snapToken'));
    }

    public function callback(Request $request)
    {
        $data = $request->all();

        $orderId = $data['order_id'] ?? null;
        $status = $data['transaction_status'] ?? null;

        if (! $orderId) {
            return response()->json(['success' => false, 'message' => 'order_id missing'], 400);
        }

        $tx = Transaction::where('order_id', $orderId)->first();
        if (! $tx) {
            return response()->json(['success' => false, 'message' => 'transaction not found'], 404);
        }

        $mapped = 'pending';
        if (in_array($status, ['capture','settlement'])) {
            $mapped = 'success';
        } elseif (in_array($status, ['deny','cancel','expire'])) {
            $mapped = 'failed';
        } else {
            $mapped = 'pending';
        }

        $tx->payment_status = $mapped;
        if (isset($data['payment_type'])) {
            $tx->payment_type = $data['payment_type'];
        }
        if (isset($data['transaction_time'])) {
            $tx->transaction_time = now(); 
        }
        $tx->save();

        return response()->json(['success' => true]);
    }
    public function confirm(Request $request, $order_id)
    {
        $tx = Transaction::where('order_id', $order_id)->firstOrFail();
        $tx->payment_status = 'success';
        $tx->payment_type = $request->input('payment_type', 'qris');
        $tx->transaction_time = now();
        $tx->save();

        return response()->json(['success' => true]);
    }

    public function success()
    {
        return view('order.success');
    }
}
