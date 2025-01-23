import { createRouter, createWebHistory } from 'vue-router';
import store from '@/store';
import Login from '@/views/Login.vue';
import Dashboard from '@/views/Dashboard.vue';
import Result from '@/views/Result.vue';
import Admin from '@/views/Admin.vue';
import Register from '@/views/Register.vue';
import Create from '@/views/Create.vue';
import Unassigned from '@/views/Unassigned.vue';

const routes = [
    {
        path: '/',
        name: 'Login',
        component: Login,
        meta: { requiresAuth: false }
    },
    {
        path: '/dashboard',
        name: 'Dashboard',
        component: Dashboard,
        props: true,
        meta: { requiresAuth: true }
    },
    {
        path: '/result',
        name: 'Result',
        component: Result,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin',
        name: 'Admin',
        component: Admin,
        meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
        path: '/register',
        name: 'Register',
        component: Register,
        meta: { requiresAuth: true }
    },
    {
        path: '/create',
        name: 'Create',
        component: Create,
        meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
        path: '/unassigned',
        name: 'Unassigned',
        component: Unassigned,
        meta: { requiresAuth: true }
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/'
    }
];

const router = createRouter({
    history: createWebHistory('/login/'),
    routes
});

router.beforeEach((to, from, next) => {
    const userId = store.getters.userId;
    const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
    const requiresAdmin = to.matched.some(record => record.meta.requiresAdmin);
    const isLoginPage = to.name === 'Login';

    if (!userId && !isLoginPage) {
        // 未ログイン状態でログインページ以外にアクセスした場合
        next({ name: 'Login', query: { redirect: to.fullPath } });
    } else if (requiresAdmin && userId !== 'admin001') {
        // 管理者権限が必要なページに一般ユーザーがアクセスした場合
        next('/dashboard');
    } else if (isLoginPage && userId) {
        // ログイン済みでログインページにアクセスした場合
        next('/dashboard');
    } else {
        next();
    }
});

export default router;
