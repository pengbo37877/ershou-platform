<!DOCTYPE html>
<html lang="utf-8">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta name="viewport" content="width=device-width,height=device-height,maximum-scale=1.0,user-scalable=no,viewport-fit=cover">
    <script src="https://cdn.jsdelivr.net/npm/vue"></script>
</head>
<body>
<div id="app" class="form-horizontal" style="margin-top: 30px">
    <div class="form-group">
        <label for="nameInput" class="col-sm-1 control-label">名称</label>
        <div class="col-sm-6">
            <input type="text" class="form-control" v-model="name" id="nameInput" placeholder="输入规则名称" />
        </div>
    </div>
    <div class="form-group">
        <label for="content" class="col-sm-1 control-label">基础运费</label>
        <div class="col-sm-6" >
            <input type="number" class="form-control" v-model="base_price" />
        </div>
    </div>
    <div class="form-group">
        <label for="content" class="col-sm-1 control-label">拒收区域</label>
        <div class="col-sm-6" >
            <input type="text" class="form-control" v-model="reject" disabled />
        </div>
    </div>
    <div class="form-group">
        <label for="content" class="col-sm-1 control-label">加运费规则</label>
        <div class="col-sm-6" >
            <div style="overflow: auto;background-color: #fff;width: 100%;height: 180px;border: 1px solid #ccc;border-radius: 1px;padding: 3px 5px;">
                <p v-for="(item,index) in content" :id="'rule'+index" style="line-height: 26px;">${item.areas}-------------${item.addition}元
                    <span style="width: 30px;float: right;background-color: #8aa4af;text-align: center;color: #fff;border-radius: 2px;" @click="delrule(index)">X</span>
                </p>
            </div>
        </div>
    </div>
    <div v-show="showAdd" class="form-group">
        <label class="col-sm-1 control-label">地区</label>
        <div class="col-sm-2">
            <label style="display: block" v-for="item in areaArr.slice(0,11)"><input type="checkbox" :value="item">${item}</label>
        </div>
        <div class="col-sm-2">
            <label style="display: block" v-for="item in areaArr.slice(11,21)"><input type="checkbox" :value="item">${item}</label>
        </div>
        <div class="col-sm-2">
            <label style="display: block" v-for="item in areaArr.slice(21)"><input type="checkbox" :value="item">${item}</label>
        </div>
    </div>
    <div v-show="showAdd" class="form-group">
        <label for="additionInput" class="col-sm-1 control-label">加运费</label>
        <div class="col-sm-2">
            <input class="form-control" id="additionInput" v-model="addition" type="number" placeholder="输入运费（元）" />
        </div>
        <div class="col-sm-2">
            <button class="btn btn-primary" @click="addrule">添加细则</button>
            <button class="btn btn-warning" @click="rejectArea">拒发货</button>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label"><input type="reset" class="btn btn-normal" value="重置"></label>
        <label class="col-sm-3 control-label">
            <button class="btn btn-primary" id="form-submit" @click="submit_rule">提交</button>
        </label>
    </div>
