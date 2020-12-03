@extends('layouts.sale')

@section('content')
    <sale-invoice user="{{$user}}" items="{{$cartItems}}" address="{{$address}}" wallet-balance="{{$walletBalance}}"></sale-invoice>
@endsection