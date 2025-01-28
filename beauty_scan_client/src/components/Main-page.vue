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
        ></textarea>

        <div class="upload-buttons">
          <label class="button">
            Загрузить изображение
            <input type="file" @change="handleFileUpload" accept="image/*" hidden />
          </label>

          <button @click="openCamera" class="button">Сделать фото</button>
        </div>
      </div>

      <div v-if="previewImage" class="image-preview">
        <h3>Предпросмотр изображения:</h3>
        <img :src="previewImage" alt="Предпросмотр" />
        <button @click="clearImage" class="button remove-button">Удалить изображение</button>
      </div>

      <div v-if="cameraActive" class="camera-section">
        <h3>Фотографирование:</h3>
        <video ref="video" autoplay></video>
        <button @click="takePhoto" class="button">Сделать фото</button>
      </div>

      <button
          :disabled="!isSubmitEnabled"
          @click="processInput"
          class="button check-button"
      >
        Проверить состав
      </button>
    </section>
  </div>
</template>

<script>
export default {
  data() {
    return {
      inputText: "", // Содержимое текстового поля
      previewImage: null, // Предпросмотр загруженного изображения
      cameraActive: false, // Флаг активации камеры
      stream: null, // Ссылка на видеопоток
    };
  },
  computed: {
    // Кнопка активна, если есть текст или загружено изображение
    isSubmitEnabled() {
      return this.inputText.trim().length > 0 || this.previewImage !== null;
    },
  },
  methods: {
    // Обработка загрузки файла
    handleFileUpload(event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          this.previewImage = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    },
    // Очистка загруженного изображения
    clearImage() {
      this.previewImage = null;
    },
    // Открытие камеры
    openCamera() {
      navigator.mediaDevices
          .getUserMedia({ video: true })
          .then((stream) => {
            this.cameraActive = true;
            this.stream = stream; // Сохраняем ссылку на видеопоток
            // Ожидаем обновления DOM и установки ссылки на video
            this.$nextTick(() => {
              if (this.$refs.video) {
                this.$refs.video.srcObject = stream;
              } else {
                console.error("Видео элемент не найден.");
              }
            });
          })
          .catch((err) => {
            console.error("Ошибка доступа к камере:", err);
            alert("Не удалось получить доступ к камере. Проверьте настройки устройства.");
          });
    },
    // Снятие фото
    takePhoto() {
      const video = this.$refs.video;
      if (!video || !this.stream) {
        alert("Камера не активна.");
        return;
      }

      const canvas = document.createElement("canvas");
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      const context = canvas.getContext("2d");
      context.drawImage(video, 0, 0, canvas.width, canvas.height);

      this.previewImage = canvas.toDataURL("image/png"); // Сохраняем изображение
      this.closeCamera(); // Закрываем камеру после фотографирования
    },
    // Закрытие камеры
    closeCamera() {
      if (this.stream) {
        this.stream.getTracks().forEach((track) => track.stop());
        this.stream = null;
      }
      this.cameraActive = false;
    },
    async processInput() {
      if (!this.inputText.trim() && !this.previewImage) {
        alert("Введите текст или загрузите изображение.");
        return;
      }

      const formData = new FormData();

      // Добавляем текст, если он введён
      if (this.inputText.trim()) {
        formData.append("text", this.inputText.trim());
      }

      // Добавляем изображение, если оно загружено
      if (this.previewImage) {
        // Преобразуем Base64 изображение обратно в файл
        const blob = await fetch(this.previewImage).then((res) => res.blob());
        formData.append("image", blob, "uploaded_image.png");
      }

      try {
        // Отправка данных на сервер
        const response = await fetch("http://localhost:8000/api/save-request", {
          method: "POST",
          body: formData,
          headers: {
            "Authorization": `Bearer ${localStorage.getItem("auth_token")}`
          }
        });

        const result = await response.json();

        if (result.success) {
          alert("Запрос успешно сохранён!");
          this.inputText = ""; // Очистка текстового поля
          this.previewImage = null; // Сброс изображения
        } else {
          alert(`Ошибка: ${result.message}`);
        }
      } catch (error) {
        console.error("Ошибка отправки данных:", error);
        alert("Произошла ошибка при обработке запроса.");
      }
    },
  },
  beforeUnmount() {
    // Закрываем потоки, если компонент размонтируется
    this.closeCamera();
  },
};
</script>

<style scoped>
.main-page {
  padding: 20px;
  font-family: Arial, sans-serif;
  margin-top: 150px;
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
</style>
