import axios from 'axios';

// Создаем экземпляр axios
const api = axios.create({
    baseURL: 'http://localhost:8000/api', // Базовый URL вашего API
});

// Интерцептор для добавления Access Token в заголовки запросов
api.interceptors.request.use((config) => {
    const token = localStorage.getItem("auth_token"); // Получаем Access Token из localStorage
    if (token) {
        config.headers.Authorization = `Bearer ${token}`; // Добавляем токен в заголовок
    }
    return config;
});

// Интерцептор для обработки ошибок и обновления токена
api.interceptors.response.use(
    (response) => response, // Если ответ успешный, просто возвращаем его
    async (error) => {
        const originalRequest = error.config; // Сохраняем оригинальный запрос

        // Если ошибка 401 (Unauthorized) и это не запрос на обновление токена
        if (error.response?.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true; // Помечаем запрос как повторный

            // Получаем Refresh Token из localStorage
            const refreshToken = localStorage.getItem("refresh_token");

            if (refreshToken) {
                try {
                    // Запрашиваем новый Access Token
                    const response = await axios.post("http://localhost:8000/api/refresh-token", {
                        refresh_token: refreshToken,
                    });

                    const newAccessToken = response.data.access_token;

                    // Сохраняем новый Access Token в localStorage
                    localStorage.setItem("auth_token", newAccessToken);

                    // Повторяем оригинальный запрос с новым токеном
                    originalRequest.headers.Authorization = `Bearer ${newAccessToken}`;
                    return api(originalRequest);
                } catch (refreshError) {
                    // Если Refresh Token недействителен, выходим из системы
                    localStorage.removeItem("auth_token");
                    localStorage.removeItem("refresh_token");
                    window.location.href = "/login"; // Перенаправляем на страницу входа
                }
            }
        }

        // Если ошибка не связана с токеном, просто возвращаем её
        return Promise.reject(error);
    }
);

export default api; // Экспортируем настроенный экземпляр axios