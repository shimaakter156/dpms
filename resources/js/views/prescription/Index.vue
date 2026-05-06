<template>
  <div class="rx-overlay">

    <!-- Top Bar -->
    <div class="rx-topbar">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
        <rect x="9" y="3" width="6" height="4" rx="1"/>
      </svg>
      <h2>Prescription — Write &amp; Print</h2>
      <button class="tb-btn" @click="printRx">🖨 Print</button>
      <button class="tb-btn tb-btn--save" @click="saveRx" :disabled="saving">
        {{ saving ? 'Saving…' : '💾 Save' }}
      </button>
      <button class="tb-btn" @click="$router.back()">✕ Close</button>
    </div>

    <!-- Paper -->
    <div class="rx-paper" id="rx-paper">

      <!-- Doctor Header -->
      <div class="rp-hdr">
        <div class="rp-logo">
<!--          <img :src="{{asset('assets/images/hospital.png')}}" alt="">-->
          Clinic<br/>Logo </div>
        <div class="rp-doc">
          <h1>{{ doctor.DoctorName }}</h1>
          <div class="rp-deg">{{ doctor.Degrees? doctor.Degrees:'MBBS (Dhaka), FCPS (Medicine), MD (Cardiology)' }}</div>
          <div class="rp-spec">{{ doctor.Specialization ?doctor.Specialization :'Consultant Physician & Cardiologist'}}</div>
          <div class="rp-addr">📍 {{ doctor.address?doctor.address:'Rahman Medical Center, Mirpur-10, Dhaka-1216' }} &nbsp;|&nbsp; ⏰ {{ doctor.hours?doctor.hours:'Sat–Thu · 5:00–9:00 PM' }}</div>
        </div>
        <div class="rp-contact">
          <div class="rp-rxsym">℞</div>
          <div class="rp-ph">📞 {{ doctor.Phone }}</div>
          <div>BMDC Reg: {{ doctor.BMDCNumber }}</div>
          <div>Emergency: {{ doctor.emergency?doctor.emergency:'16789' }}</div>
        </div>
      </div>

      <!-- Patient Band -->
      <div class="rp-pt">
        <div class="band-lbl">👤 PATIENT INFORMATION </div>
        <div class="rp-pt-grid">
          <div class="rp-field">
            <label>SELECT PATIENT </label>
            <select v-model="form.patientID" @change="fillPatient" class="fc">
              <option value="">— Select Patient —</option>
              <option v-for="p in patientsList" :key="p.PatientID" :value="p.PatientID" >{{ p.FullName }}</option>
            </select>
          </div>
          <div class="rp-field">
            <label>AGE</label>
            <input class="fc" :value="selectedPatient.Age || '—'" readonly/>
          </div>
          <div class="rp-field">
            <label>GENDER</label>
            <input class="fc" :value="selectedPatient.Gender || '—'" readonly/>
          </div>
          <div class="rp-field">
            <label>MOBILE</label>
            <input class="fc" :value="selectedPatient.Phone || '—'" readonly/>
          </div>
          <div class="rp-field">
            <label>DATE</label>
            <input type="date" class="fc" v-model="form.date"/>
          </div>
        </div>
      </div>

      <!-- Two Column Body -->
      <div class="rp-cols">

        <!-- LEFT PANEL -->
        <div class="rp-left">

          <div class="rxs" @click="showComplaintsModal = true">
            <div class="rxs-t">⚠ CHIEF COMPLAINTS</div>
            <div class="rxs-b" >
              <textarea v-model="form.complaints" rows="4"
                        placeholder="Chest pain × 3d&#10;Breathlessness&#10;Palpitation"></textarea>
            </div>
          </div>

          <div class="rxs" @click="showHistoryModal = true">
            <div class="rxs-t">🕐 HISTORY</div>
            <div class="rxs-b">
              <textarea v-model="form.history" rows="3"
                        placeholder="Past / family / drug history, allergies..."></textarea>
            </div>
          </div>

          <div class="rxs" @click="showExamModal = true">
            <div class="rxs-t">📊 O/E EXAMINATION</div>
            <div class="rxs-b">
              <div class="vitals-g">
                <div class="vb">
                  <label>BP</label>
                  <input v-model="form.vitals.bp" placeholder="120/80"/>
                  <div class="unit">mmHg</div>
                </div>
                <div class="vb">
                  <label>PULSE</label>
                  <input v-model="form.vitals.pulse" placeholder="72"/>
                  <div class="unit">bpm</div>
                </div>
                <div class="vb">
                  <label>TEMP</label>
                  <input v-model="form.vitals.temp" placeholder="98.6"/>
                  <div class="unit">°F</div>
                </div>
                <div class="vb">
                  <label>WEIGHT</label>
                  <input v-model="form.vitals.weight" placeholder="65"/>
                  <div class="unit">kg</div>
                </div>
                <div class="vb">
                  <label>SPO₂</label>
                  <input v-model="form.vitals.spo2" placeholder="98"/>
                  <div class="unit">%</div>
                </div>
                <div class="vb">
                  <label>RBS</label>
                  <input v-model="form.vitals.rbs" placeholder="5.8"/>
                  <div class="unit">mmol/L</div>
                </div>
              </div>
              <div style="margin-top:6px">
                <textarea v-model="form.examFindings" rows="2"
                          placeholder="General exam findings..."></textarea>
              </div>
            </div>
          </div>

          <div class="rxs" @click="showDiagnosisModal=true">
            <div class="rxs-t rxs-t--teal">✅ DIAGNOSIS / D/D</div>
            <div class="rxs-b">
              <textarea v-model="form.diagnosis" rows="3"
                        placeholder="Provisional / confirmed diagnosis..."></textarea>
            </div>
          </div>

          <div class="rxs" @click="showInvestigationModal=true">
            <div class="rxs-t">🔬 INVESTIGATIONS</div>
            <div class="rxs-b">
              <textarea v-model="form.investigations" rows="3"
                        placeholder="CBC, FBS, ECG, Echo..."></textarea>
            </div>
          </div>

        </div>

        <!-- RIGHT PANEL -->
        <div class="rp-right">

          <!-- Prescription Medicine Table -->
          <div class="rxs">
            <div class="rxs-t rxs-t--teal rxs-t--rx">
              <span class="rx-sym">℞</span>&nbsp; PRESCRIPTION
            </div>
            <div class="rxs-b" style="padding:0">
              <table class="med-tbl">
                <thead>
                <tr>
                  <th style="width:22px"></th>
                  <th style="width:36%">MEDICINE NAME &amp; STRENGTH</th>
                  <th style="width:14%">DOSAGE</th>
                  <th style="width:13%">DURATION</th>
                  <th>INSTRUCTIONS</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(med, i) in form.medicines" :key="i">
                  <td class="rn">{{ i + 1 }}.</td>
                  <td><input v-model="med.name" placeholder="e.g. Tab. Metformin 500mg"/></td>
                  <td class="dos"><input v-model="med.dosage" placeholder="1+0+1"/></td>
                  <td><input v-model="med.duration" placeholder="30 days"/></td>
                  <td><input v-model="med.instructions" placeholder="After meal"/></td>
                </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Advice -->
          <div class="rxs">
            <div class="rxs-t">💡 ADVICE &amp; LIFESTYLE</div>
            <div class="rxs-b">
              <textarea v-model="form.advice" rows="5"
                        placeholder="• Low salt, low fat diet&#10;• Daily 30-min walk&#10;• No smoking / alcohol&#10;• Monitor BP twice daily"></textarea>
            </div>
          </div>

          <!-- Referral -->
          <div class="rxs">
            <div class="rxs-t">📄 REFERRAL / NOTES</div>
            <div class="rxs-b">
              <textarea v-model="form.referral" rows="2"
                        placeholder="Referred to: BSMMU Cardiology for..."></textarea>
            </div>
          </div>

        </div>
      </div>

      <!-- Footer -->
      <div class="rp-foot">
        <div class="foot-note">
          <strong>Important Notice</strong>
          Do not self-medicate. Report adverse reactions immediately.<br/>
          Prescription valid for <strong>30 days</strong> from date of issue.
        </div>
        <div class="nv-wrap">
          <label>NEXT VISIT DATE</label>
          <input type="date" v-model="form.nextVisit"/>
        </div>
        <div class="sig-box">
          <div class="sig-line"></div>
          <div class="sig-lbl">Doctor's Signature &amp; Seal</div>
        </div>
      </div>

    </div><!-- /.rx-paper -->
    <div v-if="showComplaintsModal" class="modal-overlay" @click.self="showComplaintsModal = false">
      <div class="modal-box">
            <div class="rxs">
              <div class="rxs-t" style="display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 20px;">⚠ CHIEF COMPLAINTS</span>
                <button class="btn sm" @click="showComplaintsModal = false">✕</button>
              </div>
              <div class="rxs-b" @click="showComplaintsModal = true">
              <textarea
                  v-model="form.complaints"
                  rows="4"
                  :placeholder="'Chest pain × 3d\nBreathlessness\nPalpitation'"
              ></textarea>
              </div>

            </div>
      </div>
    </div>
    <div v-if="showHistoryModal" class="modal-overlay" @click.self="showHistoryModal = false">
      <div class="modal-box">
            <div class="rxs">
              <div class="rxs-t" style="display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 20px;">🕐 HISTORY</span>
                <button class="btn sm" @click="showHistoryModal = false">✕</button>
              </div>
              <div class="rxs-b" @click="showHistoryModal = true">
              <textarea
                  v-model="form.history"
                  rows="4"
                  :placeholder="'Past / family / drug history, allergies...'"
              ></textarea>
              </div>

            </div>
      </div>
    </div>
    <div v-if="showExamModal" class="modal-overlay" @click.self="showExamModal = false">
      <div class="modal-box">
            <div class="rxs">
              <div class="rxs-t" style="display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 20px;">📊 O/E EXAMINATION</span>
                <button class="btn sm" @click="showExamModal = false">✕</button>
              </div>

              <div class="rxs-b">
                  <div class="vitals-g">
                    <div class="vb">
                      <label>BP</label>
                      <input v-model="form.vitals.bp" placeholder="120/80"/>
                      <div class="unit">mmHg</div>
                    </div>
                    <div class="vb">
                      <label>PULSE</label>
                      <input v-model="form.vitals.pulse" placeholder="72"/>
                      <div class="unit">bpm</div>
                    </div>
                    <div class="vb">
                      <label>TEMP</label>
                      <input v-model="form.vitals.temp" placeholder="98.6"/>
                      <div class="unit">°F</div>
                    </div>
                    <div class="vb">
                      <label>WEIGHT</label>
                      <input v-model="form.vitals.weight" placeholder="65"/>
                      <div class="unit">kg</div>
                    </div>
                    <div class="vb">
                      <label>SPO₂</label>
                      <input v-model="form.vitals.spo2" placeholder="98"/>
                      <div class="unit">%</div>
                    </div>
                    <div class="vb">
                      <label>RBS</label>
                      <input v-model="form.vitals.rbs" placeholder="5.8"/>
                      <div class="unit">mmol/L</div>
                    </div>
                  </div>
                  <div style="margin-top:6px">
                <textarea v-model="form.examFindings" rows="2"
                          placeholder="General exam findings..."></textarea>
                  </div>
                </div>
            </div>
      </div>
    </div>
    <div v-if="showDiagnosisModal" class="modal-overlay" @click.self="showDiagnosisModal = false">
      <div class="modal-box">
        <div class="rxs">
          <div class="rxs-t" style="display: flex; align-items: center; justify-content: space-between;">
            <span style="font-size: 20px;">✅ DIAGNOSIS / D/D</span>
            <button class="btn sm" @click="showDiagnosisModal = false">✕</button>
          </div>
        <textarea v-model="form.diagnosis" rows="3" placeholder="Provisional / confirmed diagnosis..."></textarea>

        </div>
      </div>
    </div>
    <div v-if="showInvestigationModal" class="modal-overlay" @click.self="showInvestigationModal = false">
      <div class="modal-box">
        <div class="rxs">
          <div class="rxs-t" style="display: flex; align-items: center; justify-content: space-between;">
            <span style="font-size: 20px;">🔬 INVESTIGATIONS</span>
            <button class="btn sm" @click="showInvestigationModal = false">✕</button>
          </div>
          <div class="rxs-b" @click="showInvestigationModal = true">
                                <textarea v-model="form.investigations" rows="3"
                                          placeholder="CBC, FBS, ECG, Echo..."></textarea>
          </div>

        </div>
      </div>
    </div>

  </div>


