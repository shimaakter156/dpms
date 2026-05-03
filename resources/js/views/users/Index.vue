<template>
  <div class="container-fluid dash-wrap">
    <!-- Page title -->
    <div class="page-title-box">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h4 class="page-title">Dashboard</h4>
          <p class="page-subtitle">Overview &amp; quick stats</p>
        </div>
      </div>
    </div>

    <!-- Welcome banner -->
    <div class="card welcome-card">
      <div class="card-body" v-if="!isLoading">
        <div class="welcome-text">
          <span class="welcome-greeting">Welcome to</span>
          <strong class="welcome-name">{{ projectName() }}</strong>
        </div>
      </div>
      <div class="card-body" v-else>
        <skeleton-loader :row="4"/>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row" v-if="!isLoading">
      <div class="stat-card">
        <div class="stat-icon si-blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <div>
          <div class="stat-val">{{ stats.patients }}</div>
          <div class="stat-lbl">Total Patients</div>
          <div class="stat-sub stat-pos">Registered in system</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon si-teal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
            <rect x="9" y="3" width="6" height="4" rx="1"/>
            <path d="M9 12h6M9 16h4"/>
          </svg>
        </div>
        <div>
          <div class="stat-val">{{ stats.prescriptions }}</div>
          <div class="stat-lbl">Prescriptions</div>
          <div class="stat-sub stat-info">{{ stats.todays_rx }} today</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon si-amber">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
          </svg>
        </div>
        <div>
          <div class="stat-val">{{ stats.medicines }}</div>
          <div class="stat-lbl">Medicine Types</div>
          <div :class="['stat-sub', stats.low_stock > 0 ? 'stat-warn' : 'stat-pos']">
            <template v-if="stats.low_stock > 0">⚠ {{ stats.low_stock }} low stock</template>
            <template v-else>✓ All stocked</template>
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon si-purple">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/>
          </svg>
        </div>
        <div>
          <div class="stat-val">{{ stats.investigations }}</div>
          <div class="stat-lbl">Investigations</div>
          <div class="stat-sub stat-info">{{ stats.results }} results</div>
        </div>
      </div>
    </div>

    <!-- Two-column dashboard -->
    <div class="dash-cols" v-if="!isLoading">
      <!-- LEFT: Recent Patients + Low Stock -->
      <div>
        <div class="card">
          <div class="card-head">
            <h3>Recent Patients</h3>
            <router-link :to="`${mainOrigin}patients`" class="btn btn-outline btn-sm">View All →</router-link>
          </div>
          <div class="tbl-wrap">
            <table class="tbl">
              <thead>
              <tr>
                <th>Patient</th>
                <th>Age/Sex</th>
                <th>Blood</th>
                <th>Phone</th>
                <th>Registered</th>
              </tr>
              </thead>
              <tbody>
              <tr v-if="recentPatients.length === 0">
                <td colspan="5">
                  <div class="empty"><p>No patients yet</p></div>
                </td>
              </tr>
              <tr v-for="p in recentPatients" :key="p.id">
                <td>
                  <div class="cell-user">
                    <div class="av" :style="{ background: avatarColor(p.name) }">{{ initials(p.name) }}</div>
                    <div>
                      <div class="cell-user-name">{{ p.name }}</div>
                      <div class="cell-user-sub">#{{ shortId(p.id) }}</div>
                    </div>
                  </div>
                </td>
                <td>{{ p.age }} / {{ (p.gender || '').slice(0, 1) }}</td>
                <td><span class="badge b-amber">{{ p.blood || '—' }}</span></td>
                <td class="cell-mono">{{ p.phone }}</td>
                <td class="cell-mono">{{ p.date }}</td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card mt-14">
          <div class="card-head">
            <h3>📦 Low Stock Alert</h3>
            <router-link :to="`${mainOrigin}medicines`" class="btn btn-outline btn-sm">Manage →</router-link>
          </div>
          <div v-if="lowStock.length === 0" class="card-body text-success small">
            ✓ All medicines are adequately stocked.
          </div>
          <div v-else class="tbl-wrap">
            <table class="tbl">
              <thead>
              <tr>
                <th>Medicine</th>
                <th>Type</th>
                <th>Stock</th>
                <th>Price</th>
                <th></th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="m in lowStock" :key="m.id">
                <td>
                  <div class="med-name">{{ m.name }}</div>
                  <div class="med-generic">{{ m.generic }}</div>
                </td>
                <td><span class="badge b-blue">{{ m.type }}</span></td>
                <td>
                  <div class="sbar">
                    <div class="sbar-track">
                      <div class="sbar-fill"
                           :style="{ width: pct(m.stock, 50) + '%', background: stockColor(m.stock) }"></div>
                    </div>
                    <span class="sbar-val" :style="{ color: stockColor(m.stock) }">{{ m.stock }}</span>
                  </div>
                </td>
                <td>৳ {{ m.price }}</td>
                <td>
                  <button class="btn btn-warn btn-xs" @click="$router.push(`${mainOrigin}medicines`)">Restock</button>
                </td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- RIGHT: Recent Rx + Quick Actions -->
      <div>
        <div class="card">
          <div class="card-head">
            <h3>Recent Prescriptions</h3>
            <router-link :to="`${mainOrigin}prescriptions`" class="btn btn-outline btn-sm">All →</router-link>
          </div>
          <div v-if="recentRxs.length === 0" class="card-body empty-rx">
            No prescriptions written yet
          </div>
          <div v-else>
            <div v-for="rx in recentRxs" :key="rx.id" class="activity-row">
              <div class="av av-sm" :style="{ background: avatarColor(rx.patient_name) }">
                {{ initials(rx.patient_name) }}
              </div>
              <div class="activity-info">
                <div class="activity-name">{{ rx.patient_name || 'Unknown' }}</div>
                <div class="activity-sub">
                  {{ rx.date }} · {{ rx.med_count }} med{{ rx.med_count !== 1 ? 's' : '' }} ·
                  {{ (rx.dx || '').slice(0, 30) || '—' }}
                </div>
              </div>
              <button class="btn btn-outline btn-xs" @click="viewRx(rx)">View</button>
            </div>
          </div>
        </div>

        <div class="card mt-14">
          <div class="card-head"><h3>Quick Actions</h3></div>
          <div class="card-body">
            <div class="fr c2">
              <router-link :to="`${mainOrigin}patients/new`"        class="btn btn-primary">+ New Patient</router-link>
              <router-link :to="`${mainOrigin}prescriptions/new`"   class="btn btn-teal">+ Prescription</router-link>
              <router-link :to="`${mainOrigin}medicines/new`"       class="btn btn-ghost">+ Medicine</router-link>
              <router-link :to="`${mainOrigin}investigations/new`"  class="btn btn-ghost">+ Test Type</router-link>
            </div>
          </div>
        </div>

        <div v-if="me && me.RoleID === 'SuperAdmin'" class="card mt-14">
          <div class="card-head"><h3>Help &amp; Support</h3></div>
          <div class="card-body">
            <div style="display:flex;align-items:center;gap:12px">
              <i class="ti-call" style="font-size:26px;color:var(--blue);background:var(--blue-lt);width:44px;height:44px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center"></i>
              <div>
                <strong style="display:block;font-size:13px;color:var(--g800)">Help Line</strong>
                <span style="font-size:11.5px;color:var(--g500)">Contact admin for technical support</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Common } from "../../mixins/common";
