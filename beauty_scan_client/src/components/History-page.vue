<template>
  <div class="history-container">
    <h1>История запросов</h1>
    
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
        <input 
          type="text" 
          id="ingredient-filter" 
          v-model="filters.ingredient"
          @input="applyFilters"
          placeholder="Введите название ингредиента"
        >
      </div>
      
      <button class="reset-btn" @click="resetFilters">Сбросить фильтры</button>
    </div>
    
    <!-- Прелоадер -->
    <div class="loader-container" v-if="loading">
      <div class="loader"></div>
      <p>Загрузка истории...</p>
    </div>
    
    <!-- Список истории -->
    <div v-else-if="history.items && history.items.length > 0" class="history-list">
      <div 
        v-for="item in history.items" 
        :key="item.id" 
        class="history-item"
        @click="viewHistoryDetails(item.id)"
      >
        <div class="history-item-header">
          <span class="date">{{ formatDate(item.created_at) }}</span>
          <div class="actions">
            <button class="delete-btn" @click.stop="deleteHistoryItem(item.id)">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
        <div class="history-item-content">
          <p class="query-type">{{ item.query_type === 'text' ? 'Текстовый запрос' : 'Фото запрос' }}</p>
          <p class="query-content">{{ truncateText(item.query_content, 100) }}</p>
          <div class="safety-rating">
            <span>Оценка безопасности: </span>
            <div class="rating-stars">
              <i 
                v-for="n in 5" 
                :key="n" 
                :class="['fas', 'fa-star', n <= Math.round(item.result.safety_rating) ? 'filled' : '']"
              ></i>
            </div>
            <span class="rating-value">{{ item.result.safety_rating.toFixed(1) }}</span>
          </div>
        </div>
      </div>
      
      <!-- Пагинация -->
      <div class="pagination" v-if="history.totalPages > 1">
        <button 
          :disabled="currentPage === 1" 
          @click="changePage(currentPage - 1)"
          class="pagination-btn"
        >
          <i class="fas fa-chevron-left"></i>
        </button>
        
        <button 
          v-for="page in paginationPages" 
          :key="page" 
          :class="['pagination-btn', page === currentPage ? 'active' : '']"
          @click="changePage(page)"
        >
          {{ page }}
        </button>
        
        <button 
          :disabled="currentPage === history.totalPages" 
          @click="changePage(currentPage + 1)"
          class="pagination-btn"
        >
          <i class="fas fa-chevron-right"></i>
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
  </div>
</template>

<script>
import api from '../api';
import { useRouter } from 'vue-router';
import { ref, computed, onMounted } from 'vue';

