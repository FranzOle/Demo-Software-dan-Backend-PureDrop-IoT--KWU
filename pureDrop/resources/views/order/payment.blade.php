@extends('layouts.app')

@section('title', 'Pembayaran')

@section('content')
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card p-3">
      <h4 class="mb-3">Pembayaran</h4>

      <p><strong>Order ID:</strong> {{ $tx->order_id }}</p>
      <p><strong>Nama:</strong> {{ $tx->customer_name }}</p>
      <p><strong>Liter:</strong> {{ number_format($tx->liter,2,',','.') }} L</p>
      <p><strong>Total:</strong> Rp {{ number_format($tx->price,0,',','.') }}</p>

      <div class="mt-3">
        <button id="payButton" class="btn btn-success">Bayar (QRIS)</button>
        <a href="{{ route('order.form') }}" class="btn btn-link">Kembali</a>
      </div>

      <div class="mt-3">
        <small class="text-muted">Catatan: untuk demo, setelah bayar gunakan tombol "Simulate Payment Success" di Midtrans Dashboard, atau sistem akan langsung menandai sukses lewat client callback.</small>
      </div>
    </div>
  </div>
</div>

{{-- include Snap JS (sandbox) --}}
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>

<script>
  const snapToken = "{{ $snapToken }}";
  const orderId = "{{ $tx->order_id }}";

  document.getElementById('payButton').addEventListener('click', function() {
    window.snap.pay(snapToken, {
      onSuccess: function(result){
        console.log('onSuccess', result);
        // for demo: mark as paid on server (since sandbox callback may be delayed)
        fetch("{{ url('/payment/confirm') }}/" + orderId, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({ payment_type: result.payment_type || 'qris' })
        })
        .then(res => res.json())
        .then(j => {
          if (j.success) {
            window.location.href = "{{ route('order.success') }}";
          } else {
            alert('Gagal konfirmasi pembayaran (client).');
          }
        });
      },
      onPending: function(result){
        console.log('onPending', result);
        alert('Pembayaran pending. Cek dashboard Midtrans atau tunggu notifikasi.');
      },
      onError: function(result){
        console.log('onError', result);
        alert('Terjadi error saat pembayaran.');
      }
    });
  });
</script>
@endsection
