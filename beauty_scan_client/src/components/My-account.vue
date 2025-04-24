<template>
  <div class="register-wrapper">
    <div class="register-form">
      <h2>Мой аккаунт</h2>
      
      <!-- Прелоадер -->
      <div class="loader-container" v-if="loading">
        <div class="loader"></div>
        <p>Загрузка данных...</p>
      </div>
      
      <div v-else>
        <form @submit.prevent="updateAccount">
          <div>
            <label for="username">Имя пользователя:</label>
            <input
                type="text"
                id="username"
                v-model="userData.name"
                required
            />
          </div>
          <div>
            <label for="email">Электронная почта:</label>
            <input
                type="email"
                id="email"
                v-model="userData.email"
                required
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
            <button type="submit" :disabled="saving">
              {{ saving ? 'Сохранение...' : 'Сохранить изменения' }}
            </button>
          </div>
        </form>
        
        <!-- Сообщение об успешном сохранении -->
        <div class="success-message" v-if="showSuccess">
          Данные успешно сохранены!
        </div>
        
        <!-- Кнопка удаления аккаунта -->
        <div class="delete-account-section">
          <h3>Удаление аккаунта</h3>
          <p>Внимание! Это действие нельзя отменить. Все ваши данные будут удалены.</p>
          <button class="delete-btn" @click="showDeleteConfirm = true">Удалить аккаунт</button>
        </div>
        
        <!-- Модальное окно подтверждения удаления -->
        <div class="modal" v-if="showDeleteConfirm">
          <div class="modal-content">
            <h3>Подтверждение удаления</h3>
            <p>Вы уверены, что хотите удалить свой аккаунт? Это действие нельзя отменить.</p>
            <div class="modal-actions">
              <button class="cancel-btn" @click="showDeleteConfirm = false">Отмена</button>
              <button class="confirm-btn" @click="deleteAccount">Удалить</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '@/api'; // Импортируем настроенный экземпляр axios
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';
import { computed, ref, onMounted } from 'vue';

export default {
  setup() {
    const store = useStore();
    const router = useRouter();
    
    const userData = ref({
      name: "",
      email: "",
    });
    const newPassword = ref("");
    const loading = ref(true);
    const saving = ref(false);
    const showSuccess = ref(false);
    const showDeleteConfirm = ref(false);
    
    // Получаем данные пользователя из хранилища
    const userFromStore = computed(() => store.getters.user);
    
    const fetchUserData = async () => {
      loading.value = true;
      try {
        // Используем api вместо axios
        const response = await api.get("/account");
        userData.value = response.data;
        
        // Обновляем данные в хранилище
        store.dispatch('login', response.data);
      } catch (error) {
        console.error("Ошибка при загрузке данных пользователя:", error);
        
        if (!error.response) {
          alert("Ошибка соединения с сервером. Проверьте, запущен ли сервер.");
        } else if (error.response.status === 401) {
          // Если ошибка авторизации, перенаправляем на страницу входа
          router.push('/login');
        } else {
          // Если есть данные в хранилище, используем их
          if (userFromStore.value) {
            userData.value = userFromStore.value;
          }
        }
      } finally {
        loading.value = false;
      }
    };
    
    const updateAccount = async () => {
      saving.value = true;
      showSuccess.value = false;
      
      try {
        const payload = {
          name: userData.value.name,
          email: userData.value.email
        };
        
        // Добавляем пароль только если он был введен
        if (newPassword.value) {
          payload.password = newPassword.value;
        }

        // Используем api вместо axios
        const response = await api.put("/account", payload);

        // Обновляем данные в хранилище
        store.dispatch('login', response.data.user || userData.value);

        // Показываем сообщение об успехе
        showSuccess.value = true;
        
        // Скрываем сообщение через 3 секунды
        setTimeout(() => {
          showSuccess.value = false;
        }, 3000);
        
        // Очищаем поле пароля
        newPassword.value = "";
        
        // Обновляем токен, если он был возвращен
        if (response.data.access_token) {
          localStorage.setItem("auth_token", response.data.access_token);
        }
      } catch (error) {
        console.error("Ошибка при обновлении данных:", error);
        alert("Не удалось обновить данные. Пожалуйста, попробуйте еще раз.");
      } finally {
        saving.value = false;
      }
    };
    
    const deleteAccount = async () => {
      try {
        // Используем api вместо axios
        await api.delete("/account");
        
        // Удаляем токены из localStorage
        localStorage.removeItem("auth_token");
        localStorage.removeItem("refresh_token");
        
        // Очищаем данные пользователя в хранилище
        store.dispatch('logout');
        
        // Перенаправляем на главную страницу регистрации
        router.push("/");
        
        // Закрываем модальное окно
        showDeleteConfirm.value = false;
      } catch (error) {
        console.error("Ошибка при удалении аккаунта:", error);
        alert("Не удалось удалить аккаунт. Пожалуйста, попробуйте еще раз.");
      }
    };
    
    onMounted(() => {
      fetchUserData();
    });
    
    return {
      userData,
      newPassword,
      loading,
      saving,
      showSuccess,
      showDeleteConfirm,
      fetchUserData,
      updateAccount,
      deleteAccount
    };
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

.delete-button {
  margin-top: 10px;
  background-color: #f44336;
}

.delete-button:hover {
  background-color: #d32f2f;
}

.loader-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
}

.loader {
  border: 5px solid #f3f3f3;
  border-top: 5px solid #3498db;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 1s linear infinite;
  margin-bottom: 15px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.success-message {
  margin-top: 15px;
  padding: 10px;
  background-color: #d4edda;
  color: #155724;
  border-radius: 5px;
  text-align: center;
}

.delete-account-section {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 1px solid #eee;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal-content {
  background-color: white;
  padding: 20px;
  border-radius: 5px;
  max-width: 400px;
  width: 100%;
}

.modal-actions {
  margin-top: 20px;
  display: flex;
  justify-content: space-between;
}

.cancel-btn, .confirm-btn {
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 1rem;
}

.cancel-btn {
  background-color: #ccc;
}

.confirm-btn {
  background-color: #f44336;
  color: white;
}
</style>