@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h3>Admin Dashboard</h3>

  <form method="POST" action="{{ route('logout') }}">
    @csrf
    <button class="btn btn-outline-danger btn-sm">Logout</button>
  </form>
</div>

{{-- Alerts --}}
@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif

{{-- Cards --}}
<div class="row mb-4">
  <div class="col-md-4 mb-2">
    <div class="card p-3 h-100">
      <h6 class="text-muted">Penjualan Hari Ini</h6>
      <h4>{{ $todaySalesCount }}</h4>
    </div>
  </div>

  <div class="col-md-4 mb-2">
    <div class="card p-3 h-100">
      <h6 class="text-muted">Total Liter Hari Ini</h6>
      <h4>{{ number_format($todayLiters, 2, ',', '.') }} L</h4>
    </div>
  </div>

  <div class="col-md-4 mb-2">
    <div class="card p-3 h-100">
      <h6 class="text-muted">Total Pendapatan Hari Ini</h6>
      <h4>Rp {{ number_format($totalTodayRevenue, 0, ',', '.') }}</h4>
    </div>
  </div>
</div>

{{-- Info Setting singkat & tombol modal --}}
<div class="mb-3 d-flex justify-content-between align-items-center">
  <div>
    <h6 class="mb-0">Harga Air Saat Ini:</h6>
    @if($setting)
      <small class="text-muted">
        Rp {{ number_format($setting->price,0,',','.') }} untuk {{ rtrim(rtrim(number_format($setting->liter, 2, ',', '.'), '0'), ',') }} liter
      </small>
    @else
      <small class="text-muted">Belum ada setting harga.</small>
    @endif
  </div>

  <div>
    <!-- Trigger modal -->
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editSettingModal">Ubah Harga</button>
  </div>
</div>

{{-- Filter form untuk tabel --}}
<div class="card mb-3">
  <div class="card-body">
    <form method="GET" action="{{ route('admin.dashboard') }}" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small">Dari</label>
        <input type="date" name="from" value="{{ $from ?? '' }}" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label small">Sampai</label>
        <input type="date" name="to" value="{{ $to ?? '' }}" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label small">Status</label>
        <select name="status" class="form-select">
          <option value="">Semua</option>
          <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
          <option value="success" {{ ($status ?? '') === 'success' ? 'selected' : '' }}>Success</option>
          <option value="failed"  {{ ($status ?? '') === 'failed'  ? 'selected' : '' }}>Failed</option>
        </select>
      </div>
      <div class="col-md-3">
        <button class="btn btn-primary">Filter</button>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary ms-2">Reset</a>
      </div>
    </form>
  </div>
</div>

{{-- Table transaksi --}}
<div class="card mb-4">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Order ID</th>
            <th>Nama</th>
            <th>Liter</th>
            <th>Harga</th>
            <th>Payment</th>
            <th>Type</th>
            <th>Waktu</th>
          </tr>
        </thead>
        <tbody>
          @forelse($transactions as $tx)
            <tr>
              <td>{{ $loop->iteration + ($transactions->currentPage()-1) * $transactions->perPage() }}</td>
              <td>{{ $tx->order_id }}</td>
              <td>{{ $tx->customer_name }}</td>
              <td>{{ number_format($tx->liter, 2, ',', '.') }} L</td>
              <td>Rp {{ number_format($tx->price,0,',','.') }}</td>
              <td>
                @if($tx->payment_status === 'success')
                  <span class="badge bg-success">Success</span>
                @elseif($tx->payment_status === 'pending')
                  <span class="badge bg-warning text-dark">Pending</span>
                @else
                  <span class="badge bg-danger">Failed</span>
                @endif
              </td>
              <td>{{ $tx->payment_type ?? '-' }}</td>
              <td>{{ $tx->created_at ? $tx->created_at->format('Y-m-d H:i') : '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center">Tidak ada transaksi</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-3">
      {{ $transactions->links() }}
    </div>
  </div>
</div>

{{-- Form inline untuk setting (di bawah tabel) --}}
<div class="card mb-5">
  <div class="card-body">
    <h5>Ubah Harga Air (langsung)</h5>
    <form method="POST" action="{{ route('admin.settings.update') }}" class="row g-2 align-items-end">
      @csrf
      <div class="col-md-3">
        <label class="form-label">Harga (Rupiah)</label>
        <input type="number" name="price" class="form-control" value="{{ old('price', $setting->price ?? '') }}" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Liter dasar</label>
        <input type="number" step="0.01" name="liter" class="form-control" value="{{ old('liter', $setting->liter ?? '') }}" required>
      </div>
      <div class="col-md-3">
        <button class="btn btn-success">Simpan</button>
      </div>
      <div class="col-md-3 text-muted">
        <small>Contoh: price=6000, liter=2 → artinya 2 liter = Rp 6000 (harga per liter dihitung saat checkout).</small>
      </div>
    </form>
  </div>
</div>

{{-- Modal untuk edit setting --}}
<div class="modal fade" id="editSettingModal" tabindex="-1" aria-labelledby="editSettingModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="editSettingModalLabel">Ubah Harga Air</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Harga (Rupiah)</label>
            <input type="number" name="price" class="form-control" value="{{ old('price', $setting->price ?? '') }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Liter dasar</label>
            <input type="number" step="0.01" name="liter" class="form-control" value="{{ old('liter', $setting->liter ?? '') }}" required>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" type="submit">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection
