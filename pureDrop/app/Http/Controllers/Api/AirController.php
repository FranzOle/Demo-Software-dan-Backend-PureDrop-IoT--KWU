<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;

class AirController extends Controller
{

    public function show($order_id)
    {
        $tx = Transaction::where('order_id', $order_id)->first();

        if (! $tx) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order_id' => $tx->order_id,
            'liter' => (float) $tx->liter,
            'payment_status' => $tx->payment_status, // pending | success | failed
            'payment_type' => $tx->payment_type,
            'updated_at' => $tx->updated_at ? $tx->updated_at->toDateTimeString() : null,
        ]);
    }

    public function latest(Request $request)
    {
        $status = $request->query('status', 'success'); // default success
        $tx = Transaction::where('payment_status', $status)
                         ->orderByDesc('updated_at')
                         ->first();

        if (! $tx) {
            return response()->json([
                'success' => false,
                'message' => 'No transaction found with status '.$status
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order_id' => $tx->order_id,
            'liter' => (float) $tx->liter,
            'payment_status' => $tx->payment_status,
            'payment_type' => $tx->payment_type,
            'updated_at' => $tx->updated_at ? $tx->updated_at->toDateTimeString() : null,
        ]);
    }

    public function consume($order_id)
    {
        $tx = Transaction::where('order_id', $order_id)->first();

        if (! $tx) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        $tx->payment_status = 'consumed';
        $tx->save();

        return response()->json(['success' => true, 'message' => 'Marked consumed']);
    }
}
