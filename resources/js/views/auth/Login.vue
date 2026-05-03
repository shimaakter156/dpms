<template>
  <div class="login-page">
    <div class="login-card">

      <!-- Header -->
      <div class="lc-header">
        <div class="lc-logo-row">
          <div class="lc-logo-icon">
            <img src="public/assets/images/hospital.png" alt="" style="height:28px;"/>
          </div>
          <div>
            <div class="lc-logo-name">DPMS</div>
            <div class="lc-logo-sub">Medical System v2.0</div>
          </div>
        </div>
        <h1 class="lc-title">Welcome back</h1>
        <p class="lc-sub">Sign in to your account to continue</p>
        <span class="lc-rx-watermark">R</span>
      </div>
      <div class="lc-teal-bar"></div>

      <!-- Body -->
      <div class="lc-body">
        <ValidationObserver v-slot="{ handleSubmit }">
          <form @submit.prevent="handleSubmit(onSubmit)">

            <ValidationProvider name="User ID" mode="eager" rules="required" v-slot="{ errors }">
              <div class="lc-form-group">
                <label class="lc-label">User ID</label>
                <div class="lc-input-wrap">
                  <span class="lc-icon"><i class="mdi mdi-account-outline"></i></span>
                  <input
                      type="text"
                      class="lc-input"
                      :class="{ 'lc-input-error': errors[0] }"
                      v-model="username"
                      placeholder="Enter your user ID"
                      autocomplete="off"
                  />
                </div>
                <span class="lc-error">{{ errors[0] }}</span>
              </div>
            </ValidationProvider>

            <ValidationProvider name="Password" mode="eager" rules="required|min:4" v-slot="{ errors }">
              <div class="lc-form-group">
                <label class="lc-label">Password</label>
                <div class="lc-input-wrap">
                  <span class="lc-icon"><i class="mdi mdi-lock-outline"></i></span>
                  <input
                      type="password"
                      class="lc-input"
                      :class="{ 'lc-input-error': errors[0] }"
                      v-model="password"
                      placeholder="Enter your password"
                  />
                </div>
                <span class="lc-error">{{ errors[0] }}</span>
              </div>
            </ValidationProvider>

            <button type="submit" class="lc-btn" :disabled="loading">
              <span v-if="loading"><i class="mdi mdi-loading mdi-spin"></i> Logging in...</span>
              <span v-else>Log In</span>
            </button>

          </form>
        </ValidationObserver>

        <p class="lc-footer-note">Secure medical system &nbsp;·&nbsp; <span>DPMS</span></p>
      </div>

    </div>
  </div>
</template>

<script>
import { Common } from '../../mixins/common'
import { projectName } from '../../base_url'

export default {
  mixins: [Common],
  data() {
    return {
      username: '',
      password: '',
      loading: false,
    }
  },
  methods: {
    onSubmit() {
      this.loading = true
      this.$store.commit('submitButtonLoadingStatus', true)
      this.axiosPostWithoutToken('login', {
        username: this.username,
        password: this.password
      }, (response) => {
        localStorage.setItem('token', response.access_token)
        this.successNoti('Successfully logged in.')
        this.$store.commit('submitButtonLoadingStatus', false)
        this.loading = false
        this.redirect(this.mainOrigin + 'dashboard')
      }, (error) => {
        this.errorNoti(error)
        this.$store.commit('submitButtonLoadingStatus', false)
        this.loading = false
      })
    }
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #1a2035 0%, #1a3a5c 60%, #0b8a79 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.login-card {
  width: 100%;
  max-width: 420px;
  background: #fff;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

/* Header */
.lc-header {
  background: linear-gradient(135deg, #1a2035, #1a3a5c);
  padding: 32px 32px 28px;
  position: relative;
  overflow: hidden;
}
.lc-rx-watermark {
  position: absolute;
  right: 24px; top: 16px;
  font-size: 52px; font-weight: 700;
  color: rgba(11,138,121,0.35);
  font-style: italic; line-height: 1;
}
.lc-logo-row {
  display: flex; align-items: center; gap: 12px;
  margin-bottom: 18px;
}
.lc-logo-icon {
  width: 44px; height: 44px;
  border-radius: 10px;
  background: linear-gradient(135deg, #4f7ef8, #7c5fe8);
  display: flex; align-items: center; justify-content: center;
  overflow: hidden; flex-shrink: 0;
}
.lc-logo-name { font-size: 18px; font-weight: 700; color: #fff; }
.lc-logo-sub  { font-size: 11px; color: rgba(255,255,255,0.5); margin-top: 2px; }
.lc-title { font-size: 22px; font-weight: 700; color: #fff; margin-bottom: 4px; }
.lc-sub   { font-size: 13px; color: rgba(255,255,255,0.55); }

.lc-teal-bar { height: 3px; background: linear-gradient(90deg, #0b8a79, #4f7ef8); }

/* Body */
.lc-body { padding: 28px 32px 32px; }

.lc-form-group { margin-bottom: 18px; }
.lc-label {
  display: block;
  font-size: 11px; font-weight: 600;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  color: #6b7280;
  margin-bottom: 7px;
}
.lc-input-wrap { position: relative; }
.lc-icon {
  position: absolute; left: 12px; top: 50%;
  transform: translateY(-50%);
  color: #9ca3af; font-size: 17px;
  display: flex; align-items: center;
}
.lc-input {
  width: 100%; height: 42px;
  border: 1.5px solid #e5e7eb;
  border-radius: 8px;
  padding: 0 12px 0 38px;
  font-size: 14px; color: #111827;
  outline: none;
  transition: border-color .15s, box-shadow .15s;
}
.lc-input:focus { border-color: #4f7ef8; box-shadow: 0 0 0 3px rgba(79,126,248,0.12); }
.lc-input-error { border-color: #ec4561 !important; }
.lc-input::placeholder { color: #d1d5db; }
.lc-error { font-size: 12px; color: #ec4561; margin-top: 5px; display: block; }

.lc-btn {
  width: 100%; height: 44px;
  background: linear-gradient(135deg, #0b8a79, #0fa595);
  color: #fff; border: none;
  border-radius: 8px;
  font-size: 14px; font-weight: 600;
  cursor: pointer;
  transition: opacity .15s, transform .1s;
  margin-top: 8px;
  letter-spacing: 0.3px;
}
.lc-btn:hover:not(:disabled) { opacity: 0.9; }
.lc-btn:active:not(:disabled) { transform: scale(0.98); }
.lc-btn:disabled { opacity: 0.6; cursor: not-allowed; }

.lc-footer-note {
  text-align: center;
  font-size: 12px; color: #9ca3af;
  margin-top: 20px;
}
.lc-footer-note span { color: #0b8a79; font-weight: 600; }
</style>