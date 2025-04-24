<template>
  <div class="main-page">
    <header class="header">
      <h1>Проверка состава косметики онлайн</h1>
      <p>Бесплатно. Онлайн. 2-5 секунд.</p>
    </header>

    <section class="upload-section">
      <h2>Введите текст или загрузите изображение</h2>

      <div class="input-options">
        <textarea
            v-model="inputText"
            placeholder="Введите текст здесь..."
            class="text-input"
            :disabled="!!previewImage"
        ></textarea>

        <div class="upload-buttons">
          <button @click="openTipsModal('upload')" class="button">
            Загрузить изображение
          </button>

          <input
              ref="fileInput"
              type="file"
              @change="handleFileUpload"
              accept="image/*"
              hidden
          />

          <button @click="openTipsModal('camera')" class="button">
            Сделать фото
          </button>
        </div>
      </div>

      <div v-if="previewImage" class="image-preview">
        <h3>Предпросмотр изображения:</h3>
        <img :src="previewImage" alt="Предпросмотр"/>
        <button @click="clearImage" class="button remove-button">Удалить изображение</button>
      </div>

      <button :disabled="!isSubmitEnabled" @click="processInput" class="button check-button">
        Проверить состав
      </button>
    </section>

    <!-- Результаты анализа -->
    <section v-if="analysisResults" class="results-section">
      <div class="loader-container" v-if="loading">
        <div class="loader"></div>
        <p>Анализируем состав...</p>
      </div>
      
      <div v-else class="analysis-results">
        <h2>Результаты анализа</h2>
        
        <div class="safety-summary">
          <div class="safety-rating">
            <h3>Общая оценка безопасности</h3>
            <div class="rating-display">
              <div class="stars">
                <i 
                  v-for="n in 5" 
                  :key="n" 
                  :class="['fas', 'fa-star', n <= Math.round(analysisResults.safety_rating) ? 'filled' : '']"
                ></i>
              </div>
              <span class="rating-value">{{ analysisResults.safety_rating.toFixed(1) }}</span>
            </div>
          </div>
          
          <div class="safety-percentages">
            <div class="percentage-item safe">
              <div class="percentage-label">Безопасные</div>
              <div class="percentage-bar">
                <div 
                  class="percentage-fill" 
                  :style="{ width: `${analysisResults.safe_percentage}%` }"
                ></div>
              </div>
              <div class="percentage-value">{{ analysisResults.safe_percentage.toFixed(1) }}%</div>
            </div>
            
            <div class="percentage-item caution">
              <div class="percentage-label">С предупреждением</div>
              <div class="percentage-bar">
                <div 
                  class="percentage-fill" 
                  :style="{ width: `${analysisResults.caution_percentage}%` }"
                ></div>
              </div>
              <div class="percentage-value">{{ analysisResults.caution_percentage.toFixed(1) }}%</div>
            </div>
            
            <div class="percentage-item danger">
              <div class="percentage-label">Опасные</div>
              <div class="percentage-bar">
                <div 
                  class="percentage-fill" 
                  :style="{ width: `${analysisResults.danger_percentage}%` }"
                ></div>
              </div>
              <div class="percentage-value">{{ analysisResults.danger_percentage.toFixed(1) }}%</div>
            </div>
          </div>
        </div>
        
        <div class="recommendation">
          <h3>Рекомендация</h3>
          <p>{{ analysisResults.recommendation }}</p>
          <div v-if="analysisResults.has_unknown_ingredients" class="unknown-ingredients-warning">
            <p>Обнаружено {{ analysisResults.unknown_count }} неизвестных ингредиентов ({{ analysisResults.unknown_percentage }}% от общего состава).</p>
          </div>
        </div>
        
        <div class="ingredients-list">
          <h3>Ингредиенты</h3>
          <div v-if="analysisResults.ingredients.length > 0" class="ingredients-table">
            <div class="table-header">
              <div class="position">#</div>
              <div class="name">Название</div>
              <div class="safety">Безопасность</div>
              <div class="class">Класс</div>
            </div>
            
            <div 
              v-for="(ingredient, index) in analysisResults.ingredients" 
              :key="ingredient.id"
              class="ingredient-row"
              :class="ingredient.safety_level"
            >
              <div class="position">{{ index + 1 }}</div>
              <div class="name">
                <div class="ingredient-name">
                  {{ ingredient.name }}
                  <span v-if="ingredient.unknown" class="unknown-badge">Неизвестный</span>
                </div>
                <div class="ingredient-description">{{ ingredient.description }}</div>
              </div>
              <div class="safety">
                <div 
                  class="safety-indicator" 
                  :class="ingredient.safety_level"
                  :title="getSafetyLevelText(ingredient.safety_level)"
                ></div>
                <span>{{ getSafetyLevelText(ingredient.safety_level) }}</span>
              </div>
              <div class="class">{{ ingredient.class }}</div>
            </div>
          </div>
          
          <div v-else class="no-ingredients">
            <p>Не удалось распознать ингредиенты в составе. Пожалуйста, проверьте введенный текст.</p>
          </div>
        </div>
        
        <div class="actions">
          <button @click="resetAnalysis" class="button">Новый анализ</button>
          <button @click="saveToHistory" class="button check-button">Сохранить в историю</button>
        </div>
      </div>
    </section>

    <div v-if="modalVisible" class="modal-overlay">
      <div class="modal">
        <h3>Распознанный текст</h3>
        <textarea v-model="recognizedText" class="text-modal"></textarea>

        <p v-if="recognitionError" class="error-message">
          Текст не распознан. Попробуйте ввести текст самостоятельно или загрузить новое изображение.
        </p>

        <div class="modal-buttons">
          <button @click="closeModal" class="button">Отменить</button>
          <button @click="analyzeIngredients(recognizedText)" class="button check-button">Проверить</button>
        </div>
      </div>
    </div>

    <div v-if="tipsModalVisible" class="modal-overlay">
      <div class="modal tips-modal">
        <h3>Рекомендации по фото</h3>
        <div class="tips-content">
          <p>Чтобы получить лучший результат:</p>
          <ul>
            <li>Сфотографируйте состав крупным планом.</li>
            <li>Убедитесь, что текст четкий и хорошо виден.</li>
            <li>Избегайте бликов и теней на тексте.</li>
          </ul>
          <div class="tips-examples">
            <div class="example good-example">
              <div class="example-title">Хороший пример:</div>
              <div class="example-content">
                Четкое изображение состава с хорошим освещением
              </div>
            </div>
            <div class="example bad-example">
              <div class="example-title">Плохой пример:</div>
              <div class="example-content">
                Размытое изображение с бликами и тенями
              </div>
            </div>
          </div>
        </div>
        <button @click="closeTipsModal" class="button check-button">Понятно</button>
      </div>
    </div>

    <!-- Камера -->
    <div v-if="cameraActive" class="camera-overlay">
      <video ref="video" autoplay></video>
      <button @click="capturePhoto" class="button">Сделать снимок</button>
      <button @click="closeCamera" class="button remove-button">Закрыть</button>
    </div>
  </div>
</template>

<script>
import api from '@/api'; // Импортируем настроенный экземпляр axios

export default {
  data() {
    return {
      inputText: "",
      previewImage: null,
      cameraActive: false,
      stream: null,
      modalVisible: false,
      recognizedText: "",
      recognitionError: false,
      tipsModalVisible: false,
      actionType: null,
      analysisResults: null,
      loading: false,
    };
  },
  computed: {
    isSubmitEnabled() {
      return this.inputText.trim().length > 0 || this.previewImage !== null;
    },
  },
  methods: {
    handleFileUpload(event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          this.previewImage = e.target.result;
          this.inputText = "";
        };
        reader.readAsDataURL(file);
      }
    },

    clearImage() {
      this.previewImage = null;
      this.inputText = "";
    },

    async processInput() {
      if (!this.inputText.trim() && !this.previewImage) {
        alert("Введите текст или загрузите изображение.");
        return;
      }

      if (this.inputText.trim() && !this.previewImage) {
        await this.analyzeIngredients(this.inputText.trim());
        return;
      }

      try {
        this.loading = true;
        
        if (this.previewImage) {
          // Преобразуем base64 в Blob
          const response = await fetch(this.previewImage);
          const blob = await response.blob();
          
          // Создаем FormData и добавляем изображение
          const formData = new FormData();
          formData.append('image', blob, 'image.png');
          
          // Отправляем изображение на сервер для распознавания текста
          const extractResponse = await api.post('/extract-text', formData, {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          });
          
          if (extractResponse.data.success) {
            this.recognizedText = extractResponse.data.extractedText;
            this.recognitionError = false;
          } else {
            this.recognizedText = "";
            this.recognitionError = true;
          }
          
          this.modalVisible = true;
        }
      } catch (error) {
        console.error("Ошибка при обработке запроса:", error);
        this.recognizedText = "";
        this.recognitionError = true;
        this.modalVisible = true;
      } finally {
        this.loading = false;
      }
    },

    async analyzeIngredients(text) {
      if (!text || typeof text !== "string" || !text.trim()) {
        alert("Текст обязателен для анализа.");
        return;
      }

      this.loading = true;
      this.modalVisible = false;

      try {
        const response = await api.post("/analyze", {
          ingredients: text.trim()
        });

        this.analysisResults = response.data;
      } catch (error) {
        console.error("Ошибка при анализе ингредиентов:", error);
        alert("Произошла ошибка при анализе ингредиентов.");
      } finally {
        this.loading = false;
      }
    },

    // Получение текстового описания уровня безопасности
    getSafetyLevelText(level) {
      switch (level) {
        case 'safe':
          return 'Безопасный';
        case 'caution':
          return 'С предупреждением';
        case 'danger':
          return 'Опасный';
        case 'unknown':
          return 'Неизвестный';
        default:
          return 'Неизвестно';
      }
    },

    // Сброс результатов анализа
    resetAnalysis() {
      this.analysisResults = null;
      this.inputText = "";
      this.previewImage = null;
    },

    // Сохранение результатов в историю
    async saveToHistory() {
      if (!this.analysisResults) {
        return;
      }

      try {
        await api.post("/history", {
          query_type: this.previewImage ? 'photo' : 'text',
          query_content: this.inputText || this.recognizedText,
          result: this.analysisResults
        });

        alert("Результаты успешно сохранены в историю!");
      } catch (error) {
        console.error("Ошибка при сохранении в историю:", error);
        alert("Произошла ошибка при сохранении в историю.");
      }
    },

    async saveRequest(text) {
      // Заменяем на анализ ингредиентов
      await this.analyzeIngredients(text);
    },

    openCamera() {
      this.cameraActive = true;
      navigator.mediaDevices
          .getUserMedia({ video: true })
          .then((stream) => {
            this.stream = stream;
            this.$refs.video.srcObject = stream;
          })
          .catch((error) => {
            console.error("Ошибка доступа к камере:", error);
            alert("Не удалось получить доступ к камере.");
            this.cameraActive = false;
          });
    },

    capturePhoto() {
      const canvas = document.createElement("canvas");
      const video = this.$refs.video;
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      const ctx = canvas.getContext("2d");
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      this.previewImage = canvas.toDataURL("image/png");
      this.inputText = "";
      this.closeCamera();
    },

    closeCamera() {
      if (this.stream) {
        this.stream.getTracks().forEach((track) => track.stop());
      }
      this.cameraActive = false;
    },

    closeModal() {
      this.modalVisible = false;
      this.recognizedText = "";
      this.recognitionError = false;
    },

    openTipsModal(type) {
      this.actionType = type;
      this.tipsModalVisible = true;
    },

    closeTipsModal() {
      this.tipsModalVisible = false;
      if (this.actionType === "upload") {
        this.$refs.fileInput.click();
      } else if (this.actionType === "camera") {
        this.openCamera();
      }
      this.actionType = null;
    },
  },
};
</script>

