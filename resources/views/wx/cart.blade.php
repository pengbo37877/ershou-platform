@extends('layouts.cart')

@section('content')
    <cart items="{{$cartItems}}" reminders="{{$reminders}}"></cart>
@endsection