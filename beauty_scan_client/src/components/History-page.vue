<template>
  <div class="history-container">
    <h1>История анализов</h1>
    
    <!-- Фильтры -->
    <div class="filters">
      <div class="filter-item">
        <label for="date-filter">Фильтр по дате:</label>
        <input 
          type="date" 
          id="date-filter" 
          v-model="filters.date"
          @change="applyFilters"
        >
      </div>
      
      <div class="filter-item">
        <label for="ingredient-filter">Фильтр по ингредиенту:</label>
        <div class="input-with-button">
          <input 
            type="text" 
            id="ingredient-filter" 
            v-model="filters.ingredient"
            placeholder="Введите название ингредиента"
            @keyup.enter="applyFilters"
          >
          <button class="search-btn" @click="applyFilters">Поиск</button>
        </div>
      </div>
      
      <button class="reset-btn" @click="resetFilters">Сбросить фильтры</button>
    </div>
    
    <!-- Прелоадер -->
    <div class="loader-container" v-if="loading">
      <div class="loader"></div>
      <p>Загрузка истории...</p>
    </div>
    
    <!-- Список истории -->
    <div v-else-if="historyItems.length > 0" class="history-list">
      <div 
        v-for="item in historyItems" 
        :key="item.id" 
        class="history-item"
      >
        <div class="history-item-header">
          <span class="date">{{ formatDate(item.created_at) }}</span>
          <div class="actions">
            <button class="edit-btn" @click="editItem(item)">
              Редактировать
            </button>
            <button class="delete-btn" @click="deleteItem(item.id)">
              Удалить
            </button>
          </div>
        </div>
        <div class="history-item-content" @click="viewDetails(item.id)">
          <p class="query-type"><strong>Тип запроса:</strong> {{ item.query_type === 'text' ? 'Текстовый запрос' : 'Фото запрос' }}</p>
          <p class="query-content"><strong>Содержание:</strong> {{ truncateText(item.query_content, 100) }}</p>
          <div class="safety-rating">
            <strong>Оценка безопасности:</strong> {{ item.result.safety_rating.toFixed(1) }}
          </div>
        </div>
      </div>
      
      <!-- Пагинация -->
      <div class="pagination" v-if="totalPages > 1">
        <button 
          :disabled="currentPage === 1" 
          @click="changePage(currentPage - 1)"
          class="pagination-btn"
        >
          Предыдущая
        </button>
        
        <span>Страница {{ currentPage }} из {{ totalPages }}</span>
        
        <button 
          :disabled="currentPage === totalPages" 
          @click="changePage(currentPage + 1)"
          class="pagination-btn"
        >
          Следующая
        </button>
      </div>
    </div>
    
    <!-- Пустая история -->
    <div v-else class="empty-history">
      <p>У вас пока нет истории запросов.</p>
      <button class="new-analysis-btn" @click="goToAnalysis">Создать новый анализ</button>
    </div>
    
    <!-- Модальное окно подтверждения удаления -->
    <div class="modal" v-if="showDeleteModal">
      <div class="modal-content">
        <h3>Подтверждение удаления</h3>
        <p>Вы уверены, что хотите удалить этот запрос из истории?</p>
        <div class="modal-actions">
          <button class="cancel-btn" @click="showDeleteModal = false">Отмена</button>
          <button class="confirm-btn" @click="confirmDelete">Удалить</button>
        </div>
      </div>
    </div>
    
    <!-- Модальное окно редактирования -->
    <div class="modal" v-if="showEditModal">
      <div class="modal-content">
        <h3>Редактирование записи</h3>
        <div class="form-group">
          <label for="edit-type">Тип запроса:</label>
          <select id="edit-type" v-model="editingItem.query_type">
            <option value="text">Текстовый запрос</option>
            <option value="photo">Фото запрос</option>
          </select>
        </div>
        <div class="form-group">
          <label for="edit-content">Содержание запроса:</label>
          <textarea 
            id="edit-content" 
            v-model="editingItem.query_content" 
            rows="4"
          ></textarea>
        </div>
        <div class="modal-actions">
          <button class="cancel-btn" @click="showEditModal = false">Отмена</button>
          <button class="save-btn" @click="saveEdit">Сохранить</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'HistoryPage',
  data() {
    return {
      historyItems: [],
      loading: true,
      currentPage: 1,
      totalPages: 0,
      filters: {
        date: '',
        ingredient: ''
      },
      showDeleteModal: false,
      showEditModal: false,
      deleteId: null,
      editingItem: {
        id: null,
        query_type: '',
        query_content: ''
      },
      searchTimeout: null
    };
  },
  methods: {
    async fetchHistory() {
      this.loading = true;
      try {
        const params = {
          page: this.currentPage,
          limit: 10
        };
        
        if (this.filters.date) {
          params.date = this.filters.date;
        }
        
        if (this.filters.ingredient && this.filters.ingredient.trim()) {
          params.ingredient = this.filters.ingredient.trim();
        }
        
        console.log('Отправляемые параметры фильтрации:', params);
        
        const response = await this.$http.get('/history', { params });
        console.log('Полученный ответ:', response.data);
        
        this.historyItems = response.data.items || [];
        this.totalPages = response.data.totalPages || 0;
      } catch (error) {
        console.error('Ошибка при получении истории:', error);
        alert('Ошибка при получении истории. Попробуйте позже.');
      } finally {
        this.loading = false;
      }
    },
    
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },
    
    truncateText(text, maxLength) {
      if (!text) return '';
      if (text.length <= maxLength) return text;
      return text.substring(0, maxLength) + '...';
    },
    
    viewDetails(id) {
      this.$router.push(`/history/${id}`);
    },
    
    goToAnalysis() {
      this.$router.push('/main');
    },
    
    changePage(page) {
      this.currentPage = page;
      this.fetchHistory();
    },
    
    applyFilters() {
      this.currentPage = 1;
      this.fetchHistory();
    },
    
    resetFilters() {
      this.filters = {
        date: '',
        ingredient: ''
      };
      this.currentPage = 1;
      this.fetchHistory();
    },
    
    editItem(item) {
      this.editingItem = {
        id: item.id,
        query_type: item.query_type,
        query_content: item.query_content
      };
      this.showEditModal = true;
    },
    
    async saveEdit() {
      try {
        await this.$http.put(`/history/${this.editingItem.id}`, {
          query_type: this.editingItem.query_type,
          query_content: this.editingItem.query_content
        });
        
        this.showEditModal = false;
        this.fetchHistory();
        alert('Запись успешно обновлена!');
      } catch (error) {
        console.error('Ошибка при обновлении записи:', error);
        alert('Ошибка при обновлении записи. Попробуйте позже.');
      }
    },
    
    deleteItem(id) {
      this.deleteId = id;
      this.showDeleteModal = true;
    },
    
    async confirmDelete() {
      try {
        await this.$http.delete(`/history/${this.deleteId}`);
        this.showDeleteModal = false;
        this.fetchHistory();
        alert('Запись успешно удалена!');
      } catch (error) {
        console.error('Ошибка при удалении записи:', error);
        alert('Ошибка при удалении записи. Попробуйте позже.');
      }
    },
    
    debouncedSearch() {
      // Отменяем предыдущий таймер, если он есть
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout);
      }
      
      // Устанавливаем новый таймер на 500 мс
      this.searchTimeout = setTimeout(() => {
        this.applyFilters();
      }, 500);
    }
  },
  created() {
    this.fetchHistory();
  }
};
</script>

