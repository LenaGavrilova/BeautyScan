import { createApp } from 'vue'
import App from './App.vue'
import router from './router';
import { store } from './store';
import api from './api';

const app = createApp(App);

// Добавляем Axios как глобальный элемент $http
app.config.globalProperties.$http = api;

app.use(router)
   .use(store)
   .mount('#app');


