import { createRouter, createWebHistory } from 'vue-router';
import RegisterForm from '../components/Register-form.vue';
import LoginForm from '../components/Login-form.vue';
import NotFound from '../components/Not-found.vue';
import MyAccount from '../components/My-account.vue';
import MainPage from '../components/Main-page.vue';
import HomePage from '../components/Home-page.vue';
import HistoryPage from '../components/History-page.vue';
import HistoryDetails from '../components/History-details.vue';
import AdminIngredients from '../components/Admin-ingredients.vue';

// Определение маршрутов
const routes = [
    { 
        path: '/register', 
        component: RegisterForm,
        meta: { requiresGuest: true }
    },
    { 
        path: '/login', 
        component: LoginForm,
        meta: { requiresGuest: true }
    },
    { 
        path: '/account', 
        component: MyAccount,
        meta: { requiresAuth: true }
    },
    { 
        path: '/main', 
        component: MainPage,
        meta: { requiresAuth: true }
    },
    { 
        path: '/history', 
        component: HistoryPage,
        meta: { requiresAuth: true }
    },
    { 
        path: '/history/:id', 
        component: HistoryDetails,
        meta: { requiresAuth: true }
    },
    { 
        path: '/admin/ingredients.csv',
        component: AdminIngredients,
        meta: { requiresAuth: true, requiresAdmin: true }
    },
    { 
        path: '/', 
        component: HomePage,
        meta: { requiresGuest: true }
    },
    { 
        path: '/:pathMatch(.*)*', 
        name: 'NotFound', 
        component: NotFound 
    },
];

// Создание маршрутизатора
const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach((to, from, next) => {
    const isAuthenticated = localStorage.getItem('auth_token');
    const userRole = localStorage.getItem('user_role');
    const isAdmin = userRole && userRole.includes('ROLE_ADMIN');

    // Если маршрут требует аутентификации и пользователь не авторизован
    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login');
    } 
    // Если маршрут требует прав администратора и у пользователя их нет
    else if (to.meta.requiresAdmin && !isAdmin) {
        next('/main');
    }
    // Если маршрут только для гостей и пользователь авторизован
    else if (to.meta.requiresGuest && isAuthenticated) {
        next('/main');
    } 
    // В остальных случаях разрешаем переход
    else {
        next();
    }
});


export default router;
