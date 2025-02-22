import { createRouter, createWebHistory } from 'vue-router';
import RegisterForm from '../components/Register-form.vue';
import LoginForm from '../components/Login-form.vue';
import NotFound from '../components/Not-found.vue';
import MyAccount from '../components/My-account.vue';
import MainPage from '../components/Main-page.vue';
import HomePage from '../components/Home-page.vue';
// Определение маршрутов
const routes = [
    { path: '/register', component: RegisterForm },
    { path: '/login', component: LoginForm },
    { path: '/:pathMatch(.*)*', name: 'NotFound', component: NotFound },
    { path: '/account', component: MyAccount },
    { path: '/main', component: MainPage },
    { path: '/', component: HomePage },
];

// Создание маршрутизатора
const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach((to, from, next) => {
    const isAuthenticated = localStorage.getItem('auth_token');

    if ((to.path === '/login' || to.path === '/register') && isAuthenticated) {
        next('/main');
    } else {
        next();
    }
});


export default router;
