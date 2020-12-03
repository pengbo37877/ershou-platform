@extends('layouts.wx')

@section('content')
    <shop cart-items-count="{{$cartItemsCount}}" tags="{{$tags}}" shudans="{{$shudans}}"></shop>
@endsection