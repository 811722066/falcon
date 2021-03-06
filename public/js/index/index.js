(function($, win, doc){
	var Index = {
		navigtionPosition: function(){
			var that = Index, top;
			top = $('html').scrollTop();
			top = top ? top : $('body').scrollTop();
			if(top>10){
				that.$navigation.addClass('navigation-fixed');
			}else{
				that.$navigation.removeClass('navigation-fixed');
			}
		},
		bindEvents: function(){
			var that = Index;
			$(win).on('scroll',that.navigtionPosition);
		},
		objectInit: function(){
			var that = Index;
			that.$navigation = $('#navigation');
		},
		init: function(){
			var that = Index;
			that.objectInit();
			that.bindEvents();
			if(win.GoodsEntity)win.GoodsEntity.init({container: '.goods-box'});
		}
	};
	
	Index.init();
})(jQuery, window, document);
