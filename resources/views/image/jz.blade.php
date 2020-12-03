<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta name="viewport" content="width=device-width,height=device-height,maximum-scale=1.0,user-scalable=no">
    <style>
        body{
            font-family: STKaiti;
            padding: 0;
            margin: 0;
        }
        .page
        {
            padding: 0;
            margin: 0;
        }
        .jz-image{
            position: relative;
        }
        .jz-hly {
            font-family: "PingFang SC";
            position: absolute;
            top: 10px;
            left: 0;
            width: 100%;
            font-size: 30px;
            font-weight: 600;
            color:white;
            text-align: center;
        }
        .day-month-xq {
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            text-align: center;
        }
        .day{
            font-family: "PingFang SC";
            font-size: 120px;
            color: white;
            display: inline-block;
        }
        .month-xq {
            display: inline-block;
            font-family: "PingFang SC";
            font-size: 20px;
            text-align: left;
            color: white;
        }
        .month {
            font-size: 28px;
        }
        .jz-body {
            font-family: FZSongKeBenXiuKaiS-R-GB;
            font-size: 32px;
            font-weight: 300;
            padding: 30px 30px 0 30px;
        }
        .jz-author-book {
            font-size: 28px;
            font-weight: 300;
            text-align: left;
            display: inline-block;
            position: absolute;
            top: 70px;
            right: 190px;
        }
        .jz-author {
            padding-left: 15px;
            font-family: "PingFang SC";
            font-size: 24px;
            font-weight: bold;
        }
        .jz-book {
            font-family: "PingFang SC";
            font-size: 24px;
            font-weight: lighter;
        }
        .hly {
            text-align: right;
            padding: 20px 50px;
            position: relative;
        }
        .qrcode-text {
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="jz-image">
            <img src="{{$jz->picture?$jz->picture->image:'https://ershou.ovoooo.com/images/jz-default-image.jpg'}}" width="100%" alt="">
            <div class="jz-hly">
                <img src="https://ershou.ovoooo.com/images/hly_font_logo.png" width="200" alt="">
            </div>
            <div class="day-month-xq">
                <div class="day">{{$day}}</div>
                <div class="month-xq">
                    <div class="month">{{$month}}月</div>
                    <div class="xq">{{$xq}}</div>
                    <div class="lunar">农历 {{$lunar[1]}}{{$lunar[2]}}</div>
                </div>
            </div>
        </div>
        <div class="jz-body">
            {!!$jz->body!!}
        </div>
        <div class="hly">

        @if($jz->author&&$jz->book_name)
            <div class="jz-author-book">
                <div class="jz-author">{{$jz->author}}</div>
                <div class="jz-book">《{{$jz->book_name}}》</div>
            </div>
        @elseif($jz->author&&empty($jz->book_name))
            <div class="jz-author-book">
                <div class="jz-author">{{$jz->author}}</div>
            </div>
        @elseif(empty($jz->author)&&$jz->book_name)
            <div class="jz-author-book">
                <div class="jz-book">《{{$jz->book_name}}》</div>
            </div>
        @else
        @endif
            <div class="qrcode-text">
                <div class="qrcode">
                    <img src="https://ershou.ovoooo.com/images/qrcode.jpg" width="120" style="margin-left: 10px" alt="">
                </div>
                <div class="qr-text">
                    二手书循环书店
                </div>
            </div>
        </div>
    </div>
</body>
</html>