</template>

<script>
import { Common } from '../../mixins/common'

const EMPTY_MED = () => ({ name: '', dosage: '', duration: '', instructions: '' })
const MED_ROWS  = 8

export default {
  name: 'WritePrescription',
  mixins: [Common],

  data() {
    return {
      saving:   false,
      patientsList: [],
      patients: [],
      doctor:{},
      showExamModal:false,
      showComplaintsModal:false,
      showHistoryModal:false,
      showDiagnosisModal:false,
      showInvestigationModal:false,
      // doctor:   {
      //
      //   DoctorName:       'Dr. Md. Abdur Rahman',
      //   degrees:    'MBBS (Dhaka), FCPS (Medicine), MD (Cardiology)',
      //   Specialization: 'Consultant Physician & Cardiologist',
      //   address:    'Rahman Medical Center, Mirpur-10, Dhaka-1216',
      //   hours:      'Sat–Thu · 5:00–9:00 PM',
      //   phone:      '+880-1711-000000',
      //   bmdc:       'A-12345',
      //   emergency:  '16789',
      // },
      form: {
        patientID:    '',
        date:         new Date().toISOString().slice(0, 10),
        complaints:   '',
        history:      '',
        examFindings: '',
        diagnosis:    '',
        investigations:'',
        advice:       '',
        referral:     '',
        nextVisit:    '',
        vitals: { bp: '', pulse: '', temp: '', weight: '', spo2: '', rbs: '' },
        medicines: Array.from({ length: MED_ROWS }, EMPTY_MED),
      },
    }
  },

  computed: {
    selectedPatient() {
      if (!this.form.patientID) return {}

      const id = Number(this.form.patientID)

      const patient = this.patientsList.find(
          p => Number(p.PatientID) === id
      )

      return patient || {}
    }
  },

  mounted() {
    this.loadDoctor()


    // If editing existing prescription
    // if (this.$route.params.id) {
    //   this.loadPrescription(this.$route.params.id)
    // }
  },

  methods: {
    // selectedPatient() {
    //   return this.form.patientID
    //       ? this.patientsList.find(p => p.PatientID === Number(this.form.patientID)) || {}
    //       : {}
    // },
    loadPatients(DoctorID) {
      this.axiosGet('patients/list/'+DoctorID, (res) => {
        this.patientsList = res.data || res
      }, err => this.errorNoti(err))
    },
    loadPatientByID() {
      let id = 'PT-0002'
      this.axiosGet('patients/info/'+id, (res) => {
        this.patients = res.data || res
      }, err => this.errorNoti(err))
    },

    loadDoctor() {
      this.axiosGet('doctor/info', (res) => {
        if (res) {
          this.doctor = {...res}
          this.loadPatients(this.doctor.DoctorID);
        }
      }, () => {})
    },

    loadPrescription(id) {
      this.axiosGet(`prescriptions/${id}`, (res) => {
        const rx = res.data || res
        this.form = {
          ...this.form,
          ...rx,
          vitals:   rx.vitals   || this.form.vitals,
          medicines: rx.medicines?.length
              ? [...rx.medicines, ...Array.from({ length: Math.max(0, MED_ROWS - rx.medicines.length) }, EMPTY_MED)]
              : this.form.medicines,
        }
      }, err => this.errorNoti(err))
    },

    fillPatient(e) {
     // this.selectedPatient(e.target.value)

    },

    saveRx() {
      if (!this.form.patientID) {
        return this.errorNoti('Please select a patient.')
      }

      this.saving = true
      const payload = {
        ...this.form,
        medicines: this.form.medicines.filter(m => m.name.trim()),
      }

      const id = this.$route.params.id
      if (id) {
        this.axiosPost(`prescriptions/${id}`, { _method: 'PUT', ...payload }, (res) => {
          this.saving = false
          this.successNoti(res.message || 'Prescription updated.')
        }, err => { this.saving = false; this.errorNoti(err) })
      } else {
        this.axiosPost('prescriptions', payload, (res) => {
          this.saving = false
          this.successNoti(res.message || 'Prescription saved.')
          if (res.id) this.$router.replace({ params: { id: res.id } })
        }, err => { this.saving = false; this.errorNoti(err) })
      }
    },

    printRx() {
      window.print()
    }
  }
}
</script>

