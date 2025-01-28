import { createRouter, createWebHistory } from 'vue-router';
import RegisterForm from '../components/Register-form.vue';
import LoginForm from '../components/Login-form.vue';
import NotFound from '../components/Not-found.vue';
import MyAccount from '../components/My-account.vue';
import MainPage from '../components/Main-page.vue';
// Определение маршрутов
const routes = [
    { path: '/register', component: RegisterForm },
    { path: '/login', component: LoginForm },
    { path: '/:pathMatch(.*)*', name: 'NotFound', component: NotFound },
    { path: '/account', component: MyAccount },
    { path: '/', component: MainPage },
];

// Создание маршрутизатора
const router = createRouter({
    history: createWebHistory(),
    routes
});

export default router;
