!function(h,u){function t(e){this.options=h.extend(!0,{id:"__MUI_PREVIEWIMAGE",zoom:!0,header:'<span class="mui-preview-indicator"></span>',footer:""},e||{}),this.init(),this.initEvent()}var a="__DEFAULT",i=document.createElement("div"),e=t.prototype;e.init=function(){var e=this.options,t=document.getElementById(this.options.id);t||(i.innerHTML='<div id="{{id}}" class="mui-slider mui-preview-image mui-fullscreen"><div class="mui-preview-header">{{header}}</div><div class="mui-slider-group"></div><div class="mui-preview-footer mui-hidden">{{footer}}</div><div class="mui-preview-loading"><span class="mui-spinner mui-spinner-white"></span></div></div>'.replace(/\{\{id\}\}/g,this.options.id).replace("{{header}}",e.header).replace("{{footer}}",e.footer),document.body.appendChild(i.firstElementChild),t=document.getElementById(this.options.id)),this.element=t,this.scroller=this.element.querySelector(h.classSelector(".slider-group")),this.indicator=this.element.querySelector(h.classSelector(".preview-indicator")),this.loader=this.element.querySelector(h.classSelector(".preview-loading")),e.footer&&this.element.querySelector(h.classSelector(".preview-footer")).classList.remove(h.className("hidden")),this.addImages()},e.initEvent=function(){var i=this;h(document.body).on("click","img[data-preview-src]",function(){return i.isAnimationing()||i.open(this),!1});var e=null,t=function(){e=e||h.later(function(){i.isInAnimation=!0,i.loader.removeEventListener("tap",t),i.scroller.removeEventListener("tap",t),i.close()},300)};this.scroller.addEventListener("doubletap",function(){e&&(e.cancel(),e=null)}),this.element.addEventListener("webkitAnimationEnd",function(){i.element.classList.contains(h.className("preview-out"))?(i.element.style.display="none",i.element.classList.remove(h.className("preview-out")),e=null):(i.loader.addEventListener("tap",t),i.scroller.addEventListener("tap",t)),i.isInAnimation=!1}),this.element.addEventListener("slide",function(e){var t;!i.options.zoom||(t=i.element.querySelector(".mui-zoom-wrapper:nth-child("+(i.lastIndex+1)+")"))&&h(t).zoom().setZoom(1);e=e.detail.slideNumber;i.lastIndex=e,i.indicator&&(i.indicator.innerText=e+1+"/"+i.currentGroup.length),i._loadItem(e)})},e.isAnimationing=function(){return!!this.isInAnimation||!(this.isInAnimation=!0)},e.addImages=function(e,t){this.groups={};var i=[];if((i=e?e===a?document.querySelectorAll("img[data-preview-src]:not([data-preview-group])"):document.querySelectorAll("img[data-preview-src][data-preview-group='"+e+"']"):document.querySelectorAll("img[data-preview-src]")).length)for(var s=0,n=i.length;s<n;s++)this.addImage(i[s])},e.addImage=function(e){var t=e.getAttribute("data-preview-group");this.groups[t=t||a]||(this.groups[t]=[]);var i,s=e.getAttribute("src");e.__mui_img_data&&e.__mui_img_data.src===s?this.groups[t].push(e.__mui_img_data):(i={src:s,lazyload:s===(i=(i=e.getAttribute("data-preview-src"))||s)?"":i,loaded:s===i,sWidth:0,sHeight:0,sTop:0,sLeft:0,sScale:1,el:e},this.groups[t].push(i),e.__mui_img_data=i)},e.empty=function(){this.scroller.innerHTML=""},e._initImgData=function(e,t){var i;e.sWidth||(i=e.el,e.sWidth=i.offsetWidth,e.sHeight=i.offsetHeight,i=h.offset(i),e.sTop=i.top,e.sLeft=i.left,e.sScale=Math.max(e.sWidth/u.innerWidth,e.sHeight/u.innerHeight)),t.style.webkitTransform="translate3d(0,0,0) scale("+e.sScale+")"},e._getScale=function(e,t){var i=e.width/t.width,s=e.height/t.height;return i<=s?e.height/(t.height*i):e.width/(t.width*s)},e._imgTransitionEnd=function(e){e=e.target;e.classList.remove(h.className("transitioning")),e.removeEventListener("webkitTransitionEnd",this._imgTransitionEnd.bind(this))},e._loadItem=function(e,t){var i,s=this.scroller.querySelector(h.classSelector(".slider-item:nth-child("+(e+1)+")")),n=this.currentGroup[e],a=s.querySelector("img");this._initImgData(n,a),t&&(t=this._getPosition(n),a.style.webkitTransitionDuration="0ms",a.style.webkitTransform="translate3d("+t.x+"px,"+t.y+"px,0) scale("+n.sScale+")",a.offsetHeight),!n.loaded&&a.getAttribute("data-preview-lazyload")?((i=this).loader.classList.add(h.className("active")),a.style.webkitTransitionDuration="0.5s",a.addEventListener("webkitTransitionEnd",i._imgTransitionEnd.bind(i)),a.style.webkitTransform="translate3d(0,0,0) scale("+n.sScale+")",this.loadImage(a,function(){n.loaded=!0,a.src=n.lazyload,i._initZoom(s,this.width,this.height),a.classList.add(h.className("transitioning")),a.addEventListener("webkitTransitionEnd",i._imgTransitionEnd.bind(i)),a.setAttribute("style",""),a.offsetHeight,i.loader.classList.remove(h.className("active"))})):(n.lazyload&&(a.src=n.lazyload),this._initZoom(s,a.width,a.height),a.classList.add(h.className("transitioning")),a.addEventListener("webkitTransitionEnd",this._imgTransitionEnd.bind(this)),a.setAttribute("style",""),a.offsetHeight),this._preloadItem(e+1),this._preloadItem(e-1)},e._preloadItem=function(e){var t=this.scroller.querySelector(h.classSelector(".slider-item:nth-child("+(e+1)+")"));t&&((e=this.currentGroup[e]).sWidth||(t=t.querySelector("img"),this._initImgData(e,t)))},e._initZoom=function(e,t,i){this.options.zoom&&(e.getAttribute("data-zoomer")||("IMG"===e.querySelector(h.classSelector(".zoom")).tagName?(i=this._getScale({width:e.offsetWidth,height:e.offsetHeight},{width:t,height:i}),h(e).zoom({maxZoom:Math.max(i,1)})):h(e).zoom()))},e.loadImage=function(e,t){function i(){t&&t.call(this)}var s=new Image;s.onload=i,s.onerror=i,s.src=e.getAttribute("data-preview-lazyload")},e.getRangeByIndex=function(e,t){return{from:0,to:t-1}},e._getPosition=function(e){var t=e.sLeft-u.pageXOffset,i=e.sTop-u.pageYOffset;return{left:t,top:i,x:t-(u.innerWidth-e.sWidth)/2,y:i-(u.innerHeight-e.sHeight)/2}},e.refresh=function(e,t){for(var i=(this.currentGroup=t).length,s=[],i=this.getRangeByIndex(e,i),n=i.from,a=i.to+1,r=e,o="",l="",d=(u.innerWidth,u.innerHeight,0);n<a;n++,d++){var c=t[n],m="";c.sWidth&&(m="-webkit-transform:translate3d(0,0,0) scale("+c.sScale+");transform:translate3d(0,0,0) scale("+c.sScale+")"),l='<div class="mui-slider-item mui-zoom-wrapper {{className}}"><div class="mui-zoom-scroller"><img src="{{src}}" data-preview-lazyload="{{lazyload}}" style="{{style}}" class="mui-zoom"></div></div>'.replace("{{src}}",c.src).replace("{{lazyload}}",c.lazyload).replace("{{style}}",m),o=n===e?(r=d,h.className("active")):"",s.push(l.replace("{{className}}",o))}this.scroller.innerHTML=s.join(""),this.element.style.display="block",this.element.classList.add(h.className("preview-in")),this.lastIndex=r,this.element.offsetHeight,h(this.element).slider().gotoItem(r,0),this.indicator&&(this.indicator.innerText=r+1+"/"+this.currentGroup.length),this._loadItem(r,!0)},e.openByGroup=function(e,t){e=Math.min(Math.max(0,e),this.groups[t].length-1),this.refresh(e,this.groups[t])},e.open=function(e,t){this.element.classList.contains(h.className("preview-in"))||("number"==typeof e?(this.addImages(t=t||a,e),this.openByGroup(e,t)):(t=e.getAttribute("data-preview-group"),this.addImages(t=t||a,e),this.openByGroup(this.groups[t].indexOf(e.__mui_img_data),t)))},e.close=function(e,t){this.element.classList.remove(h.className("preview-in")),this.element.classList.add(h.className("preview-out"));var i,s,n,a,r=this.scroller.querySelector(h.classSelector(".slider-item:nth-child("+(this.lastIndex+1)+")")).querySelector("img");r&&(r.classList.add(h.className("transitioning")),i=this.currentGroup[this.lastIndex],n=(s=this._getPosition(i)).left,(a=s.top)>u.innerHeight||n>u.innerWidth||a<0||n<0?(r.style.opacity=0,r.style.webkitTransitionDuration="0.5s",r.style.webkitTransform="scale("+i.sScale+")"):(this.options.zoom&&h(r.parentNode.parentNode).zoom().toggleZoom(0),r.style.webkitTransitionDuration="0.5s",r.style.webkitTransform="translate3d("+s.x+"px,"+s.y+"px,0) scale("+i.sScale+")"));for(var o=this.element.querySelectorAll(h.classSelector(".zoom-wrapper")),l=0,d=o.length;l<d;l++)h(o[l]).zoom().destory()},e.isShown=function(){return this.element.classList.contains(h.className("preview-in"))};var s=null;h.previewImage=function(e){return s=s||new t(e)},h.getPreviewImage=function(){return s}}(mui,window);