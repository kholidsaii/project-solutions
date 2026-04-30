import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import Dashboard from '../views/Dashboard.vue'; 

// router/index.ts
const routes = [
  { path: '/login', name: 'Login', component: Login },
  { path: '/', name: 'Dashboard', component: Dashboard, meta: { requiresAuth: true } },
  
  // Menu Operasional
  { path: '/sales', name: 'Sales', component: () => import('../views/Sales.vue'), meta: { requiresAuth: true } },
  
  // Perbaikan: Gunakan satu nama unik untuk list project
  { path: '/projects', name: 'Projects', component: () => import('../views/Projects.vue'), meta: { requiresAuth: true } },
  
  // Route khusus untuk melihat detail satu project secara utuh
  { path: '/projects/:id', name: 'ProjectDetail', component: () => import('../views/ProjectDetail.vue'), meta: { requiresAuth: true } },

  // Menu Lainnya
  { path: '/activity', name: 'Activity', component: () => import('../views/Activity.vue'), meta: { requiresAuth: true } },
  { path: '/financial', name: 'Financial', component: () => import('../views/Financial.vue'), meta: { requiresAuth: true } },
  { path: '/teamwork', name: 'Teamwork', component: () => import('../views/Teamwork.vue'), meta: { requiresAuth: true } },
  { path: '/accounting', name: 'Accounting', component: () => import('../views/Accounting.vue'), meta: { requiresAuth: true } },
  { path: '/documents', name: 'Documents', component: () => import('../views/Documents.vue'), meta: { requiresAuth: true } },
  { path: '/support', name: 'Support', component: () => import('../views/Support.vue'), meta: { requiresAuth: true } },
  { path: '/reports', name: 'Reports', component: () => import('../views/Reports.vue'), meta: { requiresAuth: true } },
  {
      path: '/projects/:id',
      name: 'project-detail',
      component: () => import('../views/ProjectDetail.vue') // Kita buat file baru nanti
    },
  { path: '/settings', name: 'Settings', component: () => import('../views/Settings.vue'), meta: { requiresAuth: true, role: 'super_admin' } },
  { path: '/logs', name: 'AuditLogs', component: () => import('../views/Logs.vue'), meta: { requiresAuth: true, role: 'super_admin' } },
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Penjaga Pintu: Cek token dan Role
router.beforeEach((to, _from, next) => {
  const token = localStorage.getItem('token');
  const userInfo = JSON.parse(localStorage.getItem('user_info') || '{}');
  const userRole = userInfo.role;

  if (to.meta.requiresAuth && !token) {
    next('/login');
  } 
  else if (to.meta.role && to.meta.role !== userRole) {
    alert('Anda tidak memiliki akses ke halaman ini!');
    next('/'); 
  } 
  else {
    next();
  }
});

export default router;