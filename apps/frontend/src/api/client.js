import axios from 'axios'

const token = localStorage.getItem('imi_token')

export const apiClient = axios.create({
  baseURL: '/api',
  headers: token ? { Authorization: 'Bearer ' + token } : {},
})

export const setAuthToken = (nextToken) => {
  if (nextToken) {
    localStorage.setItem('imi_token', nextToken)
    apiClient.defaults.headers.Authorization = 'Bearer ' + nextToken
  } else {
    localStorage.removeItem('imi_token')
    delete apiClient.defaults.headers.Authorization
  }
}
