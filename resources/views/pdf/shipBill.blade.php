<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta name="viewport" content="width=device-width,height=device-height,maximum-scale=1.0,user-scalable=no">
    <style>
        body{
            font-family: SimHei;
            margin: 0;
            padding: 0;
        }
        div.page
        {
            /*page-break-inside: avoid;*/
            /*page-break-after:always;*/
        }
        .order {
            padding-top: 30px;
            padding-bottom: 30px;
        }
        .order:not(:first-child){
            margin-top: 100px;
        }
        .order-no {
            font-size: 70px;
        }
        .order-time {
            font-size: 50px;
        }
        .order-contact {
            font-size: 70px;
            margin-top: 20px;
        }
        .order-address {
            font-size: 50px;
        }
        .items-count {
            font-size: 80px;
        }
        .c {
            font-size: 70px;
            line-height: 80px;
        }
        .c1 {
            font-size: 50px;
            line-height: 60px;
            padding-left: 70px;
        }
    </style>
</head>
<body>
@foreach($orders as $order)
    <div class="order page">
        <div class="order-no">{{$order->no}}</div>
        <div class="order-time">{{$order->created_at}}</div>
        @if($order->address)
        <div class="order-contact">
            {{$order->address->contact_name.$order->address->contact_phone}}
        </div>
        <div class="order-address">
            {{$order->address->province.$order->address->city.$order->address->district.$order->address->address}}
        </div>
        @endif
        <div class="items-count">[共 {{ count($order->items) }} 本]</div>
        @foreach($order->items as $item)
            <br><br><br>
            <span class="c">□ {{$item->bookSku?$item->bookSku->hly_code:'无'}} |
                {{$item->bookSku?($item->bookSku->store_shelf?$item->bookSku->store_shelf->code:'无'):'无'}}</span><br>
            <span class="c1">{{$item->book->name}}</span><br>
            <br><br><br>
            <hr>
        @endforeach
        <hr>
    </div>
@endforeach
</body>
</html>
