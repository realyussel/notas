class LottieAnimator {
	constructor(bodymovin, props) {
		this.animation = bodymovin.loadAnimation(props);
		this.throttledPlayback = false;
		this.duration_ms = this.animation.getDuration(false) * 1000;
	}
	playThrottled = (fps) => {
		if (this.throttledPlayback)
			return;
		this.min_interval = 1000 / fps;
		this.firstFrame = new Date().getTime();
		this.lastFrame = 0;
		this.throttledPlayback = true;
		requestAnimationFrame(() => this.renderFrame());
	};
	renderFrame = () => {
		if (!this.throttledPlayback)
			return;
		let now = new Date().getTime();
		if (this.lastFrame == 0 || now - this.lastFrame >= this.min_interval) {
			this.lastFrame = now;
			this.animation.goToAndStop((now - this.firstFrame) % this.duration_ms, false);
			this.animation.renderFrame();
		}
		requestAnimationFrame(() => this.renderFrame());
	}
}

function showLottieAnimation(animationFn) {
	const illustration = document.getElementById('illustration');
	const animation = document.getElementById('animation');
	bodymovin.setQuality('low');
	const animator = new LottieAnimator(bodymovin, {
		container: animation,
		renderer: 'svg',
		loop: true,
		autoplay: false,
		animationData: animationFn,
		rendererSettings: {
			id: 'lottie-err-animation'
		}
	});
	animator.playThrottled(30);
	illustration.style.display = 'none';
	animation.style.display = 'block';
}

function init() {
	showLottieAnimation(window.LOTTIE_ANIM_SITE_CONNECTION_TIMEOUT);
}
document.addEventListener('DOMContentLoaded', init);