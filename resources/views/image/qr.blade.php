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
            position: relative;
        }
        .user-nickname {
            position: absolute;
            left: 0;
            bottom: 340px;
            color: black;
            font-size: 40px;
            width: 100%;
            text-align: center;
        }
        .qr-code {
            position: absolute;
            left: 0;
            bottom: 100px;
            width: 100%;
            text-align: center;
        }
        .qr-code-image{
            width: 200px;
            height: 200px;
        }
    </style>
</head>
<body>
<div class="page">
    <img src="https://huiliuyu.com/images/shua.png" alt="">
    <div class="user-nickname">刷{{$user->nickname}}的卡，得现金券</div>
    <div class="qr-code">
        <img class='qr-code-image' src="{{$url}}" alt="">
    </div>
</div>
</body>
</html>