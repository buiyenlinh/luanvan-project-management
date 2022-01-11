<template>
	<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" class="m-spinner" :style="{'width': width + 'px', 'stroke-width': size}">
		<circle cx="25" cy="25" r="20" class="path"></circle>
		<circle cx="25" cy="25" r="20" class="active-path" :style="{'stroke': color}"></circle>
    </svg>
</template>

<script>
	export default {
		props: {
			color: {
				type: String,
				default: 'rgb(32, 160, 255)'
			},
			width: {
				type: Number,
				default: 40
			},
			size: {
				type: Number,
				default: 3
			}
		}
	}
</script>