<style scoped>
.history-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
}

h1 {
  text-align: center;
  margin-bottom: 30px;
}

.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-bottom: 20px;
  padding: 15px;
  background-color: #f7f7f7;
  border-radius: 8px;
}

.filter-item {
  flex: 1;
  padding: 5px;
  min-width: 200px;
}

label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
}

input, select, textarea {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.reset-btn {
  padding: 8px 15px;
  background-color: #f0f0f0;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 22px;
  height: 36px;
}

.loader-container {
  text-align: center;
  padding: 50px 0;
}

.loader {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.history-item {
  background-color: white;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 15px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.history-item-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
  border-bottom: 1px solid #eee;
  padding-bottom: 10px;
}

.history-item-content {
  cursor: pointer;
}

.actions {
  display: flex;
  gap: 10px;
}

.edit-btn, .delete-btn {
  padding: 5px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
}

.edit-btn {
  background-color: #3498db;
  color: white;
  border: none;
}

.delete-btn {
  background-color: #e74c3c;
  color: white;
  border: none;
}

.query-type, .query-content, .safety-rating {
  margin-bottom: 8px;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  margin-top: 30px;
}

.pagination-btn {
  padding: 8px 15px;
  background-color: #3498db;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.pagination-btn:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.empty-history {
  text-align: center;
  padding: 50px 0;
}

.new-analysis-btn {
  padding: 10px 20px;
  background-color: #3498db;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 20px;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  padding: 20px;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
}

.form-group {
  margin-bottom: 15px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.cancel-btn, .save-btn, .confirm-btn {
  padding: 8px 15px;
  border-radius: 4px;
  cursor: pointer;
}

.cancel-btn {
  background-color: #f0f0f0;
  border: 1px solid #ddd;
}

.save-btn {
  background-color: #2ecc71;
  color: white;
  border: none;
}

.confirm-btn {
  background-color: #e74c3c;
  color: white;
  border: none;
}

.input-with-button {
  display: flex;
}

.input-with-button input {
  flex: 1;
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

.search-btn {
  background-color: #3498db;
  color: white;
  border: none;
  padding: 0 15px;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
  cursor: pointer;
}
</style> 