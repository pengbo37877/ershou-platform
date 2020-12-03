<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta name="viewport" content="width=device-width,height=device-height,maximum-scale=1.0,user-scalable=no,viewport-fit=cover">


    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<style>
    .unsubscribe {
        text-align: center;
    }
</style>
<body>
    <div class="unsubscribe">
        <h2>请先关注回流鱼</h2>
        <img src="/images/qrcode.jpg" width="80%" alt="">
    </div>
</body>
<script>
</script>
</html>
