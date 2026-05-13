<template>
  <div class="rx-fullscreen">
    <div class="rx-overlay">

      <!-- Top Bar -->
      <div class="rx-topbar no-print">
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
          <div class="rp-logo">Clinic<br/>Logo</div>
          <div class="rp-doc">
            <h1>{{ doctor.DoctorName || 'Dr. — please configure profile' }}</h1>
            <div class="rp-deg">{{ doctor.Degrees || 'MBBS (Dhaka), FCPS (Medicine)' }}</div>
            <div class="rp-spec">{{ doctor.Specialization || 'Consultant Physician' }}</div>
            <div class="rp-addr">
              📍 {{ doctor.Address || 'Rahman Medical Center, Mirpur-10, Dhaka' }}
              &nbsp;|&nbsp; ⏰ {{ doctor.Hours || 'Sat–Thu · 5:00–9:00 PM' }}
            </div>
          </div>
          <div class="rp-contact">
            <div class="rp-rxsym">℞</div>
            <div class="rp-ph">📞 {{ doctor.Phone || '+880' }}</div>
            <div>BMDC Reg: {{ doctor.BMDCNumber || '—' }}</div>
            <div>Emergency: {{ doctor.Emergency || '16789' }}</div>
          </div>
        </div>

        <!-- Patient Band -->
        <div class="rp-pt">
          <div class="band-lbl">👤 PATIENT INFORMATION</div>
          <div class="rp-pt-grid">
            <div class="rp-field">
              <label>SELECT PATIENT</label>
              <select v-model="form.patientID" @change="onPatientChange" class="fc no-print-select">
                <option value="">— Select Patient —</option>
                <option v-for="p in patientsList" :key="p.PatientID" :value="p.PatientID">
                  {{ p.FullName || p.PatientName }} {{ p.Phone ? '· ' + p.Phone : '' }}
                </option>
              </select>
              <div class="print-only print-value">{{ selectedPatient.FullName || selectedPatient.PatientName || '—' }}</div>
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

          <div v-if="selectedPatient.PatientID" class="pt-chips">
            <span v-if="selectedPatient.BloodGroup" class="chip chip--info">🩸 {{ selectedPatient.BloodGroup }}</span>
            <span v-for="(a, i) in patientAllergies" :key="'al'+i" class="chip chip--warn">⚠ Allergy: {{ a }}</span>
            <span v-for="(c, i) in patientChronic" :key="'ch'+i" class="chip chip--muted">🩺 {{ c }}</span>
            <button v-if="hasPriorRx" class="chip chip--link no-print" @click="loadFromLastVisit">
              ↻ Load from last visit
            </button>
          </div>
        </div>

        <!-- Two Column Body -->
        <div class="rp-cols">

          <!-- LEFT PANEL — all multiselect -->
          <div class="rp-left">

            <!-- CHIEF COMPLAINTS -->
            <div class="rxs">
              <div class="rxs-t">⚠ CHIEF COMPLAINTS</div>
              <div class="rxs-b">
                <multiselect
                    v-model="selected.complaints"
                    :options="suggestions.complaints"
                    :multiple="true"
                    :taggable="true"
                    :close-on-select="false"
                    :clear-on-select="false"
                    :preserve-search="true"
                    :hide-selected="true"
                    track-by="ComplaintID"
                    label="ComplaintName"
                    tag-placeholder="Press Enter to add"
                    placeholder="Type or pick complaints…"
                    @tag="(tag) => addTag('complaints', tag)"
                    @search-change="(q) => onSearch('complaints', q)"
                />
                <!-- Print-only bulleted list -->
                <ul class="print-only print-bullets">
                  <li v-for="(v, i) in selected.complaints" :key="'p-c'+i">{{ labelOf(v) }}</li>
                </ul>
              </div>
            </div>

            <!-- HISTORY -->
            <div class="rxs">
              <div class="rxs-t">🕐 HISTORY</div>
              <div class="rxs-b">
                <multiselect
                    v-model="selected.history"
                    :options="suggestions.history"
                    track-by="HistoryID"
                    label="ConditionName"
                    :multiple="true" :taggable="true"
                    :close-on-select="false" :clear-on-select="false"
                    :preserve-search="true" :hide-selected="true"
                    tag-placeholder="Press Enter to add"
                    placeholder="Past / family / drug history…"
                    @tag="(tag) => addTag('history', tag)"
                />
                <ul class="print-only print-bullets">
                  <li v-for="(v, i) in selected.history" :key="'p-h'+i">{{ labelOf(v) }}</li>
                </ul>
              </div>
            </div>

            <!-- O/E EXAMINATION -->
            <div class="rxs">
              <div class="rxs-t">📊 O/E EXAMINATION</div>
              <div class="rxs-b">
                <div class="vitals-g">
                  <div class="vb"><label>BP</label><input v-model="form.vitals.bp" placeholder="120/80"/><div class="unit">mmHg</div></div>
                  <div class="vb"><label>PULSE</label><input v-model="form.vitals.pulse" placeholder="72"/><div class="unit">bpm</div></div>
                  <div class="vb"><label>TEMP</label><input v-model="form.vitals.temp" placeholder="98.6"/><div class="unit">°F</div></div>
                  <div class="vb"><label>WEIGHT</label><input v-model="form.vitals.weight" placeholder="65"/><div class="unit">kg</div></div>
                  <div class="vb"><label>SPO₂</label><input v-model="form.vitals.spo2" placeholder="98"/><div class="unit">%</div></div>
                  <div class="vb"><label>RBS</label><input v-model="form.vitals.rbs" placeholder="5.8"/><div class="unit">mmol/L</div></div>
                </div>
                <div style="margin-top:6px">
                  <multiselect
                      v-model="selected.examFindings"
                      :options="suggestions.examFindings"
                      :multiple="true" :taggable="true"
                      :close-on-select="false" :clear-on-select="false"
                      :preserve-search="true" :hide-selected="true"
                      tag-placeholder="Press Enter to add"
                      placeholder="General exam findings…"
                      @tag="(tag) => addTag('examFindings', tag)"
                  />
                  <ul class="print-only print-bullets">
                    <li v-for="(v, i) in selected.examFindings" :key="'p-e'+i">{{ labelOf(v) }}</li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- DIAGNOSIS -->
            <div class="rxs">
              <div class="rxs-t rxs-t--teal">✅ DIAGNOSIS / D/D</div>
              <div class="rxs-b">
                <multiselect
                    v-model="selected.diagnosis"
                    :options="suggestions.diagnosis"
                    :multiple="true" :taggable="true"
                    track-by="DiagnosisID"
                    label="DiagnosisName"
                    :close-on-select="false" :clear-on-select="false"
                    :preserve-search="true" :hide-selected="true"
                    tag-placeholder="Press Enter to add"
                    placeholder="Provisional / confirmed diagnosis…"
                    @tag="(tag) => addTag('diagnosis', tag)"
                    @search-change="(q) => onSearch('diagnosis', q)"
                />
                <ul class="print-only print-bullets">
                  <li v-for="(v, i) in selected.diagnosis" :key="'p-d'+i">{{ labelOf(v) }}</li>
                </ul>
              </div>
            </div>

            <!-- INVESTIGATIONS -->
            <div class="rxs">
              <div class="rxs-t">🔬 INVESTIGATIONS</div>
              <div class="rxs-b">
                <multiselect
                    v-model="selected.investigations"
                    :options="suggestions.investigations"
                    :multiple="true" :taggable="true"
                    track-by="InvestigationSetupID"
                    label="InvestigationName"
                    :close-on-select="false" :clear-on-select="false"
                    :preserve-search="true" :hide-selected="true"
                    tag-placeholder="Press Enter to add"
                    placeholder="CBC, FBS, ECG, Echo…"
                    @tag="(tag) => addTag('investigations', tag)"
                    @search-change="(q) => onSearch('investigations', q)"
                />
                <ul class="print-only print-bullets">
                  <li v-for="(v, i) in selected.investigations" :key="'p-i'+i">{{ labelOf(v) }}</li>
                </ul>
              </div>
            </div>

          </div>

          <!-- RIGHT PANEL -->
          <div class="rp-right">

            <!-- Medicine Table -->
            <div class="rxs">
              <div class="rxs-t rxs-t--teal rxs-t--rx">
                <span class="rx-sym">℞</span>&nbsp; PRESCRIPTION
                <span class="rxs-t-actions no-print">
                  <button class="btn-mini" type="button" @click="addMedicine">＋ Add</button>
                  <button class="btn-mini btn-mini--ghost" type="button" @click="clearAllMedicines" :disabled="!form.medicines.length">Clear</button>
                </span>
              </div>
              <div class="rxs-b" style="padding:0">
                <table class="med-tbl">
                  <thead>
                  <tr>
                    <th style="width:22px"></th>
                    <th style="width:32%">MEDICINE NAME &amp; STRENGTH </th>
                    <th style="width:15%">DOSAGE</th>
                    <th style="width:13%">DURATION</th>
                    <th>INSTRUCTIONS</th>
                    <th class="no-print" style="width:28px"></th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr v-for="(med, i) in form.medicines" :key="'med-'+i">

                    <td class="rn">{{ i + 1 }}.</td>

                    <!-- MEDICINE -->
                    <td class="cell-med">
                      <multiselect
                          v-model="form.medicines[i].selectedMedicine"
                          :options="medicineOptions"
                          :multiple="false"
                          :searchable="true"
                          :taggable="true"
                          :internal-search="false"
                          :loading="medicineLoading"
                          :preserve-search="true"
                          :clear-on-select="false"
                          :show-labels="false"
                          track-by="MedicineID"
                          label="MedicineName"
                          tag-placeholder="Press Enter to add"
                          placeholder="Search or create medicine..."
                          @tag="(tag) => onMedicineTag(i, tag)"
                          @search-change="(q) => onMedicineSearch(q)"
                          @select="(item) => onMedicineSelect(i, item)"
                      />
                      <span class="print-only print-cell print-cell--med">
                          {{ med.selectedMedicine?.MedicineName || med.name || '' }}
                      </span>

                    </td>

                    <!-- DOSAGE -->
                    <td class="dos">
                      <multiselect
                          v-model="form.medicines[i].dosage"
                          :options="form.medicines[i].dosageOptions && form.medicines[i].dosageOptions.length
                            ? form.medicines[i].dosageOptions
                            : suggestions.dosagePatterns"
                          :multiple="false"
                          :taggable="true"
                          :show-labels="false"
                          placeholder="Dosage"
                          @tag="val => { form.medicines[i].dosage = val }"
                      />
                      <span class="print-only print-cell print-cell--dos">{{ labelOf(med.dosage) }}</span>
                    </td>

                    <!-- DURATION -->
                    <td>
                      <multiselect
                          v-model="form.medicines[i].duration"
                          :options="suggestions.durationPatterns"
                          :multiple="false"
                          :taggable="true"
                          :show-labels="false"
                          placeholder="Duration"
                          @tag="val => { form.medicines[i].duration = val }"
                      />
                      <span class="print-only print-cell">{{ labelOf(med.duration) }}</span>
                    </td>

                    <!-- INSTRUCTION -->
                    <td>
                      <multiselect
                          v-model="form.medicines[i].instructions"
                          :options="suggestions.instructionPatterns"
                          :multiple="false"
                          :taggable="true"
                          :show-labels="false"
                          placeholder="Instructions"
                          @tag="val => { form.medicines[i].instructions = val }"
                      />
                      <span class="print-only print-cell">{{ labelOf(med.instructions) }}</span>
                    </td>

                    <!-- REMOVE -->
                    <td class="cell-rm no-print">
                      <button class="row-rm" type="button" @click="removeMedicine(i)">✕</button>
                    </td>

                  </tr>
                  <tr v-if="!form.medicines.length" class="no-print">
                    <td colspan="6" class="empty-row">
                      No medicines added. Click <strong>＋ Add</strong> to start.
                    </td>
                  </tr>
                  </tbody>
                </table>
                <div class="med-foot no-print">
                  <button class="btn-mini" type="button" @click="addMedicine">＋ Add Medicine</button>
                </div>
              </div>
            </div>

            <!-- Advice -->
            <div class="rxs">
              <div class="rxs-t">💡 ADVICE &amp; LIFESTYLE</div>
              <div class="rxs-b">
                <multiselect
                    v-model="selected.advice"
                    :options="suggestions.advice"
                    :multiple="true" :taggable="true"
                    track-by="AdviceTemplateID"
                    label="AdviceText"
                    :close-on-select="false" :clear-on-select="false"
                    :preserve-search="true" :hide-selected="true"
                    tag-placeholder="Press Enter to add"
                    placeholder="Low salt diet · 30-min walk · No smoking…"
                    @tag="(tag) => addTag('advice', tag)"
                    @search-change="(q) => onSearch('advice', q)"
                />
                <ul class="print-only print-bullets">
                  <li v-for="(v, i) in selected.advice" :key="'p-a'+i">{{ labelOf(v) }}</li>
                </ul>
              </div>
            </div>

            <!-- Referral -->
            <div class="rxs">
              <div class="rxs-t">📄 REFERRAL / NOTES</div>
              <div class="rxs-b">
                <multiselect
                    v-model="selected.referral"
                    :options="suggestions.referral"
                    :multiple="true" :taggable="true"
                    :close-on-select="false" :clear-on-select="false"
                    :preserve-search="true" :hide-selected="true"
                    tag-placeholder="Press Enter to add"
                    placeholder="Referred to: BSMMU Cardiology…"
                    @tag="(tag) => addTag('referral', tag)"
                />
                <ul class="print-only print-bullets">
                  <li v-for="(v, i) in selected.referral" :key="'p-r'+i">{{ labelOf(v) }}</li>
                </ul>
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

      </div>
    </div>
  </div>
