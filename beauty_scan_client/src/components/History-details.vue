<template>
  <div class="history-details-container">
    <!-- Прелоадер -->
    <div class="loader-container" v-if="loading">
      <div class="loader"></div>
      <p>Загрузка данных...</p>
    </div>
    
    <div v-else-if="historyItem" class="history-details">
      <div class="header">
        <button class="back-btn" @click="goBack">
          <i class="fas fa-arrow-left"></i> Назад к истории
        </button>
        <span class="date">{{ formatDate(historyItem.created_at) }}</span>
      </div>
      
      <h1>Результаты анализа</h1>
      
      <div class="query-section">
        <h2>Исходный запрос</h2>
        <div class="query-type-badge" :class="historyItem.query_type">
          {{ historyItem.query_type === 'text' ? 'Текстовый запрос' : 'Фото запрос' }}
        </div>
        <div class="query-content">
          <p>{{ historyItem.query_content }}</p>
        </div>
      </div>
      
      <div class="results-section">
        <h2>Результаты анализа</h2>
        
        <div class="safety-summary">
          <div class="safety-rating">
            <h3>Общая оценка безопасности</h3>
            <div class="rating-display">
              <div class="stars">
                <i 
                  v-for="n in 5" 
                  :key="n" 
                  :class="['fas', 'fa-star', n <= Math.round(historyItem.result.safety_rating) ? 'filled' : '']"
                ></i>
              </div>
              <span class="rating-value">{{ historyItem.result.safety_rating.toFixed(1) }}</span>
            </div>
          </div>
          
          <div class="safety-percentages">
            <div class="percentage-item safe">
              <div class="percentage-label">Безопасные</div>
              <div class="percentage-bar">
                <div 
                  class="percentage-fill" 
                  :style="{ width: `${historyItem.result.safe_percentage}%` }"
                ></div>
              </div>
              <div class="percentage-value">{{ historyItem.result.safe_percentage.toFixed(1) }}%</div>
            </div>
            
            <div class="percentage-item caution">
              <div class="percentage-label">С предупреждением</div>
              <div class="percentage-bar">
                <div 
                  class="percentage-fill" 
                  :style="{ width: `${historyItem.result.caution_percentage}%` }"
                ></div>
              </div>
              <div class="percentage-value">{{ historyItem.result.caution_percentage.toFixed(1) }}%</div>
            </div>
            
            <div class="percentage-item danger">
              <div class="percentage-label">Опасные</div>
              <div class="percentage-bar">
                <div 
                  class="percentage-fill" 
                  :style="{ width: `${historyItem.result.danger_percentage}%` }"
                ></div>
              </div>
              <div class="percentage-value">{{ historyItem.result.danger_percentage.toFixed(1) }}%</div>
            </div>
          </div>
        </div>
        
        <div class="recommendation">
          <h3>Рекомендация</h3>
          <p>{{ historyItem.result.recommendation }}</p>
        </div>
        
        <div class="ingredients-list">
          <h3>Ингредиенты</h3>
          <div class="ingredients-table">
            <div class="table-header">
              <div class="position">#</div>
              <div class="name">Название</div>
              <div class="safety">Безопасность</div>
              <div class="class">Происхождение</div>
            </div>
            
            <div 
              v-for="(ingredient, index) in historyItem.result.ingredients" 
              :key="ingredient.id"
              class="ingredient-row"
              :class="ingredient.danger_factor"
            >
              <div class="position">{{ index + 1 }}</div>
              <div class="name">
                <div class="ingredient-name">{{ ingredient.traditional_name }}</div>
                <div class="ingredient-description">{{ ingredient.usages }}</div>
              </div>
              <div class="safety">
                <div 
                  class="safety-indicator" 
                  :class="ingredient.danger_factor"
                  :title="getSafetyLevelText(ingredient.danger_factor)"
                ></div>
                <span>{{ getSafetyLevelText(ingredient.danger_factor) }}</span>
              </div>
              <div class="class">{{ getNaturalnessLevelText(ingredient.naturalness) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else class="not-found">
      <h2>Запись не найдена</h2>
      <p>Запрошенная запись истории не существует или была удалена.</p>
      <button class="back-btn" @click="goBack">Вернуться к истории</button>
    </div>
  </div>
</template>

<script>
import api from '../api';
import { useRouter, useRoute } from 'vue-router';
import { ref, onMounted } from 'vue';

export default {
  name: 'HistoryDetails',
  
  setup() {
    const router = useRouter();
    const route = useRoute();
    const historyItem = ref(null);
    const loading = ref(true);
    
    // Получение данных истории
    const fetchHistoryDetails = async () => {
      loading.value = true;
      try {
        const historyId = route.params.id;
        const response = await api.get(`/history/${historyId}`);
        historyItem.value = response.data;
      } catch (error) {
        console.error('Ошибка при получении данных истории:', error);
        historyItem.value = null;
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
    
    // Получение текстового описания уровня безопасности
    const getSafetyLevelText = (level) => {
      switch (level) {
        case 'Низкий':
          return 'Безопасный';
        case 'Средний':
          return 'С предупреждением';
        case 'Высокий':
          return 'Опасный';
        default:
          return 'Неизвестно';
      }
    };

    const getNaturalnessLevelText = (level) => {
      switch (level) {
        case 'Натуральный':
          return 'Натуральный компонент';
        case 'Синтетический':
          return 'Синтетический компонент';
        default:
          return 'Неизвестный компонент';
      }
    };
    
    // Возврат к списку истории
    const goBack = () => {
      router.push('/history');
    };
    
    // Загрузка данных при монтировании компонента
    onMounted(() => {
      fetchHistoryDetails();
    });
    
    return {
      historyItem,
      loading,
      formatDate,
      getSafetyLevelText,
      getNaturalnessLevelText,
      goBack
    };
  }
};
</script>

<style scoped>
.history-details-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.loader-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 300px;
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

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.back-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 15px;
  background-color: #f0f0f0;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.back-btn:hover {
  background-color: #e0e0e0;
}

.date {
  font-size: 0.9rem;
  color: #666;
}

h1 {
  text-align: center;
  margin-bottom: 30px;
  color: #333;
}

h2 {
  margin-bottom: 15px;
  color: #333;
  border-bottom: 1px solid #eee;
  padding-bottom: 10px;
}

h3 {
  margin-bottom: 10px;
  color: #444;
}

.query-section {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  padding: 20px;
  margin-bottom: 20px;
}

.query-type-badge {
  display: inline-block;
  padding: 5px 10px;
  border-radius: 4px;
  font-size: 0.9rem;
  margin-bottom: 10px;
}

.query-type-badge.text {
  background-color: #e3f2fd;
  color: #1976d2;
}

.query-type-badge.photo {
  background-color: #e8f5e9;
  color: #388e3c;
}

.query-content {
  background-color: #f9f9f9;
  padding: 15px;
  border-radius: 4px;
  font-family: monospace;
  white-space: pre-wrap;
  word-break: break-word;
}

.results-section {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  padding: 20px;
}

.safety-summary {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 20px;
}

.safety-rating {
  flex: 1;
  min-width: 250px;
}

.rating-display {
  display: flex;
  align-items: center;
  gap: 10px;
}

.stars {
  display: flex;
  gap: 2px;
}

.fa-star {
  color: #ddd;
  font-size: 1.5rem;
}

.fa-star.filled {
  color: #f1c40f;
}

.rating-value {
  font-size: 1.5rem;
  font-weight: 500;
}

.safety-percentages {
  flex: 2;
  min-width: 300px;
}

.percentage-item {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.percentage-label {
  width: 150px;
  font-weight: 500;
}

.percentage-bar {
  flex: 1;
  height: 15px;
  background-color: #f0f0f0;
  border-radius: 10px;
  overflow: hidden;
  margin: 0 10px;
}

.percentage-fill {
  height: 100%;
  border-radius: 10px;
}

.percentage-item.safe .percentage-fill {
  background-color: #2ecc71;
}

.percentage-item.caution .percentage-fill {
  background-color: #f39c12;
}

.percentage-item.danger .percentage-fill {
  background-color: #e74c3c;
}

.percentage-value {
  width: 60px;
  text-align: right;
  font-weight: 500;
}

.recommendation {
  background-color: #f8f9fa;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
  border-left: 4px solid #3498db;
}

.ingredients-list {
  margin-top: 30px;
}

.ingredients-table {
  border: 1px solid #eee;
  border-radius: 8px;
  overflow: hidden;
}

.table-header {
  display: flex;
  background-color: #f5f5f5;
  font-weight: 500;
  padding: 12px 15px;
}

.ingredient-row {
  display: flex;
  padding: 15px;
  border-top: 1px solid #eee;
}

.ingredient-row:hover {
  background-color: #f9f9f9;
}

.ingredient-row.Низкий {
  border-left: 4px solid #2ecc71;
}

.ingredient-row.Средний {
  border-left: 4px solid #f39c12;
}

.ingredient-row.Высокий {
  border-left: 4px solid #e74c3c;
}

.ingredient-row.unknown {
  border-left: 4px solid #9b59b6;
}

.position {
  width: 40px;
  text-align: center;
}

.name {
  flex: 3;
  min-width: 200px;
}

.ingredient-name {
  font-weight: 500;
  margin-bottom: 5px;
}

.ingredient-description {
  font-size: 0.9rem;
  color: #666;
}

.safety {
  flex: 1;
  min-width: 150px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.safety-indicator {
  width: 15px;
  height: 15px;
  border-radius: 50%;
}

.safety-indicator.Низкий {
  background-color: #2ecc71;
}

.safety-indicator.Средний {
  background-color: #f39c12;
}

.safety-indicator.Высокий {
  background-color: #e74c3c;
}

.safety-indicator.unknown {
  background-color: #9b59b6;
}

.class {
  flex: 1;
  min-width: 120px;
}

.not-found {
  text-align: center;
  padding: 50px 0;
}

.not-found h2 {
  margin-bottom: 15px;
}

.not-found p {
  margin-bottom: 20px;
  color: #666;
}

@media (max-width: 768px) {
  .safety-summary {
    flex-direction: column;
  }
  
  .ingredient-row {
    flex-direction: column;
  }
  
  .position, .name, .safety, .class {
    width: 100%;
    margin-bottom: 10px;
  }
  
  .position {
    text-align: left;
  }
}
</style> 