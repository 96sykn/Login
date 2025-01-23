import { createApp } from 'vue';
import App from './App.vue';
import axios from 'axios';
import router from './router';
import store from './store';
import { mutationTypes } from './store';

// Axiosのデフォルト設定
axios.defaults.baseURL = 'https://2024isc1231028.weblike.jp/login/backend';
axios.defaults.headers.common['Content-Type'] = 'application/json';
axios.defaults.timeout = 10000; // 10秒

// インターセプターの設定
axios.interceptors.request.use(
    config => {
        store.commit(mutationTypes.SET_LOADING, true);
        return config;
    },
    error => {
        store.commit(mutationTypes.SET_LOADING, false);
        store.commit(mutationTypes.SET_ERROR, error.message);
        return Promise.reject(error);
    }
);

axios.interceptors.response.use(
    response => {
        store.commit(mutationTypes.SET_LOADING, false);
        return response;
    },
    error => {
        store.commit(mutationTypes.SET_LOADING, false);
        let errorMessage = '通信エラーが発生しました。';
        
        if (error.response) {
            switch (error.response.status) {
                case 401:
                    errorMessage = '認証エラー：ログインが必要です。';
                    router.push('/');
                    break;
                case 403:
                    errorMessage = 'アクセス権限がありません。';
                    break;
                case 404:
                    errorMessage = 'リソースが見つかりません。';
                    break;
                case 500:
                    errorMessage = 'サーバーエラーが発生しました。';
                    break;
                default:
                    errorMessage = `エラーが発生しました。(${error.response.status})`;
            }
        } else if (error.request) {
            errorMessage = 'サーバーに接続できません。';
        }

        store.commit(mutationTypes.SET_ERROR, errorMessage);
        return Promise.reject(error);
    }
);

const app = createApp(App);

// グローバルエラーハンドラー
app.config.errorHandler = (err) => {
    console.error('グローバルエラー:', err);
    store.commit(mutationTypes.SET_ERROR, err.message);
};

// プロパティの設定
app.config.globalProperties.$axios = axios;

// プラグインの使用
app.use(router);
app.use(store);

// アプリケーションのマウント
app.mount('#app');