</template>

<script>
import { Common } from '../../mixins/common'
import Multiselect from 'vue-multiselect'
import 'vue-multiselect/dist/vue-multiselect.min.css'

const EMPTY_MED = () => ({
  selectedMedicine: null,
  medicineID: null,
  name: '',
  dosage: '',
  duration: '',
  instructions: '',
  dosageOptions: [],
})
const AutocompleteInput = {
  name: 'AutocompleteInput',
  props: {
    value:         { default: '' },
    staticOptions: { type: Array, default: () => [] },
    fetcher:       { type: Function, default: null },
    itemLabel:     { type: Function, default: x => (typeof x === 'string' ? x : x.label || x.name || '') },
    placeholder:   { type: String, default: '' },
  },
  data() { return { open: false, items: [], hoverIdx: -1, busy: false, debounce: null } },
  watch: { value(v) { if (this.open) this.maybeSearch(v) } },
  methods: {
    maybeSearch(q) {
      if (this.fetcher) {
        clearTimeout(this.debounce)
        this.debounce = setTimeout(async () => {
          this.busy = true
          try {
            const res = await this.fetcher(q || '')
            this.items = Array.isArray(res) ? res.slice(0, 12) : []
          } catch { this.items = [] }
          this.busy = false
        }, 200)
      } else {
        const qq = String(q || '').toLowerCase()
        this.items = this.staticOptions
            .filter(o => !qq || String(this.itemLabel(o)).toLowerCase().includes(qq))
            .slice(0, 12)
      }
    },
    onInput(e) { this.$emit('input', e.target.value) },
    onFocus()  { this.open = true; this.maybeSearch(this.value) },
    onBlur()   { setTimeout(() => { this.open = false }, 150) },
    pick(item) {
      const lbl = this.itemLabel(item)
      this.$emit('input', lbl)
      this.$emit('select', item)
      this.open = false; this.hoverIdx = -1
    },
    onKey(e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); this.open = true; this.hoverIdx = Math.min(this.hoverIdx + 1, this.items.length - 1) }
      else if (e.key === 'ArrowUp') { e.preventDefault(); this.hoverIdx = Math.max(this.hoverIdx - 1, -1) }
      else if (e.key === 'Enter' && this.hoverIdx >= 0 && this.items[this.hoverIdx]) {
        e.preventDefault(); this.pick(this.items[this.hoverIdx])
      } else if (e.key === 'Escape') { this.open = false }
    },
  },
  template: `
    <div class="aci">
    <input
        type="text"
        :value="value"
        :placeholder="placeholder"
        @input="onInput"
        @focus="onFocus"
        @blur="onBlur"
        @keydown="onKey"
    />
    <ul v-if="open && items.length" class="aci-drop">
      <li v-for="(o, i) in items" :key="i"
          :class="{ active: i === hoverIdx }"
          @mousedown.prevent="pick(o)">
        {{ itemLabel(o) }}
      </li>
    </ul>
    </div>
  `,
}
const MS_FIELDS = ['complaints', 'history', 'examFindings', 'diagnosis',
  'investigations', 'advice', 'referral']

