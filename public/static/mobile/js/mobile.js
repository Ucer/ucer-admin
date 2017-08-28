//右上角菜单显示隐藏功能
function show_menu() {
	var bd_top = $(document).scrollTop();
	if($('#menu').css('display')=='none') {
		$('#menu').removeClass('hid');
		$('#menu').addClass('show');
		if(/iphone/i.test(navigator.userAgent) || (Sys.uc >= 9 && bd_top<300)) {
			$('#hed_id').removeClass('hd_box_float');
			$('#play_box').removeClass('p48');
			$('.mnav').css({"position":"relative"});
		}
		setcookie('hidtips','1'); 
	} else {
		$('#menu').removeClass('show');
		$('#menu').addClass('hid');
		if(/iphone/i.test(navigator.userAgent) || (Sys.uc >= 9 && bd_top<300)) {
			$('#hed_id').addClass('hd_box_float');
			$('#play_box').addClass('p48');
			$('.mnav').css({"position":"absolute"});
		}
		setcookie('hidtips','1'); 
	}
 }
 
(function(){
   var $nav = $('.goods_nav');
   $(window).on("scroll", function() {	
   $('#menu').removeClass('show');	
	$('#menu').addClass('hid');
	});
 })();

/**
 * addcart 将商品加入购物车
 * @goods_id  商品id
 * @num   商品数量
 * @form_id  商品详情页所在的 form表单
 * @to_catr 加入购物车后再跳转到 购物车页面 默认0不跳转 1 为跳转
 * layer弹窗插件请参考http://layer.layui.com/mobile/
 */
var ajaxAddCart = function(goods_id,num,to_catr){
	if($("#buy_goods_form").length >0 ){  //如果有商品规格 说明是商品详情页提交
		$.ajax({
			type: "POST",
			url:"/mobile/Cart/ajaxAddCart",
			data:$("#buy_goods_form").serialize(),//表单序列化提交
			dataType: 'json',
			success: function(data){
				// 加入购物车后再跳转到 购物车页面
				if(data.code <1){ //错误
					//layer.msg(data.msg,{icon:5,time:1000,shade: [0.8, '#000']});
					layer.open({content: data.msg,time: 1});
					return false;
				}
				if(to_catr == 1)  //直接点击了购买
				{
					location.href = "/mobile/Cart/cart";
				}
				if(data.code ==1) { //成功
					var cart_num = parseInt($("#tp_cart_info").html()) +parseInt($('#number').val());
					$('#tp_cart_info').html(cart_num);
					layer.open({
						content: '添加成功！',
						btn: ['再逛逛', '去购物车'],
						shadeClose: false,
						yes: function(){
							layer.closeAll();
						}, no: function(){
							location.href = "/mobile/Cart/cart";
						}
					});
				}
			},
			error:function(){
				//layer.msg('网络错误,请稍后再试',{icon:5,time:1000,shade: [0.8, '#000']});
				layer.open({content:'网络错误,请稍后再试',time: 1});
				return false;
			}
		});
	}else{ //否则可能是商品列表页 、收藏页商品点击加入购物车
		$.ajax({
			type: "POST",
			url:"/mobile/Cart/ajaxAddCart",
			data: {goods_id:goods_id,goods_num:num},
			dataType: 'json',
			success: function(data){
				if(data.code ==1){
					var cart_num = parseInt($('#tp_cart_info').html())+parseInt(num);
					$('#tp_cart_info').html(cart_num)
					layer.open({content: data.msg,time: 1});
					return false;
				}else{
					if(data.code==-1){//如果商品有规格
						location.href = "/mobile/Goods/goodsInfo/id/"+goods_id;
					}else{
						//layer.msg(data.msg,{icon:5,time:1000,shade: [0.8, '#000']});
						layer.open({content: data.msg,time: 1});
						return false;
					}
				}
			},
			error:function(){
				//layer.msg('网络错误,请稍后再试',{icon:5,time:1000,shade: [0.8, '#000']});
				layer.open({content:'网络错误,请稍后再试',time: 1});
				return false;
			}
		});
	}
}
/**
 * 序列化表单ajax异步提交
 *@param url 控制器url
 *@param formId 表单id
 */
function mobileAjaxFormBtn(url,formId,layerClose){
	$.ajax({
		type: 'post',
		url: url,
		data: $("#"+formId).serialize(),
		success: function(data) {
			if(data.code ==1){
				window.location = data.url;
			}else{
				layer.open({content:data.msg,time: 1});
				return false;
			}
		},
		error:function(){
			layer.open({content:'网络错误,请稍后再试',time: 1});
			return false;
		}
	});
}