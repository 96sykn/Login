<!-- 
  ふむ…これは新たな訪問者を迎え入れる場所だね
  君の情報を記録し、正式な参加者として認める儀式を執り行う場所さ
-->
<template>
    <div class="content">
        <h2 class="page-title">ユーザー登録</h2>
        <p class="instruction">氏名と新しいパスワードを設定して下さい。</p>
        <div class="form-container">
            <!-- 登録の儀を執り行うための祭壇だ -->
            <form @submit.prevent="register" novalidate class="register-form">
                <!-- 過ちがあれば、此処に警告を示そう -->
                <div v-if="error" class="error-message text-center" role="alert">
                    {{ error }}
                </div>
                <!-- 君の真なる名を記す場所だ -->
                <div class="form-group">
                    <label for="name">氏名</label>
                    <input 
                        type="text" 
                        id="name"
                        v-model="name" 
                        class="form-control"
                        :class="{ 'is-invalid': nameError }"
                        @input="validateName"
                        placeholder="氏名を入力"
                        required
                    />
                    <div v-if="nameError" class="error-message" role="alert">
                        {{ nameError }}
                    </div>
                </div>
                <!-- 君の新しいパスワードを設定するのだ -->
                <div class="form-group">
                    <label for="password">新規パスワード</label>
                    <div class="password-container">
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
                        <button 
                            type="button" 
                            @click="togglePassVis"
                            class="password-toggle"
                        >
                            {{ passVis ? '非表示' : '表示' }}
                        </button>
                    </div>
                    <div v-if="passwordError" class="error-message" role="alert">
                        {{ passwordError }}
                    </div>
                </div>
                <!-- 君の新しいパスワードを再入力するのだ -->
                <div class="form-group">
                    <label for="confirmPassword">パスワード確認</label>
                    <div class="password-container">
                        <input 
                            :type="passVis ? 'text' : 'password'" 
                            id="confirmPassword"
                            v-model="confirmPassword" 
                            class="form-control"
                            :class="{ 'is-invalid': confirmPasswordError }"
                            @input="validateConfirmPassword"
                            placeholder="パスワードを再入力"
                            required
                        />
                    </div>
                    <div v-if="confirmPasswordError" class="error-message" role="alert">
                        {{ confirmPasswordError }}
                    </div>
                </div>
                <!-- パスワードの要件を示すのだ -->
                <div class="password-requirements">
                    <h4>パスワード要件：</h4>
                    <ul>
                        <li :class="{ 'requirement-met': hasMinLength }">8文字以上</li>
                        <li :class="{ 'requirement-met': hasUpperCase }">大文字を含む</li>
                        <li :class="{ 'requirement-met': hasLowerCase }">小文字を含む</li>
                        <li :class="{ 'requirement-met': hasNumber }">数字を含む</li>
                    </ul>
                </div>
                <!-- さあ、記録の儀を執り行おう -->
                <div class="form-group">
                    <button 
                        type="submit" 
                        class="btn btn-primary w-100"
                        :disabled="!isFormValid || isLoading"
                    >
                        {{ isLoading ? '登録中...' : '登録' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import { mapGetters } from 'vuex';
import { actionTypes } from '@/store';
import '@/assets/style.css';

export default {
    data() {
        return {
            name: "",           // 君の真なる名
            error: "",         // 過ちの記録
            nameError: "",     // 名前の誤りを示す印
            password: "",      // 君の新しいパスワード
            confirmPassword: "", // 君の新しいパスワードの再入力
            passwordError: "", // パスワードの誤りを示す印
            confirmPasswordError: "", // パスワードの再入力の誤りを示す印
            passVis: false,    // パスワードの表示非表示
            isLoading: false   // ローディング中の印
        };
    },
    computed: {
        ...mapGetters(["userId", "isLoading", "error"]),
        
        // パスワードの要件を満たしているか確認するのだ
        hasMinLength() {
            return this.password.length >= 8;
        },
        hasUpperCase() {
            return /[A-Z]/.test(this.password);
        },
        hasLowerCase() {
            return /[a-z]/.test(this.password);
        },
        hasNumber() {
            return /[0-9]/.test(this.password);
        },
        // 全ての条件が整ったか確認するのだ
        isFormValid() {
            return this.name && 
                   this.password && 
                   this.confirmPassword &&
                   !this.nameError && 
                   !this.passwordError && 
                   !this.confirmPasswordError &&
                   this.hasMinLength &&
                   this.hasUpperCase &&
                   this.hasLowerCase &&
                   this.hasNumber;
        }
    },
    methods: {
        // 名前の正当性を吟味しよう
        validateName() {
            const nameRegex = /^[a-zA-Z0-9]+$/;
            if (!this.name.trim()) {
                this.nameError = "氏名を入力してください";
                return;
            }
            if (!nameRegex.test(this.name)) {
                this.nameError = "氏名は半角英数字のみ使用できます";
                return;
            }
            this.nameError = "";
        },

        // パスワードの正当性を吟味しよう
        validatePassword() {
            if (!this.hasMinLength) {
                this.passwordError = "パスワードは8文字以上である必要があります";
            } else if (!this.hasUpperCase || !this.hasLowerCase || !this.hasNumber) {
                this.passwordError = "パスワードは大文字、小文字、数字を含める必要があります";
            } else {
                this.passwordError = "";
            }
            this.validateConfirmPassword();
        },

        // パスワードの再入力の正当性を吟味しよう
        validateConfirmPassword() {
            if (this.password !== this.confirmPassword) {
                this.confirmPasswordError = "パスワードが一致しません";
            } else {
                this.confirmPasswordError = "";
            }
        },

        // パスワードの表示非表示を切り替えるのだ
        togglePassVis() {
            this.passVis = !this.passVis;
        },

        // 登録の儀を執り行おう
        async register() {
            if (!this.isFormValid) return;
            
            this.isLoading = true;
            this.error = "";

            try {
                // 登録の請願を送信するのだ
                const response = await axios.post(
                    "https://2024isc1231028.weblike.jp/login/backend/register.php",
                    {
                        userId: this.userId,
                        name: this.name,
                        password: this.password
                    }
                );
                if (response.data.success) {
                    // 登録が完了したら、君の活動の場へ案内しよう
                    await this.$store.dispatch(actionTypes.SET_USER_NAME, this.name);
                    await this.$store.dispatch(actionTypes.SET_STARTLINE);
                    this.$router.push("/dashboard");
                } else {
                    this.error = response.data.message;
                }
            } catch (error) {
                this.error = "サーバーとの通信に失敗しました。もう一度お試しください。";
            } finally {
                this.isLoading = false;
            }
        },
    },
};
</script>

<style scoped>
.page-title {
    color: var(--secondary-color);
    text-align: center;
    margin-bottom: 1rem;
}

.instruction {
    text-align: center;
    color: var(--text-color);
    margin-bottom: 2rem;
}

.register-form {
    animation: fadeIn 0.5s ease-out;
}

.password-requirements {
    margin: 1rem 0;
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.password-requirements h4 {
    color: var(--secondary-color);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.password-requirements ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.password-requirements li {
    font-size: 0.85rem;
    color: var(--text-color);
    margin: 0.25rem 0;
    padding-left: 1.5rem;
    position: relative;
}

.password-requirements li::before {
    content: "×";
    position: absolute;
    left: 0;
    color: var(--danger-color);
}

.password-requirements li.requirement-met::before {
    content: "✓";
    color: var(--success-color);
}

.loading {
    opacity: 0.7;
    cursor: not-allowed;
}

.w-100 {
    width: 100%;
}

.text-center {
    text-align: center;
}

.is-invalid {
    border-color: var(--error-color);
}

.is-invalid:focus {
    box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
