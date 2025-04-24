<template>
  <div class="register-wrapper">
    <div class="register-form">
      <h2>Регистрация</h2>
      <form @submit.prevent="submitForm">
        <div>
          <label for="username">Имя пользователя:</label>
          <input
              type="text"
              id="username"
              v-model="form.username"
              @blur="validateUsername"
              :class="{ 'is-invalid': errors.username }"
              required
          />
          <span v-if="errors.username" class="error-message">{{ errors.username }}</span>
        </div>

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

        <div>
          <label for="confirmPassword">Повторите пароль:</label>
          <input
              type="password"
              id="confirmPassword"
              v-model="form.confirmPassword"
              @blur="validateConfirmPassword"
              :class="{ 'is-invalid': errors.confirmPassword }"
              required
          />
          <span v-if="errors.confirmPassword" class="error-message">{{ errors.confirmPassword }}</span>
        </div>

        <button type="submit" :disabled="hasErrors">Зарегистрироваться</button>
      </form>

      <div class="login-link">
        <span>Уже есть аккаунт?</span>
        <router-link to="/login" class="login-button">Войти</router-link>
      </div>

      <p v-if="message">{{ message }}</p>
    </div>
  </div>
</template>

<script>
import { useRouter } from 'vue-router';
import { useStore } from 'vuex';
import api from '../api';

export default {
  name: 'RegisterForm',
  setup() {
    const router = useRouter();
    const store = useStore();
    return { router, store };
  },
  data() {
    return {
      form: {
        username: '',
        email: '',
        password: '',
        confirmPassword: ''
      },
      errors: {
        username: '',
        email: '',
        password: '',
        confirmPassword: ''
      },
      message: ''
    };
  },
  computed: {
    hasErrors() {
      return !!this.errors.username || !!this.errors.email || !!this.errors.password || !!this.errors.confirmPassword;
    }
  },
  methods: {
    validateUsername() {
      if (this.form.username.length < 3) {
        this.errors.username = "Имя пользователя должно быть не менее 3 символов.";
      } else {
        this.errors.username = null;
      }
    },
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
    validateConfirmPassword() {
      if (this.form.password !== this.form.confirmPassword) {
        this.errors.confirmPassword = "Пароли не совпадают.";
      } else {
        this.errors.confirmPassword = null;
      }
    },
    validateForm() {
      this.validateUsername();
      this.validateEmail();
      this.validatePassword();
      this.validateConfirmPassword();
      return !this.hasErrors;
    },
    async submitForm() {
      // Проверка формы
      if (!this.validateForm()) {
        this.message = "Пожалуйста, исправьте ошибки в форме";
        return;
      }

      try {
        // Отправляем запрос на регистрацию
        const response = await api.post("/register", this.form, {
          headers: {
            "Content-Type": "application/json"
          }
        });

        // Если регистрация успешна, пытаемся выполнить вход
        if (response.status === 201) {
          this.message = "Регистрация успешна! Выполняем вход...";
          
          try {
            // Выполняем вход с данными, которые использовались при регистрации
            const loginResponse = await api.post("/login", {
              email: this.form.email,
              password: this.form.password
            }, {
              headers: {
                "Content-Type": "application/json"
              }
            });

            // Получаем токены и данные пользователя из ответа
            const { access_token, refresh_token, user } = loginResponse.data;

            // Сохраняем токены в localStorage
            localStorage.setItem("auth_token", access_token);
            localStorage.setItem("refresh_token", refresh_token);
            
            // Обновляем данные пользователя в хранилище
            await this.store.dispatch('login', user);

            this.message = "Вход выполнен успешно";
            setTimeout(() => {
              this.router.push("/main");
            }, 1000);
          } catch (loginError) {
            console.error("Ошибка при входе после регистрации:", loginError);
            this.message = "Регистрация успешна, но возникла ошибка при входе. Перенаправление на страницу входа...";
            setTimeout(() => {
              this.router.push("/login");
            }, 2000);
          }
        }
      } catch (error) {
        console.error("Ошибка при регистрации:", error);
        
        if (!error.response) {
          this.message = "Ошибка соединения с сервером. Проверьте, запущен ли сервер.";
        } else if (error.response.status === 409) {
          this.message = "Пользователь с таким email уже зарегистрирован";
        } else if (error.response.status === 400) {
          this.message = error.response.data.message || "Ошибка валидации данных";
        } else {
          this.message = error.response?.data?.message || "Ошибка при регистрации";
        }
      }
    }
  }
};
</script>

<style scoped>
html, body {
  overflow: visible;
  margin: 0;
  padding: 0;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  font-family: 'Arial', sans-serif;
  color: #333;
}

.register-wrapper {
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 50px;
}

.register-form {
  max-width: 400px;
  width: 100%;
  padding: 20px;
  border: 1px solid #ccc;
  border-radius: 10px;
  background-color: rgba(255, 255, 255, 0.9);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
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

.login-link {
  margin-top: 20px;
  text-align: center;
}

.login-link span {
  margin-right: 10px;
  font-size: 1rem;
  color: #333;
}

.login-link .login-button {
  font-size: 1rem;
  color: #007bff;
  text-decoration: none;
  font-weight: bold;
  cursor: pointer;
  transition: color 0.3s;
}

.login-link .login-button:hover {
  color: #0056b3;
}
</style>