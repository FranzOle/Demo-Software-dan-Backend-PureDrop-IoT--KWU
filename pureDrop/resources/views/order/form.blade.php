@extends('layouts.app')

@section('title', 'Pesan Air')

@section('content')
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card p-3">
      <h4 class="mb-3">Form Pemesanan Air</h4>

      <form method="POST" action="{{ route('order.store') }}" id="orderForm">
        @csrf

        <div class="mb-3">
          <label class="form-label">Nama (opsional)</label>
          <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name') }}">
        </div>

        <div class="mb-3">
          <label class="form-label">Jumlah (liter)</label>
          <input type="number" step="0.01" min="0.01" name="liter" id="liter" class="form-control" value="{{ old('liter', 1) }}" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Harga Saat Ini</label>
          @if($setting)
            <div>
              <small class="text-muted">Rp {{ number_format($setting->price,0,',','.') }} untuk {{ rtrim(rtrim(number_format($setting->liter,2,',','.'),'0'),',') }} liter</small>
            </div>
            <div class="mt-2">
              <strong>Total Estimasi: </strong>
              <span id="estPrice">Rp {{ number_format( (int) round( (1) * ($setting->price / $setting->liter) ),0,',','.') }}</span>
            </div>
          @else
            <div class="text-danger">Belum ada setting harga. Hubungi admin.</div>
          @endif
        </div>

        <div class="d-grid">
          <button class="btn btn-primary" type="submit">Checkout & Bayar</button>
        </div>
      </form>
    </div>
  </div>
</div>

@if($setting)
<script>
  // update estimasi harga saat input liter berubah
  const literInput = document.getElementById('liter');
  const estPrice = document.getElementById('estPrice');
  const unit = {{ $setting->price / $setting->liter }}; // price per liter

  function formatRupiah(num) {
    return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  }

  function updateEstimate() {
    const v = parseFloat(literInput.value) || 0;
    const total = Math.round(v * unit);
    estPrice.innerText = formatRupiah(total);
  }

  literInput.addEventListener('input', updateEstimate);
  document.addEventListener('DOMContentLoaded', updateEstimate);
</script>
@endif
@endsection
