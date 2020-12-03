function makeChart(ctx,data,labels,t,title,subtitle) {
    var myChart = echarts.init(ctx);
    var arr = [];
    for(var i=0;i<data.length;i++){
        var json = {};
        json.value = data[i];
        json.name = labels[i]+"——"+data[i];
        arr.push(json);
    }
    var option = {
        title: {
            text: title,
            subtext:subtitle,
            x: 'center'
        },
        tooltip:{
            trigger: 'item',
            formatter: "{a} <br/>{b} : {c} ({d}%)"
        },
        legend:{
            orient:'vertical',
            left:'left',
            data:labels
        },
        series:[
            {
                name:'访问来源',
                type:'pie',
                radius:'55%',
                center:['50%','56%'],
                data:arr
            }
        ]
    };
    myChart.setOption(option)
}