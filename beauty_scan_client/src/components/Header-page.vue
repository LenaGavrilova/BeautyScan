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
        <li v-if="isAuthenticated"><router-link to="/">Главная</router-link></li>
        <li v-if="isAuthenticated"><router-link to="/history">История поиска</router-link></li>
        <li v-if="isAuthenticated"><router-link to="/account">Мой аккаунт</router-link></li>
        <li v-if="isAuthenticated"><button @click="logout">Выйти</button></li>
      </ul>
    </nav>
  </header>
</template>

<script>
export default {
  computed: {
    isAuthenticated() {
      return this.$store.getters.isAuthenticated;
    },
  },
  methods: {
    logout() {
      this.$store.dispatch('logout');
      this.$router.push('/login');
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
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
}



.logo {
  display: flex;
  align-items: center;
}

.logo img {
  width: 40px;
  margin-right: 10px;
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
  color: #007bff;
  font-size: 1rem;
}

button {
  background: none;
  border: none;
  color: #007bff;
  cursor: pointer;
  font-size: 1rem;
}
</style>
