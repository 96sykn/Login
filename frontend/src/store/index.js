/**
 * ふむ…これは状態管理を司る中枢だね
 * アプリケーション全体の記憶を保持し、操作する場所さ
 */

import { createStore } from 'vuex';
import VuexPersist from 'vuex-persist';

// 記憶を永続化する術を施そう
const vuexPersist = new VuexPersist({
    key: 'faltec',
    storage: window.localStorage,
    // 永続化すべき記憶を選別するのだ
    reducer: state => ({
        userName: state.userName,
        userId: state.userId,
        startline: state.startline,
        gps: state.gps,
        can: state.can
    })
});

// 初期状態を定める…これが全ての始まりとなる
const getDefaultState = () => ({
    userName: "",    // 君の名を記す場所
    userId: "",      // 君を特定する印
    startline: "",   // 始まりの刻
    gps: "",         // 位置の記録
    can: "",         // 車両の記憶
    error: null,     // 過ちの痕跡
    loading: false   // 動作の状態
});

// 変異の種類を定義しよう…状態を変える術の名だ
export const mutationTypes = {
    SET_USER_NAME: 'setUserName',
    SET_USER_ID: 'setUserId',
    SET_STARTLINE: 'setStartline',
    SET_GPS: 'setGps',
    SET_CAN: 'setCan',
    SET_ERROR: 'setError',
    SET_LOADING: 'setLoading',
    RESET_STATE: 'resetState'
};

// 行動の種類を定める…状態を変える命令の名だね
export const actionTypes = {
    SET_USER_NAME: 'setUserName',
    SET_USER_ID: 'setUserId',
    SET_STARTLINE: 'setStartline',
    SET_GPS: 'setGps',
    SET_CAN: 'setCan',
    RESET_STATE: 'resetState',
    LOGIN: 'login'
};

// 記憶の器を創造しよう
const store = createStore({
    state: getDefaultState(),
    
    // 状態を変異させる術
    mutations: {
        // 君の名を刻む
        [mutationTypes.SET_USER_NAME](state, name) {
            state.userName = name;
        },
        // 君の印を記す
        [mutationTypes.SET_USER_ID](state, id) {
            state.userId = id;
        },
        // 始まりの時を記録する
        [mutationTypes.SET_STARTLINE](state, startline) {
            state.startline = startline;
        },
        // 位置の記録を更新
        [mutationTypes.SET_GPS](state, gps) {
            state.gps = gps;
        },
        // 車両の記憶を更新
        [mutationTypes.SET_CAN](state, can) {
            state.can = can;
        },
        // 過ちを記す
        [mutationTypes.SET_ERROR](state, error) {
            state.error = error;
        },
        // 動作の状態を変える
        [mutationTypes.SET_LOADING](state, loading) {
            state.loading = loading;
        },
        // 全てを初めから…記憶を消し去る
        [mutationTypes.RESET_STATE](state) {
            Object.assign(state, getDefaultState());
            localStorage.removeItem('faltec');
        }
    },

    // 非同期の術を執り行う場所
    actions: {
        // 認証の儀式
        async [actionTypes.LOGIN]({ commit }, credentials) {
            try {
                // 認証の請願を送るのだ
                const response = await fetch('https://2024isc1231028.weblike.jp/login/backend/index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(credentials),
                    credentials: 'include'
                });

                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'ログインに失敗しました。');
                }

                // 認証が成功したなら、記憶を更新しよう
                if (data.success) {
                    commit(mutationTypes.SET_USER_ID, credentials.userId);
                    commit(mutationTypes.SET_USER_NAME, data.name);
                    return { success: true };
                } else {
                    throw new Error(data.message || 'ユーザーIDまたはパスワードが正しくありません。');
                }
            } catch (error) {
                console.error('Login error:', error);
                const errorMessage = error.message || 'ログインに失敗しました。もう一度お試しください。';
                commit(mutationTypes.SET_ERROR, errorMessage);
                return {
                    success: false,
                    message: errorMessage
                };
            }
        },

        async [actionTypes.SET_USER_NAME]({ commit }, name) {
            try {
                commit(mutationTypes.SET_LOADING, true);
                commit(mutationTypes.SET_USER_NAME, name);
                commit(mutationTypes.SET_ERROR, null);
            } catch (error) {
                commit(mutationTypes.SET_ERROR, error.message);
                throw error;
            } finally {
                commit(mutationTypes.SET_LOADING, false);
            }
        },

        async [actionTypes.SET_USER_ID]({ commit }, id) {
            try {
                commit(mutationTypes.SET_LOADING, true);
                commit(mutationTypes.SET_USER_ID, id);
                commit(mutationTypes.SET_ERROR, null);
            } catch (error) {
                commit(mutationTypes.SET_ERROR, error.message);
                throw error;
            } finally {
                commit(mutationTypes.SET_LOADING, false);
            }
        },

        async [actionTypes.SET_STARTLINE]({ commit }, startline) {
            try {
                commit(mutationTypes.SET_LOADING, true);
                commit(mutationTypes.SET_STARTLINE, startline);
                commit(mutationTypes.SET_ERROR, null);
            } catch (error) {
                commit(mutationTypes.SET_ERROR, error.message);
                throw error;
            } finally {
                commit(mutationTypes.SET_LOADING, false);
            }
        },

        async [actionTypes.SET_GPS]({ commit }, gps) {
            try {
                commit(mutationTypes.SET_LOADING, true);
                commit(mutationTypes.SET_GPS, gps);
                commit(mutationTypes.SET_ERROR, null);
            } catch (error) {
                commit(mutationTypes.SET_ERROR, error.message);
                throw error;
            } finally {
                commit(mutationTypes.SET_LOADING, false);
            }
        },

        async [actionTypes.SET_CAN]({ commit }, can) {
            try {
                commit(mutationTypes.SET_LOADING, true);
                commit(mutationTypes.SET_CAN, can);
                commit(mutationTypes.SET_ERROR, null);
            } catch (error) {
                commit(mutationTypes.SET_ERROR, error.message);
                throw error;
            } finally {
                commit(mutationTypes.SET_LOADING, false);
            }
        },

        [actionTypes.RESET_STATE]({ commit }) {
            commit(mutationTypes.RESET_STATE);
        }
    },

    // 記憶を取り出す術
    getters: {
        userName: state => state.userName,         // 君の名を呼び出す
        userId: state => state.userId,             // 君の印を確認する
        startline: state => state.startline,       // 始まりの時を知る
        gps: state => state.gps,                   // 位置の記録を読み取る
        can: state => state.can,                   // 車両の記憶を読み取る
        error: state => state.error,               // 過ちを確認する
        isLoading: state => state.loading,         // 動作の状態を知る
        isAuthenticated: state => !!state.userId,  // 君の存在を確認する
        isAdmin: state => state.userId === 'admin001' // 管理者の印を確認する
    },

    plugins: [vuexPersist.plugin]
});

export default store;