<style scoped>
.main-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.header {
  text-align: center;
  margin-bottom: 30px;
}

.header h1 {
  color: #333;
  margin-bottom: 10px;
}

.header p {
  color: #666;
}

.upload-section {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  padding: 20px;
  margin-bottom: 30px;
}

.upload-section h2 {
  text-align: center;
  margin-bottom: 20px;
  color: #333;
}

.input-options {
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin-bottom: 20px;
}

.text-input {
  width: 100%;
  height: 150px;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  resize: vertical;
  font-family: inherit;
}

.upload-buttons {
  display: flex;
  justify-content: center;
  gap: 15px;
}

.button {
  padding: 10px 20px;
  background-color: #f0f0f0;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.button:hover {
  background-color: #e0e0e0;
}

.button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.check-button {
  background-color: #3498db;
  color: white;
  border: none;
}

.check-button:hover {
  background-color: #2980b9;
}

.remove-button {
  background-color: #e74c3c;
  color: white;
  border: none;
}

.remove-button:hover {
  background-color: #c0392b;
}

.image-preview {
  margin-top: 20px;
  text-align: center;
}

.image-preview h3 {
  margin-bottom: 10px;
  color: #333;
}

.image-preview img {
  max-width: 100%;
  max-height: 300px;
  border-radius: 4px;
  margin-bottom: 10px;
}