export default {
  name: 'WritePrescription',
  mixins: [Common],
  components: { Multiselect, AutocompleteInput },

  data() {
    return {
      medicineOptions: [],

      medicineLoading: false,
      saving:       false,
      patientsList: [],
      doctor:       {},
      hasPriorRx:   false,
      lastRx:       null,

      selected: {
        complaints:     [],
        history:        [],
        examFindings:   [],
        diagnosis:      [],
        investigations: [],
        advice:         [],
        referral:       [],
      },

      suggestions: {
        complaints:[],
        history: [],
        examFindings: [
          'Anaemia present', 'No anaemia', 'No jaundice', 'No oedema',
          'JVP not raised', 'Heart sounds normal', 'Lungs clear',
          'Abdomen soft, non-tender', 'CNS — intact',
        ],
        diagnosis: [],
        investigations:[],
        advice: [],
        referral: [],
        dosagePatterns: [],
        durationPatterns: [],
        instructionPatterns: [],
        medicine: [],
      },

      form: {
        patientID:      '',
        date:           new Date().toISOString().slice(0, 10),
        nextVisit:      '',
        vitals: { bp: '', pulse: '', temp: '', weight: '', spo2: '', rbs: '' },
        medicines: [
          {
            selectedMedicine: null,
            medicineID: null,
            name: '',
            dosage: '',
            duration: '',
            instructions: '',
            dosageOptions: []
          }
        ]
      },
    }
  },

  computed: {
    selectedPatient() {
      if (!this.form.patientID) return {}
      const id = Number(this.form.patientID)
      return this.patientsList.find(p => Number(p.PatientID) === id) || {}
    },
    patientAllergies() {
      const a = this.selectedPatient.Allergies || this.selectedPatient.allergies || ''
      return String(a).split(/[,;\n]/).map(s => s.trim()).filter(Boolean)
    },
    patientChronic() {
      const c = this.selectedPatient.ChronicConditions
          || this.selectedPatient.chronic
          || this.selectedPatient.MedicalHistory
          || ''
      return String(c).split(/[,;\n]/).map(s => s.trim()).filter(Boolean)
    },
  },

  mounted() {
    this.loadDoctor()
    this.loadAllLookups()
    if (this.$route.params.id) this.loadPrescription(this.$route.params.id)
  },

  methods: {
    // patients details
    onPatientChange() {
      const p = this.selectedPatient
      if (!p || !p.PatientID) return
      //
      // const histSet = new Set(this.selected.history)
      // this.patientChronic.forEach(c => histSet.add(c))
      //
      // this.patientAllergies.forEach(a => histSet.add('Allergic to ' + a))
      //
      // this.selected.history = Array.from(histSet)
      //
      // this.selected.history.forEach(h => {
      //   if (!this.suggestions.history.includes(h)) this.suggestions.history.push(h)
      // })

      this.axiosGet(`patients/${p.PatientID}`, (res) => {
        const full = res.data || res
        const idx = this.patientsList.findIndex(x => Number(x.PatientID) === Number(p.PatientID))
        if (idx !== -1) this.patientsList.splice(idx, 1, { ...this.patientsList[idx], ...full })
      }, () => {})

      this.fetchLastRx(p.PatientID)
    },
    fetchLastRx(patientID) {
      this.hasPriorRx = false
      this.lastRx = null
      this.axiosGet(`prescriptions/last-by-patient/${patientID}`, ({ data }) => {
            const rx = data || {}
            const hasData =
                rx.diagnosis ||
                rx.medicines?.length
            if (!hasData) return
            this.hasPriorRx = true
            this.lastRx = rx
            this.prescriptionID = rx.prescriptionID
          }

      )
    },
    loadFromLastVisit() {
      this.loadPrescription(this.prescriptionID)
      if (!this.lastRx) return

      const rx = this.lastRx

      const mapField = {
        complaints: {
          label: 'ComplaintName',
          id: 'ComplaintID',
        },
        diagnosis: {
          label: 'DiagnosisName',
          id: 'DiagnosisID',
        },
        investigations: {
          label: 'InvestigationName',
          id: 'InvestigationSetupID',
        },
        advice: {
          label: 'AdviceText',
          id: 'AdviceTemplateID',
        },
      }

      const fillArr = (key, raw) => {
        if (!raw) {
          this.selected[key] = []
          return
        }

        const config = {
          complaints: {
            label: 'ComplaintName',
            id: 'ComplaintID'
          },
          diagnosis: {
            label: 'DiagnosisName',
            id: 'DiagnosisID'
          },
          investigations: {
            label: 'InvestigationName',
            id: 'InvestigationSetupID'
          },
          advice: {
            label: 'AdviceText',
            id: 'AdviceTemplateID'
          }
        }[key]

        let arr = []

        if (Array.isArray(raw)) {
          arr = raw.map((item, idx) => {

            // already object from DB
            if (typeof item === 'object') {

              return {
                ...item,

                // ensure label exists
                [config.label]:
                item[config.label] ||
                item.label ||
                item.name ||
                '',

                // ensure ID exists
                [config.id]:
                item[config.id] ||
                item.id ||
                `tmp_${idx}_${Date.now()}`
              }
            }

            // plain string
            return {
              [config.label]: item,
              [config.id]: `tmp_${idx}_${Date.now()}`
            }
          })
        }

        // string separated by newline
        else if (typeof raw === 'string') {
          arr = raw
              .split('\n')
              .map((s, idx) => ({
                [config.label]: s.trim(),
                [config.id]: `tmp_${idx}_${Date.now()}`
              }))
              .filter(v => v[config.label])
        }

        this.selected[key] = arr

        // merge into options
        arr.forEach(item => {

          const exists = this.suggestions[key].some(opt => {

            if (typeof opt === 'object') {
              return (
                  opt[config.id] === item[config.id] ||
                  opt[config.label] === item[config.label]
              )
            }

            return opt === item[config.label]
          })

          if (!exists) {
            this.suggestions[key].push(item)
          }
        })
      }

      fillArr('complaints', rx.complaints)
      fillArr('diagnosis', rx.diagnosis)
      fillArr('investigations', rx.investigations)
      fillArr('advice', rx.advice)

      // medicines
      if (
          rx.medicines &&
          rx.medicines.length &&
          this.form.medicines.every(m => !m.name?.trim())
      ) {
        this.form.medicines = rx.medicines.map(m => ({
          ...EMPTY_MED(),

          selectedMedicine: {
            MedicineID: m.medicineID || `custom_${Date.now()}`,
            MedicineName: m.name || m.MedicineName || '',
          },

          medicineID: m.medicineID || null,
          name: m.name || m.MedicineName || '',
          dosage: m.dosage || '',
          duration: m.duration || '',
          instructions: m.instructions || '',
          dosageOptions: this.suggestions.dosagePatterns || [],
        }))
      }

      this.successNoti &&
      this.successNoti('Loaded from last visit.')
    },

    labelOf(v) {
      if (!v) return ''
      if (typeof v === 'string') return v
      return v.ComplaintName || v.DiagnosisName || v.InvestigationName
          || v.AdviceText    || v.ConditionName  || v.HistoryName
          || v.label || v.name || v.MedicineName || ''
    },
    onSearch(field, q) {
      const types = {
        complaints:     'complaints',
        diagnosis:      'diagnosis',
        investigations: 'investigations',
        advice:         'advice',
      }
      const type = types[field]
      if (!type || !q || q.length < 2) return

      clearTimeout(this._searchTimer)
      this._searchTimer = setTimeout(() => {
        this.axiosGet(`lookup/${type}?q=${encodeURIComponent(q)}`, (res) => {
          const data = res.data || res || []
          data.forEach(item => {
            const label = item.label || item.name || item
            if (!this.suggestions[field].includes(label)) {
              this.suggestions[field].push(label)
            }
          })
        })
      }, 250)
    },

    onMedicineSearch(q) {

      if (!q || q.length < 2) {
        // Show all preloaded options when query is cleared
        return
      }
      clearTimeout(this._medSearchTimer)
      this.medicineLoading = true
      this._medSearchTimer = setTimeout(() => {
        this.axiosGet(`lookup/medicine?q=${encodeURIComponent(q)}`, (res) => {
          const data = res.data || res || []
          const incoming = data.map((item, idx) => ({
            ...item,
            MedicineID: item.MedicineID || item.id || item.medicine_id || `s_${idx}`,
            MedicineName: item.MedicineName || item.label || item.name || '',
          }))
          // Replace options with search results + keep already-selected ones
          const selected = this.form.medicines
              .map(m => m.selectedMedicine)
              .filter(Boolean)
          const merged = [...selected]
          incoming.forEach(inc => {
            if (!merged.some(o => o.MedicineID === inc.MedicineID)) {
              merged.push(inc)
            }
          })
          this.medicineOptions = merged
          this.medicineLoading = false
        }, () => { this.medicineLoading = false })
      }, 250)
    },

    onMedicineTag(index, tag) {
      const t = String(tag || '').trim()
      if (!t) return

      const customMed = {
        MedicineID: `custom_${Date.now()}`,
        MedicineName: t
      }

      // add into options also
      this.medicineOptions.push(customMed)

      this.$set(this.form.medicines, index, {
        ...this.form.medicines[index],
        selectedMedicine: customMed,
        medicineID: null,
        name: t,
        dosageOptions: [...this.suggestions.dosagePatterns],
      })

      // auto add row
      if (index === this.form.medicines.length - 1) {
        this.$nextTick(() => {
          this.form.medicines.push(EMPTY_MED())
        })
      }
    },

    onMedicineSelect(index, item) {
      console.log('item',item);
      if (!item) return
      const med = this.form.medicines[index]

      // Assign medicine details
      med.medicineID    = item.MedicineID
      med.name          = item.MedicineName
      med.dosageOptions = item.dosageOptions?.length
          ? item.dosageOptions
          : this.suggestions.dosagePatterns

      if (!med.dosage)       med.dosage       = item.DefaultDosage       || ''
      if (!med.duration)     med.duration     = item.DefaultDuration     || ''
      if (!med.instructions) med.instructions = item.DefaultInstructions || ''

      // Force Vue reactivity — replace the object
      this.$set(this.form.medicines, index, { ...med })

      // Auto-add new row if this was the last row
      if (index === this.form.medicines.length - 1) {
        this.$nextTick(() => this.form.medicines.push(EMPTY_MED()))
      }
    },

// UPDATE — addTag: when a complaint is added, fetch suggested medicines for it
    addTag(field, tag) {
      const t = String(tag || '').trim()
      if (!t) return

      const already = (this.selected[field] || []).some(
          s => String(s).toLowerCase() === t.toLowerCase()
      )
      if (already) return

      if (!this.suggestions[field].some(
          s => String(s).toLowerCase() === t.toLowerCase()
      )) {
        this.suggestions[field].push(t)
      }
      this.selected[field].push(t)

      // Save to DB
      const dbType = {
        complaints:     'complaints',
        diagnosis:      'diagnosis',
        investigations: 'investigations',
        advice:         'advice',
      }[field]

      if (dbType) {
        this.axiosPost(`lookup/${dbType}`, { label: t }, (res) => {
          // When a complaint is saved, the backend returns its ID.
          // Use that complaint_id to fetch related medicine suggestions.
          if (field === 'complaints' && res && res.data) {
            const complaintID = res.data.id || res.data.ComplaintID || res.id
            if (complaintID) this.fetchMedicinesForComplaint(complaintID)
          }
        }, () => {})
      }
    },

// NEW — fetch medicine suggestions linked to a complaint
    fetchMedicinesForComplaint(complaintID) {
      this.axiosGet(`lookup/medicines-by-complaint/${complaintID}`, (res) => {
        const data = res.data || res || []
        const incoming = data.map(item => ({
          ...item,
          MedicineID: item.MedicineID || item.id,
          MedicineName: item.MedicineName || item.label || item.name || '',
        }))
        incoming.forEach(inc => {
          const exists = this.medicineOptions.some(o => o.MedicineID === inc.MedicineID)
          if (!exists) this.medicineOptions.push(inc)
        })
      }, () => {})
    },
    loadDoctor() {
      this.axiosGet('doctor/info', (res) => {
        if (res) {
          this.doctor = res.data || res
          if (this.doctor.DoctorID) this.loadPatients(this.doctor.DoctorID)
        }
      }, () => {})
    },
    loadPatients(DoctorID) {
      this.axiosGet('patients/list/' + DoctorID, (res) => {
        const list = res.data || res
        this.patientsList = (list || []).map(p => ({
          ...p,
          PatientID: Number(p.PatientID),
          FullName: p.FullName || p.PatientName || p.Name,
        }))
      }, err => this.errorNoti(err))
    },
    loadAllLookups() {
      this.axiosGet('lookup/all', (res) => {
        const data = res || {}

        this.suggestions.complaints = (data.complaints || []).map(i => ({
          ComplaintID: i.ComplaintID,
          ComplaintName: i.ComplaintName
        }))

        this.suggestions.diagnosis = (data.diagnosis || []).map(i => ({
          DiagnosisID: i.DiagnosisID,
          DiagnosisName: i.DiagnosisName
        }))

        this.suggestions.investigations = (data.investigations || []).map(i => ({
          InvestigationSetupID: i.InvestigationSetupID,
          InvestigationName: i.InvestigationName
        }))

        this.suggestions.advice = (data.advice || []).map(i => ({
          AdviceTemplateID: i.AdviceTemplateID,
          AdviceText: i.AdviceText
        }))
        this.suggestions.history             = data.history             || []
        this.suggestions.dosagePatterns      = data.dosage              || []
        this.suggestions.durationPatterns    = data.duration            || []
        this.suggestions.instructionPatterns = data.instruction         || []
        let medicineList                = data.medicine || []
        this.medicineOptions = medicineList.map(item => ({
          'MedicineName': `${item.BrandName} (${item.Strength}) `,
          'MedicineID': item.MedicineID
        }));
      });
    },
    loadPrescription(id) {
      this.axiosGet(`prescriptions/${id}`, (res) => {
        const rx = res.data || res

        this.form.patientID  = rx.patientID  || rx.PatientID  || ''
        this.form.date       = rx.date       || rx.VisitDate  || new Date().toISOString().slice(0, 10)
        this.form.nextVisit  = rx.nextVisit  || rx.NextVisitDate || ''
        this.form.vitals     = {
          bp:     rx.vitals?.bp     || (rx.BPSystolic ? `${rx.BPSystolic}/${rx.BPDiastolic}` : ''),
          pulse:  rx.vitals?.pulse  || rx.PulseRate   || '',
          temp:   rx.vitals?.temp   || rx.Temperature || '',
          weight: rx.vitals?.weight || rx.Weight      || '',
          spo2:   rx.vitals?.spo2   || rx.SpO2        || '',
          rbs:    rx.vitals?.rbs    || rx.RBS         || '',
        }

        // Re-hydrate medicines — map saved rows back to selectedMedicine shape
        this.form.medicines = (rx.medicines && rx.medicines.length)
            ? rx.medicines.map(m => ({
              ...EMPTY_MED(),
              selectedMedicine: m.medicineID
                  ? { MedicineID: m.medicineID, MedicineName: m.name }
                  : { MedicineID: `custom_${Date.now()}`, MedicineName: m.name },
              medicineID:   m.medicineID   || null,
              name:         m.name         || m.MedicineName || '',
              dosage:       m.dosage       || m.Dosage       || '',
              duration:     m.duration     || m.Duration     || '',
              instructions: m.instructions || m.Instructions || '',
              dosageOptions: [],
            }))
            : [EMPTY_MED()]

        // Re-hydrate multiselects — backend returns arrays of objects or plain strings
        const toStrings = (raw) => {
          if (!raw) return []
          if (typeof raw === 'string') return raw.split('\n').map(s => s.trim()).filter(Boolean)
          if (Array.isArray(raw)) return raw.map(v =>
              typeof v === 'string' ? v :
                  (v.ComplaintName || v.DiagnosisName || v.InvestigationName
                      || v.AdviceText || v.label || v.name || '')
          ).filter(Boolean)
          return []
        }

        this.selected.complaints     = toStrings(rx.complaints)
        this.selected.diagnosis      = toStrings(rx.diagnosis)
        this.selected.investigations = toStrings(rx.investigations)
        this.selected.advice         = toStrings(rx.advice)
        this.selected.history        = toStrings(rx.history || rx.HistoryOfIllness)
        this.selected.examFindings   = toStrings(rx.examFindings || rx.ExaminationNotes)
        this.selected.referral       = toStrings(rx.referral || rx.ReferralNotes)
      }, err => this.errorNoti(err))
    },



    /* ---------- Medicines --------------------------------- */
    addMedicine()       { this.form.medicines.push(EMPTY_MED()) },
    removeMedicine(i)   { this.form.medicines.splice(i, 1) },
    clearAllMedicines() {
      if (!this.form.medicines.length) return
      if (!window.confirm('Clear all prescribed medicines?')) return
      this.form.medicines = [EMPTY_MED()]
    },


    saveRx() {
      if (!this.form.patientID) return this.errorNoti('Please select a patient.')
      this.saving = true

      // ── Helper: extract plain string label from a value that may be
      //            an object {ComplaintName/DiagnosisName/...} or a plain string
      const toLabel = (v) => {
        if (!v) return ''
        if (typeof v === 'string') return v.trim()
        // Object from multiselect with track-by/label
        return (v.ComplaintName || v.DiagnosisName || v.InvestigationName
            || v.AdviceText    || v.ConditionName || v.label || v.name || '').trim()
      }

      // ── Arrays for fields that backend expects as arrays ──────────────
      const complaintsArr    = (this.selected.complaints     || []).map(toLabel).filter(Boolean)
      const diagnosisArr     = (this.selected.diagnosis      || []).map(toLabel).filter(Boolean)
      const investigationsArr= (this.selected.investigations || []).map(toLabel).filter(Boolean)
      const adviceArr        = (this.selected.advice         || []).map(toLabel).filter(Boolean)

      // ── Strings for fields backend expects as nullable string ─────────
      const historyStr       = (this.selected.history      || []).map(toLabel).filter(Boolean).join('\n')
      const examFindingsStr  = (this.selected.examFindings || []).map(toLabel).filter(Boolean).join('\n')
      const referralStr      = (this.selected.referral     || []).map(toLabel).filter(Boolean).join('\n')

      // ── Medicines — only filled rows ──────────────────────────────────
      const filledMedicines = this.form.medicines
          .filter(m => {
            // Accept both object-selected and manually typed name
            const name = m.selectedMedicine?.MedicineName
                || m.selectedMedicine?.label
                || m.name || ''
            return name.trim().length > 0
          })
          .map(m => {
            const rawID = m.selectedMedicine?.MedicineID || m.medicineID || null
            // Custom medicines have string IDs like "custom_1234" — send null for those
            const medicineID = (rawID && !String(rawID).startsWith('custom_') && !String(rawID).startsWith('str_'))
                ? Number(rawID)
                : null

            return {
              medicineID,
              name:         m.selectedMedicine?.MedicineName
                  || m.selectedMedicine?.label
                  || m.name || '',
              dosage:       m.dosage        || '',
              duration:     m.duration      || '',
              instructions: m.instructions  || '',
            }
          })

      if (!filledMedicines.length) {
        this.saving = false
        return this.errorNoti('Please add at least one medicine.')
      }

      // ── Final payload ─────────────────────────────────────────────────
      const payload = {
        patientID:      this.form.patientID,
        DoctorID:       this.doctor.DoctorID,
        date:           this.form.date,
        nextVisit:      this.form.nextVisit || null,

        // Arrays — backend iterates these into child tables
        complaints:     complaintsArr,
        diagnosis:      diagnosisArr,
        investigations: investigationsArr,
        advice:         adviceArr,

        // Strings — backend stores directly on tbl_Prescription
        history:        historyStr   || null,
        examFindings:   examFindingsStr || null,
        referral:       referralStr  || null,

        // Vitals object — backend reads vitals.bp, vitals.pulse, etc.
        vitals: {
          bp:     this.form.vitals.bp     || null,
          pulse:  this.form.vitals.pulse  || null,
          temp:   this.form.vitals.temp   || null,
          weight: this.form.vitals.weight || null,
          spo2:   this.form.vitals.spo2   || null,
          rbs:    this.form.vitals.rbs    || null,
        },

        medicines: filledMedicines,
      }

      const id = this.$route.params.id
      const cb = (res) => {
        this.saving = false
        this.successNoti((res && res.message) || (id ? 'Prescription updated.' : 'Prescription saved.'))
        if (!id && res && res.id) this.$router.replace({ params: { id: res.id } })
      }
      const err = (e) => { this.saving = false; this.errorNoti(e) }

      if (id) this.axiosPost(`prescriptions/${id}`, { _method: 'PUT', ...payload }, cb, err)
      else    this.axiosPost('prescriptions', payload, cb, err)
    },

    printRx() {
      document.body.classList.add('rx-printing')
      setTimeout(() => {
        window.print()
        setTimeout(() => document.body.classList.remove('rx-printing'), 500)
      }, 50)
    },
  },
}
</script>

