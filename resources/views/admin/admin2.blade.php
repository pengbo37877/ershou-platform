<!DOCTYPE html>
<html lang="utf-8">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta name="viewport" content="width=device-width,height=device-height,maximum-scale=1.0,user-scalable=no,viewport-fit=cover">
    <script src="https://cdn.bootcss.com/echarts/4.3.0-rc.1/echarts.min.js"></script>
    <script src="/js/sts.js"></script>
    <style>
        body {
            font-family: "Helvetica Neue", Helvetica, "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", "微软雅黑", Arial, sans-serif;
        }
        /*#my_chart {width: 300px !important;height: 300px !important;float:left;border: 1px solid #e1e3e9;margin: 2px}*/
        /*#my_chart2 {width: 300px !important;height: 300px !important;float:left;border: 1px solid #e1e3e9}*/
        #single_data p {background: antiquewhite;border-radius: 2px;float: left;width: 150px;height: 60px;
            line-height: 20px;padding: 10px 0;margin: 10px 10px;text-align: center}
        #single_data p span {color: #ff4848;}
        .clear {clear: both}
    </style>
</head>
<body>
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Message</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label for="recipient-name" class="col-form-label">条目:</label>
                            <input type="text" class="form-control" id="recipient-name">
                        </div>
                        <div class="form-group">
                            <label for="money" class="col-form-label">金额:</label>
                            <input class="form-control" id="money" />
                        </div>
                        <div class="form-group">
                            <label for="mark" class="col-form-label">备注:</label>
                            <textarea class="form-control" style="resize: none" id="mark"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
                    <button type="button" class="btn btn-primary" id="submit">提交</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="exampleModal2" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Message</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label for="recipient-name" class="col-form-label">条目:</label>
                            <input type="text" class="form-control" id="recipient-name">
                        </div>
                        <div class="form-group">
                            <label for="money" class="col-form-label">金额:</label>
                            <input class="form-control" id="money" />
                        </div>
                        <div class="form-group">
                            <label for="mark" class="col-form-label">备注:</label>
                            <textarea class="form-control" style="resize: none" id="mark"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
                    <button type="button" class="btn btn-primary" id="submit">提交</button>
                </div>
            </div>
        </div>
    </div>
    <div style="padding-left: 10px;font-size: 16px;">
        截至{{ $datas->date }}统计数据
        <button>过往统计数据</button>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal" data-whatever="zto">中通快递费用</button>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal2" data-whatever="sf">顺丰快递费用</button>
    </div>
    <div id="single_data">
        <p>数据库总量<br><span>{{ $datas->book_count }}本</span></p>
        <p>SKU总量<br><span>{{ $datas->sku_count }}本</span></p>
        <p>顺丰总额<br><span>{{ $datas->SF_amount }}元</span></p>
        <p>回收运费<br><span>{{ round($datas->SF_amount/$datas->sku_count,2) }}元/本</span></p>
        <p>回收总额<br><span>{{ $datas->recover_amount }}元</span></p>
        <p>回收均价<br><span>{{ $datas->recover_avg }}元/本</span></p>
        <p>已卖总量<br><span>{{ $datas->sold_count }}本</span></p>
        <p>流转中位数<br><span>{{ round($datas->median/86400,1) }}天</span></p>
        <div class="clear"></div>
        <p>中通总额<br><span>{{ $datas->ZTO_amount }}元</span></p>
        <p>卖书运费<br><span>{{ round($datas->ZTO_amount/$datas->sold_count,2) }}元/本</span></p>
        <p>卖书总额<br><span>{{ $datas->sold_amount }}元</span></p>
        <p>卖书均价<br><span>{{ $datas->sold_avg }}元/本</span></p>
        <p>问题书籍<br><span>{{ $datas->issue_count }}本</span></p>
        <p>回收订单<br><span>{{ $datas->recover_order_count }}个</span></p>
        <p>卖书订单<br><span>{{ $datas->sale_order_count }}个</span></p>
    </div>
    <div style="clear: both"></div>
    {{--<div id="main" style="width: 600px;height: 600px"></div>--}}
    <div id="my_chart" style="margin-top:20px;display:inline-block;width: 35%;height: 350px"></div>
    <div id="my_chart2" style="display:inline-block;width: 35%;height: 350px"></div>
    <div id="my_chart3" style="display:inline-block;width: 35%;height: 350px"></div>
    <div id="my_chart4" style="display:inline-block;width: 35%;height: 350px"></div>
    {{--@foreach ($chapters as $reminder)--}}
        {{--The current value is {{ $reminder->book->name }}--}}
    {{--@endforeach--}}
</body>
<script src="https://cdn.bootcss.com/axios/0.19.0/axios.min.js"></script>
<script>
    $('#exampleModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var recipient = button.data('whatever');
        var modal = $(this);
        modal.find('.modal-title').text('登记'+recipient+'快递费用');
        modal.find('.modal-body #recipient-name').val(recipient)
    });
    $('#exampleModal2').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var recipient = button.data('whatever');
        var modal = $(this);
        modal.find('.modal-title').text('登记'+recipient+'快递费用');
        modal.find('.modal-body #recipient-name').val(recipient)
    });
    $('#submit').on('click', function (event) {
        var name = $('#recipient-name').val(),
            money = $('#money').val(),
            mark = $('#mark').val();
        axios.post('/admin/sts/update_ship',{name:name,money:money,mark:mark}).then(function (res) {
            console.log(res.data)
        })
    });
    var data1 = eval('{{ $datas->sale_linedata }}'),
        data2 = eval('{{ $datas->sold_linedata }}'),
        data3 = eval('{{ $datas->recover_linedata }}'),
        data4 = eval('{{ $datas->sales_linedata }}'),
        {{--line_data = eval('{{ $data }}'),--}}
        {{--line_data2 = eval('{{ $data1 }}'),--}}
        label = '{{ $date_arr }}'.replace(/&quot;/g,'').replace('[','').replace(']','').split(','),
        labels1 = ["1天内","1-5天内","5-10天内","10-30天内","30-100天内","100-180天内","大于180天"],
        price_label = ["小于1元","1-2元","2-3元","3-5元","5-10元","10-30元","大于30元"],
        price_label2 = ["小于5元","5-10元","10-15元","15-20元","20-50元","50-100元","大于100元"],
        t1 = "pie";
    console.log(data1,data2);
    var ctx = document.getElementById("my_chart"),
        ctx2 = document.getElementById("my_chart2"),
        ctx3 = document.getElementById("my_chart3");
        ctx4 = document.getElementById("my_chart4");
    makeChart(ctx,data1,labels1,t1,'在售书籍在库时间','');
    makeChart(ctx2,data2,labels1,t1,'已售书籍流转时间','');
    makeChart(ctx3,data3,price_label,t1,'回收价格分布','');
    makeChart(ctx4,data4,price_label2,t1,'卖出价格分布','');
</script>
</html>
