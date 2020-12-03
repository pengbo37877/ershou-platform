@extends('layouts.order')

@section('content')
    <my-orders orders="{{$orders}}"></my-orders>
@endsection