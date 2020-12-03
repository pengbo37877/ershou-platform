@extends('layouts.buySale')

@section('content')
    <buy-sale-books buy-original-price="{{$buyOriginalPrice}}"
                    buy-price="{{$buyPrice}}"
                    sale-original-price="{{$saleOriginalPrice}}"
                    sale-price="{{$salePrice}}"
                    buy-count="{{$buyCount}}"
                    sale-count="{{$saleCount}}"></buy-sale-books>
@endsection