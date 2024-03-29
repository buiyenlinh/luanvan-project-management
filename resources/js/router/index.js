import { toPairs } from 'lodash';
import Vue from 'vue';
import VueRouter from 'vue-router';
Vue.use(VueRouter);

const router = new VueRouter({
  mode:'history',
  routes: [ 
    {
      path: '/',
      component: require('../views/Home.vue').default,
      meta: {
        auth: true,
        title: 'Trang chủ'
      },
      children: [
        {
          path: '/',
          name: 'dashboard',
          component: require('../views/DashBoard.vue').default,
          meta: {
            auth: true,
            title: 'Thống kê'
          }
        },
        {
          path: 'nguoi-dung',
          name: 'user',
          component: require('../views/User.vue').default,
          meta: {
            auth: true,
            title: 'Người dùng'
          }
        },
        {
          path: 'du-an',
          name: 'project',
          component: require('../views/Project.vue').default,
          meta: {
            auth: true,
            title: 'Dự án'
          },
        },
        {
          path: 'du-an/:id',
          name: 'task',
          component: require('../views/Task.vue').default,
          meta: {
            auth: true,
            title: 'Công việc'
          },
        },
        {
          path: 'du-an/:project_id/cong-viec/:id',
          name: 'job',
          component: require('../views/Job.vue').default,
          meta: {
            auth: true,
            title: 'Nhiệm vụ'
          },
        },
        {
          path: 'du-an/:project_id/cong-viec/:task_id/nhiem-vu/:job_id/lich-su',
          name: 'job_history',
          component: require('../views/History.vue').default,
          meta: {
            auth: true,
            title: 'Lịch sử nhiệm vụ'
          },
        },
        {
          path: 'du-an/:project_id/cong-viec/:task_id/lich-su',
          name: 'task_history',
          component: require('../views/History.vue').default,
          meta: {
            auth: true,
            title: 'Lịch sử công việc'
          },
        },
        {
          path: 'du-an/:project_id/lich-su',
          name: 'project_history',
          component: require('../views/History.vue').default,
          meta: {
            auth: true,
            title: 'Lịch sử dự án'
          },
        },
        {
          path: 'phong-ban',
          name: 'department',
          component: require('../views/Department.vue').default,
          meta: {
            auth: true,
            title: 'Nhóm làm việc'
          },
        },
        {
          path: 'phong-ban/:department_id',
          name: 'department-detail',
          component: require('../views/DepartmentDetail.vue').default,
          meta: {
            auth: true,
            title: 'Chi tiết nhóm làm việc'
          },
        },
        {
          path: 'ca-nhan',
          name: 'profile',
          component: require('../views/Profile.vue').default,
          meta: {
            auth: true,
            title: 'Trang cá nhân'
          }
        },
        {
          path: 'nhan',
          name: 'label',
          component: require('../views/Label.vue').default,
          meta: {
            auth: true,
            title: 'Nhãn'
          },
        },
        {
          path: 'danh-sach/:type/:status',
          name: 'deadline',
          component: require('../views/Deadline.vue').default,
          meta: {
            auth: true,
            title: 'Danh sách'
          },
        },
        {
          path: 'tien-do/:name/:id',
          name: 'chart',
          component: require('../views/Chart.vue').default,
          meta: {
            auth: true,
            title: 'Biểu đồ'
          },
        },
        {
          path: 'chat',
          name: 'chat',
          component: require('../views/Chat.vue').default,
          meta: {
            auth: true,
            title: 'Chat'
          },
        },
      ]
    },
    {
      path: '/dang-nhap',
      name: 'login',
      component: require('../views/Login.vue').default,
      meta: {
        guest: true,
        title: 'Đăng nhập'
      }
    },
    {
      path: '/quen-mat-khau',
      name: 'forgetpass',
      component: require('../views/ForgetPassword.vue').default,
      meta: {
        guest: true,
        title: 'Quên mật khẩu'
      }
    },
    {
      path: '*',
      name: 'page_404',
      component: require('../views/Page404.vue').default,
      meta: {
        title: 'Trang không tồn tại'
      }
    }
  ]
})

router.beforeEach((to, from, next) => {
  let token = localStorage.getItem('yl_token');
  if (to.meta) {
    if (to.meta.auth && !token) {
      next({name: 'login'});
    } else if (to.meta.guest && token) {
      next({name: 'dashboard'});
    } else {
      next();
    }
  } else {
    next();
  }
})

export default router;