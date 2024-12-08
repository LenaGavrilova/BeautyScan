import { createRouter, createWebHistory } from 'vue-router';
import RegisterForm from '../components/Register-form.vue';
import LoginForm from '../components/Login-form.vue';

// Определение маршрутов
const routes = [
    { path: '/register', component: RegisterForm },
    { path: '/login', component: LoginForm }
];

// Создание маршрутизатора
const router = createRouter({
    history: createWebHistory(),
    routes
});

export default router;
