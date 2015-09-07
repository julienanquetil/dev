$(function() {
	//===== Default navigation =====//

	$('.navigation').find('li.active').parents('li').addClass('active');
	$('.navigation').find('li').not('.active').has('ul').children('ul').addClass('hidden-ul');
	$('.navigation').find('li').has('ul').children('a').parent('li').addClass('has-ul');

	$('.navigation').find('li').has('ul').children('a').on('click', function (e) {
	    e.preventDefault(); 
		$(this).parent('li').not('.disabled').toggleClass('active').children('ul').slideToggle(250);
		$(this).parent('li').not('.disabled').siblings().removeClass('active').children('ul').slideUp(250);
	}); 

	//===== Disabling main navigation links =====//

	$('.navigation .disabled a, .navbar-nav > .disabled > a').click(function (e){
		e.preventDefault();
	});


});