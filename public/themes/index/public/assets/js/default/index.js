/*解决ios点击问题*/
$(function() {    
    FastClick.attach(document.body);    
});  

/*搜索*/
$(function(){
  $(".ss").click(function(){
    $(".logo").fadeOut();
    $(".ss_form").fadeIn();
	$(this).fadeOut();
	$(".ss_bei").fadeIn();
  });
   $(".ss_bei").click(function(){
	   $(".ss_form").fadeOut();
	   $(".logo").fadeIn();
	   $(".ss").fadeIn();
   });
});



/*首页banner*/
$(function(){
  var swiper = new Swiper('.banner .swiper-container', {
    loop : true,
    centeredSlides: true,
    autoplay: {
      delay: 2500,
      disableOnInteraction: false,
    },
    pagination: {
      el: '.banner .swiper-pagination',
      clickable: true,
    },
    navigation: {
      nextEl: '.banner .swiper-button-next',
      prevEl: '.banner .swiper-button-prev',
    },
  });
});

/*商品详情分享*/
$(function(){
  $(".fxan").click(function(){
    $(".fxbj").fadeIn();
  });
  $(".fxbj").click(function(){
    $(".fxbj").fadeOut();
  });
});


/*公告*/
$(function(){
  var swiper = new Swiper('.in3 .swiper-container', {
    loop : true,
    centeredSlides: true,
    autoplay: {
      delay: 2500,
      disableOnInteraction: false,
    },
    direction: 'vertical',
    pagination: {
      el: '.in3 .swiper-pagination',
      clickable: true,
    },
  });
});



/*商品列表banner*/
$(function(){
  var swiper = new Swiper('.banner2 .swiper-container', {
    loop : true,
    centeredSlides: true,
    autoplay: {
      delay: 2500,
      disableOnInteraction: false,
    },
    pagination: {
      el: '.banner2 .swiper-pagination',
      clickable: true,
    },
    navigation: {
      nextEl: '.banner2 .swiper-button-next',
      prevEl: '.banner2 .swiper-button-prev',
    },
  });
});



/*商品详情banner*/
$(function(){
  var swiper = new Swiper('.banner3 .swiper-container', {
    loop : true,
    centeredSlides: true,
    autoplay: {
      delay: 2500,
      disableOnInteraction: false,
    },
    pagination: {
      el: '.banner3 .swiper-pagination',
      clickable: true,
    },
    navigation: {
      nextEl: '.banner3 .swiper-button-next',
      prevEl: '.banner3 .swiper-button-prev',
    },
  });
});



/*商品详情选择开关*/
// $(function(){
//   $(".xzan").click(function(){
//     $(".xz").fadeIn();
//   });
// });
$(function(){
  $(".xz_cha").click(function(){
    $(".xz").fadeOut();
  });
  // $(".xz_qr").click(function(){
  //
  //
  //   $(".xz").fadeOut();
  // });
});



/*商品详情选择参数*/
$(function(){
  $(".xz_ch .xz_cs .xz_cs_ch ul li").click(function(){
  $(this).addClass("bg");
  $(this).siblings().removeClass("bg");
});
});



/*商品详情选择件数*/
/*-*/
function jian(){
  //获取-号按钮
  var jian = document.getElementById("jian");
  //获取文本框
  var number = document.getElementById("number");
  if (number.value<=1) {
      //如果文本框的值小于1,则设置值为1
      number.value = 1;
  }else {
      number.value = number.value - 1;
  }
}
/*离开赋值1*/
function number(){
  var number = document.getElementById("number");
  var value = number.value;
  //如果文本值为空,设置为1
  if (value=="") {
      number.value = 1;
  }
  //如果文本值为非纯数字,设置为1
  //isNaN()是否为非法数字
  if (isNaN(value)) {
      number.value = 1;
  }
  //如果文本值小于1,设置为1
  if (parseInt(value)<=1) {
      number.value = 1;
  }
}
/*+*/
function jia(){
  var jia = document.getElementById("jia");
  var number = document.getElementById("number");
  //parseInt() 将数值型字符串转换为数值
  number.value = parseInt(number.value)+1;
}



/*tab*/
$(function(){
    function tabs(tabTit,on,tabCon){
        $(tabTit).children().click(function(){
            $(this).addClass(on).siblings().removeClass(on);
            var index = $(tabTit).children().index(this);
            $(tabCon).children().eq(index).show().siblings().hide();
        });
    };
    tabs(".xq_tab","bg",".xq_tab2");    //详情页tab
    tabs(".wddd_tab1","bg",".wddd_tab2");    //订单页tab
    tabs(".fp_tab1","bg",".fp_tab2");    //发票信息页tab
    tabs(".jifen_tab1","bg",".jifen_tab2");    //我的积分页tab
    tabs(".youhui_tab1","bg",".youhui_tab2");    //优惠券页tab
    tabs(".fx3_tab1","bg",".fx3_tab2");    //提现佣金tab
    tabs(".fx4_tab1","bg",".fx4_tab2");    //我的下线tab
    tabs(".fxyj_tab1","bg",".fxyj_tab2");  //分销佣金tab
    tabs(".yemx_tab1","bg",".yemx_tab2");  //余额明细tab
});



/*确认订单支付方式切换*/
$(function(){
  $(".js_zffs li").click(function(){
  $(this).addClass("bg");
  $(this).siblings().removeClass("bg");
});
});



/*收货地址管理删除、选择默认*/
$(function(){
  $(".dzgl li .dz_gn .dz_mr").click(function(){
    $(this).addClass("bg");
    $(this).parent(".dz_gn").parent("li").siblings().children(".dz_gn").children(".dz_mr").removeClass("bg");
    $(".dzgl li .dz_gn .dz_mr").html("地址");
    $(".dzgl li .dz_gn .dz_mr.bg").html("默认地址");
  });
  $(".dzgl_sc").click(function(){
    $(this).parent(".dz_bs").parent(".dz_gn").parent("li").remove();
  });
});

/*收银台弹窗*/
$(function(){
  $(".yue_tan").click(function(){
    $(".txmb").fadeIn(300);
  });
  $(".txmb-close").click(function(){
    $(".txmb").fadeOut(300);
  });
  $(".txmb-sure").click(function(){
    $(".txmb").fadeOut(300);
  });
});


/*提现信息佣金提现方式切换*/
$(function(){
  $(".txxx_yjfs li").click(function(){
  $(this).addClass("bg");
  $(this).siblings().removeClass("bg");
});
});
