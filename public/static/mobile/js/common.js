// 手机端 产品 详情 评价 切换
// 用户评论 待评价 已评价 切换
function setGoodsTab(name,cursel,n){
	$('html,body').animate({'scrollTop':0},600);
		for(i=1;i<=n;i++){
		var menu=document.getElementById(name+i);
		var con=document.getElementById("user_"+name+"_"+i);
		menu.className=i==cursel?"on":"";
		con.style.display=i==cursel?"block":"none";
	}
}
// 手机端 下单页面 显示 其他选择 js效果
function showCheckoutOther(obj)
{
	var otherParent = obj.parentNode;
	otherParent.className = (otherParent.className=='checkout_other') ? 'checkout_other2' : 'checkout_other';
	var spanzi = obj.getElementsByTagName('span')[0];
	spanzi.className= spanzi.className == 'right_arrow_flow' ? 'right_arrow_flow2' : 'right_arrow_flow';
}