<!-- ════════════════════════════════════════════════════════════
     GLOBAL print styles — unscoped to hide app sidebar / navbar
     ════════════════════════════════════════════════════════════ -->
<style>
/* 1. RESET & PRINT DEFAULTS */
@media print {
  @page { size: A4 portrait; margin: 12mm 14mm; }
  html, body { background: #fff !important; margin: 0 !important; padding: 0 !important; }

  body * { visibility: hidden !important; }
  #rx-paper, #rx-paper * { visibility: visible !important; }
  #rx-paper {
    min-height: 100vh; padding: 0;
    box-shadow: none !important;
    position: absolute !important;
    left: 0 !important; top: 0 !important;
    width: 100% !important; margin: 0 !important;
    border-radius: 0 !important;
  }

  .no-print, .rx-topbar, .row-rm, .btn-mini, .med-foot, .multiselect {
    display: none !important;
  }

  /* Force print cells visible & give them table-cell padding */
  .print-only { display: block !important; visibility: visible !important; }
  .med-tbl td .print-cell {
    display: block !important;
    padding: 6px 8px !important;
    font-size: 12px !important;
    color: #000 !important;
    min-height: 18px;
  }
  .med-tbl td .print-cell--med { font-weight: 600 !important; }
  .med-tbl td .print-cell--dos {
    font-family: 'Courier New', monospace !important;
    font-weight: 700 !important;
    color: #0b8a79 !important;
  }

  /* Hide medicine rows that have no medicine name */

  .med-tbl input, .vb input, .nv-wrap input, .fc {
    border: none !important;
    background: transparent !important;
    color: #000 !important;
    padding: 2px 4px !important;
  }

  .print-bullets { list-style: none; margin: 4px 0; padding: 0 0 0 4px; }
  .print-bullets li {
    position: relative; padding: 1px 0 1px 14px;
    font-size: 12px; color: #000;
    page-break-inside: avoid;
  }
  .print-bullets li::before {
    content: '•'; position: absolute;
    left: 2px; top: 0; font-weight: 700;
  }

  .no-print-select { display: none !important; }
  .print-value {
    display: block !important; visibility: visible !important;
    border: none !important; padding: 2px 0 !important;
    font-weight: 600 !important;
  }
}

