<template>
	<div :class="{'m-loading': true, 'full': full}">
		<div class="bg">
			<m-spinner :width="20" />
			<span>{{ title }}</span><i>{{ dots }}</i>
		</div>
	</div>
</template>

<script>
	export default {
		props: {
			full: {
				type: Boolean,
				default: false
			},
			title: {
				type: String,
				default: 'Đang tải'
			}
		},
		data() {
			return {
				dots: '...',
				inter: null
			}
		},
		mounted() {
			this.inter = setInterval(() => {
				let len = this.dots.length;

				if (len == 1) {
					this.dots = '..';
				} else if (len == 2) {
					this.dots = '...';
				} else {
					this.dots = '.';
				}
			}, 300);
		},
		beforeDestroy() {
			clearInterval(this.inter);
		}
	}
</script>