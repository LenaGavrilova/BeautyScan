<template>
  <div class="home-container">
    <div class="content-box">
      <h1 class="title">Добро пожаловать!</h1>
      <p class="subtitle">Зарегистрируйтесь или войдите, чтобы продолжить</p>
      <router-link to="/register">
        <button class="btn register-btn">Регистрация</button>
      </router-link>
      <router-link to="/login">
        <button class="btn login-btn">Вход</button>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

onMounted(() => {
  const isAuthenticated = localStorage.getItem('auth_token');
  if (isAuthenticated) {
    router.push('/main');
  }
});
</script>

<style scoped>
.home-container {
  min-height: 90vh;
  display: flex;
  justify-content: center;
  align-items: center;
  font-family: 'Arial', sans-serif;
  background: linear-gradient(135deg, #c6e3ff, #eee1ff);
}

.content-box {
  background: rgb(255, 255, 255);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 40px;
  text-align: center;
  box-shadow: 0 15px 25px rgba(0, 0, 0, 0.2);
  max-width: 300px;

}

.title {
  color: #000000;
  font-size: 32px;
  margin-bottom: 20px;
}

.subtitle {
  color: #000000;
  margin-bottom: 30px;
}

.btn {
  display: block;
  width: 100%;
  padding: 10px;
  margin-bottom: 15px;
  border: none;
  border-radius: 25px;
  font-size: 18px;
  cursor: pointer;
  transition: background 0.3s ease;
}

.register-btn {
  background: #007bff;
  color: white;
}

.register-btn:hover {
  background: #03458b;
}

.login-btn {
  background: #007bff;
  color: white;
}

.login-btn:hover {
  background: #03458b;
}
</style>