import { projectName } from "../../base_url";

export default {
  mixins: [Common],
  data() {
    return {
      isLoading: true,
      stats: {
        patients: 0,
        prescriptions: 0,
        todays_rx: 0,
        medicines: 0,
        low_stock: 0,
        investigations: 0,
        results: 0
      },
      recentPatients: [],
      lowStock: [],
      recentRxs: []
    };
  },
  computed: {
    me() { return this.$store.state.me || {}; }
  },
  created() { this.getData(); },
  methods: {
    projectName() { return projectName; },
    initials(name) {
      return (name || '?')
          .split(' ')
          .slice(0, 2)
          .map(w => (w[0] || '').toUpperCase())
          .join('');
    },
    shortId(id) { return String(id || '').slice(-5).toUpperCase(); },
    avatarColor(name) {
      const colors = ['#1a6fb5','#0b8a79','#7c3aed','#b45309','#b91c1c',
        '#1e40af','#0e7490','#065f46','#9f1239','#6d28d9'];
      let h = 0;
      for (const ch of (name || '')) h += ch.charCodeAt(0);
      return colors[h % colors.length];
    },
    pct(val, max) { return Math.min(100, Math.max(0, (val / max) * 100)); },
    stockColor(s) { return s < 15 ? '#dc2626' : s < 40 ? '#d97706' : '#059669'; },
    viewRx(rx) {
      this.$router.push(`${this.mainOrigin}prescriptions/${rx.id}`);
    },
    getData() {
      this.axiosGet('dashboard-data', (response) => {
        this.stats = response.stats || this.stats;
        this.recentPatients = response.recent_patients || [];
        this.lowStock = response.low_stock || [];
        this.recentRxs = response.recent_rxs || [];
        this.isLoading = false;
      }, () => {
        this.isLoading = false;
      });
    }
  }
};
</script>

<!--
  No <style scoped> block — every class above (.dash-wrap, .stat-card, .card,
  .tbl, .badge, .btn, .activity-row, etc.) is defined in
  /assets/css/rxbd-theme.css. That's how the look stays consistent across
  every page in the application.
-->