.modal-overlay {
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

.modal {
  background-color: white;
  border-radius: 8px;
  padding: 20px;
  width: 90%;
  max-width: 600px;
}

.modal h3 {
  margin-top: 0;
  margin-bottom: 15px;
  color: #333;
}

.text-modal {
  width: 100%;
  height: 200px;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  resize: vertical;
  margin-bottom: 15px;
  font-family: inherit;
}

.modal-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.error-message {
  color: #e74c3c;
  margin-bottom: 15px;
}

.tips-modal {
  max-width: 700px;
}

.tips-content {
  margin-bottom: 20px;
}

.tips-content ul {
  margin-left: 20px;
  margin-bottom: 15px;
}

.tips-examples {
  display: flex;
  justify-content: space-between;
  gap: 15px;
}

.example {
  flex: 1;
  min-width: 48%;
  padding: 15px;
  background-color: #f8f9fa;
  border-radius: 8px;
}

.good-example {
  border-left: 4px solid #2ecc71;
}

.bad-example {
  border-left: 4px solid #e74c3c;
}

.example-title {
  font-weight: 500;
  margin-bottom: 10px;
}

.example-content {
  font-size: 0.9rem;
  color: #666;
}

.camera-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: #000;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.camera-overlay video {
  max-width: 100%;
  max-height: 70vh;
  margin-bottom: 20px;
}

.camera-overlay .button {
  margin: 5px;
}

/* Стили для результатов анализа */
.results-section {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  padding: 20px;
  margin-top: 30px;
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

.analysis-results h2 {
  text-align: center;
  margin-bottom: 30px;
  color: #333;
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

.ingredients-list h3 {
  margin-bottom: 15px;
  color: #333;
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

.ingredient-row.safe {
  border-left: 4px solid #2ecc71;
}

.ingredient-row.caution {
  border-left: 4px solid #f39c12;
}

.ingredient-row.danger {
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

.safety-indicator.safe {
  background-color: #2ecc71;
}

.safety-indicator.caution {
  background-color: #f39c12;
}

.safety-indicator.danger {
  background-color: #e74c3c;
}

.safety-indicator.unknown {
  background-color: #9b59b6;
}

.class {
  flex: 1;
  min-width: 120px;
}

.no-ingredients {
  text-align: center;
  padding: 20px;
  color: #666;
}

.actions {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 30px;
}

.unknown-ingredients-warning {
  margin-top: 15px;
  padding: 10px 15px;
  background-color: #f8f4fc;
  border-left: 4px solid #9b59b6;
  border-radius: 4px;
}

.unknown-ingredients-warning p {
  margin: 0;
  color: #6c3483;
  font-size: 14px;
}

.unknown-badge {
  display: inline-block;
  margin-left: 8px;
  padding: 2px 6px;
  background-color: #9b59b6;
  color: white;
  font-size: 11px;
  border-radius: 4px;
  vertical-align: middle;
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