</div>
</body>
<script src="https://cdn.bootcss.com/axios/0.19.0/axios.min.js"></script>
<script>
    function HTMLDecode(text) {
        var temp = document.createElement("div");
        temp.innerHTML = text;
        var output = temp.innerText || temp.textContent;
        temp = null;
        return output;
    }
    var content = HTMLDecode('{{ $ship_rule->content }}');
    console.log(content)
    var app = new Vue({
        el: '#app',
        delimiters: ['${', '}'],
        data: {
            content: eval(content),
            reject: '{{ $ship_rule->reject }}',
            base_price: '{{ $ship_rule->base_price }}',
            areaArr: ['北京市','天津市','河北省','山西省', '上海市','江苏省','浙江省','云南省','陕西省',
                '安徽省','福建省','江西省','山东省','河南省','湖北省','湖南省','广东省','重庆市','四川省',
                '广西壮族自治区','辽宁省','吉林省','黑龙江省','贵州省','海南省','内蒙古自治区',
                '甘肃省','青海省', '宁夏回族自治区','新疆维吾尔自治区','西藏自治区'],
            showAdd:true,
            addition: null,
            name:'{{ $ship_rule->name }}',
        },
        methods:{
            addrule:function () {
                var areas = $('input[type=checkbox]:checked').map(function () {
                    return $(this).val();
                }).get();
                for(var i in areas){
                    if(this.reject.indexOf(areas[i]) !== -1){
                        alert('同一地区不允许多次设置');
                        return false;
                    }
                    if(JSON.stringify(this.content).indexOf(areas[i])!==-1){
                        alert('同一地区不允许多次设置');
                        return false;
                    }
                }
                this.content.push({areas:areas.join(','),addition:this.addition});
                this.addition = null;
                $('input[type=checkbox]').attr('checked',false);
            },
            rejectArea:function () {
                var areas = $('input[type=checkbox]:checked').map(function () {
                    return $(this).val();
                }).get();
                console.log(areas);
                for(var i in areas){
                    if(this.reject.indexOf(areas[i]) !== -1){
                        alert('同一地区不允许多次设置');
                        return false;
                    }
                    if(JSON.stringify(this.content).indexOf(areas[i])!==-1){
                        alert('同一地区不允许多次设置');
                        return false;
                    }
                }
                this.reject = areas.join(',');
                $('input[type=checkbox]').attr('checked',false);
            },
            submit_rule:function () {
                console.log(this.content);
                axios.post('/admin/ship_rules/add',
                    {
                        id: Number('{{ $ship_rule->id }}'),
                        name:this.name,
                        content:JSON.stringify(this.content),
                        base_price: this.base_price,
                        reject: this.reject
                    }).then(function (res) {
                    console.log(res.data);
                    if(res.data.code == 200){
                        var r = confirm('更新成功');
                        if(r) window.location.href = '/admin/ship_rules';
                    }else{
                        alert('更新失败')
                    }
                })
            },
            delrule:function(i){
                this.content.splice(i,1)
            }
        }
    })
    // var content = [];
    // function delrule(index) {
    //     content.unshift(content[index]);
    // }
    // $('#rule-submit').on('click',function (event) {
    //     var areas = $('input[type=checkbox]:checked').map(function () {
    //         var areaArr = [];
    //         for(var j=0;j<content.length;j++){
    //             areaArr.push.apply(areaArr,content[j].areas);
    //         }
    //         console.log(areaArr)
    //         if (areaArr.indexOf($(this).val()) !== -1){
    //             alert('同一地区不允许多次设置')
    //         }else{
    //             return $(this).val();
    //         }
    //     }).get();
    //     var addition = $('#additionInput').val();
    //     content.push({areas:areas,addition:addition});
    //     var html = '';
    //     for(var i=0;i<content.length;i++){
    //         html += '<p id="rule'+i+'" class="little-rule">'+content[i].areas.join(',')+'-------'+content[i].addition+'元<span onClick="delrule('+i+')">X</span></p>';
    //     }
    //     $('#content').html(html);
    //     $('input[type=checkbox]').attr('checked',false);
    //     $('#additionInput').val(null);
    // });
    // $('#form-submit').on('click', function (event) {
    //     var name = $('#nameInput').val(),
    //         mark = $('#additionInput').val();
    //     var areas = $('input[type=checkbox]:checked').map(function () {
    //         return $(this).val();
    //     }).get().join(',');
    //     console.log(areas)
    //     axios.post('/admin/ship_rules/add',{name:name,areas:areas,addition:mark}).then(function (res) {
    //         console.log(res.data)
    //         var r = confirm(res.data.msg);
    //         if(r){window.location.href = '/admin/ship_rules'}
    //     })
    // });
</script>
</html>