.print-only { display: none; }


/* 2. MAIN LAYOUT (FLEXBOX) */
.rx-paper {
  display: flex;
  flex-direction: column;
  max-width: 1100px;
  min-height: 297mm; /* Standard A4 height */
  margin: 18px auto 40px;
  background: #fff;
  border-radius: 9px;
  box-shadow: 0 6px 24px rgba(0,0,0,.13);
  box-sizing: border-box;
  overflow: hidden;
}

/* rp-cols acts as the "stretcher" to push the footer down */
.rp-cols {
  flex: 1;
  display: grid;
  grid-template-columns: 350px 1fr;
}

/* 3. HEADER & SECTIONS */
.rp-hdr {
  background: linear-gradient(135deg, #0b2545 0%, #1a6fb5 100%);
  color: #fff; padding: 16px 22px;
  display: grid; grid-template-columns: 66px 1fr auto;
  gap: 14px; align-items: center;
  border-bottom: 3px solid #0fa595;
}

.rp-left { border-right: 1.5px solid #e2e8f0; padding: 15px; background: #fafcfe; }
.rp-right { padding: 15px; }

/* 4. FOOTER (MODERNIZED BASED ON IMAGE REFERENCE) */
.rp-foot {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  padding: 25px 30px;
  border-top: 1.5px solid #eee;
  background: #fff;
  margin-top: auto; /* Keeps footer at bottom of flex container */
  break-inside: avoid;
}

.foot-note {
  font-size: 11px;
  color: #666;
  /*max-width: 30%;*/
  line-height: 1.4;
  text-align: left;
}

.nv-wrap {
  text-align: center;
}

.nv-wrap label {
  display: block;
  font-size: 10px;
  font-weight: bold;
  color: #1a6fb5;
  text-transform: uppercase;
  margin-bottom: 5px;
}

.nv-wrap input {
  border: none;
  border-bottom: 2px solid #000;
  padding: 2px 5px;
  font-size: 14px;
  width: 160px;
  text-align: center;
  outline: none;
  background: transparent;
}

.sig-box {
  text-align: center;
}

.sig-line {
  border-bottom: 2px solid #000;
  margin-bottom: 8px;
  width: 100%;
}

.sig-lbl {
  font-size: 11px;
  font-weight: 600;
  color: #444;
}

/* 5. TABLE & INPUTS */
.med-tbl { width: 100%; border-collapse: collapse; }
.med-tbl thead tr { background: #0b2545; }
.med-tbl thead th {
  padding: 8px; text-align: left;
  font-size: 10px; color: #fff;
  text-transform: uppercase;
}
.med-tbl td { border-bottom: 1px solid #eee; }

.dos input {
  font-family: 'Courier New', monospace;
  font-weight: 700;
  color: #0b8a79;
}

/* Helper to hide empty rows on screen/print */

.print-only { display: none; }
</style>

<style scoped>
.rp-foot {
  display: flex;
  justify-content: space-between; /* Pushes items to left, center, and right */
  align-items: flex-end;         /* Aligns all bottom text on the same baseline */
  padding: 20px 0;
  border-top: 1px solid #eee;
  margin-top: auto;              /* Essential for flex-column papers */
}

.foot-note {
  font-size: 11px;
  color: #666;
  /*max-width: 30%;*/
  line-height: 1.4;
}

.nv-wrap {
  text-align: center;
}

.nv-wrap label {
  display: block;
  font-size: 10px;
  font-weight: bold;
  color: #888;
  margin-bottom: 5px;
}

/* Matching the underlined style for the date input in image_fd44e4.png */
.nv-wrap input {
  border: none;
  border-bottom: 2px solid #000;
  padding: 2px 5px;
  font-size: 14px;
  outline: none;
  background: transparent;
}


.sig-line {
  border-bottom: 2px solid #000;
  margin-bottom: 5px;
}

.sig-lbl {
  font-size: 11px;
  color: #777;
}
.rx-fullscreen { position: relative; }
.rx-overlay {
  position: fixed; inset: 0; background: #edf2f7; z-index: 800;
  overflow-y: auto; font-family: 'Segoe UI', system-ui, Helvetica, sans-serif;
  font-size: 13.5px; color: #111827;
}

.rx-topbar {
  position: sticky; top: 0;
  background: linear-gradient(90deg, #0b2545, #163060);
  color: #fff; padding: 9px 20px;
  display: flex; align-items: center; gap: 10px;
  z-index: 10; box-shadow: 0 2px 12px rgba(0,0,0,.25);
}
.rx-topbar h2 { font-size: 14.5px; font-weight: 700; flex: 1; }
.tb-btn {
  padding: 6px 14px; border-radius: 5px; font-size: 12.5px; font-weight: 600;
  border: 1.5px solid rgba(255,255,255,.22); cursor: pointer;
  background: rgba(255,255,255,.1); color: #fff; transition: background .13s;
}
.tb-btn:hover { background: rgba(255,255,255,.2); }
.tb-btn--save { background: #059669; border-color: #059669; }
.tb-btn--save:hover { background: #047857; }
.tb-btn:disabled { opacity: .6; cursor: not-allowed; }

.rx-paper {
  max-width: 1100px; margin: 18px auto 40px;
  background: #fff; border-radius: 9px;
  box-shadow: 0 6px 24px rgba(0,0,0,.13);
}

.rp-hdr {
  background: linear-gradient(135deg, #0b2545 0%, #1a6fb5 100%);
  color: #fff; padding: 16px 22px;
  display: grid; grid-template-columns: 66px 1fr auto;
  gap: 14px; align-items: center;
  border-bottom: 3px solid #0fa595;
  border-radius: 9px 9px 0 0;
}
.rp-logo {
  width: 58px; height: 58px;
  border: 2px dashed rgba(255,255,255,.3); border-radius: 5px;
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

.rp-pt { background: #f9fafb; border-bottom: 1.5px solid #e2e8f0; padding: 10px 18px; }
.band-lbl {
  font-size: 9.5px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .1em;
  color: #1a6fb5; margin-bottom: 7px;
}
.rp-pt-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1.2fr; gap: 9px; }
.rp-field label {
  display: block; font-size: 9.5px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .06em;
  color: #9ca3af; margin-bottom: 3px;
}
.fc {
  width: 100%; border: 1.5px solid #d1d5db; border-radius: 4px;
  padding: 5px 8px; font-size: 12.5px; font-family: inherit;
  outline: none; transition: border-color .12s; background: #fff;
}
.fc:focus { border-color: #1a6fb5; }
.print-value {
  border: 1.5px solid #d1d5db; border-radius: 4px;
  padding: 5px 8px; font-size: 12.5px; font-weight: 600;
}

.pt-chips { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 8px; }
.chip {
  font-size: 10.5px; padding: 2px 8px; border-radius: 11px;
  background: #eef2f7; color: #374151; border: 1px solid #d8dde6;
}
.chip--info  { background: #fde2e1; color: #b91c1c; border-color: #f5b7b3; }
.chip--warn  { background: #fff4d6; color: #9a6b00; border-color: #f5deaa; }
.chip--muted { background: #eaf5ff; color: #0b2545; border-color: #c8e0f4; }
.chip--link  { cursor: pointer; background: #dcfce7; color: #166534; border-color: #b8e6c2; font-weight: 600; }
.chip--link:hover { background: #bbf7d0; }

.rp-cols { display: grid; grid-template-columns: 350px 1fr; min-height: 550px; }
.rp-left {
  border-right: 1.5px solid #e2e8f0; padding: 11px 10px;
  background: #fafcfe; display: flex; flex-direction: column; gap: 9px;
}
.rp-right { padding: 11px 15px; display: flex; flex-direction: column; gap: 9px; }

.rxs { border: 1px solid #e2e8f0; border-radius: 5px; overflow: visible; background: #fff; }
.rxs-t {
  background: #d6eaf8; border-bottom: 1px solid #e2e8f0;
  padding: 4px 9px; font-size: 9.5px; font-weight: 700;
  letter-spacing: .08em; text-transform: uppercase;
  color: #0b2545; display: flex; align-items: center; gap: 5px;
}
.rxs-t--teal { background: #d0f0ec; color: #0b8a79; border-color: #a5d9d3; }
.rxs-t--rx   { font-size: 12px; padding: 6px 9px; }
.rxs-t-actions { margin-left: auto; display: flex; gap: 6px; }
.rx-sym      { font-size: 22px; font-weight: 700; font-family: Georgia, serif; line-height: 1; color: #0b8a79; }
.rxs-b { padding: 7px 8px; }

/* ── vue-multiselect overrides (compact look) ── */
.rxs-b >>> .multiselect              { min-height: 34px; font-size: 12.5px; }
.rxs-b >>> .multiselect__tags        { min-height: 34px; padding: 4px 36px 0 6px; border: 1.5px solid #d1d5db; border-radius: 4px; font-size: 12.5px; }
.rxs-b >>> .multiselect--active .multiselect__tags { border-color: #1a6fb5; }
.rxs-b >>> .multiselect__tag         { background: #e0f2fe; color: #075985; padding: 3px 22px 3px 8px; font-size: 11.5px; font-weight: 600; border-radius: 11px; border: 1px solid #bae6fd; margin: 2px 4px 2px 0; }
.rxs-b >>> .multiselect__tag-icon              { line-height: 18px; border-radius: 50%; }
.rxs-b >>> .multiselect__tag-icon::after       { color: #075985; font-size: 13px; }
.rxs-b >>> .multiselect__tag-icon:hover        { background: #b91c1c; }
.rxs-b >>> .multiselect__tag-icon:hover::after { color: #fff; }
.rxs-b >>> .multiselect__input,
.rxs-b >>> .multiselect__single      { font-size: 12.5px; padding: 2px 0 0 4px; margin-bottom: 4px; }
.rxs-b >>> .multiselect__placeholder { font-size: 12.5px; color: #9ca3af; padding-top: 4px; }
.rxs-b >>> .multiselect__select      { height: 30px; }
.rxs-b >>> .multiselect__select::before { border-color: #6b7280 transparent transparent; }
.rxs-b >>> .multiselect__content-wrapper { border-color: #d1d5db; box-shadow: 0 4px 12px rgba(0,0,0,.12); }
.rxs-b >>> .multiselect__option      { font-size: 12.5px; padding: 7px 11px; min-height: 30px; }
.rxs-b >>> .multiselect__option--highlight,
.rxs-b >>> .multiselect__option--selected.multiselect__option--highlight { background: #eef6fb; color: #0b2545; }
.rxs-b >>> .multiselect__option--highlight::after { background: #1a6fb5; }

/* ── AutocompleteInput (medicine cells) ── */
.aci { position: relative; }
.aci input {
  width: 100%; border: none; padding: 7px 8px;
  font-size: 12.5px; font-family: inherit;
  background: transparent; outline: none; color: #111827;
}
.aci input:focus { background: #eef6fb; }
.aci-drop {
  position: absolute; left: 0; right: 0; top: 100%;
  background: #fff; border: 1px solid #d1d5db; border-radius: 4px;
  box-shadow: 0 4px 12px rgba(0,0,0,.12); z-index: 60;
  list-style: none; padding: 4px 0; margin: 0;
  max-height: 220px; overflow-y: auto;
}
.aci-drop li { padding: 5px 10px; font-size: 12px; cursor: pointer; }
.aci-drop li.active, .aci-drop li:hover { background: #eef6fb; color: #0b2545; }

.btn-mini {
  font-size: 11px; padding: 2px 9px; border-radius: 4px;
  border: 1px solid #0b8a79; background: #0b8a79; color: #fff;
  cursor: pointer; font-weight: 600;
}
.btn-mini:hover { background: #097064; }
.btn-mini:disabled { opacity: .5; cursor: not-allowed; }
.btn-mini--ghost { background: #fff; color: #0b8a79; }
.btn-mini--ghost:hover { background: #effaf7; }

.vitals-g { display: grid; grid-template-columns: 1fr 1fr; gap: 5px; }
.vb { border: 1.5px solid #d1d5db; border-radius: 3px; padding: 4px 7px; background: #fff; }
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
.med-tbl td { padding: 0; vertical-align: middle; position: relative; }
.rn { width: 22px; padding: 7px 0 7px 7px; font-size: 9px; color: #9ca3af; font-weight: 700; text-align: center; }
.dos input { font-family: 'Courier New', monospace !important; font-weight: 700 !important; color: #0b8a79 !important; letter-spacing: .05em; }
.cell-rm { text-align: center; }
.row-rm {
  border: none; background: transparent; cursor: pointer;
  color: #b91c1c; font-size: 13px; padding: 4px 6px;
}
.row-rm:hover { color: #fff; background: #b91c1c; border-radius: 3px; }
.empty-row { padding: 14px; text-align: center; color: #9ca3af; font-size: 12px; }
.med-foot { padding: 6px; text-align: right; border-top: 1px solid #e2e8f0; background: #fafcfe; }

.rp-foot {
  border-top: 1.5px solid #e2e8f0; padding: 12px 18px;
  display: grid; grid-template-columns: 1fr auto 1fr;
  align-items: end; gap: 18px; background: #f9fafb;
  border-radius: 0 0 9px 9px;
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
.sig-line { width: 140px; height: 50px; border-bottom: 2px solid #374151; margin-left: auto; margin-bottom: 4px; }
.sig-lbl  { font-size: 10px; color: #9ca3af; text-align: center; width: 140px; margin-left: auto; }
</style>