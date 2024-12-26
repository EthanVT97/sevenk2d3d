const config = {
    // API ဆာဗာလိပ်စာ
    API_URL: process.env.REACT_APP_API_URL || 'https://twod3d-lottery-api-q68w.onrender.com',
    APP_ENV: process.env.NODE_ENV || 'development',
    
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
    }
};

export default config; 