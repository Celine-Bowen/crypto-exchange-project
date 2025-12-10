import axios from 'axios';

const baseURL = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api';

const api = axios.create({
  baseURL,
  headers: {
    Accept: 'application/json',
  },
});

export function setAuthToken(token) {
  if (token) {
    api.defaults.headers.common.Authorization = `Bearer ${token}`;
  }
}

export function clearAuthToken() {
  delete api.defaults.headers.common.Authorization;
}

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      clearAuthToken();
    }

    return Promise.reject(error);
  },
);

export default api;
