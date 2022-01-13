<template>
	<ul class="pagination justify-content-center">
		<li :class="{'page-item': true, 'disabled': (current_page == 1)}">
			<a class="page-link" @click="prevPage()"><em class="fa fa-chevron-left"></em></a>
		</li>
		
		<li v-for="(page, index) in list_page" :class="{'page-item': true, 'active': page.num == current_page, 'disabled': !page.num}" :key="index">
			<a class="page-link" v-if="page.num && page.num != current_page" @click="changePage(page.num)">{{ page.text }}</a>
			<span v-else class="page-link">{{ page.text }}</span>
		</li>

		<li :class="{'page-item': true, 'disabled': (current_page == lastPage)}">
			<a class="page-link" @click="nextPage()"><em class="fa fa-chevron-right"></em></a>
		</li>
	</ul>
</template>

<script>
	export default {
		props: {
			page: {
				type: Number,
				default: 1
			},
			lastPage: {
				type: Number,
				default: 1
			},
			isUrl: {
				type: Boolean,
				default: false
			}
		},
		data() {
			return {
				current_page: 1,
				list_page: []
			}
		},
		methods: {
			prevPage() {
				if (this.current_page <= 1) {
					return false;
				}

				this.current_page--;
				this.changePage(this.current_page);
			},
			nextPage() {
				if (this.current_page >= this.lastPage) {
					return false;
				}

				this.current_page++;
				this.changePage(this.current_page);
			},
			changePage(_page) {
				this.current_page = _page;
				this.$emit('change', _page);

				if (this.isUrl) {
					if (_page > 1) {
						this.$router.push(this.$route.path + '?page=' + _page);
					} else {
						this.$router.push(this.$route.path);
					}
				}
			},
			createPage() {
				let _list = [];
				if (this.lastPage <= 5) {
					for (let i = 1; i <= this.lastPage; i++) {
						_list.push({
							text: i,
							num: i
						});
					}
				} else {
					_list.push({text: 1, num: 1});

					if (this.current_page > 3) {
						_list.push({text: '...', num: null});
					}

					if (this.current_page - 1 > 1) {
						_list.push({text: this.current_page - 1, num: this.current_page - 1});
					}

					if (this.current_page > 1 && this.current_page < this.lastPage) {
						_list.push({text: this.current_page, num: this.current_page});
					}

					if (this.current_page + 1 < this.lastPage) {
						_list.push({text: this.current_page + 1, num: this.current_page + 1});
					}

					if (this.current_page < this.lastPage - 2) {
						_list.push({text: '...', num: null});
					}

					_list.push({text: this.lastPage, num: this.lastPage});
				}
				
				this.list_page = _list;
			}
		},
		created() {
			if (this.page) {
				this.current_page = parseInt(this.page);
			}
			
			if (this.isUrl) {
				if (this.$route.query.page) {
					this.current_page = parseInt(this.$route.query.page);
				}
			}

			this.createPage();
		},
		watch: {
			current_page: function(newPage, oldPage) {
				this.createPage();
			}
		}
	}
</script>