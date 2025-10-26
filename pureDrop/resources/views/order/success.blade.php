@extends('layouts.app')

@section('title', 'Pembayaran Berhasil')

@section('content')
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card text-center p-4">
      <h4 class="text-success">Pembayaran Berhasil ✅</h4>
      <p>Terima kasih. Pembayaran sudah diterima. Silakan ambil air.</p>
      <a href="{{ route('order.form') }}" class="btn btn-primary">Kembali ke Beranda</a>
    </div>
  </div>
</div>
@endsection