<style scoped>
/* ── Overlay ────────────────────────────────────────── */
.rx-overlay {
  position: fixed;
  inset: 0;
  background: #edf2f7;
  z-index: 800;
  overflow-y: auto;
  font-family: 'Segoe UI', system-ui, Helvetica, sans-serif;
  font-size: 13.5px;
  color: #111827;
}

/* ── Top Bar ─────────────────────────────────────────── */
.rx-topbar {
  position: sticky;
  top: 0;
  background: linear-gradient(90deg, #0b2545, #163060);
  color: #fff;
  padding: 9px 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  z-index: 10;
  box-shadow: 0 2px 12px rgba(0,0,0,.25);
}
.rx-topbar h2 { font-size: 14.5px; font-weight: 700; flex: 1; }
.tb-btn {
  padding: 6px 14px; border-radius: 5px; font-size: 12.5px; font-weight: 600;
  border: 1.5px solid rgba(255,255,255,.22); cursor: pointer;
  background: rgba(255,255,255,.1); color: #fff;
  transition: background .13s;
}
.tb-btn:hover { background: rgba(255,255,255,.2); }
.tb-btn--save { background: #059669; border-color: #059669; }
.tb-btn--save:hover { background: #047857; }
.tb-btn:disabled { opacity: .6; cursor: not-allowed; }

/* ── Paper ───────────────────────────────────────────── */
.rx-paper {
  max-width: 880px;
  margin: 18px auto 40px;
  background: #fff;
  border-radius: 9px;
  box-shadow: 0 6px 24px rgba(0,0,0,.13);
  overflow: hidden;
}

/* ── Doctor Header ───────────────────────────────────── */
.rp-hdr {
  background: linear-gradient(135deg, #0b2545 0%, #1a6fb5 100%);
  color: #fff;
  padding: 16px 22px;
  display: grid;
  grid-template-columns: 66px 1fr auto;
  gap: 14px;
  align-items: center;
  border-bottom: 3px solid #0fa595;
}
.rp-logo {
  width: 58px; height: 58px;
  border: 2px dashed rgba(255,255,255,.3);
  border-radius: 5px;
  display: flex; align-items: center; justify-content: center;
  color: rgba(255,255,255,.35); font-size: 9px;
  text-align: center; line-height: 1.4;
}
.rp-doc h1    { font-size: 18px; font-weight: 700; font-family: Georgia, serif; }
.rp-deg       { font-size: 10.5px; color: rgba(180,215,240,.8); margin-top: 2px; }
.rp-spec      { font-size: 12.5px; font-weight: 600; color: #e0f3ff; margin-top: 3px; }
.rp-addr      { font-size: 11px; color: rgba(150,200,235,.8); margin-top: 5px; }
.rp-contact   { text-align: right; font-size: 11px; color: rgba(150,200,235,.8); line-height: 1.9; }
.rp-rxsym     { font-size: 38px; color: rgba(255,255,255,.1); font-weight: 700; font-family: Georgia, serif; line-height: 1; }
.rp-ph        { font-size: 12px; color: #e0f3ff; font-weight: 600; }

/* ── Patient Band ────────────────────────────────────── */
.rp-pt { background: #f9fafb; border-bottom: 1.5px solid #e2e8f0; padding: 10px 18px; }
.band-lbl {
  font-size: 9.5px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .1em;
  color: #1a6fb5; margin-bottom: 7px;
}
.rp-pt-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1.2fr;
  gap: 9px;
}
.rp-field label {
  display: block; font-size: 9.5px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .06em;
  color: #9ca3af; margin-bottom: 3px;
}
.fc {
  width: 100%; border: 1.5px solid #d1d5db;
  border-radius: 4px; padding: 5px 8px;
  font-size: 12.5px; font-family: inherit;
  outline: none; transition: border-color .12s;
  background: #fff;
}
.fc:focus { border-color: #1a6fb5; }

/* ── Two Columns ─────────────────────────────────────── */
.rp-cols {
  display: grid;
  grid-template-columns: 190px 1fr;
  min-height: 550px;
}
.rp-left {
  border-right: 1.5px solid #e2e8f0;
  padding: 11px 10px;
  background: #fafcfe;
  display: flex; flex-direction: column; gap: 9px;
}
.rp-right {
  padding: 11px 15px;
  display: flex; flex-direction: column; gap: 9px;
}

/* ── RX Section Box ──────────────────────────────────── */
.rxs { border: 1px solid #e2e8f0; border-radius: 5px; overflow: hidden; }
.rxs-t {
  background: #d6eaf8; border-bottom: 1px solid #e2e8f0;
  padding: 4px 9px; font-size: 9.5px; font-weight: 700;
  letter-spacing: .08em; text-transform: uppercase;
  color: #0b2545; display: flex; align-items: center; gap: 5px;
}
.rxs-t--teal { background: #d0f0ec; color: #0b8a79; border-color: #a5d9d3; }
.rxs-t--rx   { font-size: 12px; padding: 6px 9px; }
.rx-sym      { font-size: 22px; font-weight: 700; font-family: Georgia, serif; line-height: 1; color: #0b8a79; }
.rxs-b { padding: 7px 8px; }
.rxs-b textarea,
.rxs-b input,
.rxs-b select {
  width: 100%; border: 1.5px solid #d1d5db; border-radius: 3px;
  padding: 4px 7px; font-size: 12px; font-family: inherit;
  outline: none; resize: vertical; transition: border-color .12s; background: #fff;
}
.rxs-b textarea:focus,
.rxs-b input:focus { border-color: #1a6fb5; }

/* ── Vitals Grid ─────────────────────────────────────── */
.vitals-g { display: grid; grid-template-columns: 1fr 1fr; gap: 5px; }
.vb {
  border: 1.5px solid #d1d5db; border-radius: 3px;
  padding: 4px 7px; background: #fff;
}
.vb label {
  display: block; font-size: 9px; font-weight: 700;
  text-transform: uppercase; color: #9ca3af; margin-bottom: 2px;
}
.vb input {
  border: none; border-bottom: 1.5px solid #d1d5db;
  width: 100%; font-size: 13px; font-weight: 600;
  color: #0b2545; background: transparent; outline: none; padding: 1px 0;
}
.unit { font-size: 9px; color: #9ca3af; margin-top: 1px; }

/* ── Medicine Table ──────────────────────────────────── */
.med-tbl { width: 100%; border-collapse: collapse; }
.med-tbl thead tr { background: #0b2545; }
.med-tbl thead th {
  padding: 6px 8px; text-align: left;
  font-size: 9px; font-weight: 700;
  letter-spacing: .08em; text-transform: uppercase;
  color: rgba(255,255,255,.85);
}
.med-tbl tbody tr { border-bottom: 1px solid #e2e8f0; }
.med-tbl tbody tr:nth-child(even) { background: #f7fafd; }
.med-tbl td { padding: 0; }
.med-tbl td input {
  width: 100%; border: none; padding: 7px 8px;
  font-size: 12.5px; font-family: inherit;
  background: transparent; outline: none; color: #111827;
}
.med-tbl td input:focus { background: #eef6fb; }
.rn   { width: 22px; padding: 7px 0 7px 7px; font-size: 9px; color: #9ca3af; font-weight: 700; text-align: center; }
.dos input { font-family: 'Courier New', monospace !important; font-weight: 700 !important; color: #0b8a79 !important; letter-spacing: .05em; }

/* ── Footer ──────────────────────────────────────────── */
.rp-foot {
  border-top: 1.5px solid #e2e8f0;
  padding: 12px 18px;
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: end; gap: 18px;
  background: #f9fafb;
}
.foot-note { font-size: 10.5px; color: #6b7280; line-height: 1.6; }
.foot-note strong { display: block; color: #374151; margin-bottom: 1px; }
.nv-wrap { text-align: center; }
.nv-wrap label {
  display: block; font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .07em; color: #9ca3af; margin-bottom: 4px;
}
.nv-wrap input {
  border: none; border-bottom: 2px solid #0b2545;
  padding: 3px 8px; font-size: 13px; text-align: center;
  width: 155px; background: transparent; outline: none; color: #111827;
}
.sig-box { text-align: right; }
.sig-line { width: 140px; height: 50px; border-bottom: 2px solid #374151; margin-left: auto; margin-bottom: 4px; }
.sig-lbl  { font-size: 10px; color: #9ca3af; text-align: center; width: 140px; margin-left: auto; }
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-box {
  background: #fff;
  border-radius: 8px;
  width: 800px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
/* ── Print ───────────────────────────────────────────── */
@media print {
  @page { size: A4 portrait; margin: 13mm 15mm 12mm; }
  .rx-topbar { display: none !important; }
  .rx-overlay { position: static !important; overflow: visible !important; background: #fff !important; }
  .rx-paper   { box-shadow: none !important; border-radius: 0; margin: 0; max-width: 100%; }
  .rp-hdr     { background: #fff !important; color: #000 !important; border-bottom: 2.5px solid #000; -webkit-print-color-adjust: exact; }
  .rp-doc h1  { color: #000 !important; }
  .rp-deg, .rp-addr, .rp-contact { color: #444 !important; }
  .rp-spec    { color: #111 !important; }
  .rxs-t      { background: #e8e8e8 !important; color: #000 !important; -webkit-print-color-adjust: exact; }
  .rxs-t--teal{ background: #e0e0e0 !important; }
  .med-tbl thead tr { background: #1a1a1a !important; -webkit-print-color-adjust: exact; }
  .med-tbl thead th { color: #fff !important; }
  .med-tbl tbody tr:nth-child(even) { background: #f4f4f4 !important; }
  .rp-pt      { background: #f5f5f5 !important; -webkit-print-color-adjust: exact; }
  .rp-foot    { background: #f5f5f5 !important; }
  .dos input  { color: #000 !important; }
}
</style>