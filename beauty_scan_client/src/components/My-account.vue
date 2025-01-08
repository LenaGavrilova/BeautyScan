<template>
  <div class="register-wrapper">
    <div class="register-form">
      <h2>Мой аккаунт</h2>
      <form @submit.prevent="updateAccount">
        <div>
          <label for="username">Имя пользователя:</label>
          <input
              type="text"
              id="username"
              v-model="userData.username"
              required
          />
        </div>
        <div>
          <label for="email">Электронная почта:</label>
          <input
              type="email"
              id="email"
              v-model="userData.email"
          />
        </div>
        <div>
          <label for="newPassword">Новый пароль:</label>
          <input
              type="password"
              id="newPassword"
              v-model="newPassword"
              placeholder="Оставьте пустым, если не хотите менять"
          />
        </div>
        <div>
        <button type="submit">Сохранить изменения</button>
        </div>
      </form>
      <div>
      <button @click="deleteAccount" class="delete-button">Удалить аккаунт</button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  data() {
    return {
      userData: {
        username: "",
        email: "",
      },
      newPassword: "",
    };
  },
  methods: {
    async fetchUserData() {
      try {
        const response = await axios.get("http://localhost:8000/api/account", {
          headers: {
            "Authorization": `Bearer ${localStorage.getItem("auth_token")}`,
          },
        });
        this.userData = response.data;
      } catch (error) {
        console.error(error);
        alert("Не удалось загрузить данные пользователя.");
      }
    },
    async updateAccount() {
      try {
        const payload = {
          ...this.userData,
          newPassword: this.newPassword || undefined,
        };

        const response = await axios.put(
            "http://localhost:8000/api/account",
            payload,
            {
              headers: {
                "Authorization": `Bearer ${localStorage.getItem("auth_token")}`,
                "Content-Type": "application/json"
              },
            }
                    );
        alert("Данные успешно обновлены!");
        const token = response.data.token;
        localStorage.setItem("auth_token", token);
        this.newPassword = "";
      } catch (error) {
        console.error(error);
        alert("Не удалось обновить данные.");
      }
    },
    async deleteAccount() {
      if (confirm("Вы уверены, что хотите удалить аккаунт? Это действие нельзя отменить.")) {
        try {
          await axios.delete("http://localhost:8000/api/account", {
            headers: {
              "Authorization": `Bearer ${localStorage.getItem("auth_token")}`,
            },
          });
          alert("Аккаунт успешно удалён.");
          localStorage.removeItem("auth_token");
          localStorage.setItem("isAuthenticated", "false");
          this.$store.commit("logout");
          this.$router.push("/login");
        } catch (error) {
          console.error(error);
          alert("Не удалось удалить аккаунт.");
        }
      }
    },
  },
  mounted() {
    this.fetchUserData();
  },
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
  margin-top: 150px;
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

.delete-button {
  margin-top: 10px;
  background-color: #f44336;
}

.delete-button:hover {
  background-color: #d32f2f;
}
</style>
