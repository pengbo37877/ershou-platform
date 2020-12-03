import wx from "weixin-js-sdk";
import router from './routes';
const wxApi = {
    /**
     * Api 初始化
     * 
     */
    wxConfig(options,tag) {
        var url = window.localStorage.getItem("url");
        var link =router.history.current.fullPath;
        link =link.replace('/wechat/','');
        var option ={
            title:'回流⻥，1折淘好书，看完还能卖',
            desc:'回流⻥二手循环书店，让好书流动起来',
            link:url + "/wechat/shop",
            imgUrl:url+'/images/image/logo.jpeg'
        }
        var options =options==undefined||options==''?option:options;
        var tag =tag==undefined||tag==''?'shop':tag;
        axios
          .post("/wx-api/config", {
            url: link
          })
          .then(response => {
            wx.config(response.data)
            wx.ready(() => {
              wx.onMenuShareAppMessage({
                title: options.title, // 分享标题
                desc: options.desc, // 分享描述
                link: options.link, // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
                imgUrl: options.imgUrl, // 分享图标
                type: "", // 分享类型,music、video或link，不填默认为link
                dataUrl: "", // 如果type是music或video，则要提供数据链接，默认为空
                success: function() {
                  // 用户点击了分享后执行的回调函数
                  console.log("分享成功");
                }
              });
              wx.onMenuShareTimeline({
                title: options.title, // 分享标题
                link: options.link, // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
                imgUrl: options.imgUrl, // 分享图标
                success: function() {
                  // 用户点击了分享后执行的回调函数
                  console.log("分享成功");
                }
              });
            });
          });
      }
    
}

export default wxApi