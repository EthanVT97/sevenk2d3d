import { AuthService } from '../services/auth.service.js';
import { showNotification } from '../utils/notification.utils.js';

export class AuthHandler {
    // Login Form ကို စီမံခြင်း
    static async handleLogin(event) {
        event.preventDefault();
        const form = event.target;
        
        try {
            // Loading ပြခြင်း
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> စောင့်ဆိုင်းပါ...';

            const phone = form.phone.value;
            const password = form.password.value;

            // ဖုန်းနံပါတ် စစ်ဆေးခြင်း
            if (!this.validatePhone(phone)) {
                throw new Error('မှန်ကန်သော ဖုန်းနံပါတ် ထည့်သွင်းပါ');
            }

            // အကောင့်ဝင်ရောက်ခြင်း
            await AuthService.login(phone, password);
            
            // အောင်မြင်ပါက dashboard သို့ သွားရန်
            window.location.href = '/dashboard';

        } catch (error) {
            showNotification(error.message || 'အကောင့်ဝင်ရောက်ရန် မအောင်မြင်ပါ', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    // Register Form ကို စီမံခြင်း
    static async handleRegister(event) {
        event.preventDefault();
        const form = event.target;

        try {
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> စောင့်ဆိုင်းပါ...';

            const userData = {
                name: form.name.value,
                phone: form.phone.value,
                password: form.password.value,
                confirmPassword: form.confirmPassword.value
            };

            // ဖုန်းနံပါတ် စစ်ဆေးခြင်း
            if (!this.validatePhone(userData.phone)) {
                throw new Error('မှန်ကန်သော ဖုန်းနံပါတ် ထည့်သွင်းပါ');
            }

            // လျှို့ဝှက်နံပါတ် စစ်ဆေးခြင်း
            if (userData.password !== userData.confirmPassword) {
                throw new Error('လျှို့ဝှက်နံပါတ်များ မတူညီပါ');
            }

            if (userData.password.length < 6) {
                throw new Error('လျှို့ဝှက်နံပါတ်သည် အနည်းဆုံး ၆ လုံး ရှိရပါမည်');
            }

            // အကောင့်ဖွင့်ခြင်း
            await AuthService.register(userData);
            
            showNotification('အကောင့်ဖွင့်ခြင်း အောင်မြင်ပါသည်။ အကောင့်ဝင်ရောက်နိုင်ပါပြီ', 'success');
            
            // Login page သို့ သွားရန်
            window.location.href = '/login';

        } catch (error) {
            showNotification(error.message || 'အကောင့်ဖွင့်ရန် မအောင်မြင်ပါ', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    // ဖုန်းနံပါတ် စစ်ဆေးခြင်း
    static validatePhone(phone) {
        const phoneRegex = /^(09|\+?959)\d{7,9}$/;
        return phoneRegex.test(phone);
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    // Login Form Event
    document.getElementById('loginForm')?.addEventListener('submit', AuthHandler.handleLogin);
    
    // Register Form Event
    document.getElementById('registerForm')?.addEventListener('submit', AuthHandler.handleRegister);
}); 