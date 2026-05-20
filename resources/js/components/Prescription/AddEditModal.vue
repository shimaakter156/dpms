<template>
  <div>
    <div class="modal fade" id="open-add-medicine-modal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <div class="modal-title modal-title-font">
              Add New Medicine
            </div>
          </div>

          <ValidationObserver v-slot="{ handleSubmit }">
            <form @submit.prevent="handleSubmit(onSubmit)" autocomplete="off">
              <div class="modal-body">
                <div class="row">
                  <div class="col-12 col-md-6">
                    <ValidationProvider name="Brand Name" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label>Brand Name <span class="error">*</span></label>
                        <input class="form-control" :class="{'error-border': errors[0]}" v-model="form.BrandName">
                        <span class="error-message">{{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>

                  <div class="col-12 col-md-6">
                    <ValidationProvider name="Generic" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label>Generic <span class="error">*</span></label>
                        <multiselect
                            v-model="form.generic"
                            :options="generics"
                            :multiple="false"
                            :taggable="true"
                            :show-labels="false"
                            label="GenericName"
                            track-by="GenericID"
                            placeholder="Select or type generic"
                            @tag="addGenericTag"
                        />
                        <span class="error-message">{{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>

                  <div class="col-12 col-md-6">
                    <ValidationProvider name="Dosage Form" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label>Dosage Form <span class="error">*</span></label>
                        <multiselect
                            v-model="form.dosageForm"
                            :options="dosageForms"
                            :multiple="false"
                            :show-labels="false"
                            label="FormName"
                            track-by="DosageFormID"
                            placeholder="Tablet, Syrup, Injection..."
                        />
                        <span class="error-message">{{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>

                  <div class="col-12 col-md-6">
                    <ValidationProvider name="Strength" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label>Strength <span class="error">*</span></label>
                        <multiselect
                            v-model="form.Strength"
                            :options="strengths"
                            :multiple="false"
                            :show-labels="false"
                            label="StrengthLabel"
                            track-by="StrengthID"
                            placeholder="0.5mg..."
                        />
<!--                        <input class="form-control" :class="{'error-border': errors[0]}" v-model="form.Strength" placeholder="500mg">-->
                        <span class="error-message">{{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>

                  <div class="col-12 col-md-6">
                    <div class="form-group">
                      <label>Category</label>
                      <multiselect
                          v-model="form.category"
                          :options="categories"
                          :multiple="false"
                          :show-labels="false"
                          label="CategoryName"
                          track-by="MedicineCategoryID"
                          placeholder="Select category"
                      />
                    </div>
                  </div>

                  <div class="col-12 col-md-6">
                    <div class="form-group">
                      <label>Manufacturer</label>
                      <multiselect
                          v-model="form.manufacturer"
                          :options="manufacturers"
                          :multiple="false"
                          :show-labels="false"
                          label="ManufacturerName"
                          track-by="ManufacturerID"
                          placeholder="Select manufacturer"
                      />
                    </div>
                  </div>

                  <div class="col-12 col-md-4">
                    <div class="form-group">
                      <label>Unit</label>
                      <multiselect
                          v-model="form.Unit"
                          :options="units"
                          :multiple="false"
                          :taggable="true"
                          :show-labels="false"
                          label="UnitName"
                          track-by="UnitID"
                          placeholder="Select or type Unit"
                      />

<!--                      <input class="form-control" v-model="form.Unit" placeholder="pcs/ml">-->
                    </div>
                  </div>

                  <div class="col-12 col-md-4">
                    <div class="form-group">
                      <label>Unit Price</label>
                      <input type="number" step="0.01" class="form-control" v-model="form.UnitPrice">
                    </div>
                  </div>

                  <div class="col-12 col-md-4">
                    <div class="form-group">
                      <label>Owner</label>
                      <select class="form-control" v-model="form.ownerType">
                        <option value="doctor">Doctor Medicine</option>
                        <option value="hospital">Hospital Medicine</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal-footer">
                <submit-form :name="buttonText"/>
                <button type="button" class="btn btn-secondary" @click="closeModal">Close</button>
              </div>
            </form>
          </ValidationObserver>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { bus } from '../../app'
import { Common } from '../../mixins/common'
import Multiselect from 'vue-multiselect'

export default {
  name: 'AddMedicineModal',
  mixins: [Common],
  components: { Multiselect },

  data() {
    return {
      modalInstance: null,
      rowIndex: null,
      buttonText: 'Save Medicine',
      generics: [],
      dosageForms: [],
      categories: [],
      manufacturers: [],
      strengths: [],
      units: [],
      doctor: {},
      hospitalID: null,

      form: {
        BrandName: '',
        generic: null,
        dosageForm: null,
        category: null,
        manufacturer: null,
        Strength: '',
        Unit: '',
        UnitPrice: '',
        ownerType: 'doctor',
      },
    }
  },
  mounted() {
    this.loadModalData()

    bus.$on('open-add-medicine-modal', payload => {
      this.resetForm()

      this.rowIndex = payload.index
      this.doctor = payload.doctor || {}
      this.hospitalID = payload.hospitalID || null
      this.form.BrandName = payload.name || ''

      const modalEl = document.getElementById('open-add-medicine-modal')
      this.modalInstance = bootstrap.Modal.getInstance
          ? bootstrap.Modal.getInstance(modalEl)
          : null

      if (!this.modalInstance) {
        this.modalInstance = new bootstrap.Modal(modalEl, {
          backdrop: true,
          keyboard: true,
        })
      }

      this.modalInstance.show()
    })
  },

  destroyed() {
    bus.$off('open-add-medicine-modal')
    this.closeModal()
  },

  methods: {
    resetForm() {
      this.rowIndex = null
      this.form = {
        BrandName: '',
        generic: null,
        dosageForm: null,
        category: null,
        manufacturer: null,
        Strength: null,
        Unit: null,
        UnitPrice: '',
        ownerType: 'doctor',
      }
    },
    closeModal() {
      const modalEl = document.getElementById('add-medicine-modal')

      let modal = null
      if (bootstrap.Modal.getInstance) {
        modal = bootstrap.Modal.getInstance(modalEl)
      }

      if (!modal && this.modalInstance) {
        modal = this.modalInstance
      }

      if (modal) {
        modal.hide()
      }

      setTimeout(() => {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove())
        document.body.classList.remove('modal-open')
        document.body.style.removeProperty('overflow')
        document.body.style.removeProperty('padding-right')
      }, 300)
    },
    loadModalData() {
      this.axiosGet('medicine/modal', response => {
        const data = response.data || response || {}

        this.units = data.unit || []
        this.strengths = data.strength || []
        this.dosageForms = data.dosageForms || []
        this.categories = data.categories || []
        this.manufacturers = data.manufacturers || []
        this.generics = data.generics || []
      }, error => {
        this.errorNoti(error)
      })
    },

    addGenericTag(tag) {
      const name = String(tag || '').trim()
      if (!name) return

      const generic = {
        GenericID: `tmp_generic_${Date.now()}`,
        GenericName: name,
      }

      this.generics.push(generic)
      this.form.generic = generic
    },

    onSubmit() {
      this.$store.commit('submitButtonLoadingStatus', true)

      const payload = {
        BrandName: this.form.BrandName,
        GenericName: this.form.generic ? this.form.generic.GenericName : '',
        GenericID: this.form.generic && !String(this.form.generic.GenericID).startsWith('tmp_')
            ? this.form.generic.GenericID
            : null,

        DosageFormID: this.form.dosageForm ? this.form.dosageForm.DosageFormID : null,
        MedicineCategoryID: this.form.category ? this.form.category.MedicineCategoryID : null,
        ManufacturerID: this.form.manufacturer ? this.form.manufacturer.ManufacturerID : null,

        Strength: this.form.Strength ? this.form.Strength.StrengthID : null,
        Unit: this.form.Unit?this.form.UnitID: null,
        UnitPrice: this.form.UnitPrice || null,

        IsSystemMedicine: 0,
        DoctorID: this.form.ownerType === 'doctor' ? this.doctor.DoctorID : null,
        HospitalID: this.form.ownerType === 'hospital' ? this.hospitalID : null,
        IsActive: 1,
        IsDeleted: 0,
      }

      this.axiosPost('medicine/add-from-prescription', payload, response => {
        const med = response.data || response.medicine || response

        const createdMedicine = {
          ...med,
          MedicineID: med.MedicineID || med.id,
          MedicineName: `${med.BrandName || payload.BrandName} (${med.Strength || payload.Strength})`,
          BrandName: med.BrandName || payload.BrandName,
          GenericName: med.GenericName || payload.GenericName,
          Strength: med.Strength || payload.Strength,
        }

        this.$emit('medicine-created', {
          index: this.rowIndex,
          medicine: createdMedicine,
        })

        this.successNoti(response.message || 'Medicine added.')
        this.closeModal()

        // $('#add-medicine-modal').modal('toggle')

        // ✅ Replace $('#add-medicine-modal').modal('toggle') with:
        // const modalEl = document.getElementById('add-medicine-modal')
        // const modal = bootstrap.Modal.getInstance(modalEl)
        // if (modal) modal.hide()
        this.$store.commit('submitButtonLoadingStatus', false)
      }, error => {
        this.errorNoti(error)
        this.$store.commit('submitButtonLoadingStatus', false)
      })
    },
  }
}

</script>

<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>

<style>
.modal {
  z-index: 2000 !important;
}

.modal-backdrop {
  z-index: 1990 !important;
}
</style>
