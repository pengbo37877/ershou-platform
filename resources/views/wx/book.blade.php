@extends('layouts.wx')

@section('content')
    <book book="{{$book}}" cart-items-count="{{$cartItemsCount}}"></book>
@endsection