<!-- 
  ふむ…これはログインを司るコンポーネントだね
  認証という儀式を執り行う、重要な存在さ
-->
<template>
    <div class="form-container">
        <!-- 認証の扉を開くためのフォームだ -->
        <form @submit.prevent="login" novalidate>
            <!-- 過ちがあれば、此処に警告を示そう -->
            <div v-if="error" class="error-message" role="alert">
                {{ error }}
            </div>
            
            <!-- 君の身分を証明する識別子を記すのだ -->
            <div class="form-group">
                <label for="userId">ユーザーID</label>
                <input
                    type="text"
                    id="userId"
                    v-model="userId"
                    class="form-control"
                    :class="{ 'is-invalid': userIdError }"
                    @input="validateUserId"
                    placeholder="8桁の英数字"
                    required
                />
            </div>

            <!-- 秘めたる鍵を託す場所だね -->
            <div class="form-group">
                <label for="password">パスワード</label>
                <div class="password-field">
                    <input
                        :type="passVis ? 'text' : 'password'"
                        id="password"
                        v-model="password"
                        class="form-control"
                        :class="{ 'is-invalid': passwordError }"
                        @input="validatePassword"
                        placeholder="8文字以上の英数字"
                        required
                    />
                    <!-- 秘密の扉を開け閉めする仕掛けさ -->
                    <button
                        type="button"
                        class="password-toggle"
                        @click="passVis = !passVis"
                        :aria-label="passVis ? 'パスワードを隠す' : 'パスワードを表示'"
                    >
                        <i :class="passVis ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                </div>
            </div>

            <!-- さあ、扉の向こうへ進もう -->
            <div class="form-group">
                <button
                    type="submit"
                    class="btn btn-primary w-100"
                    :disabled="!isFormValid || isLoading"
                >
                    {{ isLoading ? 'ログイン中...' : 'ログイン' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import axios from "axios";
import { mapGetters, mapMutations } from "vuex";
import { actionTypes } from '@/store';
import '@/assets/style.css';

export default {
    data() {
        return {
            userId: "",          // 君を特定する識別子だ
            password: "",        // 扉を開くための暗号さ
            error: "",          // 過ちを記す場所
            userIdError: "",    // 識別子の誤りを示すものだね
            passwordError: "",   // 暗号の不備を伝える印
            passVis: false      // 暗号の可視性を司る旗
        };
    },
    computed: {
        ...mapGetters(['isLoading']),
        // 全ての条件が整ったか確認するのだ
        isFormValid() {
            return this.userId && 
                   this.password && 
                   !this.userIdError && 
                   !this.passwordError;
        }
    },
    methods: {
        ...mapMutations(["setUserName", "setStartline"]),

        // 識別子の正当性を吟味しよう
        validateUserId() {
            if (!this.userId) {
                this.error = 'ユーザーIDを入力してください';
                return false;
            }
            if (!/^\w{8}$/.test(this.userId)) {
                this.error = 'ユーザーIDは8桁の英数字で入力してください';
                return false;
            }
            this.error = '';
            return true;
        },

        // 暗号の強度を確かめるのだ
        validatePassword() {
            if (!this.password) {
                this.error = 'パスワードを入力してください';
                return false;
            }
            if (this.password.length < 8) {
                this.error = 'パスワードは8文字以上で入力してください';
                return false;
            }
            this.error = '';
            return true;
        },

        // 認証の儀式を執り行おう
        async login() {
            if (!this.validateUserId() || !this.validatePassword()) return;
            
            this.$store.dispatch(actionTypes.SET_LOADING, true);
            this.error = "";

            try {
                // 認証の請願を送信するのだ
                const response = await axios.post(
                    "https://2024isc1231028.weblike.jp/login/backend/index.php",
                    {
                        userId: this.userId,
                        password: this.password,
                    }
                );
                if (response.data.success) {
                    // 君の存在を記録しよう
                    await this.$store.dispatch(actionTypes.SET_USER_ID, this.userId);
                    
                    // 未登録の場合は登録の間へ案内しよう
                    if(response.data.name==""){
                        this.$router.push("/register");
                        return;
                    }
                    
                    // 君の名を刻むのだ
                    this.setUserName(response.data.name);
                    
                    // 管理者なら、その座に相応しい場所へ
                    if (this.userId == 'admin001') {
                        this.$router.push("/admin");
                        return;
                    }
                    
                    // さあ、目的の場所へ案内しよう
                    const redirectPath = this.$route.query.redirect;
                    if (redirectPath) {
                        this.$router.push(redirectPath);
                    } else {
                        await this.getStartline();
                    }
                } else {
                    this.error = "ユーザーIDまたはパスワードが正しくありません";
                }
            } catch (error) {
                this.error = "ログインに失敗しました。しばらく時間をおいて再度お試しください。";
                console.error('Login error:', error);
            } finally {
                this.$store.dispatch(actionTypes.SET_LOADING, false);
            }
        },

        // 始まりの時を探り当てよう
        async getStartline() {
            try {
                const response = await axios.post(
                    "https://2024isc1231028.weblike.jp/login/backend/startline.php",
                    {
                        userId: this.userId,
                    }
                );
                if (response.data.success) {
                    this.setStartline(response.data.startline);
                    this.$router.push("/dashboard");
                } else {
                    this.error = response.data.message;
                }
            } catch (error) {
                this.error = "サーバーとの通信に失敗しました。もう一度お試しください。";
            }
        },
    },
};
</script>

<style scoped>
.form-container {
    width: 100%;
    max-width: 400px;
    margin: 20px auto;
    padding: 2rem;
    background: var(--card-background);
    border-radius: 1rem;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05),
                0 8px 10px -6px rgba(0, 0, 0, 0.03);
    transition: transform 0.2s ease;
}

@media (max-width: 480px) {
    .form-container {
        margin: 10px;
        padding: 1.5rem;
        border-radius: 0.75rem;
    }
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-primary);
}

.password-field {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 0.5rem;
    transition: color 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    min-height: 2rem;
    z-index: 1;
}

.password-toggle i {
    font-size: 1.2rem;
    line-height: 1;
}

@media (max-width: 480px) {
    .password-toggle {
        padding: 0.75rem; /* タップ領域を広げる */
    }
}

.password-toggle:hover {
    color: var(--text-primary);
}

button[type="submit"] {
    width: 100%;
    padding: 0.875rem;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.2),
                0 2px 4px -2px rgba(99, 102, 241, 0.1);
}

@media (max-width: 480px) {
    button[type="submit"] {
        padding: 1rem;
        margin-top: 1rem;
    }
}

button[type="submit"]:not(:disabled):hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 8px -1px rgba(99, 102, 241, 0.3),
                0 4px 6px -2px rgba(99, 102, 241, 0.2);
}

@media (hover: none) {
    button[type="submit"]:not(:disabled):hover {
        transform: none;
    }
}

button[type="submit"]:disabled {
    background: var(--text-secondary);
    cursor: not-allowed;
    transform: none;
}

.is-invalid {
    border-color: var(--error-color);
}

.is-invalid:focus {
    border-color: var(--error-color);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}
</style>