export default {
  name: 'HistoryPage',
  
  setup() {
    const router = useRouter();
    const history = ref({ items: [], total: 0, page: 1, limit: 10, totalPages: 0 });
    const loading = ref(true);
    const currentPage = ref(1);
    const filters = ref({
      date: '',
      ingredient: ''
    });
    const showDeleteModal = ref(false);
    const itemToDelete = ref(null);
    
    // Получение истории запросов
    const fetchHistory = async () => {
      loading.value = true;
      try {
        const params = {
          page: currentPage.value,
          limit: 10
        };
        
        // Добавляем фильтры, если они заданы
        if (filters.value.date) {
          params.date = filters.value.date;
        }
        
        if (filters.value.ingredient) {
          params.ingredient = filters.value.ingredient;
        }
        
        const response = await api.get('/history', { params });
        history.value = response.data;
      } catch (error) {
        console.error('Ошибка при получении истории:', error);
      } finally {
        loading.value = false;
      }
    };
    
    // Форматирование даты
    const formatDate = (dateString) => {
      const date = new Date(dateString);
      return date.toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    };
    
    // Обрезка длинного текста
    const truncateText = (text, maxLength) => {
      if (text.length <= maxLength) return text;
      return text.substring(0, maxLength) + '...';
    };
    
    // Переход на страницу с деталями запроса
    const viewHistoryDetails = (id) => {
      router.push(`/history/${id}`);
    };
    
    // Переход на страницу анализа
    const goToAnalysis = () => {
      router.push('/main');
    };
    
    // Изменение страницы пагинации
    const changePage = (page) => {
      currentPage.value = page;
      fetchHistory();
    };
    
    // Применение фильтров
    const applyFilters = () => {
      currentPage.value = 1; // Сбрасываем на первую страницу при применении фильтров
      fetchHistory();
    };
    
    // Сброс фильтров
    const resetFilters = () => {
      filters.value = {
        date: '',
        ingredient: ''
      };
      currentPage.value = 1;
      fetchHistory();
    };
    
    // Удаление записи из истории
    const deleteHistoryItem = (id) => {
      itemToDelete.value = id;
      showDeleteModal.value = true;
    };
    
    // Подтверждение удаления
    const confirmDelete = async () => {
      try {
        await api.delete(`/history/${itemToDelete.value}`);
        fetchHistory(); // Обновляем список после удаления
        showDeleteModal.value = false;
      } catch (error) {
        console.error('Ошибка при удалении записи:', error);
      }
    };
    
    // Вычисляемое свойство для отображения страниц пагинации
    const paginationPages = computed(() => {
      const totalPages = history.value.totalPages;
      const currentPageVal = currentPage.value;
      
      if (totalPages <= 5) {
        return Array.from({ length: totalPages }, (_, i) => i + 1);
      }
      
      if (currentPageVal <= 3) {
        return [1, 2, 3, 4, 5];
      }
      
      if (currentPageVal >= totalPages - 2) {
        return [totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
      }
      
      return [currentPageVal - 2, currentPageVal - 1, currentPageVal, currentPageVal + 1, currentPageVal + 2];
    });
    
    // Загрузка истории при монтировании компонента
    onMounted(() => {
      fetchHistory();
    });
    
    return {
      history,
      loading,
      currentPage,
      filters,
      showDeleteModal,
      paginationPages,
      formatDate,
      truncateText,
      viewHistoryDetails,
      goToAnalysis,
      changePage,
      applyFilters,
      resetFilters,
      deleteHistoryItem,
      confirmDelete
    };
  }
};
</script>

<style scoped>
.history-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

h1 {
  text-align: center;
  margin-bottom: 30px;
  color: #333;
}

.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-bottom: 20px;
  padding: 15px;
  background-color: #f5f5f5;
  border-radius: 8px;
}

.filter-item {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 200px;
}

.filter-item label {
  margin-bottom: 5px;
  font-weight: 500;
}

.filter-item input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.reset-btn {
  align-self: flex-end;
  padding: 8px 15px;
  background-color: #f0f0f0;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.reset-btn:hover {
  background-color: #e0e0e0;
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

.history-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.history-item {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  padding: 15px;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.history-item:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.history-item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.date {
  font-size: 0.9rem;
  color: #666;
}

.actions {
  display: flex;
  gap: 10px;
}

.delete-btn {
  background: none;
  border: none;
  color: #e74c3c;
  cursor: pointer;
  font-size: 1rem;
  padding: 5px;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.delete-btn:hover {
  background-color: rgba(231, 76, 60, 0.1);
}

.history-item-content {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.query-type {
  font-weight: 500;
  color: #333;
}

.query-content {
  color: #555;
  font-size: 0.95rem;
}

.safety-rating {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 5px;
}

.rating-stars {
  display: flex;
  gap: 2px;
}

.fa-star {
  color: #ddd;
}

.fa-star.filled {
  color: #f1c40f;
}

.rating-value {
  font-weight: 500;
}

.pagination {
  display: flex;
  justify-content: center;
  gap: 5px;
  margin-top: 30px;
}

.pagination-btn {
  padding: 8px 12px;
  border: 1px solid #ddd;
  background-color: white;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background-color: #f5f5f5;
}

.pagination-btn.active {
  background-color: #3498db;
  color: white;
  border-color: #3498db;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.empty-history {
  text-align: center;
  padding: 40px 0;
}

.empty-history p {
  margin-bottom: 20px;
  color: #666;
}

.new-analysis-btn {
  padding: 10px 20px;
  background-color: #3498db;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.new-analysis-btn:hover {
  background-color: #2980b9;
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
  z-index: 1000;
}

.modal-content {
  background-color: white;
  padding: 20px;
  border-radius: 8px;
  width: 90%;
  max-width: 400px;
}

.modal-content h3 {
  margin-top: 0;
  margin-bottom: 15px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.cancel-btn {
  padding: 8px 15px;
  background-color: #f0f0f0;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
}

.confirm-btn {
  padding: 8px 15px;
  background-color: #e74c3c;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
</style> 