<template>
  <div>
    <div class="modal fade" id="add-edit-item" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <div class="modal-title modal-title-font" id="exampleModalLabel">{{ title }}</div>
          </div>
          <ValidationObserver v-slot="{ handleSubmit }">
            <form class="form-horizontal" id="form" @submit.prevent="handleSubmit(onSubmit)" autocomplete="off">
              <input type="text" name="name" autocomplete="name" style="display:none">
              <div class="modal-body">
                <div class="row">
                  <div class="col-12 col-md-6">
                    <ValidationProvider name="Product Name" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="ProductName">Product Name <span class="error">*</span></label>
                        <input type="text" class="form-control" :class="{'error-border': errors[0]}"
                               v-model="productName" placeholder="Product Name" >
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-12 col-md-6">
                    <ValidationProvider name="ProductCode" mode="eager" rules="" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="ProductCode">Product Code <span class="error">*</span></label>
                        <input type="text" class="form-control" :class="{'error-border': errors[0]}" id="productCode"
                               v-model="productCode" name="productCode" placeholder="Product Code">
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-12 col-md-6">
                    <ValidationProvider name="CompanyRate" mode="eager" rules="" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="CompanyRate">Company Rate<span class="error">*</span></label>
                        <input type="number" class="form-control" :class="{'error-border': errors[0]}" id="companyRate"
                               v-model="companyRate" name="companyRate" placeholder="Company Rate">
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>

                  <div class="col-12 col-md-6">
                    <ValidationProvider name="Status" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="status">Status <span class="error">*</span></label>
                        <select class="form-control" id="status" v-model="status">
                          <option value="Y">Active</option>
                          <option value="N">Inactive</option>
                        </select>
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <submit-form v-if="buttonShow" :name="buttonText"/>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </form>
          </ValidationObserver>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {bus} from "../../app";
import {Common} from "../../mixins/common";

export default {
  mixins: [Common],
  data() {
    return {
      title: '',
      companyRate: '',
      productCode: '',
      productName: '',
      status: '',
      buttonText: '',
      actionType: '',
      buttonShow: false,
    }
  },
  mounted() {
    $('#add-edit-item').on('hidden.bs.modal', () => {
      this.$emit('changeStatus');
    });
    bus.$on('add-edit-product', (row) => {
      this.resetForm();
      if (row) {
        this.axiosGet('setup/get-product-info/' + row.ProductCode, (response) => {
          const data = response;
          this.title = 'Update Product';
          this.buttonText = 'Update';
          this.companyRate = data.CompanyRate;
          this.productName = data.ProductName;
          this.productCode = data.ProductCode;
          this.status = data.Status;
          this.buttonShow = true;
          this.actionType = 'edit';
        }, (error) => {
          console.log('Error fetching product info:', error);
        });
      } else {
        this.title = 'Add Product';
        this.buttonText = 'Add';
        this.buttonShow = true;
        this.actionType = 'add';

      }
      $("#add-edit-item").modal("toggle");
    });
  },
  destroyed() {
    bus.$off('add-edit-product');
  },
  methods: {
    resetForm() {
      this.companyRate = '';
      this.productCode = '';
      this.productName = '';
      this.status = 'Y';
      this.buttonShow = false;
    },

    onSubmit() {
      this.$store.commit('submitButtonLoadingStatus', true);
      const url = this.actionType === 'add' ? 'setup/store-product' : 'setup/store-product';
      this.axiosPost(url, {
        companyRate: this.companyRate,
        productName: this.productName,
        productCode: this.productCode,
        status: this.status,
      }, (response) => {
        this.successNoti(response.message);
        $("#add-edit-item").modal("toggle");
        bus.$emit('refresh-datatable');
        this.$store.commit('submitButtonLoadingStatus', false);
      }, (error) => {
        this.errorNoti(error);
        this.$store.commit('submitButtonLoadingStatus', false);
      });
    },
  }
}
</script>

<style src="../../../../node_modules/vue-multiselect/dist/vue-multiselect.min.css"></style>