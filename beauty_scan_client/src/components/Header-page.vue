<template>
  <header>
    <div class="logo">
      <img src="@/assets/BeautyScan.webp" alt="Logo">
      <span>BeautyScan</span>
    </div>
    <nav>
      <ul>
        <li v-if="!isAuthenticated"><router-link to="/login">Войти</router-link></li>
        <li v-if="!isAuthenticated"><router-link to="/register">Зарегистрироваться</router-link></li>
        <li v-if="isAuthenticated"><router-link to="/main">Анализ состава</router-link></li>
        <li v-if="isAuthenticated"><router-link to="/history">История анализов</router-link></li>
        <li v-if="isAuthenticated"><router-link to="/account">Мой аккаунт</router-link></li>
        <li v-if="isAuthenticated"><button @click="logout">Выйти</button></li>
      </ul>
    </nav>
  </header>
</template>

<script>
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';

export default {
  setup() {
    const store = useStore();
    const router = useRouter();
    
    return { store, router };
  },
  computed: {
    isAuthenticated() {
      // Проверяем наличие токена в localStorage и состояние в хранилище
      return !!localStorage.getItem('auth_token') || this.store.getters.isAuthenticated;
    },
  },
  methods: {
    logout() {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('refresh_token');
      this.store.dispatch('logout');
      this.router.push('/login');
    },
  },
};
</script>

<style scoped>
header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  background-color: rgba(213, 212, 212, 0.09);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.logo {
  display: flex;
  align-items: center;
}

.logo img {
  width: 40px;
  margin-right: 10px;
}

.logo span {
  font-size: 1.5rem;
  font-weight: bold;
  color: #333;
}

nav ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
}

nav ul li {
  margin-left: 20px;
}

nav ul li a {
  text-decoration: none;
  color: #3498db;
  font-size: 1rem;
  transition: color 0.2s;
}

nav ul li a:hover {
  color: #2980b9;
}

nav ul li a.router-link-active {
  color: #2c3e50;
  font-weight: 500;
}

button {
  background: none;
  border: none;
  color: #e74c3c;
  cursor: pointer;
  font-size: 1rem;
  transition: color 0.2s;
}

button:hover {
  color: #c0392b;
}

@media (max-width: 768px) {
  header {
    flex-direction: column;
    padding: 15px;
  }
  
  .logo {
    margin-bottom: 15px;
  }
  
  nav ul {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  nav ul li {
    margin: 5px 10px;
  }
}
</style>
