	jQuery(document).ready(function() {
		$("#wiki-nav button").click(function(event) {
			event.stopPropagation();
			event.preventDefault();
			$(this).parent('#wiki-nav').prop('onclick', null);
		});


		$("#subnavbar-nav button").click(function(event) {
			event.stopPropagation();
			event.preventDefault();
		});

		// MARKS

		const markers = [...document.querySelectorAll('mark')];

		const observer = new IntersectionObserver(entries => {
			entries.forEach((entry) => {
				if (entry.intersectionRatio > 0) {
					entry.target.style.animationPlayState = 'running';
					observer.unobserve(entry.target);
				}
			});
		}, {
			threshold: 0.8
		});

		markers.forEach(mark => {
			observer.observe(mark);
		});
	});