
	//

	$('#js_cmt_addbtn1').click(function(){
	  $('#com_form').css('display','block');
        $("textarea").focus();
	});
     $("textarea").focus(function(){
    $("textarea").css("background-color","#fff");
  }); 
      
        
	//
	$('.sheet-item').on('click','li',function(){
	  $('#Alert').css('display','none');
	   $("#addBox").fadeIn();//弹出
       $("#addBox").fadeOut(5000); //变淡消失

	})


	//
	$(".tousu").click(function(){
	$("#Alert").css("display","block");
	});

	$('#puxiao').click(function(){
	  $('#Alert').css('display','none');
	  console.log($('#puxiao'));
	});
	$('#Alert').click(function(){
	$('#Alert').css('display','none');
	});
    
/*********回顶部************************/
$(document).ready(function($) {

				$("<div id='toTop'><img src='../addons/han_article/images/dingbu1.png'style='width:30px;height:30px'></div>").appendTo('body');
				$("#toTop").css({
					width: '30px',
					height: '30px',
					bottom:'106px',
					right:'77px',    
					position:'fixed',
					cursor:'pointer',
					zIndex:'99',     
				});
				if($(this).scrollTop()==0){
						$("#toTop").hide();
					}
				$(window).scroll(function(event) {
					/* Act on the event */
					if($(this).scrollTop()==0){
						$("#toTop").hide();
					}
					if($(this).scrollTop()!=0){
						$("#toTop").show();
					}
				});
					$("#toTop").click(function(event) {
								/* Act on the event */
								$("html,body").animate({
									scrollTop:"0px"},
									666
									)
							});
					  


		});



  