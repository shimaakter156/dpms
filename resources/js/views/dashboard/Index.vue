<template>
  <div class="container-fluid">
    <!-- Page title -->
<!--    <div class="page-title-box">-->
<!--      <div class="row align-items-center">-->
<!--        <div class="col-sm-6">-->
<!--&lt;!&ndash;          <h4 class="page-title">Dashboard</h4>&ndash;&gt;-->
<!--&lt;!&ndash;          <p class="page-subtitle">Overview &amp; quick stats</p>&ndash;&gt;-->
<!--        </div>-->
<!--      </div>-->
<!--    </div>-->

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
          <div class="card-body quick-actions">
            <router-link :to="`${mainOrigin}patients/new`" class="btn btn-primary">+ New Patient</router-link>
            <router-link :to="`${mainOrigin}prescriptions/new`" class="btn btn-teal">+ Prescription</router-link>
            <router-link :to="`${mainOrigin}medicines/new`" class="btn btn-ghost">+ Medicine</router-link>
            <router-link :to="`${mainOrigin}investigations/new`" class="btn btn-ghost">+ Test Type</router-link>
          </div>
        </div>

        <div v-if="me && me.RoleID === 'SuperAdmin'" class="card mt-14">
          <div class="card-head"><h3>Help &amp; Support</h3></div>
          <div class="card-body helpline">
            <div class="helpline-item">
              <i class="ti-call"></i>
              <div>
                <strong>Help Line</strong>
                <span>Contact admin for technical support</span>
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
    me() {
      return this.$store.state.me || {};
    }
  },
  created() {
    this.getData();
  },
  methods: {
    projectName() {
      return projectName;
    },
    initials(name) {
      return (name || '?')
          .split(' ')
          .slice(0, 2)
          .map(w => (w[0] || '').toUpperCase())
          .join('');
    },
    shortId(id) {
      return String(id || '').slice(-5).toUpperCase();
    },
    avatarColor(name) {
      const colors = ['#1a6fb5', '#0b8a79', '#7c3aed', '#b45309', '#b91c1c',
        '#1e40af', '#0e7490', '#065f46', '#9f1239', '#6d28d9'];
      let h = 0;
      for (const ch of (name || '')) h += ch.charCodeAt(0);
      return colors[h % colors.length];
    },
    pct(val, max) {
      return Math.min(100, Math.max(0, (val / max) * 100));
    },
    stockColor(s) {
      return s < 15 ? '#dc2626' : s < 40 ? '#d97706' : '#059669';
    },
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
      }, (error) => {
        // Fallback: just show the welcome card
        this.isLoading = false;
        // Uncomment if you want toast notifications on failure:
        // this.errorNoti(error);
      });
    }
  }
};
</script>

<style scoped>
/* ===== RxBD Pro Dashboard Theme ===== */
.dash-wrap {
  padding: 18px 20px;
  background: #edf2f7;
  min-height: calc(100vh - 56px);
  font-size: 13.5px;
  color: #111827;
}

/* Page title */
.page-title-box { margin-bottom: 14px; }
.page-title {
  font-size: 18px;
  font-weight: 700;
  color: #0b2545;
  margin: 0;
}
.page-subtitle {
  font-size: 12px;
  color: #9ca3af;
  margin: 2px 0 0 0;
}

/* Cards */
.card {
  background: #ffffff;
  border-radius: 9px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, .07);
  overflow: hidden;
}
.mt-14 { margin-top: 14px; }

.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 11px 16px;
  border-bottom: 1px solid #e2e8f0;
  background: #f9fafb;
}
.card-head h3 {
  font-size: 13px;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}
.card-body { padding: 14px 16px; }

