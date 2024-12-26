import { ApiService } from './api.service.js';
import config from '../config.js';

export class AuthService {
    // အကောင့်ဝင်ရောက်ခြင်း
    static async login(phone, password) {
        try {
            const response = await ApiService.request('/api/auth/login', {
                method: 'POST',
                body: JSON.stringify({ phone, password })
            });

            if (response.token) {
                localStorage.setItem(config.AUTH.TOKEN_KEY, response.token);
                localStorage.setItem(config.AUTH.USER_KEY, JSON.stringify(response.user));
            }

            return response;
        } catch (error) {
            console.error('အကောင့်ဝင်ရောက်ရန် မအောင်မြင်ပါ:', error);
            throw error;
        }
    }

    // အကောင့်အသစ်ဖွင့်ခြင်း
    static async register(userData) {
        try {
            return await ApiService.request('/api/auth/register', {
                method: 'POST',
                body: JSON.stringify(userData)
            });
        } catch (error) {
            console.error('အကောင့်ဖွင့်ရန် မအောင်မြင်ပါ:', error);
            throw error;
        }
    }

    // အကောင့်ထွက်ခြင်း
    static async logout() {
        try {
            await ApiService.request('/api/auth/logout', { method: 'POST' });
            localStorage.removeItem(config.AUTH.TOKEN_KEY);
            localStorage.removeItem(config.AUTH.USER_KEY);
            window.location.href = '/login';
        } catch (error) {
            console.error('အကောင့်ထွက်ရန် မအောင်မြင်ပါ:', error);
            throw error;
        }
    }

    // အကောင့်ဝင်ထားခြင်း ရှိ/မရှိ စစ်ဆေးခြင်း
    static isAuthenticated() {
        const token = localStorage.getItem(config.AUTH.TOKEN_KEY);
        const user = localStorage.getItem(config.AUTH.USER_KEY);
        return !!(token && user);
    }
} 