import { router } from '@/plugins/1.router';
import axios from 'axios';
import { ref } from 'vue';
const { toast } = useToast();

export const useAxios = (url, config = {}) => {
  const data = ref(null);
  const error = ref(null);
  const isLoading = ref(false);
  const response = ref(null);

  const axiosInstance = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
    headers: { 'Content-Type': 'application/json' },
    ...config,
  });

  axiosInstance.interceptors.request.use((config) => {
    const accessToken = useCookie('accessToken').value;
    if (accessToken) config.headers.Authorization = `Bearer ${accessToken}`;
    if (config.data instanceof FormData) delete config.headers['Content-Type'];
    return config;
  }, (err) => Promise.reject(err));

  const request = async (method, requestConfig = {}) => {
    isLoading.value = true;
    error.value = null;
    data.value = null;

    try {
      const result = await axiosInstance({ url, method, ...requestConfig });
      response.value = result;
      data.value = result.data;

      if (data.value?.message && data.value?.code === 200) {
        toast('Éxito', data.value.message, 'success');
      }

      if (data.value?.message && data.value?.code != 200) {
        toast("Error", data.value.message, "danger");
      }

      return { data: data.value, response: result };
    } catch (err) {
      error.value = err.response?.data || err.message;
      response.value = err.response;

      const status = err.response?.status;
      const errorData = err.response?.data || { code: status, message: 'Error desconocido' };

      if (status === 403 || errorData?.code === 403) {
        router.push({ name: 'NotAuthorized' });
      } else if (errorData?.message) {
        toast('Error', errorData.message, 'danger');
      }

      return { data: errorData, response: err.response }; // Siempre resuelve, incluso en error
    } finally {
      isLoading.value = false;
    }
  };

  return {
    data,
    error,
    isLoading,
    response,
    get: (config = {}) => request('GET', config),
    post: (body, config = {}) => request('POST', { data: body, ...config }),
    put: (body, config = {}) => request('PUT', { data: body, ...config }),
    delete: (config = {}) => request('DELETE', config),
  };
};
