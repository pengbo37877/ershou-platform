@extends('layouts.orderInfo')

@section('content')
    <order-info items="{{$cartItems}}"></order-info>
@endsection