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

      <p>
        <strong>Status:</strong>
        <span id="paymentStatusBadge" class="badge bg-warning">Loading...</span>
        <small id="statusUpdated" class="text-muted ms-2"></small>
      </p>

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

{{-- Snap JS --}}
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>

<script>
  const snapToken = "{{ $snapToken }}";
  const orderId = "{{ $tx->order_id }}";
  const statusBadge = document.getElementById('paymentStatusBadge');
  const statusUpdated = document.getElementById('statusUpdated');
  let pollInterval = null;

  // helper update badge UI
  function setStatusBadge(status, updatedAt = null) {
    status = (status || 'pending').toLowerCase();

    statusBadge.className = 'badge'; // reset classes
    if (status === 'success') {
      statusBadge.classList.add('bg-success');
      statusBadge.innerText = 'Success';
    } else if (status === 'failed') {
      statusBadge.classList.add('bg-danger');
      statusBadge.innerText = 'Failed';
    } else {
      statusBadge.classList.add('bg-warning', 'text-dark');
      statusBadge.innerText = 'Pending';
    }

    if (updatedAt) {
      statusUpdated.innerText = '(updated: ' + updatedAt + ')';
    } else {
      statusUpdated.innerText = '';
    }
  }

  // polling ke server untuk cek status
  async function pollStatus() {
    try {
      const res = await fetch("{{ url('/payment/status') }}/" + orderId, {
        headers: { 'Accept': 'application/json' }
      });
      if (!res.ok) {
        // kalau not found atau error, tampilkan pending/atau stop
        console.warn('Status fetch failed', res.status);
        return;
      }
      const j = await res.json();
      if (!j.success) {
        console.warn('Status API returned success=false', j);
        return;
      }

      const st = (j.payment_status || 'pending').toLowerCase();
      setStatusBadge(st, j.updated_at);

      if (st === 'success') {
        // stop polling dan redirect ke success
        stopPolling();
        // beri sedikit jeda supaya user lihat badge berubah
        setTimeout(() => {
          window.location.href = "{{ route('order.success') }}";
        }, 800);
      } else if (st === 'failed') {
        // stop polling, beri tahu user
        stopPolling();
        // optionally leave user to retry or return
      }
    } catch (err) {
      console.error('pollStatus error', err);
    }
  }

  function startPolling() {
    // langsung cek sekali dulu
    pollStatus();
    // lalu interval
    pollInterval = setInterval(pollStatus, 3000); // 3 detik
  }

  function stopPolling() {
    if (pollInterval) {
      clearInterval(pollInterval);
      pollInterval = null;
    }
  }

  // inisialisasi polling saat halaman dibuka
  document.addEventListener('DOMContentLoaded', function() {
    startPolling();
  });

  // Snap pay handler (tetap ada)
  document.getElementById('payButton').addEventListener('click', function() {
    window.snap.pay(snapToken, {
      onSuccess: function(result){
        console.log('onSuccess', result);
        // untuk demo: konfirmasi ke server (client confirm) agar cepat ter-update
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
          if (!j.success) {
            alert('Gagal konfirmasi pembayaran (client).');
          } else {
            // kalau konfirmasi sukses, server sudah menyimpan status success
            // polling akan mendeteksi dan redirect otomatis
            console.log('Client confirm success');
          }
        });
      },
      onPending: function(result){
        console.log('onPending', result);
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
            // polling akan mendeteksi dan redirect otomatis, atau kamu bisa redirect langsung
            console.log('Client confirm (pending) success');
          } else {
            console.warn('Client confirm failed', j);
          }
        })
        .catch(e => console.error('confirm error', e));
      },
      onError: function(result){
        console.log('onError', result);
        alert('Terjadi error saat pembayaran.');
      }
    });
  });
</script>
@endsection
