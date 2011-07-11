/**
 * This code was borrowed from the origional Fuel Documentation to be
 * implemented into this package.
 * 
 * It extracts the navigation into a single file for easier updating.
 */

// document navigation
var nav = {
	'E-Commerce': {
		'Home': 'index.html',
		'Requirements': 'requirements.html',
		'License': 'license.html',
		'Credits': 'credits.html'
	},
	'Installation': {
		'Instructions': 'installation/instructions.html',
		'Download': 'installation/download.html',
		'Troubleshooting': 'installation/troubleshooting.html'
	},
	'Classes': {
		'Cart': 'classes/cart.html',
	}
};

// insert the navigation
function show_nav(page, path) {

	active_path = window.location.pathname;
	path = path == null ? '' : path;

	$.each(nav, function(section,links) {

		var h3 = $('<h3></h3>');
		h3.addClass('collapsible').html(section);
		h3.attr('id', 'nav_'+section.toLowerCase().replace(' ', ''));

		h3.bind('click', function() {
			$(this).next('div').slideToggle();
		});

		$('#main-nav').append(h3);

		var div = $('<div></div>');

		if ('nav_'+page != h3.attr('id')) {
			div.hide();
		}

		var ul = div.append('<ul></ul>');
		ul.find('ul').append(generate_nav(path, links));

		$('#main-nav').append(div);
		$('#main-nav').find('#nav_'+page).next('div').slideDown();
	});
}

// generate the navigation
function generate_nav(path, links) {

	var html = '';

	$.each(links, function(title, href) {
		if (typeof(href) == "object") {
			for(var link in href) break;
			html = html + '<li><a href="'+path+href[link]+'">' + title + '</a>';
			html = html + '<ul>' + generate_nav(path, href) + '</ul></li>';
		}
		else {
			active = '';

			if (active_path.indexOf(href, active_path.length - href.length) != -1) {
				active = ' class="active"';
			}

			html = html + '<li><a href="'+path+href+'"'+active+'>'+title+'</a></li>';
		}
	});

	return html;
}