/* Welcome banner */
.welcome-card {
  background: linear-gradient(135deg, #0b2545 0%, #1a6fb5 60%, #0b8a79 100%);
  color: #fff;
  margin: 15px 0px;
  border: none;
}
.welcome-card .card-body {
  padding: 22px 24px;
  text-align: center;
}
.welcome-text {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 10px;
  flex-wrap: wrap;
}
.welcome-greeting {
  font-size: 14px;
  letter-spacing: 5px;
  text-transform: uppercase;
  color: rgba(255, 255, 255, .8);
  font-weight: 500;
}
.welcome-name {
  font-size: 22px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
}

/* Stats row */
.stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 13px;
  margin-bottom: 18px;
}
.stat-card {
  background: #fff;
  border-radius: 9px;
  padding: 15px 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, .07);
  display: flex;
  align-items: center;
  gap: 13px;
  transition: box-shadow .14s, transform .14s;
}
.stat-card:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, .09);
  transform: translateY(-1px);
}
.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.stat-icon svg { width: 22px; height: 22px; }
.si-blue   { background: #dbeafe; color: #2563eb; }
.si-teal   { background: #d0f0ec; color: #0b8a79; }
.si-amber  { background: #fef3c7; color: #d97706; }
.si-purple { background: #ede9fe; color: #7c3aed; }
.stat-val {
  font-size: 26px;
  font-weight: 700;
  line-height: 1.1;
  color: #111827;
}
.stat-lbl { font-size: 11.5px; color: #6b7280; margin-top: 2px; }
.stat-sub { font-size: 11px; margin-top: 2px; font-weight: 600; }
.stat-pos  { color: #059669; }
.stat-info { color: #2563eb; }
.stat-warn { color: #dc2626; }

/* Two columns */
.dash-cols {
  display: grid;
  grid-template-columns: 3fr 2fr;
  gap: 14px;
}

/* Tables */
.tbl-wrap { overflow-x: auto; }
.tbl {
  width: 100%;
  border-collapse: collapse;
}
.tbl thead tr {
  background: #f9fafb;
  border-bottom: 2px solid #e2e8f0;
}
.tbl thead th {
  padding: 9px 12px;
  text-align: left;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: #6b7280;
  white-space: nowrap;
}
.tbl tbody tr {
  border-bottom: 1px solid #e2e8f0;
  transition: background .1s;
}
.tbl tbody tr:hover { background: #f8fafc; }
.tbl tbody tr:last-child { border-bottom: none; }
.tbl td {
  padding: 9px 12px;
  font-size: 13px;
  color: #374151;
  vertical-align: middle;
}
.cell-mono { font-size: 12px; }

/* Cell user */
.cell-user {
  display: flex;
  align-items: center;
  gap: 9px;
}
.cell-user-name {
  font-weight: 700;
  color: #0b2545;
}
.cell-user-sub {
  font-size: 11px;
  color: #9ca3af;
}

.av {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  color: #fff;
  flex-shrink: 0;
}
.av-sm { width: 28px; height: 28px; font-size: 10.5px; }

/* Badge */
.badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 20px;
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: .02em;
  white-space: nowrap;
}
.b-blue  { background: #d6eaf8; color: #1e40af; }
.b-amber { background: #fef3c7; color: #92400e; }

/* Stock bar */
.sbar {
  display: flex;
  align-items: center;
  gap: 7px;
}
.sbar-track {
  flex: 1;
  background: #f3f6fa;
  border-radius: 3px;
  height: 5px;
  overflow: hidden;
  min-width: 50px;
}
.sbar-fill {
  height: 5px;
  border-radius: 3px;
  transition: width .3s;
}
.sbar-val {
  font-size: 11.5px;
  font-weight: 700;
  min-width: 28px;
  text-align: right;
}

/* Medicine cell */
.med-name { font-weight: 700; color: #0b2545; }
.med-generic { font-size: 11px; color: #9ca3af; }

/* Activity row */
.activity-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 14px;
  border-bottom: 1px solid #e2e8f0;
}
.activity-row:last-child { border-bottom: none; }
.activity-info {
  flex: 1;
  min-width: 0;
}
.activity-name {
  font-size: 12.5px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.activity-sub {
  font-size: 11px;
  color: #9ca3af;
}
.empty-rx {
  color: #9ca3af;
  font-size: 13px;
  text-align: center;
  padding: 24px;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 7px 14px;
  border-radius: 5px;
  font-size: 12.5px;
  font-weight: 600;
  border: 1.5px solid transparent;
  transition: all .13s;
  white-space: nowrap;
  cursor: pointer;
  text-decoration: none;
}
.btn-sm { padding: 4px 10px; font-size: 12px; }
.btn-xs { padding: 2px 8px; font-size: 11px; }
.btn-primary { background: #1a6fb5; color: #fff; border-color: #1a6fb5; }
.btn-primary:hover { background: #2388d4; border-color: #2388d4; color: #fff; }
.btn-teal    { background: #0b8a79; color: #fff; border-color: #0b8a79; }
.btn-teal:hover { background: #0fa595; color: #fff; }
.btn-outline { background: transparent; color: #1a6fb5; border-color: #1a6fb5; }
.btn-outline:hover { background: #eaf4fb; color: #1a6fb5; }
.btn-ghost { background: transparent; color: #4b5563; border-color: #d1d5db; }
.btn-ghost:hover { background: #f3f6fa; color: #1f2937; }
.btn-warn { background: #d97706; color: #fff; border-color: #d97706; }
.btn-warn:hover { background: #b45309; color: #fff; }

/* Quick actions grid */
.quick-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

/* Helpline card */
.helpline-item {
  display: flex;
  align-items: center;
  gap: 12px;
}
.helpline-item i {
  font-size: 26px;
  color: #1a6fb5;
  background: #d6eaf8;
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.helpline-item strong {
  display: block;
  font-size: 13px;
  color: #1f2937;
}
.helpline-item span {
  font-size: 11.5px;
  color: #6b7280;
}

.empty {
  text-align: center;
  padding: 40px 20px;
  color: #6b7280;
}

.text-success { color: #059669 !important; }
.small { font-size: 13px; }

/* Responsive */
@media (max-width: 991px) {
  .stats-row { grid-template-columns: 1fr 1fr; }
  .dash-cols { grid-template-columns: 1fr; }
}

@media (max-width: 575px) {
  .stats-row { grid-template-columns: 1fr; }
  .quick-actions { grid-template-columns: 1fr; }
  .welcome-name { font-size: 17px; }
  .welcome-greeting { letter-spacing: 3px; font-size: 12px; }
  .dash-wrap { padding: 12px; }
}
</style>