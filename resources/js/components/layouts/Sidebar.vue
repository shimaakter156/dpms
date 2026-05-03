<template>
  <div class="left side-menu">
    <div class="sidebar-inner">
      <!-- Logo Section -->
      <div class="sidebar-logo">
        <img src="public/assets/images/hospital.png" alt="" class="logo-img"/>


        <div class="logo-text">
          <span class="logo-name">DPMS</span>
          <span class="logo-sub">Medical System v2.0</span>
        </div>
      </div>
      <div id="sidebar-menu">
        <ul class="metismenu" id="side-menu">
          <li v-for="parent in menuList" :key="parent.id">
            <template v-if="parent.children && parent.children.length > 1">
              <a href="javascript:void(0);" class="nav-link has-arrow waves-effect">
                <i :class="['nav-icon mdi', 'mdi-' + (parent.icon || 'circle-outline')]"></i>
                <span class="nav-text">{{ parent.name }}</span>
                <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
              </a>
              <ul class="sub-menu">
                <li v-for="child in parent.children" :key="child.id">
                  <router-link
                      v-if="child.url !== parent.url"
                      :to="baseurl()+ child.url"
                      class="nav-link waves-effect"
                  >

                    <span class="sub-menu"></span>{{ child.name }}
                  </router-link>
                </li>
              </ul>
            </template>

            <router-link
                v-else
                :to="baseurl()+parent.url || '#'"
                class="nav-link waves-effect"
            >
              <i :class="['nav-icon mdi', 'mdi-' + (parent.icon || 'circle-outline')]"></i>
              <span class="nav-text">{{ parent.name }}</span>
            </router-link>

          </li>
        </ul>
      </div>

      <!-- Footer logic... -->
    </div>
  </div>
</template>

<script>
import { Common } from '../../mixins/common'
import {baseurl} from "../../base_url";
export default {
  mixins: [Common],
  data() {
    return { personal: {}, type: '',menuList:[] }
  },
  computed: {
    initials() {
      if (!this.personal.name) return 'AD'
      return this.personal.name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
    }
  },
  mounted() {
    setTimeout(() => { $("#side-menu").metisMenu(); }, 1000)
    this.getMenu()
  },
  methods: {
    baseurl() {
      return baseurl
    },
    getMenu() {
      this.axiosGet('get-menu', (response) => {
        const rawMenu = Array.isArray(response) ? response : [];
        this.menuList = rawMenu.map(item => {
          return {
            ...item,
            children: Array.isArray(item.children) ? item.children : []
          };
        });

        this.$nextTick(() => {
          const menu = $("#side-menu");
          if (menu.length > 0) {
            if (menu.data('mm')) {
              menu.metisMenu('dispose');
            }
            menu.metisMenu();
          }
        });
      }, (error) => {
        console.error("Sidebar Error:", error);
      });
    }
  }
}
</script>

<style scoped>
/* ── Base ── */
.left.side-menu {
  width: 200px;
  background: #1a2035;
  height: 100vh;
  position: fixed;
  top: 0; left: 0;
  display: flex;
  flex-direction: column;
  z-index: 100;
  transition: width 0.25s ease;
  overflow: hidden;
}
.sidebar-inner {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: none;
  width: 200px; /* keeps inner from shrinking */
}
.sidebar-inner::-webkit-scrollbar { display: none; }

/* ── Logo ── */
.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  text-decoration: none;
  flex-shrink: 0;
  white-space: nowrap;
  overflow: hidden;
}
.logo-img {
  height: 36px;
  width: 36px;
  object-fit: contain;
  flex-shrink: 0;
  border-radius: 8px;
}
.logo-name { display: block; color: #fff; font-size: 14px; font-weight: 700; }
.logo-sub  { display: block; color: #5a6a8a; font-size: 10px; margin-top: 1px; }

/* ── Section labels ── */
.menu-section-label {
  font-size: 10px !important;
  font-weight: 600;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  color: #4a5878 !important;
  padding: 18px 20px 6px !important;
  pointer-events: none;
  white-space: nowrap;
  overflow: hidden;
}

/* ── Nav links ── */
.nav-link {
  display: flex !important;
  align-items: center;
  gap: 11px;
  padding: 10px 20px !important;
  color: #a8b4cf !important;
  font-size: 13.5px !important;
  font-weight: 500;
  border-left: 2.5px solid transparent;
  transition: background 0.18s, color 0.18s;
  white-space: nowrap;
  overflow: hidden;
}
.nav-link:hover { background: rgba(255,255,255,0.05) !important; color: #d4daea !important; }
.nav-link.router-link-active,
.nav-link.active {
  background: rgba(79,126,248,0.18) !important;
  color: #fff !important;
  border-left-color: #4f7ef8;
}
.nav-icon {
  font-size: 18px;
  width: 22px;
  text-align: center;
  flex-shrink: 0;
  opacity: 0.75;
}
.nav-link.router-link-active .nav-icon { opacity: 1; }
.nav-text { flex: 1; }
.menu-arrow { margin-left: auto; font-size: 13px; opacity: 0.4; transition: transform 0.22s; flex-shrink: 0; }

/* ── Submenu ── */
.sub-menu { background: rgba(0,0,0,0.15); list-style: none; padding: 0; }
.sub-menu li a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 20px 8px 50px;
  color: #7a8aaa;
  font-size: 13px;
  text-decoration: none;
  border-left: 2.5px solid transparent;
  transition: background 0.15s, color 0.15s;
  white-space: nowrap;
}
.sub-menu li a:hover { background: rgba(255,255,255,0.04); color: #c0cce0; }
.sub-menu li a.router-link-active {
  color: #4f7ef8;
  background: rgba(79,126,248,0.1);
  border-left-color: #4f7ef8;
  font-weight: 500;
}
.sub-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; opacity: 0.5; flex-shrink: 0; }
.sub-menu li a.router-link-active .sub-dot { opacity: 1; }

/* ── Footer ── */
.sidebar-footer {
  margin-top: auto;
  border-top: 1px solid rgba(255,255,255,0.06);
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
  white-space: nowrap;
  overflow: hidden;
}
.user-avatar {
  width: 34px; height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, #4f7ef8, #7c5fe8);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 600; color: #fff;
  flex-shrink: 0;
}
.user-name { color: #fff; font-size: 13px; font-weight: 500; }
.user-role { color: #5a6a8a; font-size: 11px; margin-top: 1px; }
</style>