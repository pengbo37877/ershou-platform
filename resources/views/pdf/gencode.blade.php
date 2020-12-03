<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta name="viewport" content="width=device-width,height=device-height,maximum-scale=1.0,user-scalable=no,viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            width: 100%;
            font-family: "Helvetica Neue", Helvetica, "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", "微软雅黑", Arial, sans-serif;
        }
        html {width: 100%}
        .order.page {text-align: center;width: 100%}
        .order.page div {width: 100%;}
        .order.page div img {width: 100%;}
    </style>
</head>
<body>
@foreach($arr as $code)
    <div class="order page">
        <div><img src="data:image/png;base64,{{ $code["base64"] }}" /></div>
        <div class="order-no">{{$code["code"]}}</div>
        <div style="height: 20px"></div>
    </div>
@endforeach
</body>
</html>
