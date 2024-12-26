const config = {
    // API ဆာဗာလိပ်စာ
    API_URL: 'https://twod3d-lottery-api-q68w.onrender.com',
    API_BASE_URL: 'https://twod3d-lottery-api-q68w.onrender.com/api',
    APP_ENV: 'production',
    
    // API Endpoints
    ENDPOINTS: {
        AUTH: {
            LOGIN: '/auth/login',
            REGISTER: '/auth/register',
            LOGOUT: '/auth/logout',
            VERIFY: '/auth/verify'
        },
        LOTTERY: {
            LIVE: '/lottery/live',
            HISTORY: '/lottery/history'
        }
    },
    
    // အနိမ့်ဆုံးထိုးငွေနှင့် ငွေသွင်း/ထုတ်ကန့်သတ်ချက်များ
    MIN_BET_AMOUNT: 100,
    MIN_DEPOSIT_AMOUNT: 1000,
    MAX_WITHDRAW_AMOUNT: 1000000,
    
    // ထီပိတ်ချိန်များ
    CLOSE_TIMES: {
        '2D': {
            MORNING: '10:30',  // မနက်ပိုင်း
            EVENING: '16:30'   // ညနေပိုင်း
        },
        '3D': {
            DAILY: '16:30'     // နေ့စဉ်
        }
    },
    
    // အကောင့်ဝင်ရောက်မှုဆိုင်ရာ
    AUTH: {
        TOKEN_KEY: 'auth_token',
        USER_KEY: 'user_data',
        SESSION_DURATION: 86400 // ၂၄ နာရီ (စက္ကန့်ဖြင့်)
    },

    // Performance settings
    REFRESH_INTERVAL: 30000, // 30 seconds
    RATE_LIMIT: {
        REQUESTS_PER_MINUTE: 60
    }
};

export default config;
