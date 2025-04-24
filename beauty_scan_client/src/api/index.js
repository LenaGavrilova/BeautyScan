import axios from 'axios';

// Создаем экземпляр axios с настройкой для работы с реальным API
const api = axios.create({
    baseURL: 'http://localhost:8000/api', // URL API сервера
    timeout: 10000, // Добавляем таймаут для запросов
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    withCredentials: false // Отключаем отправку куки для кросс-доменных запросов
});

// Добавляем перехватчик для добавления токена авторизации
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        console.error('Ошибка при отправке запроса:', error);
        return Promise.reject(error);
    }
);

// Добавляем перехватчик для обработки ошибок
api.interceptors.response.use(
    (response) => {
        return response;
    },
    async (error) => {
        const originalRequest = error.config;
        
        // Если нет соединения с сервером
        if (!error.response) {
            console.error('Ошибка соединения с сервером');
            alert('Ошибка соединения с сервером. Проверьте, запущен ли сервер.');
            return Promise.reject({
                response: {
                    status: 0,
                    data: { message: 'Ошибка соединения с сервером. Проверьте, запущен ли сервер.' }
                }
            });
        }
        
        // Если ошибка 401 (Unauthorized) и запрос не был повторен
        if (error.response && error.response.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true;
            
            try {
                // Пытаемся обновить токен
                const refreshToken = localStorage.getItem('refresh_token');
                if (!refreshToken) {
                    throw new Error('No refresh token available');
                }
                
                const response = await axios.post('http://localhost:8000/api/refresh-token', {
                    refresh_token: refreshToken
                });
                
                const { access_token } = response.data;
                
                // Сохраняем новый токен
                localStorage.setItem('auth_token', access_token);
                
                // Обновляем заголовок в оригинальном запросе
                originalRequest.headers['Authorization'] = `Bearer ${access_token}`;
                
                // Повторяем оригинальный запрос с новым токеном
                return axios(originalRequest);
            } catch (refreshError) {
                // Если не удалось обновить токен, выходим из системы
                localStorage.removeItem('auth_token');
                localStorage.removeItem('refresh_token');
                window.location.href = '/login';
                return Promise.reject(refreshError);
            }
        }
        
        return Promise.reject(error);
    }
);

export default api;