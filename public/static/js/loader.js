var loadCss = function(href){
    var cssLink = document.createElement('link');
    cssLink.rel = 'stylesheet';
    cssLink.href = href;
    var head = document.getElementsByTagName('head')[0];
    head.parentNode.insertBefore(cssLink, head);
};

var loadScript = function (src) {
	var script = document.createElement('script');
	script.src = src;
	document.head.appendChild(script);
}

var loads = {
	css: [
		'/static/css/bulma.min.css',
		'/static/css/app.css',
		'/static/css/sweetalert.css',
		'/static/css/snackbar.css',
		'/static/css/datepicker.css',
		'https://fonts.googleapis.com/css?family=Rubik&display=swap'
	],
	js: [
		'/static/css/bulma.min.css',
		'/static/js/jquery.min.js',
		'/static/js/datepicker.js',
		'/static/js/fontawesome_all.js',
		'/static/js/sweetalert.min.js',
		'/static/js/pdf.js',
		'/static/js/pdf.worker.js',
		'/static/js/snackbar.js',
		'/static/js/app.js'
	],
}

for(var i in loads.css){
	loadCss(loads.css[i])
}

for(var i in loads.js){
	loadScript(loads.js[i])
}
