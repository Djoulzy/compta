import axios from 'axios';

const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Comptes
export const getComptes = () => api.get('/comptes.php');
export const getCompte = (id) => api.get(`/comptes.php/${id}`);
export const createCompte = (data) => api.post('/comptes.php', data);
export const updateCompte = (id, data) => api.put(`/comptes.php/${id}`, data);
export const deleteCompte = (id) => api.delete(`/comptes.php/${id}`);

// Opérations
export const getOperations = (params) => api.get('/operations.php', { params });
export const getBalance = (params) => api.get('/operations.php/balance', { params });
export const createOperation = (data) => api.post('/operations.php', data);
export const updateOperationTags = (id, tags) => api.put(`/operations.php/tags/${id}`, { tags });
export const deleteOperation = (id) => api.delete(`/operations.php/${id}`);

// Tags
export const getTags = () => api.get('/tags.php');
export const getTag = (id) => api.get(`/tags.php/${id}`);
export const createTag = (data) => api.post('/tags.php', data);
export const updateTag = (id, data) => api.put(`/tags.php/${id}`, data);
export const deleteTag = (id) => api.delete(`/tags.php/${id}`);

// Import/Upload
export const importCSV = (formData) => {
  return axios.post(`${API_BASE_URL}/upload.php`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
};

// Gestion des imports
export const getImports = () => api.get('/imports.php');
export const getImport = (id) => api.get(`/imports.php/${id}`);
export const getImportOperations = (id) => api.get(`/imports.php/operations/${id}`);
export const deleteImport = (id) => api.delete(`/imports.php/${id}`);

export default api;
