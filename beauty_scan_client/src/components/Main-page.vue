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

    <div v-if="modalVisible" class="modal-overlay">
      <div class="modal">
        <h3>Распознанный текст</h3>
        <textarea v-model="recognizedText" class="text-modal"></textarea>

        <p v-if="recognitionError" class="error-message">
          Текст не распознан. Попробуйте ввести текст самостоятельно или загрузить новое изображение.
        </p>

        <div class="modal-buttons">
          <button @click="closeModal" class="button">Отменить</button>
          <button @click="saveRequest(recognizedText)" class="button check-button">Проверить</button>
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
          <div class="tips-images">
            <img src="/path/to/example1.jpg" alt="Пример хорошего фото"/>
            <img src="/path/to/example2.jpg" alt="Пример плохого фото"/>
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
        await this.saveRequest(this.inputText.trim());
        return;
      }

      const formData = new FormData();
      const blob = await fetch(this.previewImage).then((res) => res.blob());
      formData.append("image", blob, "uploaded_image.png");

      try {
        // Используем api вместо fetch
        const response = await api.post("/extract-text", formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        });

        const result = response.data;

        if (result.success) {
          this.recognizedText = result.extractedText || "";
          this.recognitionError = !result.extractedText;
          this.modalVisible = true;
        } else {
          alert(result.message); // Показываем сообщение об ошибке
          this.recognizedText = "";
          this.recognitionError = true;
          this.modalVisible = true;
        }
      } catch (error) {
        console.error("Ошибка при обработке запроса:", error);
        alert("Произошла ошибка при обработке запроса.");
      }
    },

    async saveRequest(text) {
      if (!text || typeof text !== "string" || !text.trim()) {
        alert("Текст обязателен для сохранения.");
        return;
      }

      const formData = new FormData();
      formData.append("text", text.trim());

      // Если есть изображение, добавляем его путь
      if (this.previewImage) {
        const blob = await fetch(this.previewImage).then((res) => res.blob());
        formData.append("image", blob, "uploaded_image.png");
      }

      try {
        // Используем api вместо fetch
        const response = await api.post("/save-request", formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        });

        const result = response.data;

        if (result.success) {
          alert("Запрос успешно сохранён!");
          this.modalVisible = false;
          this.inputText = "";
          this.previewImage = null;
        } else {
          alert(`Ошибка: ${result.message}`);
        }
      } catch (error) {
        console.error("Ошибка отправки данных:", error);
        alert("Произошла ошибка при обработке запроса.");
      }
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

    openTipsModal(action) {
      this.actionType = action; // Сохраняем тип действия
      this.tipsModalVisible = true; // Показываем модальное окно
    },

    closeTipsModal() {
      this.tipsModalVisible = false; // Скрываем модальное окно

      // Выполняем действие после нажатия "Понятно"
      if (this.actionType === "upload") {
        this.$refs.fileInput.click(); // Открываем выбор файла
      } else if (this.actionType === "camera") {
        this.openCamera(); // Открываем камеру
      }
    },
  },
};
</script>

<style scoped>
.camera-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}
</style>


<style scoped>
.main-page {
  padding: 20px;
  font-family: Arial, sans-serif;
  margin-top: 50px;
}

.header {
  text-align: center;
  margin-bottom: 20px;
}

.upload-section {
  max-width: 600px;
  margin: 0 auto;
  text-align: center;
}

.input-options {
  margin: 20px 0;
}

.text-input {
  width: 100%;
  min-height: 100px;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 5px;
  resize: none;
}

.upload-buttons {
  margin: 20px 0;
  display: flex;
  justify-content: center;
  gap: 10px;
}

.button {
  display: inline-block;
  padding: 10px 20px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  text-align: center;
  font-size: 1rem;
  transition: background-color 0.3s, transform 0.2s;
}

.button:hover {
  background-color: #0056b3;
}

.button:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.image-preview {
  margin-top: 20px;
}

.error-message {
  color: red;
  margin-top: 10px;
  font-size: 14px;
  font-weight: bold;
}

.image-preview img {
  max-width: 100%;
  border: 1px solid #ccc;
  border-radius: 5px;
}

.image-preview .remove-button {
  margin-top: 10px;
  background-color: #dc3545;
}

.camera-section {
  margin-top: 20px;
}

video {
  width: 100%;
  max-width: 600px;
  border: 1px solid #ccc;
  border-radius: 5px;
}

.check-button {
  margin-top: 20px;
  width: 100%;
}

.modal-content {
  background: white;
  padding: 20px;
  border-radius: 5px;
  text-align: center;
}

.text-area {
  width: 100%;
  height: 100px;
}

.modal {
  background: white;
  padding: 20px;
  border-radius: 10px;
  width: 400px;
  text-align: center;
  box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
}

/* Поле для текста в модальном окне */
.text-modal {
  width: 100%;
  min-height: 120px;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 8px;
  resize: none;
  font-size: 16px;
  text-align: left; /* Выравнивание текста */
}

/* Контейнер кнопок */
.modal-buttons {
  margin-top: 15px;
  display: flex;
  justify-content: space-between;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal-buttons .button:hover {
  background-color: #0056b3;
}

/* Красная кнопка "Отмена" */
.modal-buttons .button:first-child {
  background-color: #dc3545;
}

.modal-buttons .button:first-child:hover {
  background-color: #a71d2a;
}

.tips-modal {
  max-width: 600px;
  padding: 20px;
  text-align: center;
}

.tips-content {
  text-align: left;
  margin-bottom: 20px;
}

.tips-content ul {
  list-style-type: disc;
  padding-left: 20px;
}

.tips-images {
  display: flex;
  justify-content: space-around;
  margin-top: 20px;
}

.tips-images img {
  max-width: 45%;
  border: 1px solid #ccc;
  border-radius: 5px;
}
</style>
