<template>
  <header id="topbar" class="topbar">

<!--     Brand (mirrors sidebar header) -->
<!--   <div class="tb-brand">-->
<!--      <div class="logo-icon">-->
<!--        <img style=" height: 40px;weight:40;" src="public/assets/images/hospital.png" alt="">-->
<!--      </div>-->
<!--      <div>-->
<!--&lt;!&ndash;        <div class="logo-name">DPMS </div>&ndash;&gt;-->
<!--&lt;!&ndash;        <div class="logo-sub">Medical System v2.0 mini</div>&ndash;&gt;-->
<!--      </div>-->
<!--    </div>-->

    <!-- Hamburger + Page title -->
    <div class="tb-center">
      <button @click="toggleSidebar" class="tb-toggle">
        <i class="mdi mdi-menu"></i>
      </button>
      <div class="tb-title">
        <h1>{{ pageTitle }}</h1>
        <p>{{ pageSubtitle }}</p>
      </div>
    </div>

    <!-- Actions -->
    <div class="tb-actions">
      <router-link
          v-if="me.RoleCode==='DOCTOR'"
          :to="`${mainOrigin}prescriptions/new`"
          target="_blank"
          class="btn-new-rx d-none d-md-inline-flex">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
        </svg>
        New Prescription
      </router-link>

      <div class="dropdown">
        <a class="tb-avatar dropdown-toggle"
           data-toggle="dropdown" href="#"
           role="button" aria-haspopup="false" aria-expanded="false">
          {{ initials }}
        </a>
        <div class="dropdown-menu dropdown-menu-right profile-dropdown">
          <router-link class="dropdown-item" :to="`${mainOrigin}profile`">
            <i class="mdi mdi-account-circle"></i> Profile
          </router-link>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item text-danger" @click.prevent="logout" href="#">
            <i class="mdi mdi-power"></i> Logout
          </a>
        </div>
      </div>
    </div>

  </header>
</template>

<script>
import { Common } from '../../mixins/common'
export default {
  mixins: [Common],
  props: {
    showNewRx: { type: Boolean, default: true }
  },
  state: {
    me: null
  },
  mutations: {
    me(state, payload) {
      state.me = payload
    },

  },
  data(){
    return{
      user : ''
    }
  },
  computed: {
    me() {

      return this.$store.state.me || {}
    },

    initials() {
      return (this.me.Name || 'AD').split(' ').slice(0,2).map(w => w[0].toUpperCase()).join('')
    },
    pageTitle() {
      return this.$route.meta?.title || this.$route.name || 'Dashboard'
    },
    pageSubtitle() {
      return this.$route.meta?.subtitle || 'Overview & quick stats'
    }
  },
  created() { this.getData() },
  methods: {
    toggleSidebar() { $("body").toggleClass("enlarged") },
    // getData() {
    //   this.axiosPost('me', {}, (response) => {
    //     this.$store.commit('me', response)
    //   }, (error) => { this.errorNoti(error) })
    // },
      clearMe(state) {
        state.me = null
      },
    logout() {
      this.axiosPost("logout", {}, () => {
        localStorage.setItem("token", "")
        localStorage.removeItem('user')
        this.clearMe();
        this.$router.push(this.mainOrigin + "login")
      }, (error) => { this.errorNoti(error) })
    }
  }
}
</script>

<style scoped>
#topbar.topbar {
  height: 58px;
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  padding: 0;
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
  position: sticky;
  top: 0;
  z-index: 100;
}

/* Brand */
.tb-brand {
  width: 71px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 16px;
  border-right: 1px solid #e2e8f0;
  height: 100%;
  text-decoration: none;
}
.logo-icon {
  width: 34px; height: 34px;
  background: linear-gradient(135deg, #4f7ef8, #7c5fe8);
  border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 16px;
}
.logo-name { font-size: 14px; font-weight: 700; color: #1a2035; }
.logo-sub  { font-size: 10px; color: #94a3b8; margin-top: 1px; }

/* Center */
.tb-center {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 0 40px;
  flex: 1;
  min-width: 0;
}
.tb-toggle {
  background: transparent;
  border: 1.5px solid #e2e8f0;
  border-radius: 6px;
  width: 34px; height: 34px;
  display: flex; align-items: center; justify-content: center;
  color: #4b5563;
  font-size: 18px;
  cursor: pointer;
  transition: all .14s;
}
.tb-toggle:hover { background: #f3f6fa; color: #4f7ef8; border-color: #c7d9f8; }
.tb-title h1 { font-size: 15px; font-weight: 700; color: #111827; margin: 0; }
.tb-title p  { font-size: 11px; color: #9ca3af; margin: 2px 0 0; }

/* Actions */
.tb-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 20px;
  flex-shrink: 0;
}
.btn-new-rx {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: #0b8a79;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: background .14s, box-shadow .14s;
}
.btn-new-rx:hover { background: #0fa595; box-shadow: 0 3px 10px rgba(11,138,121,.35); color: #fff; }

.tb-avatar {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #4f7ef8, #7c5fe8);
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 12px; font-weight: 700;
  border: 2px solid #e2e8f0;
  cursor: pointer;
  transition: border-color .14s;
}
.tb-avatar:hover { border-color: #4f7ef8; }
.tb-brand {
  width: 220px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 16px;
  border-right: 1px solid #e2e8f0;
  height: 100%;
  text-decoration: none;
  background: #1a2035 !important; /* ← change this, it's probably green or missing */
}
/* Dropdown */
.profile-dropdown {
  border: 1px solid #e2e8f0;
  border-radius: 9px;
  box-shadow: 0 6px 24px rgba(0,0,0,.12);
  padding: 6px 0;
  min-width: 170px;
}
.profile-dropdown .dropdown-item {
  padding: 8px 14px;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.profile-dropdown .dropdown-item:hover { background: #f3f6fa; color: #4f7ef8; }
.profile-dropdown .dropdown-item.text-danger:hover { background: #fee2e2; color: #b91c1c !important; }
</style>