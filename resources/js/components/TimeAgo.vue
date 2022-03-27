<template>
	<span>{{ timeToString(second) }}</span>
</template>

<script>
	export default {
		props: ['timestamp'],
		data() {
			return {
				second: 0,
				interval: null
			}
		},
		methods: {
			createInterval() {
				if (this.interval) {
					clearInterval(this.interval);
				}

				this.second = parseInt((new Date().getTime() - this.timestamp) / 1000);
				this.interval = setInterval(() => {
					this.second += 60;
				}, 60000);
			},
			timeToString(second) {
				if (second >= 30 * 86400) {
					let date = new Date(this.timestamp);
					let day = date.getDate();
					let month = date.getMonth() + 1;

					if (day < 10) day = '0' + day;
					if (month < 10) month = '0' + month;

					return day + '/' + month;
				} else if (second >= 86400) {
					return parseInt(second / 86400) + ' ngày';
				} else if (second >= 3600) {
					return parseInt(second / 3600) + ' giờ';
				} else if (second >= 60) {
					return parseInt(second / 60) + ' phút';
				} else if (second >= 10) {
					return second + ' giây';
				}

				return 'vài giây';
			}
		},
		watch: {
			timestamp(n) {
				this.createInterval();
			}
		},
		mounted() {
			this.createInterval();
		},
		beforeDestroy() {
			if (this.interval) {
				clearInterval(this.interval);
				this.interval = null;
			}
		}
	}
</script>