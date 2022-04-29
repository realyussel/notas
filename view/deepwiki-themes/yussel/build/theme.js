	jQuery(document).ready(function() {
		$(".list-group-item button").click(function(event) {
			$(this).children().toggleClass("fa-caret-up fa-caret-down");
			event.stopPropagation();
			event.preventDefault();
			$(this).parent('.list-group-item').prop('onclick', null);
		});

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