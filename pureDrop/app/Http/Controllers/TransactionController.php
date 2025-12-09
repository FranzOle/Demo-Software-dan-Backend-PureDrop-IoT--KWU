<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Setting;
use Illuminate\Support\Facades\Validator;
use Midtrans\Snap;
use Midtrans\Config;
use Illuminate\Support\Facades\Log;

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

        // ensure midtrans config (in case AppServiceProvider not set)
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
            // you can restrict enabled payments or omit to allow all available
            'enabled_payments' => ['qris', 'gopay', 'shopeepay', 'bank_transfer'],
        ];

        $snapToken = Snap::getSnapToken($params);

        return view('order.payment', compact('tx','snapToken'));
    }

    /**
     * Midtrans server-to-server callback (notification)
     * Verifies signature_key (if provided) then updates transaction.
     */
    public function callback(Request $request)
    {
        $data = $request->all();

        Log::info('Midtrans callback received', $data);

        $orderId = $data['order_id'] ?? null;
        $status = $data['transaction_status'] ?? null;

        if (! $orderId) {
            return response()->json(['success' => false, 'message' => 'order_id missing'], 400);
        }

        // Optional: verify signature_key (recommended for production)
        if (isset($data['signature_key'])) {
            $local = hash('sha512', ($data['order_id'] ?? '') . ($data['status_code'] ?? '') . ($data['gross_amount'] ?? '') . config('midtrans.server_key'));
            if ($local !== $data['signature_key']) {
                Log::warning('Midtrans signature mismatch', ['order' => $orderId]);
                return response()->json(['success' => false, 'message' => 'invalid signature'], 403);
            }
        }

        $tx = Transaction::where('order_id', $orderId)->first();
        if (! $tx) {
            return response()->json(['success' => false, 'message' => 'transaction not found'], 404);
        }

        // Map midtrans statuses to our statuses
        $mapped = 'pending';
        if (in_array($status, ['capture','settlement'])) {
            $mapped = 'success';
        } elseif (in_array($status, ['deny','cancel','expire'])) {
            $mapped = 'failed';
        } else {
            $mapped = 'pending';
        }

        // update only if changed (idempotency)
        if ($tx->payment_status !== $mapped) {
            $tx->payment_status = $mapped;
            if (isset($data['payment_type'])) {
                $tx->payment_type = $data['payment_type'];
            }
            // prefer transaction_time from Midtrans if available
            if (isset($data['transaction_time'])) {
                try {
                    $tx->transaction_time = now(); // optionally parse $data['transaction_time']
                } catch (\Throwable $e) {
                    // ignore parse errors, use now()
                    $tx->transaction_time = now();
                }
            } else {
                $tx->transaction_time = now();
            }
            $tx->save();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Client-side quick confirm (demo only)
     * Mark transaction success (idempotent) for demo flows where callback is delayed.
     */
    public function confirm(Request $request, $order_id)
    {
        $tx = Transaction::where('order_id', $order_id)->firstOrFail();

        // if already success, return OK (idempotent)
        if ($tx->payment_status === 'success') {
            return response()->json(['success' => true, 'message' => 'already success']);
        }

        // For demo only: accept and set success
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

    /**
     * Return current payment status (JSON) for polling client.
     */
    public function status($order_id)
    {
        $tx = Transaction::where('order_id', $order_id)->first();

        if (! $tx) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        return response()->json([
            'success' => true,
            'payment_status' => $tx->payment_status,
            'payment_type' => $tx->payment_type,
            'updated_at' => $tx->updated_at ? $tx->updated_at->toDateTimeString() : null,
        ]);
    }
}
