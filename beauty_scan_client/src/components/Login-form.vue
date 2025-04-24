<template>
  <div class="login-form">
    <h2>Вход</h2>
    <form @submit.prevent="submitForm">
      <div>
        <label for="email">Email:</label>
        <input
            type="email"
            id="email"
            v-model="form.email"
            @blur="validateEmail"
            :class="{ 'is-invalid': errors.email }"
            required
        />
        <span v-if="errors.email" class="error-message">{{ errors.email }}</span>
      </div>

      <div>
        <label for="password">Пароль:</label>
        <input
            type="password"
            id="password"
            v-model="form.password"
            @blur="validatePassword"
            :class="{ 'is-invalid': errors.password }"
            required
        />
        <span v-if="errors.password" class="error-message">{{ errors.password }}</span>
      </div>

      <button type="submit" :disabled="hasErrors">Войти</button>
    </form>

    <p v-if="message">{{ message }}</p>
  </div>
</template>

<script>
import api from '@/api'; // Импортируем настроенный экземпляр axios
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';

export default {
  setup() {
    const store = useStore();
    const router = useRouter();
    
    return { store, router };
  },
  data() {
    return {
      form: {
        email: "",
        password: ""
      },
      errors: {
        email: null,
        password: null
      },
      message: ""
    };
  },
  computed: {
    hasErrors() {
      return !!this.errors.email || !!this.errors.password;
    }
  },
  methods: {
    validateEmail() {
      const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
      if (!emailPattern.test(this.form.email)) {
        this.errors.email = "Введите корректный email.";
      } else {
        this.errors.email = null;
      }
    },
    validatePassword() {
      if (this.form.password.length < 6) {
        this.errors.password = "Пароль должен быть не менее 6 символов.";
      } else {
        this.errors.password = null;
      }
    },
    async submitForm() {
      this.validateEmail();
      this.validatePassword();

      if (this.hasErrors) {
        this.message = "Пожалуйста, исправьте ошибки в форме.";
        return;
      }

      try {
        // Используем api вместо axios
        const response = await api.post("/login", this.form, {
          headers: {
            "Content-Type": "application/json"
          }
        });

        const { access_token, refresh_token, user } = response.data;

        // Сохраняем токены в localStorage
        localStorage.setItem("auth_token", access_token);
        localStorage.setItem("refresh_token", refresh_token);
        
        // Обновляем данные пользователя в хранилище
        await this.store.dispatch('login', user);

        this.message = "Вход выполнен успешно";
        setTimeout(() => {
          this.router.push("/main");
        }, 1000);
      } catch (error) {
        console.error("Ошибка при входе:", error);
        if (!error.response) {
          this.message = "Ошибка соединения с сервером. Проверьте, запущен ли сервер.";
        } else {
          this.message = error.response?.data?.message || "Ошибка при входе в систему";
        }
      }
    }
  }
};
</script>

<style scoped>
.login-form {
  max-width: 400px;
  margin: 0 auto;
  padding: 20px;
  border: 1px solid #ccc;
  border-radius: 10px;
  background-color: #fff;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
  margin-top: 50px;
}
label {
  display: block;
  margin-bottom: 5px;
  font-size: 1rem;
}
input {
  width: 90%;
  padding: 10px;
  margin-bottom: 15px;
  border: 1px solid #ccc;
  border-radius: 5px;
  font-size: 1rem;
}
button {
  width: 100%;
  padding: 12px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 1.1rem;
  transition: background-color 0.3s;
}
button:hover {
  background-color: #0056b3;
}
button:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}
.is-invalid {
  border-color: red;
}
.error-message {
  color: red;
  font-size: 0.9rem;
  margin-top: -10px;
  margin-bottom: 10px;
}
</style>