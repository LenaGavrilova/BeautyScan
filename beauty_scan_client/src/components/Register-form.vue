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
import axios from "axios";

export default {
  data() {
    return {
      form: {
        username: "",
        email: "",
        password: ""
      },
      errors: {
        username: null,
        email: null,
        password: null
      },
      message: ""
    };
  },
  computed: {
    hasErrors() {
      return !!this.errors.username || !!this.errors.email || !!this.errors.password;
    }
  },
  methods: {
    validateUsername() {
      if (this.form.username.length < 2) {
        this.errors.username = "Имя пользователя должно содержать не менее 2 символов.";
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
      const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
      if (!passwordPattern.test(this.form.password)) {
        this.errors.password =
            "Пароль должен содержать не менее 8 символов, включая заглавные и строчные буквы, а также хотя бы одну цифру.";
      } else {
        this.errors.password = null;
      }
    },
    async submitForm() {
      this.validateUsername();
      this.validateEmail();
      this.validatePassword();

      if (this.hasErrors) {
        this.message = "Пожалуйста, исправьте ошибки в форме.";
        return;
      }

      try {
        const response = await axios.post("http://localhost:8000/api/register", this.form, {
          headers: {
            "Content-Type": "application/json"
          }
        });
        const token = response.data.token;
        localStorage.setItem("auth_token", token);
        this.message = response.data.message;
        if (this.message === 'Регистрация прошла успешно') {
          setTimeout(() => {
            this.$router.push("/login");
          }, 1000);
        }
      } catch (error) {
        this.message = error.response?.data?.message || "Ошибка при соединении";
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


