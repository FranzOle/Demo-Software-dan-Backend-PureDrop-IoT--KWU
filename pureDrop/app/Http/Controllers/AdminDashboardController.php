<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Setting;
use Illuminate\Support\Facades\Validator;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $setting = Setting::orderByDesc('id')->first();

        // filter
        $from = $request->query('from');
        $to   = $request->query('to');
        $status = $request->query('status');

        $transactionsQuery = Transaction::query();

        // filter tanggal (created_at)
        if ($from) {
            $transactionsQuery->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $transactionsQuery->whereDate('created_at', '<=', $to);
        }

        // filter status
        if ($status && in_array($status, ['pending','success','failed'])) {
            $transactionsQuery->where('payment_status', $status);
        }

        // order terbaru dulu
        $transactionsQuery->orderByDesc('created_at');

        // pagination (10 per halaman)
        $transactions = $transactionsQuery->paginate(10)->withQueryString();

        // statistik hari ini (menggunakan created_at)
        $todaySalesCount = Transaction::whereDate('created_at', now()->toDateString())->count();
        $todayLiters = Transaction::whereDate('created_at', now()->toDateString())->sum('liter'); // total liter hari ini
        $totalTodayRevenue = Transaction::whereDate('created_at', now()->toDateString())->sum('price');

        return view('admin.dashboard', compact(
            'transactions',
            'setting',
            'todaySalesCount',
            'todayLiters',
            'totalTodayRevenue',
            'from','to','status'
        ));
    }

    /**
     * Update or create setting (harga + liter)
     */
    public function updateSetting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|min:0',    // price in Rupiah
            'liter' => 'required|numeric|min:0.001' // liter base
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.dashboard')->withErrors($validator)->withInput();
        }

        $s = Setting::create([
            'price' => (int) $request->input('price'),
            'liter' => (float) $request->input('liter'),
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Setting harga berhasil diperbarui.');
    }
}
